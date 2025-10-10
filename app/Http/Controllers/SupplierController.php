<?php

namespace App\Http\Controllers;

use App\Models\Supplier;

class SupplierController extends Controller
{
    public function index()
    {
        return Supplier::where('active', 1)
                       ->select('id', 'name', 'email', 'phone', 'company')
                       ->get();
    }
}