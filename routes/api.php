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
use App\Http\Controllers\OutputController;
use App\Http\Controllers\ExitDetailController;
use App\Http\Controllers\InventoryDetailController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ProductSupplierController;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes - Sistema de Inventario
|--------------------------------------------------------------------------
| Rutas organizadas por módulos con prioridad correcta
|--------------------------------------------------------------------------
*/

// ✅ Ruta de prueba
Route::get('/ping', fn() => response()->json(['message' => 'API funcionando correctamente 🚀']));

// ==========================
// 🟢 RUTAS PÚBLICAS
// ==========================
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::get('roles-public', [RoleController::class, 'getRolesForRegister']);
Route::post('roles-public', [RoleController::class, 'store']);

// ==========================
// 🟡 RUTAS PROTEGIDAS (Auth)
// ==========================
Route::middleware('auth:sanctum')->group(function () {

    // ======================
    // 🔐 Autenticación
    // ======================
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('profile', [UserController::class, 'profile']);
    Route::get('user', [AuthController::class, 'me']);

    // ======================
    // 📊 Dashboard
    // ======================
    Route::get('dashboard/summary', [DashboardController::class, 'summary']);

    // ======================
    // 🚨 ALERTAS (PRIORIDAD)
    // ======================
    Route::prefix('alerts')->group(function () {
        // 📊 Obtener estadísticas de alertas
        Route::get('stats', [AlertController::class, 'stats']);

        // 🔄 Verificar todo el inventario y actualizar alertas
        Route::post('check-all', [AlertController::class, 'checkAll']);

        // 📋 Listar alertas con filtros opcionales (?alert_type=low_stock&status=active)
        Route::get('/', [AlertController::class, 'index']);

        // ✅ Marcar alerta como resuelta
        Route::put('{id}/resolve', [AlertController::class, 'resolve']);
    });

    // ======================
    // 🧾 Categorías
    // ======================
    Route::prefix('categories')->group(function () {
        Route::post('init', [CategoryController::class, 'init']);
        Route::post('sync', [CategoryController::class, 'sync']);
        Route::get('/', [CategoryController::class, 'index']);
        Route::get('{id}', [CategoryController::class, 'show']);
    });

    // ======================
    // 📦 Productos
    // ======================
    Route::prefix('products')->group(function () {
        Route::get('{productId}/suppliers', [ProductController::class, 'getSuppliers']);
        Route::post('{productId}/attach-suppliers', [ProductSupplierController::class, 'attachSuppliersToProduct']);
    });
    Route::apiResource('products', ProductController::class);
    Route::apiResource('product-details', ProductDetailController::class);

    // ======================
    // 📦 Inventarios (ACTUALIZADO)
    // ======================
    Route::prefix('inventories')->group(function () {
        // Resumen general (debe ir antes de {id})
        Route::get('summary', [InventoryController::class, 'summary']);

        // Ajustar stock
        Route::post('{id}/adjust', [InventoryController::class, 'adjustStock']);
    });
    Route::apiResource('inventories', InventoryController::class);
    Route::apiResource('inventory-details', InventoryDetailController::class);

    // ======================
    // 🏭 Almacenes y Ubicaciones
    // ======================
    Route::apiResource('warehouses', WarehouseController::class);
    Route::apiResource('locations', LocationController::class);

    // ======================
    // 📥 Entradas
    // ======================
    Route::prefix('entries')->group(function () {
        Route::get('summary', [EntryController::class, 'summary']);
        Route::get('form-data', [EntryController::class, 'formData']);
    });
    Route::apiResource('entries', EntryController::class);
    Route::apiResource('entry-notes', EntryNoteController::class);

    // ======================
    // 📤 Salidas
    // ======================
    Route::prefix('outputs')->group(function () {
        Route::get('summary', [OutputController::class, 'summary']);
        Route::get('form-data', [OutputController::class, 'formData']);
    });
    Route::apiResource('outputs', OutputController::class);
    Route::apiResource('exit-details', ExitDetailController::class);

    // ======================
    // 🛍️ Órdenes y Dependencias
    // ======================
    Route::apiResource('orders', OrderController::class);
    Route::apiResource('dep-buys', DepBuyController::class);

    // ======================
    // 🧑‍🤝‍🧑 Proveedores
    // ======================
    Route::prefix('suppliers')->group(function () {
        Route::get('{supplier}/products', [SupplierController::class, 'getProducts']);
        Route::post('{supplier}/products', [SupplierController::class, 'syncProducts']);
        Route::post('{supplier}/products/attach', [SupplierController::class, 'attachProducts']);
        Route::delete('{supplier}/products/{product}', [SupplierController::class, 'detachProduct']);
        Route::post('{supplierId}/attach-products', [ProductSupplierController::class, 'attachProductsToSupplier']);
    });
    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('product-suppliers', ProductSupplierController::class);

    // ======================
    // 👑 Administración (solo admin)
    // ======================
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::post('users/{id}/change-password', [UserController::class, 'changePassword']);
        Route::apiResource('roles', RoleController::class);
    });
});
