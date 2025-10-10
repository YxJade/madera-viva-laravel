<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DolibarrService;

class DolibarrTestController extends Controller
{
    protected $dolibarr;

    public function __construct()
    {
        $this->dolibarr = new DolibarrService();
    }

    public function testConnection()
    {
        $result = $this->dolibarr->testConnection();
        
        return response()->json([
            'message' => $result['success'] ? '✅ Conexión exitosa con Dolibarr!' : '❌ Error en la conexión',
            'result' => $result
        ], $result['success'] ? 200 : 500);
    }

    public function getSystemStatus()
    {
        $result = $this->dolibarr->getSystemStatus();
        return response()->json($result);
    }

    public function getUsers()
    {
        $result = $this->dolibarr->getUsers();
        return response()->json($result);
    }

    public function getProducts()
    {
        $result = $this->dolibarr->getProducts();
        return response()->json($result);
    }

    public function getThirdParties()
    {
        $result = $this->dolibarr->getThirdParties();
        return response()->json($result);
    }

    public function getContacts()
    {
        $result = $this->dolibarr->getContacts();
        return response()->json($result);
    }

    public function getOrders()
    {
        $result = $this->dolibarr->getOrders();
        return response()->json($result);
    }

    public function getInvoices()
    {
        $result = $this->dolibarr->getInvoices();
        return response()->json($result);
    }

    public function getCategories()
    {
        $result = $this->dolibarr->getCategories();
        return response()->json($result);
    }

    public function getSuppliers()
    {
        $result = $this->dolibarr->getSuppliers();
        return response()->json($result);
    }

    // Sincronizar productos a Dolibarr
    public function syncProducts()
    {
        $result = $this->dolibarr->syncProductsToDolibarr();
        return response()->json([
            'message' => 'Productos sincronizados a Dolibarr',
            'total_products' => count($result),
            'results' => $result
        ]);
    }

    // Crear clientes de ejemplo
    public function createSampleClients()
    {
        $result = $this->dolibarr->createSampleClients();
        return response()->json([
            'message' => 'Clientes de ejemplo creados en Dolibarr',
            'results' => $result
        ]);
    }

    public function dashboard()
    {
        return view('dolibarr.dashboard');
    }



    // Probar crear un solo cliente
    public function testCreateClient()
    {
        $result = $this->dolibarr->createSingleTestClient();
        return response()->json([
            'message' => 'Prueba de creación de cliente',
            'result' => $result
        ]);
    }
}