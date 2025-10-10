<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Product; 

class DolibarrService
{
    protected $baseUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->baseUrl = env('DOLIBARR_URL');
        $this->apiKey = env('DOLIBARR_API_KEY');
    }

    private function makeRequest($endpoint, $method = 'GET', $data = [])
    {
        $url = $this->baseUrl . '/api/index.php' . $endpoint;
        
        try {
            $response = Http::withHeaders([
                'DOLAPIKEY' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->timeout(30)->{$method}($url, $data);

            Log::info("Dolibarr API Request: {$method} {$endpoint}", [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $response->json(),
                'body' => $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('Error Dolibarr API: ' . $e->getMessage(), [
                'endpoint' => $endpoint,
                'method' => $method
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status' => 500
            ];
        }
    }

    // Probar conexión básica
    public function testConnection()
    {
        return $this->makeRequest('/status');
    }

    // Obtener información del sistema
    public function getSystemStatus()
    {
        return $this->makeRequest('/status');
    }

    // Obtener usuarios
    public function getUsers()
    {
        return $this->makeRequest('/users');
    }

    // Obtener productos
    public function getProducts()
    {
        return $this->makeRequest('/products');
    }

    // Obtener terceros (clientes/proveedores)
    public function getThirdParties()
    {
        return $this->makeRequest('/thirdparties');
    }

    // Obtener contactos
    public function getContacts()
    {
        return $this->makeRequest('/contacts');
    }

    // Obtener pedidos
    public function getOrders()
    {
        return $this->makeRequest('/orders');
    }

    // Obtener facturas
    public function getInvoices()
    {
        return $this->makeRequest('/invoices');
    }

    // Obtener categorías
    public function getCategories()
    {
        return $this->makeRequest('/categories?type=product');
    }

    // Obtener proveedores (filtrado de terceros)
    public function getSuppliers()
    {
        return $this->makeRequest('/thirdparties?sortfield=t.rowid&sortorder=ASC&limit=100');
    }

    // Sincronizar productos de Madera Viva a Dolibarr
    public function syncProductsToDolibarr()
    {
        // Obtener productos de tu base de datos
        $products = Product::all();
        
        $results = [];
        foreach ($products as $product) {
            $productData = [
                'ref' => 'MV' . $product->id, // Sin guión
                'label' => $product->name,
                'description' => $product->description,
                'price' => $product->price,
                'stock' => $product->stock,
                'status' => $product->active ? 1 : 0
            ];
            
            $result = $this->makeRequest('/products', 'POST', $productData);
            $results[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'dolibarr_result' => $result
            ];
        }
        
        return $results;
    }

    // Crear clientes de ejemplo en Dolibarr (CORREGIDO)
    public function createSampleClients()
    {
        $clients = [
            [
                'name' => 'Carpintería Hernández',
                'client' => 1,
                'code_client' => 'CLI001', // Sin guión
                'address' => 'Av. Madera 123, Ciudad',
                'email' => 'hernandez@carpinteria.com',
                'phone' => '+52 123 456 7890',
                'town' => 'Ciudad de México',
                'zip' => '01000'
            ],
            [
                'name' => 'Mueblería Moderna', 
                'client' => 1,
                'code_client' => 'CLI002', // Sin guión
                'address' => 'Calle Diseño 456, Ciudad',
                'email' => 'ventas@muebleriamoderna.com',
                'phone' => '+52 987 654 3210',
                'town' => 'Guadalajara',
                'zip' => '44100'
            ]
        ];
        
        $results = [];
        foreach ($clients as $client) {
            $result = $this->makeRequest('/thirdparties', 'POST', $client);
            $results[] = [
                'client_name' => $client['name'],
                'dolibarr_result' => $result
            ];
        }
        
        return $results;
    }

    // Crear un solo cliente de prueba (MUY SIMPLE)
    public function createSingleTestClient()
    {
        $clientData = [
            'name' => 'Cliente Prueba Madera Viva',
            'client' => 1,
            'email' => 'prueba@maderaviva.com'
            // Solo campos mínimos requeridos
        ];
        
        return $this->makeRequest('/thirdparties', 'POST', $clientData);
    }







    // Reportes CRM - Clientes y Ventas
    public function getCRMReports()
    {
        return [
            'clientes_totales' => $this->makeRequest('/thirdparties?sortfield=t.rowid&sortorder=ASC&limit=100'),
            'mejores_clientes' => $this->makeRequest('/invoices?sortfield=total_ttc&sortorder=DESC&limit=10'),
            'clientes_nuevos' => $this->makeRequest('/thirdparties?sortfield=t.datec&sortorder=DESC&limit=10')
        ];
    }




    // Reportes ERP - Finanzas y Operaciones
    public function getERPReports()
    {
        return [
            'ventas_totales' => $this->makeRequest('/invoices?sortfield=date_creation&sortorder=DESC&limit=30'),
            'facturacion_mensual' => $this->makeRequest('/invoices?sqlfilters=(date_creation%3A%3E%3A\'2025-10-01\')'),
            'estado_financiero' => $this->makeRequest('/invoices/statistics')
        ];
    }


    // Reportes SCM - Inventario y Compras
    public function getSCMReports()
    {
        return [
            'inventario' => $this->makeRequest('/products?sortfield=stock&sortorder=DESC&limit=50'),
            'productos_mas_vendidos' => $this->makeRequest('/invoicelines?sortfield=qty&sortorder=DESC&limit=10'),
            'stock_bajo' => $this->makeRequest('/products?filter=stock:less:10')
        ];
    }
}
