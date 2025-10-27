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





// routes/web.php
Route::get('/gtm.js', function() {
    try {
        $gtmId = 'GTM-T94039V7';
        $url = "https://www.googletagmanager.com/gtm.js?id={$gtmId}";
        
        // Usar cURL en lugar de file_get_contents
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Solo para prueba
        
        $script = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || empty($script)) {
            \Log::error("GTM Proxy failed: HTTP {$httpCode}");
            return response('// GTM unavailable', 503)
                ->header('Content-Type', 'application/javascript');
        }
        
        return response($script)
            ->header('Content-Type', 'application/javascript')
            ->header('Cache-Control', 'public, max-age=3600');
            
    } catch (\Exception $e) {
        \Log::error('GTM Proxy error: ' . $e->getMessage());
        return response('// GTM error', 500)
            ->header('Content-Type', 'application/javascript');
    }
});
