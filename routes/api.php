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
<<<<<<< HEAD
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OutputController;

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
=======
use App\Models\ExitDetail;
>>>>>>> 0ed22cfdc47b44ea2a0de0d18550105196679823

// ==========================
// 🟢 RUTAS PÚBLICAS
// ==========================
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::get('roles-public', [RoleController::class, 'getRolesForRegister']);
Route::post('roles-public', [RoleController::class, 'store']);


    // ======================
    // 📊 Dashboard
    // ======================
   // Route::get('dashboard/summary', [DashboardController::class, 'summary']);

<<<<<<< HEAD
// ==========================
// 🟡 RUTAS PROTEGIDAS (Auth)
// ==========================
=======
//entradas de productos
Route::get('entries/form-data', [EntryController::class, 'formData']);
Route::apiResource('entries', EntryController::class);
//salidas de productos
Route::get('outputs/form-data', [ExitDetailController::class, 'formData']);
Route::apiResource('outputs', ExitDetailController::class);


// Protected routes
>>>>>>> 0ed22cfdc47b44ea2a0de0d18550105196679823
Route::middleware('auth:sanctum')->group(function () {

    // 🔐 Autenticación
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('profile', [UserController::class, 'profile']);
    Route::get('user', [AuthController::class, 'me']);

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
   Route::get('/outputs/summary', [OutputController::class, 'summary']);

    Route::get('/outputs/form-data', [OutputController::class, 'formData']);
    Route::apiResource('/outputs', OutputController::class);
    Route::apiResource('/exit-details', ExitDetailController::class);

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
    // 🔹 Asociar múltiples productos a un proveedor
Route::post('suppliers/{supplierId}/attach-products', [ProductSupplierController::class, 'attachProductsToSupplier']);

// 🔹 Asociar múltiples proveedores a un producto
Route::post('products/{productId}/attach-suppliers', [ProductSupplierController::class, 'attachSuppliersToProduct']);

// 🔹 Obtener todos los productos de un proveedor (con detalles del pivot)
Route::get('suppliers/{supplierId}/products', [SupplierController::class, 'getProducts']);

// 🔹 Obtener todos los proveedores de un producto (con detalles del pivot)
Route::get('products/{productId}/suppliers', [ProductController::class, 'getSuppliers']);

    // ======================
    // 🚨 Alertas
    // ======================
    Route::apiResource('alerts', AlertController::class);
    Route::patch('alerts/{id}/status', [AlertController::class, 'resolve']);
    Route::post('alerts/check/{inventory}', [AlertController::class, 'checkStockRoute']);
    Route::get('alerts/test/{inventory}', [AlertController::class, 'test']);


    // ======================
    // 🧑‍💼 RUTAS SOLO ADMIN
    // ======================
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::post('users/{id}/change-password', [UserController::class, 'changePassword']);
        Route::apiResource('roles', RoleController::class);
    });
});
