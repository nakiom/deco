<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Services\TableQrService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;

class CartaController extends Controller
{
    public function __construct(
        private readonly TableQrService $tableQr,
    ) {}

    public function show(string $slug): View
    {
        $restaurant = Restaurant::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return view('carta.show', [
            'restaurant' => $restaurant,
            'categories' => $this->categoriesForRestaurant($restaurant),
            'table' => null,
        ]);
    }

    public function showByTable(string $qrUuid, string $secret): View|RedirectResponse
    {
        $table = $this->tableQr->resolveByUuidAndSecret($qrUuid, $secret);

        return $this->renderMenuForResolvedTable($table, $qrUuid, $secret, null);
    }

    public function showByTableLegacy(string $legacyToken): View|RedirectResponse
    {
        $table = $this->tableQr->resolveByLegacyToken($legacyToken);

        return $this->renderMenuForResolvedTable($table, null, null, $legacyToken);
    }

    public function unlockMenuPassword(Request $request, string $qrUuid, string $secret): RedirectResponse
    {
        return $this->processUnlock($request, $this->tableQr->resolveByUuidAndSecret($qrUuid, $secret), $qrUuid, $secret, null);
    }

    public function unlockMenuPasswordLegacy(Request $request, string $legacyToken): RedirectResponse
    {
        return $this->processUnlock($request, $this->tableQr->resolveByLegacyToken($legacyToken), null, null, $legacyToken);
    }

    public function kitchenStatus(string $qrUuid, string $secret): JsonResponse
    {
        $table = $this->tableQr->resolveByUuidAndSecret($qrUuid, $secret);
        if ($table === null || ! $table->allowsQrMenuAccess()) {
            abort(404);
        }

        return $this->kitchenStatusResponse($table);
    }

    public function kitchenStatusLegacy(string $legacyToken): JsonResponse
    {
        $table = $this->tableQr->resolveByLegacyToken($legacyToken);
        if ($table === null || ! $table->allowsQrMenuAccess()) {
            abort(404);
        }

        return $this->kitchenStatusResponse($table);
    }

    public function callWaiter(string $qrUuid, string $secret): RedirectResponse
    {
        $table = $this->tableQr->resolveByUuidAndSecret($qrUuid, $secret);

        return $this->callWaiterForTable($table, $qrUuid, $secret, null);
    }

    public function callWaiterLegacy(string $legacyToken): RedirectResponse
    {
        $table = $this->tableQr->resolveByLegacyToken($legacyToken);

        return $this->callWaiterForTable($table, null, null, $legacyToken);
    }

    /**
     * Estado de cocina para la carta por QR (polling desde el cliente).
     */
    private function kitchenStatusResponse(RestaurantTable $table): JsonResponse
    {
        $order = $this->findKitchenReadyOrderForTable($table);

        return response()->json([
            'kitchen_ready' => $order !== null,
            'order_id' => $order?->id,
        ]);
    }

    /**
     * Llamado de mesa desde la carta QR (rate limited).
     */
    private function callWaiterForTable(
        ?RestaurantTable $table,
        ?string $qrUuid,
        ?string $secret,
        ?string $legacyToken,
    ): RedirectResponse {
        if ($table === null || ! $table->allowsQrMenuAccess()) {
            abort(404);
        }

        $restaurant = $table->restaurant;
        if ($restaurant === null || ! $restaurant->is_active) {
            abort(404);
        }

        if ($restaurant->menu_public_password_enabled && ! session($table->menuUnlockSessionKey())) {
            abort(403);
        }

        $key = 'menu-call-waiter:'.$table->id;
        if (RateLimiter::tooManyAttempts($key, 4)) {
            return redirect()
                ->back()
                ->with('waiter_call_feedback', 'Esperá un momento antes de volver a llamar.');
        }

        RateLimiter::hit($key, 90);

        $table->update(['waiter_call_at' => now()]);

        return redirect()
            ->back()
            ->with('waiter_call_feedback', 'Listo: avisamos al equipo de salón.');
    }

    private function processUnlock(
        Request $request,
        ?RestaurantTable $table,
        ?string $qrUuid,
        ?string $secret,
        ?string $legacyToken,
    ): RedirectResponse {
        $request->validate([
            'password' => ['required', 'string', 'max:255'],
        ]);

        if ($table === null || ! $table->allowsQrMenuAccess()) {
            abort(404);
        }

        $restaurant = $table->restaurant;
        if ($restaurant === null || ! $restaurant->is_active) {
            abort(404);
        }

        if (RateLimiter::tooManyAttempts('menu-unlock:'.$table->id, 8)) {
            return back()->withErrors(['password' => 'Demasiados intentos. Probá más tarde.']);
        }

        RateLimiter::hit('menu-unlock:'.$table->id, 120);

        if (! $restaurant->verifyMenuPublicPassword($request->input('password'))) {
            return back()->withErrors(['password' => 'Contraseña incorrecta.']);
        }

        session([$table->menuUnlockSessionKey() => true]);
        $this->storeQrSessionAudit($table);

        if ($qrUuid !== null && $secret !== null) {
            return redirect()->route('menu.public', ['qrUuid' => $qrUuid, 'secret' => $secret]);
        }

        return redirect()->route('menu.public.legacy', ['legacyToken' => $legacyToken]);
    }

    private function renderMenuForResolvedTable(
        ?RestaurantTable $table,
        ?string $qrUuid,
        ?string $secret,
        ?string $legacyToken,
    ): View|RedirectResponse {
        if ($table === null || ! $table->allowsQrMenuAccess()) {
            abort(404);
        }

        $restaurant = $table->restaurant;
        if ($restaurant === null || ! $restaurant->is_active) {
            abort(404);
        }

        if ($restaurant->menu_public_password_enabled && ! session($table->menuUnlockSessionKey())) {
            return view('menu.password', [
                'restaurant' => $restaurant,
                'table' => $table,
                'qrUuid' => $qrUuid,
                'secret' => $secret,
                'legacyToken' => $legacyToken,
            ]);
        }

        $this->storeQrSessionAudit($table);

        $kitchenReadyOrder = $this->findKitchenReadyOrderForTable($table);

        return view('menu.public', [
            'table' => $table,
            'restaurant' => $restaurant,
            'categories' => $this->categoriesForRestaurant($restaurant),
            'menuQrKitchenStatusUrl' => $this->kitchenStatusUrl($qrUuid, $secret, $legacyToken),
            'menuQrCallWaiterUrl' => $this->callWaiterUrl($qrUuid, $secret, $legacyToken),
            'menuOrderingEnabled' => $table->menu_public_ordering_enabled,
            'menuOrderSubmitUrl' => $this->menuOrderSubmitUrl($qrUuid, $secret, $legacyToken),
            'kitchenReadyOrderId' => $kitchenReadyOrder?->id,
            'kitchenReadyInitial' => $kitchenReadyOrder !== null,
        ]);
    }

    private function menuOrderSubmitUrl(?string $qrUuid, ?string $secret, ?string $legacyToken): ?string
    {
        if ($qrUuid !== null && $secret !== null) {
            return route('menu.order.submit', ['qrUuid' => $qrUuid, 'secret' => $secret]);
        }
        if ($legacyToken !== null) {
            return route('menu.order.submit.legacy', ['legacyToken' => $legacyToken]);
        }

        return null;
    }

    private function kitchenStatusUrl(?string $qrUuid, ?string $secret, ?string $legacyToken): string
    {
        if ($qrUuid !== null && $secret !== null) {
            return route('menu.kitchen-status', ['qrUuid' => $qrUuid, 'secret' => $secret]);
        }

        return route('menu.kitchen-status.legacy', ['legacyToken' => $legacyToken]);
    }

    private function callWaiterUrl(?string $qrUuid, ?string $secret, ?string $legacyToken): string
    {
        if ($qrUuid !== null && $secret !== null) {
            return route('menu.call-waiter', ['qrUuid' => $qrUuid, 'secret' => $secret]);
        }

        return route('menu.call-waiter.legacy', ['legacyToken' => $legacyToken]);
    }

    /**
     * Contexto validado en sesión para futuros POST de pedidos (solo backend debe aceptar pedidos con este contexto + validación re-forzada).
     */
    private function storeQrSessionAudit(RestaurantTable $table): void
    {
        session([
            'deco_menu_qr' => [
                'table_id' => $table->id,
                'restaurant_id' => $table->restaurant_id,
                'verified_at' => now()->getTimestamp(),
            ],
        ]);
    }

    private function findKitchenReadyOrderForTable(RestaurantTable $table): ?Order
    {
        return Order::query()
            ->where('table_id', $table->id)
            ->whereNotNull('customer_id')
            ->whereNotNull('kitchen_completed_at')
            ->whereNotIn('status', [
                OrderStatus::Closed->value,
                OrderStatus::Cancelled->value,
                OrderStatus::Delivered->value,
            ])
            ->latest()
            ->first();
    }

    /**
     * @return Collection<int, Category>
     */
    public function categoriesForRestaurant(Restaurant $restaurant): Collection
    {
        return $restaurant->categories()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->with([
                'products' => fn ($q) => $q->forMenu()->orderBy('sort_order'),
            ])
            ->get()
            ->filter(fn ($category) => $category->products->isNotEmpty());
    }
}
