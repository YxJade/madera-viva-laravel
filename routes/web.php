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





// Proxy para Google Tag Manager
Route::get('/gtm-proxy.js', function() {
    try {
        $gtmId = 'GTM-T94039V7';
        $url = "https://www.googletagmanager.com/gtm.js?id={$gtmId}";
        
        // Intentar obtener desde caché
        $cacheKey = 'gtm_script_' . $gtmId;
        $cached = Cache::get($cacheKey);
        
        if ($cached) {
            return response($cached)
                ->header('Content-Type', 'application/javascript; charset=utf-8')
                ->header('Cache-Control', 'public, max-age=3600');
        }
        
        // Descargar script usando cURL
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; GTMProxy/1.0)',
        ]);
        
        $script = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode !== 200 || empty($script)) {
            \Log::error("GTM Proxy failed: HTTP {$httpCode}, Error: {$error}");
            
            // Fallback: script mínimo para evitar errores JS
            $fallbackScript = "window.dataLayer = window.dataLayer || []; console.warn('GTM proxy failed');";
            return response($fallbackScript, 503)
                ->header('Content-Type', 'application/javascript; charset=utf-8');
        }
        
        // Cachear por 1 hora
        Cache::put($cacheKey, $script, 3600);
        
        return response($script)
            ->header('Content-Type', 'application/javascript; charset=utf-8')
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('X-GTM-Proxy', 'active');
            
    } catch (\Exception $e) {
        \Log::error('GTM Proxy exception: ' . $e->getMessage());
        return response('window.dataLayer = window.dataLayer || [];', 500)
            ->header('Content-Type', 'application/javascript; charset=utf-8');
    }
});




