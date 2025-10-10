<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\DolibarrTestController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 1️⃣ PRIMERO - Ruta SIMPLE de prueba
Route::get('/prueba-simple', function () {
    return response()->json([
        'mensaje' => '✅ Laravel funciona correctamente',
        'timestamp' => now()
    ]);
});

// Ruta específica para dashboard Dolibarr
Route::get('/admin/dolibarr-dashboard', [DolibarrTestController::class, 'dashboard']);





// Route::prefix('dolibarr')->group(function () {
//     Route::get('/test', [DolibarrTestController::class, 'testConnection']);
//     Route::get('/status', [DolibarrTestController::class, 'getSystemStatus']);
//     Route::get('/users', [DolibarrTestController::class, 'getUsers']);
//     Route::get('/products', [DolibarrTestController::class, 'getProducts']);
//     Route::get('/thirdparties', [DolibarrTestController::class, 'getThirdParties']);
//     Route::get('/contacts', [DolibarrTestController::class, 'getContacts']);
//     Route::get('/orders', [DolibarrTestController::class, 'getOrders']);
//     Route::get('/invoices', [DolibarrTestController::class, 'getInvoices']);
//     Route::get('/categories', [DolibarrTestController::class, 'getCategories']);
//     Route::get('/suppliers', [DolibarrTestController::class, 'getSuppliers']);
//     Route::get('/dashboard', [DolibarrTestController::class, 'dashboard']);
// });

// 3️⃣ ÚLTIMO - Ruta comodín para el frontend (DEBE SER LA ÚLTIMA)
Route::get('/{any?}', function () {
    $path = public_path('madera/index.html');
    
    if (!file_exists($path)) {
        abort(404, 'Archivo no encontrado');
    }
    
    return response(file_get_contents($path))->header('Content-Type', 'text/html');
})->where('any', '.*');