<?php

namespace App\Http\Controllers;

use App\Models\User;

class UserController extends Controller
{
    /**
     * Devuelve TODOS los usuarios (sin auth) en formato JSON.
     */
    public function index()
    {
        return User::select('id', 'name', 'email', 'address', 'phone')
                   ->get();
    }
}