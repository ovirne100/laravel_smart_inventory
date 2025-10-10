<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\DepBuyController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductDetailController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\EntryController;
use App\Http\Controllers\EntryNoteController;
use App\Http\Controllers\ProductExitController;
use App\Http\Controllers\ExitDetailController;
use App\Http\Controllers\InventoryDetailController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ProductSupplierController;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Archivo fusionado y optimizado de rutas del backend.
| Estructurado en secciones: públicas, protegidas y admin.
|--------------------------------------------------------------------------
*/

// ✅ Ruta de prueba básica
Route::get('/ping', fn() => response()->json(['message' => 'API funcionando correctamente 🚀']));

// ==========================
// 🟢 RUTAS PÚBLICAS
// ==========================
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::get('roles-public', [RoleController::class, 'getRolesForRegister']);
Route::post('roles-public', [RoleController::class, 'store']);

// Inicialización o sincronización de categorías
Route::post('categories/init', [CategoryController::class, 'init']);
Route::post('categories/sync', [CategoryController::class, 'sync']);
Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);


    // ======================
    // 📦 Productos e Inventarios
    // ======================
    Route::apiResource('products', ProductController::class);
    Route::apiResource('product-details', ProductDetailController::class);
    Route::apiResource('inventories', InventoryController::class);
    Route::post('inventories/{id}/adjust', [InventoryController::class, 'adjustStock']);
    Route::apiResource('inventory-details', InventoryDetailController::class);

    // ======================
    // 🏭 Almacenes y ubicaciones
    // ======================
    Route::apiResource('warehouses', WarehouseController::class);
    Route::apiResource('locations', LocationController::class);


    // ======================
    // 🧾 Entradas / Salidas
    // ======================
    // Entradas
    Route::get('entries/summary', [EntryController::class, 'summary']);
    Route::get('entries/form-data', [EntryController::class, 'formData']);
    Route::apiResource('entries', EntryController::class);
    Route::apiResource('entry-notes', EntryNoteController::class);

    // Salidas (renombrado a ProductExitController)
    Route::get('product-exits/summary', [ProductExitController::class, 'summary']);
    Route::get('product-exits/form-data', [ProductExitController::class, 'formData']);
    Route::apiResource('product-exits', ProductExitController::class);
    Route::apiResource('exit-details', ExitDetailController::class);

    // ======================
    // 🛍️ Órdenes y Dependencias
    // ======================
    Route::apiResource('orders', OrderController::class);
    Route::apiResource('dep-buys', DepBuyController::class);

    // ======================
    // 🧑‍🤝‍🧑 Proveedores y relaciones
    // ======================
    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('product-suppliers', ProductSupplierController::class);

    // Relaciones producto <-> proveedor
    Route::get('suppliers/{supplier}/products', [SupplierController::class, 'products']);
    Route::post('suppliers/{supplier}/products', [SupplierController::class, 'syncProducts']);
    Route::post('suppliers/{supplier}/products/attach', [SupplierController::class, 'attachProducts']);
    Route::delete('suppliers/{supplier}/products/{product}', [SupplierController::class, 'detachProduct']);

    // ======================
    // 🚨 Alertas
    // ======================
    Route::apiResource('alerts', AlertController::class);
    Route::patch('alerts/{id}/status', [AlertController::class, 'resolve']);
    Route::post('alerts/check/{inventory}', [AlertController::class, 'checkStockRoute']);
    Route::get('alerts/test/{inventory}', [AlertController::class, 'test']);

    // ======================
    // 📊 Dashboard
    // ======================
   // Route::get('dashboard/summary', [DashboardController::class, 'summary']);

// ==========================
// 🟡 RUTAS PROTEGIDAS (Auth)
// ==========================
Route::middleware('auth:sanctum')->group(function () {

    // 🔐 Autenticación
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('profile', [UserController::class, 'profile']);
    Route::get('user', [AuthController::class, 'me']);

    // ======================
    // 🧑‍💼 RUTAS SOLO ADMIN
    // ======================
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::post('users/{id}/change-password', [UserController::class, 'changePassword']);
        Route::apiResource('roles', RoleController::class);
    });
});
