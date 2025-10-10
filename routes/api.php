<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\CartItemController;
use App\Http\Controllers\CategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DolibarrTestController;
use App\Http\Controllers\DolibarrReportController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\SupplierController; // si también haces supplier
use App\Http\Controllers\UserController;
use App\Http\Controllers\ERPReportController;
use App\Http\Controllers\ERPReportPDFController;




/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// 🔥 RUTAS DOLIBARR (AL INICIO)
Route::prefix('dolibarr')->group(function () {
    // Rutas GET
    Route::get('/test', [DolibarrTestController::class, 'testConnection']);
    Route::get('/status', [DolibarrTestController::class, 'getSystemStatus']);
    Route::get('/users', [DolibarrTestController::class, 'getUsers']);
    Route::get('/products', [DolibarrTestController::class, 'getProducts']);
    Route::get('/thirdparties', [DolibarrTestController::class, 'getThirdParties']);
    Route::get('/contacts', [DolibarrTestController::class, 'getContacts']);
    Route::get('/orders', [DolibarrTestController::class, 'getOrders']);
    Route::get('/invoices', [DolibarrTestController::class, 'getInvoices']);
    Route::get('/categories', [DolibarrTestController::class, 'getCategories']);
    Route::get('/suppliers', [DolibarrTestController::class, 'getSuppliers']);
    Route::post('/test-create-client', [DolibarrTestController::class, 'testCreateClient']);
    
    // Rutas POST para sincronización
    Route::post('/sync-products', [DolibarrTestController::class, 'syncProducts']);
    Route::post('/create-clients', [DolibarrTestController::class, 'createSampleClients']);
});

// ... en el grupo de rutas de reportes Dolibarr
Route::prefix('dolibarr/reports')->group(function () {
    // ... tus rutas GET existentes
    Route::get('/consolidated', [DolibarrReportController::class, 'consolidatedReport']);
    Route::get('/crm', [DolibarrReportController::class, 'crmReport']);
    Route::get('/scm', [DolibarrReportController::class, 'scmReport']);
    Route::get('/erp', [DolibarrReportController::class, 'erpReport']);
    
    // 🔥 Verifica esta ruta
    Route::post('/generate-pdf/{type}', [DolibarrReportController::class, 'generatePdfReport']);
});


Route::get('/products', function() {
    $products = DB::table('products')
        ->select('id', 'name', 'price', 'stock', 'brand', 'category_id', 'active')
        ->where('active', 1)
        ->get();
    
    return response()->json($products);
});



// Auth Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public Product Routes

// ✅ Después (correcto)
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/offers', [ProductController::class, 'offers']);
Route::get('/products/search', [ProductController::class, 'search']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{slug}/products', [CategoryController::class, 'products']);
Route::get('/products/stock', [ProductController::class, 'stock']);


//USUARIOS
Route::get('/users',     [UserController::class, 'index']);
///EMPLEADOS
Route::get('/employees', [EmployeeController::class, 'index']);
//PROVEEDORES
Route::get('/suppliers', [SupplierController::class, 'index']);

// Reportes ERP (Finanzas)
Route::get('/erp-report', [App\Http\Controllers\ERPReportController::class, 'getERPReport']);// Ruta existente - actualízala para incluir ERP
Route::get('/reports/erp', [ERPReportController::class, 'getERPReport']);
// PDF Reports
Route::post('/reports/generate-erp-pdf', [App\Http\Controllers\ERPReportPDFController::class, 'generateERPReportPDF']);


// Newsletter
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe']);

// Protected Routes (Require Auth)
Route::middleware('auth:api')->group(function () {
    // User
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    


    Route::middleware('auth:api')->group(function () {
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/add', [CartController::class, 'add']);
    Route::put('/cart/items/{id}', [CartController::class, 'update']);
    Route::delete('/cart/items/{id}', [CartController::class, 'remove']);
    Route::delete('/cart/clear', [CartController::class, 'clear']);
    Route::post('/checkout', [CartController::class, 'checkout']);
});





});