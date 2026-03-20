<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\TableStatus;
use App\Models\FloorPlan;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SalonMap extends Page
{
    public bool $editMode = true;

    public ?int $selectedFloorPlanId = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?string $navigationLabel = 'Mapa del Salón';

    protected static ?string $title = 'Mapa del Salón';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.salon-map';

    public static function getNavigationGroup(): ?string
    {
        return 'Operación';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('tables.view');
    }

    public function mount(): void
    {
        $restaurant = $this->resolveRestaurant();
        if (! $restaurant || ! $this->supportsFloorPlanVersioning()) {
            return;
        }

        $current = FloorPlan::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('is_active', true)
            ->oldest('id')
            ->first();

        if (! $current) {
            $current = $this->resolveOrCreateFloorPlan($restaurant);
        }

        $this->selectedFloorPlanId = $current->id;
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('editLayout')
                ->label(fn () => $this->editMode ? 'Bloquear edición' : 'Editar layout')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->color($this->editMode ? 'success' : 'gray')
                ->action('toggleEditMode')
                ->visible(fn () => auth()->user()?->can('tables.update')),
            Action::make('createVersion')
                ->label('Nueva versión')
                ->icon(Heroicon::OutlinedDocumentDuplicate)
                ->color('warning')
                ->action('createVersion')
                ->visible(fn () => auth()->user()?->can('tables.update')),
            Action::make('publishVersion')
                ->label('Publicar versión')
                ->icon(Heroicon::OutlinedRocketLaunch)
                ->color('success')
                ->requiresConfirmation()
                ->action('publishVersion')
                ->visible(fn () => auth()->user()?->can('tables.update')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getEditorPayload(): array
    {
        $restaurant = $this->resolveRestaurant();
        if (! $restaurant) {
            return [
                'restaurant' => null,
                'floor' => [
                    'name' => 'Salón principal',
                    'width' => 1000,
                    'height' => 640,
                    'shape' => 'rectangle',
                    'grid_size' => 20,
                    'show_grid' => true,
                    'zones' => [],
                    'fixed_elements' => [],
                ],
                'tables' => [],
            ];
        }

        if (! $this->supportsFloorPlanVersioning()) {
            $tables = $restaurant->tables()
                ->orderBy('number')
                ->get();

            return [
                'restaurant' => [
                    'id' => $restaurant->id,
                    'name' => $restaurant->name,
                ],
                'floor' => [
                    'id' => null,
                    'name' => 'Salón principal',
                    'version' => 1,
                    'is_active' => true,
                    'width' => 1000,
                    'height' => 640,
                    'shape' => 'rectangle',
                    'grid_size' => 20,
                    'show_grid' => true,
                    'zones' => [],
                    'fixed_elements' => [],
                ],
                'versions' => [],
                'tables' => $tables->map(fn (RestaurantTable $table): array => [
                    'id' => $table->id,
                    'number' => (int) $table->number,
                    'name' => $table->name,
                    'capacity' => (int) $table->capacity,
                    'shape' => $table->shape,
                    'x' => (float) $table->pos_x,
                    'y' => (float) $table->pos_y,
                    'width' => (float) $table->width,
                    'height' => (float) $table->height,
                    'rotation' => (float) ($table->rotation ?? 0),
                    'status' => $table->status->value,
                    'notes' => null,
                    'layout_meta' => [],
                ])->all(),
            ];
        }

        $floorPlan = $this->resolveOrCreateFloorPlan($restaurant);
        $versions = FloorPlan::query()
            ->where('restaurant_id', $restaurant->id)
            ->orderByDesc('version')
            ->orderByDesc('id')
            ->get();

        $tables = $restaurant->tables()
            ->where('floor_plan_id', $floorPlan->id)
            ->orderBy('number')
            ->get();

        return [
            'restaurant' => [
                'id' => $restaurant->id,
                'name' => $restaurant->name,
            ],
            'floor' => [
                'id' => $floorPlan->id,
                'name' => $floorPlan->name,
                'version' => (int) $floorPlan->version,
                'is_active' => (bool) $floorPlan->is_active,
                'width' => (int) $floorPlan->width,
                'height' => (int) $floorPlan->height,
                'shape' => $floorPlan->base_shape,
                'grid_size' => (int) $floorPlan->grid_size,
                'show_grid' => (bool) $floorPlan->show_grid,
                'zones' => $floorPlan->zones ?? [],
                'fixed_elements' => $floorPlan->fixed_elements ?? [],
            ],
            'versions' => $versions->map(fn (FloorPlan $version): array => [
                'id' => $version->id,
                'name' => $version->name,
                'version' => (int) $version->version,
                'is_active' => (bool) $version->is_active,
            ])->all(),
            'tables' => $tables->map(fn (RestaurantTable $table): array => [
                'id' => $table->id,
                'number' => (int) $table->number,
                'name' => $table->name,
                'capacity' => (int) $table->capacity,
                'shape' => $table->shape,
                'x' => (float) $table->pos_x,
                'y' => (float) $table->pos_y,
                'width' => (float) $table->width,
                'height' => (float) $table->height,
                'rotation' => (float) ($table->rotation ?? 0),
                'status' => $table->status->value,
                'notes' => $table->internal_notes,
                'layout_meta' => $table->layout_meta ?? [],
            ])->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function saveLayout(array $payload): void
    {
        if (! auth()->user()?->can('tables.update')) {
            abort(403);
        }

        if (! $this->supportsFloorPlanVersioning()) {
            throw ValidationException::withMessages([
                'floor' => 'Falta actualizar base de datos. Ejecutá: php artisan migrate',
            ]);
        }

        $restaurant = $this->resolveRestaurant();
        if (! $restaurant) {
            throw ValidationException::withMessages([
                'restaurant' => 'No hay restaurante disponible para guardar el layout.',
            ]);
        }

        $validated = $this->validatePayload($payload);
        $floorData = Arr::get($validated, 'floor', []);
        $tablesData = Arr::get($validated, 'tables', []);

        DB::transaction(function () use ($restaurant, $floorData, $tablesData): void {
            $floorPlan = $this->resolveOrCreateFloorPlan($restaurant, Arr::get($floorData, 'id'));
            $floorPlan->update([
                'name' => (string) Arr::get($floorData, 'name'),
                'width' => (int) Arr::get($floorData, 'width'),
                'height' => (int) Arr::get($floorData, 'height'),
                'base_shape' => (string) Arr::get($floorData, 'shape', 'rectangle'),
                'grid_size' => (int) Arr::get($floorData, 'grid_size', 20),
                'show_grid' => (bool) Arr::get($floorData, 'show_grid', true),
                // Preparado para obstáculos y zonas futuras sin romper el esquema.
                'zones' => Arr::get($floorData, 'zones', []),
                'fixed_elements' => Arr::get($floorData, 'fixed_elements', []),
            ]);

            /** @var array<int> $keptIds */
            $keptIds = [];
            $existing = $restaurant->tables()
                ->where('floor_plan_id', $floorPlan->id)
                ->get()
                ->keyBy('id');

            foreach ($tablesData as $tableData) {
                $tableId = Arr::get($tableData, 'id');
                $table = $tableId ? $existing->get((int) $tableId) : null;

                $attributes = [
                    'restaurant_id' => $restaurant->id,
                    'floor_plan_id' => $floorPlan->id,
                    'number' => (int) Arr::get($tableData, 'number'),
                    'name' => Arr::get($tableData, 'name'),
                    'capacity' => (int) Arr::get($tableData, 'capacity'),
                    'shape' => (string) Arr::get($tableData, 'shape'),
                    'status' => (string) Arr::get($tableData, 'status', TableStatus::Free->value),
                    'pos_x' => (float) Arr::get($tableData, 'x'),
                    'pos_y' => (float) Arr::get($tableData, 'y'),
                    'width' => (float) Arr::get($tableData, 'width'),
                    'height' => (float) Arr::get($tableData, 'height'),
                    'rotation' => (float) Arr::get($tableData, 'rotation', 0),
                    'internal_notes' => Arr::get($tableData, 'notes'),
                    'layout_meta' => Arr::get($tableData, 'layout_meta', []),
                ];

                if ($table) {
                    $table->update($attributes);
                    $keptIds[] = $table->id;

                    continue;
                }

                $tableByNumber = RestaurantTable::query()
                    ->where('restaurant_id', $restaurant->id)
                    ->where('floor_plan_id', $floorPlan->id)
                    ->where('number', (int) Arr::get($tableData, 'number'))
                    ->first();

                if ($tableByNumber) {
                    $tableByNumber->update($attributes);
                    $keptIds[] = $tableByNumber->id;

                    continue;
                }

                $newTable = RestaurantTable::create($attributes);
                $keptIds[] = $newTable->id;
            }

            $restaurant->tables()
                ->where('floor_plan_id', $floorPlan->id)
                ->whereNotIn('id', $keptIds)
                ->delete();
        });

        Notification::make()
            ->title('Layout guardado')
            ->body('El mapa del salón se guardó correctamente.')
            ->success()
            ->send();

        $this->selectedFloorPlanId = (int) Arr::get($floorData, 'id', $this->selectedFloorPlanId);
    }

    public function toggleEditMode(): void
    {
        $this->editMode = ! $this->editMode;
    }

    public function selectVersion(int $floorPlanId): void
    {
        if (! $this->supportsFloorPlanVersioning()) {
            return;
        }

        $restaurant = $this->resolveRestaurant();
        if (! $restaurant) {
            return;
        }

        $exists = FloorPlan::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('id', $floorPlanId)
            ->exists();

        if (! $exists) {
            return;
        }

        $this->selectedFloorPlanId = $floorPlanId;
    }

    public function createVersion(): void
    {
        if (! $this->supportsFloorPlanVersioning()) {
            Notification::make()
                ->title('Migraciones pendientes')
                ->body('Ejecutá "php artisan migrate" para habilitar versionado.')
                ->warning()
                ->send();

            return;
        }

        $restaurant = $this->resolveRestaurant();
        if (! $restaurant || ! auth()->user()?->can('tables.update')) {
            abort(403);
        }

        DB::transaction(function () use ($restaurant): void {
            $basePlan = $this->resolveOrCreateFloorPlan($restaurant);
            $nextVersion = (int) FloorPlan::query()
                ->where('restaurant_id', $restaurant->id)
                ->max('version') + 1;

            $newPlan = FloorPlan::create([
                'restaurant_id' => $restaurant->id,
                'name' => "{$basePlan->name} v{$nextVersion}",
                'version' => $nextVersion,
                'is_active' => false,
                'width' => $basePlan->width,
                'height' => $basePlan->height,
                'base_shape' => $basePlan->base_shape,
                'grid_size' => $basePlan->grid_size,
                'show_grid' => $basePlan->show_grid,
                'zones' => $basePlan->zones ?? [],
                'fixed_elements' => $basePlan->fixed_elements ?? [],
            ]);

            $sourceTables = RestaurantTable::query()
                ->where('restaurant_id', $restaurant->id)
                ->where('floor_plan_id', $basePlan->id)
                ->get();

            foreach ($sourceTables as $sourceTable) {
                RestaurantTable::create([
                    'restaurant_id' => $restaurant->id,
                    'floor_plan_id' => $newPlan->id,
                    'sector_id' => $sourceTable->sector_id,
                    'number' => $sourceTable->number,
                    'name' => $sourceTable->name,
                    'capacity' => $sourceTable->capacity,
                    'shape' => $sourceTable->shape,
                    'status' => $sourceTable->status->value,
                    'pos_x' => (float) $sourceTable->pos_x,
                    'pos_y' => (float) $sourceTable->pos_y,
                    'width' => (float) $sourceTable->width,
                    'height' => (float) $sourceTable->height,
                    'rotation' => (float) ($sourceTable->rotation ?? 0),
                    'internal_notes' => $sourceTable->internal_notes,
                    'layout_meta' => $sourceTable->layout_meta,
                ]);
            }

            $this->selectedFloorPlanId = $newPlan->id;
        });

        Notification::make()
            ->title('Versión creada')
            ->body('Se creó una nueva versión editable del plano.')
            ->success()
            ->send();
    }

    public function publishVersion(): void
    {
        if (! $this->supportsFloorPlanVersioning()) {
            Notification::make()
                ->title('Migraciones pendientes')
                ->body('Ejecutá "php artisan migrate" para habilitar publicación de versiones.')
                ->warning()
                ->send();

            return;
        }

        $restaurant = $this->resolveRestaurant();
        if (! $restaurant || ! $this->selectedFloorPlanId || ! auth()->user()?->can('tables.update')) {
            abort(403);
        }

        DB::transaction(function () use ($restaurant): void {
            FloorPlan::query()
                ->where('restaurant_id', $restaurant->id)
                ->update(['is_active' => false]);

            FloorPlan::query()
                ->where('restaurant_id', $restaurant->id)
                ->where('id', $this->selectedFloorPlanId)
                ->update(['is_active' => true]);
        });

        Notification::make()
            ->title('Versión publicada')
            ->body('La versión seleccionada quedó activa para operación.')
            ->success()
            ->send();
    }

    private function resolveRestaurant(): ?Restaurant
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        if ($user->restaurant_id) {
            return Restaurant::find($user->restaurant_id);
        }

        if ($user->hasRole('dev')) {
            return Restaurant::query()->oldest('id')->first();
        }

        return null;
    }

    private function resolveOrCreateFloorPlan(Restaurant $restaurant, mixed $floorPlanId = null): FloorPlan
    {
        if (! $this->supportsFloorPlanVersioning()) {
            throw ValidationException::withMessages([
                'floor' => 'Falta actualizar base de datos. Ejecutá: php artisan migrate',
            ]);
        }

        if ($floorPlanId) {
            $selected = FloorPlan::query()
                ->where('restaurant_id', $restaurant->id)
                ->where('id', (int) $floorPlanId)
                ->first();
            if ($selected) {
                $this->selectedFloorPlanId = $selected->id;

                return $selected;
            }
        }

        if ($this->selectedFloorPlanId) {
            $selected = FloorPlan::query()
                ->where('restaurant_id', $restaurant->id)
                ->where('id', $this->selectedFloorPlanId)
                ->first();
            if ($selected) {
                return $selected;
            }
        }

        $active = FloorPlan::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('is_active', true)
            ->first();
        if ($active) {
            $this->selectedFloorPlanId = $active->id;

            return $active;
        }

        $created = FloorPlan::query()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Salón principal',
            'version' => 1,
            'is_active' => true,
            'width' => 1000,
            'height' => 640,
            'base_shape' => 'rectangle',
            'grid_size' => 20,
            'show_grid' => true,
            'zones' => [],
            'fixed_elements' => [],
        ]);

        $this->selectedFloorPlanId = $created->id;

        return $created;
    }

    private function supportsFloorPlanVersioning(): bool
    {
        return Schema::hasTable('floor_plans')
            && Schema::hasTable('restaurant_tables')
            && Schema::hasColumn('restaurant_tables', 'floor_plan_id')
            && Schema::hasColumn('restaurant_tables', 'internal_notes')
            && Schema::hasColumn('restaurant_tables', 'layout_meta');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function validatePayload(array $payload): array
    {
        $validator = Validator::make($payload, [
            'floor' => ['required', 'array'],
            'floor.id' => ['nullable', 'integer'],
            'floor.name' => ['required', 'string', 'max:120'],
            'floor.width' => ['required', 'integer', 'min:420', 'max:3000'],
            'floor.height' => ['required', 'integer', 'min:280', 'max:2200'],
            'floor.shape' => ['required', Rule::in(['rectangle'])],
            'floor.grid_size' => ['required', 'integer', 'min:8', 'max:80'],
            'floor.show_grid' => ['required', 'boolean'],
            'floor.zones' => ['nullable', 'array'],
            'floor.fixed_elements' => ['nullable', 'array'],
            'tables' => ['required', 'array', 'max:200'],
            'tables.*.id' => ['nullable', 'integer'],
            'tables.*.number' => ['required', 'integer', 'min:1', 'max:999'],
            'tables.*.name' => ['nullable', 'string', 'max:120'],
            'tables.*.capacity' => ['required', 'integer', 'min:1', 'max:24'],
            'tables.*.shape' => ['required', Rule::in(['square', 'rectangle', 'round', 'oval'])],
            'tables.*.x' => ['required', 'numeric', 'min:-600', 'max:5000'],
            'tables.*.y' => ['required', 'numeric', 'min:-600', 'max:5000'],
            'tables.*.width' => ['required', 'numeric', 'min:40', 'max:280'],
            'tables.*.height' => ['required', 'numeric', 'min:40', 'max:280'],
            'tables.*.rotation' => ['required', 'numeric', 'min:-180', 'max:180'],
            'tables.*.status' => ['required', Rule::in(array_map(
                static fn (TableStatus $status): string => $status->value,
                TableStatus::cases()
            ))],
            'tables.*.notes' => ['nullable', 'string', 'max:600'],
            'tables.*.layout_meta' => ['nullable', 'array'],
        ]);

        $validator->after(function ($validator) use ($payload): void {
            $tables = Arr::get($payload, 'tables', []);
            $floorWidth = (float) Arr::get($payload, 'floor.width', 1000);
            $floorHeight = (float) Arr::get($payload, 'floor.height', 640);
            $numbers = [];

            foreach ($tables as $index => $table) {
                $tableNumber = (int) Arr::get($table, 'number');
                if (in_array($tableNumber, $numbers, true)) {
                    $validator->errors()->add("tables.{$index}.number", "El número de mesa {$tableNumber} está duplicado.");
                }
                $numbers[] = $tableNumber;

                $x = (float) Arr::get($table, 'x');
                $y = (float) Arr::get($table, 'y');
                $width = (float) Arr::get($table, 'width');
                $height = (float) Arr::get($table, 'height');

                if ($x > $floorWidth || $y > $floorHeight || ($x + $width) < 0 || ($y + $height) < 0) {
                    $validator->errors()->add("tables.{$index}.x", 'La mesa no puede quedar completamente fuera del salón.');
                }
            }
        });

        return $validator->validate();
    }
}
