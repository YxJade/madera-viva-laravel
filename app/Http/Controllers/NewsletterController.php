<?php
// app/Http/Controllers/NewsletterController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        try {
            \Log::info('Newsletter subscription attempt:', $request->all());

            // Validar el email
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|unique:newsletter_subscribers,email'
            ]);

            if ($validator->fails()) {
                \Log::warning('Newsletter validation failed:', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            // Insertar directamente en la tabla (SIN modelo)
            $id = DB::table('newsletter_subscribers')->insertGetId([
                'email' => $request->email,
                'subscribed_at' => now(),
                'active' => 1
            ]);

            \Log::info('Newsletter subscription successful:', ['id' => $id, 'email' => $request->email]);

            return response()->json([
                'success' => true,
                'message' => '¡Gracias por suscribirte a nuestro newsletter!',
                'id' => $id
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Newsletter subscription error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor. Por favor, intenta más tarde.'
            ], 500);
        }
    }
}
