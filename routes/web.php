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
        
        // Log para debugging
        \Log::info('GTM Proxy: Iniciando descarga desde ' . $url);
        
        // Intentar obtener desde caché (opcional en Railway)
        $cacheKey = 'gtm_script_' . $gtmId;
        
        try {
            $cached = Cache::get($cacheKey);
            if ($cached) {
                \Log::info('GTM Proxy: Sirviendo desde caché');
                return response($cached)
                    ->header('Content-Type', 'application/javascript; charset=utf-8')
                    ->header('Cache-Control', 'public, max-age=3600')
                    ->header('X-GTM-Proxy', 'cached');
            }
        } catch (\Exception $cacheError) {
            \Log::warning('GTM Proxy: Cache no disponible, continuando sin caché');
        }
        
        // Descargar script usando cURL
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_HTTPHEADER => [
                'Accept: */*',
                'Accept-Language: es-ES,es;q=0.9',
            ],
        ]);
        
        $script = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        \Log::info("GTM Proxy: HTTP {$httpCode}, Size: " . strlen($script) . " bytes");
        
        if ($httpCode !== 200 || empty($script)) {
            \Log::error("GTM Proxy FAILED: HTTP {$httpCode}, Error: {$error}");
            
            // Fallback: script mínimo
            $fallbackScript = "// GTM Proxy Error\nwindow.dataLayer = window.dataLayer || [];\nconsole.warn('GTM proxy failed: HTTP {$httpCode}');";
            return response($fallbackScript, 200) // Retornar 200 para evitar errores en el navegador
                ->header('Content-Type', 'application/javascript; charset=utf-8')
                ->header('X-GTM-Proxy', 'fallback');
        }
        
        // Intentar cachear (ignorar errores si falla)
        try {
            Cache::put($cacheKey, $script, 3600);
        } catch (\Exception $cacheError) {
            \Log::warning('GTM Proxy: No se pudo cachear');
        }
        
        return response($script)
            ->header('Content-Type', 'application/javascript; charset=utf-8')
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('X-GTM-Proxy', 'live');
            
    } catch (\Exception $e) {
        \Log::error('GTM Proxy EXCEPTION: ' . $e->getMessage());
        $fallbackScript = "// GTM Proxy Exception\nwindow.dataLayer = window.dataLayer || [];\nconsole.error('GTM proxy exception');";
        return response($fallbackScript, 200)
            ->header('Content-Type', 'application/javascript; charset=utf-8')
            ->header('X-GTM-Proxy', 'error');
    }
});




