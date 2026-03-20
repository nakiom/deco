<?php

use App\Http\Controllers\CartaController;
use App\Http\Controllers\MenuOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/carta/{slug}', [CartaController::class, 'show'])
    ->where('slug', '[a-zA-Z0-9]+(?:-[a-zA-Z0-9]+)*')
    ->name('carta.show');

Route::get('/menu/{qrUuid}/{secret}/kitchen-status', [CartaController::class, 'kitchenStatus'])
    ->whereUuid('qrUuid')
    ->name('menu.kitchen-status');

Route::post('/menu/{qrUuid}/{secret}/call-waiter', [CartaController::class, 'callWaiter'])
    ->whereUuid('qrUuid')
    ->name('menu.call-waiter');

Route::post('/menu/{qrUuid}/{secret}/unlock', [CartaController::class, 'unlockMenuPassword'])
    ->whereUuid('qrUuid')
    ->name('menu.unlock');

Route::post('/menu/{qrUuid}/{secret}/order/submit', [MenuOrderController::class, 'submitCart'])
    ->whereUuid('qrUuid')
    ->name('menu.order.submit');

Route::get('/menu/{qrUuid}/{secret}', [CartaController::class, 'showByTable'])
    ->whereUuid('qrUuid')
    ->name('menu.public');

Route::get('/menu/{legacyToken}/kitchen-status', [CartaController::class, 'kitchenStatusLegacy'])
    ->where('legacyToken', '[a-zA-Z0-9]+')
    ->name('menu.kitchen-status.legacy');

Route::post('/menu/{legacyToken}/call-waiter', [CartaController::class, 'callWaiterLegacy'])
    ->where('legacyToken', '[a-zA-Z0-9]+')
    ->name('menu.call-waiter.legacy');

Route::post('/menu/{legacyToken}/unlock', [CartaController::class, 'unlockMenuPasswordLegacy'])
    ->where('legacyToken', '[a-zA-Z0-9]+')
    ->name('menu.unlock.legacy');

Route::post('/menu/{legacyToken}/order/submit', [MenuOrderController::class, 'submitCartLegacy'])
    ->where('legacyToken', '[a-zA-Z0-9]+')
    ->name('menu.order.submit.legacy');

Route::get('/menu/{legacyToken}', [CartaController::class, 'showByTableLegacy'])
    ->where('legacyToken', '[a-zA-Z0-9]+')
    ->name('menu.public.legacy');
