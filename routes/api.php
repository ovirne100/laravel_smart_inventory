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



// Public routes
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::get('roles-public', [RoleController::class, 'getRolesForRegister']);
Route::post('roles-public', [RoleController::class, 'store']);

//categiries rutas
Route::post('/categories/init', [CategoryController::class, 'init']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::post('/categories/sync', [CategoryController::class, 'sync']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('profile', [UserController::class, 'profile']);
      Route::get('user', [AuthController::class, 'me']); // Para obtener usuario actual

    // Productos
    Route::apiResource('products', ProductController::class);

    // Rutas comunes protegidas
    Route::apiResource('dep-buys', DepBuyController::class);
    Route::apiResource('orders', OrderController::class);
    Route::apiResource('product-details', ProductDetailController::class);
    //Route::apiResource('categories', CategoryController::class);
    Route::apiResource('inventories', InventoryController::class);
    Route::apiResource('locations', LocationController::class);
    Route::apiResource('warehouses', WarehouseController::class);
    Route::apiResource('alerts', AlertController::class);
    Route::apiResource('entries', EntryController::class);
    Route::apiResource('entry-notes', EntryNoteController::class);
    Route::apiResource('product-exits', ProductExitController::class);
    Route::apiResource('exit-details', ExitDetailController::class);
    Route::apiResource('inventory-details', InventoryDetailController::class);

    // Suppliers
    Route::apiResource('suppliers', SupplierController::class);
    // Product-Supplier associations
    Route::apiResource('product-suppliers', ProductSupplierController::class);
    // Association endpoints: products of a supplier
    Route::get('suppliers/{supplier}/products', [SupplierController::class, 'products']);
    Route::post('suppliers/{supplier}/products', [SupplierController::class, 'syncProducts']);
    Route::post('suppliers/{supplier}/products/attach', [SupplierController::class, 'attachProducts']);
    Route::delete('suppliers/{supplier}/products/{product}', [SupplierController::class, 'detachProduct']);



    // Rutas solo admin
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::apiResource('roles', RoleController::class);
    });
});
