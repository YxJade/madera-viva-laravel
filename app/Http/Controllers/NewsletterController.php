<?php
// app/Http/Controllers/NewsletterController.php
namespace App\Http\Controllers;

use App\Models\Newsletter;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:newsletters,email'
        ]);

        $subscriber = Newsletter::create([
            'email' => $request->email
        ]);

        return response()->json([
            'message' => 'Suscripción exitosa al newsletter',
            'subscriber' => $subscriber
        ], 201);
    }
}
