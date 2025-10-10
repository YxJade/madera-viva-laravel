<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DolibarrService;
use Barryvdh\DomPDF\Facade\Pdf; // 👈 NECESARIO para generar el PDF

class DolibarrReportController extends Controller
{
    protected $dolibarr;

    public function __construct()
    {
        $this->dolibarr = new DolibarrService();
    }

    // Reporte consolidado
    public function consolidatedReport()
    {
        return response()->json([
            'crm' => $this->dolibarr->getCRMReports(),
            'scm' => $this->dolibarr->getSCMReports(),
            'erp' => $this->dolibarr->getERPReports(),
            'timestamp' => now()
        ]);
    }

    // Reporte específico CRM
    public function crmReport()
    {
        return response()->json($this->dolibarr->getCRMReports());
    }

    // Reporte específico SCM
    public function scmReport()
    {
        return response()->json($this->dolibarr->getSCMReports());
    }

    // Reporte específico ERP
    public function erpReport()
    {
        return response()->json($this->dolibarr->getERPReports());
    }

    // 🔥 NUEVA FUNCIÓN: Generación de PDF a partir de datos POST
    /**
     * Genera un reporte en formato PDF a partir de los datos recibidos.
     * @param \Illuminate\Http\Request $request
     * @param string $type El tipo de reporte (crm, scm, erp).
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function generatePdfReport(Request $request, $type)
    {
        // 1. Obtener y validar los datos del reporte (JSON enviado por el frontend)
        $reportData = $request->input('reportData');

        if (empty($reportData) || !is_array($reportData)) {
            return response()->json(['error' => 'No se recibieron datos válidos para generar el PDF.'], 400);
        }
        
        $reportType = strtoupper($type);
        $fileName = "Reporte_MaderaViva_{$reportType}_" . now()->format('Ymd_His') . ".pdf";

        // 2. Generar el contenido HTML
        $htmlContent = $this->generateReportHtml($reportData, $reportType);

        try {
            // 3. Crear el PDF usando DomPDF
            $pdf = Pdf::loadHtml($htmlContent);

            // 4. Devolver el PDF como descarga
            return $pdf->download($fileName);

        } catch (\Exception $e) {
            // Manejo de errores de DomPDF
            \Log::error("Error al generar el PDF de {$reportType}: " . $e->getMessage());
            return response()->json(['error' => 'Error al generar el PDF.', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * Función auxiliar para generar el HTML del reporte con un formato ordenado.
     * @param array $data Los datos del reporte.
     * @param string $type El tipo de reporte (CRM, SCM, ERP).
     * @return string
     */




    ///******GENERAR REPORTE******* */

    private function generateReportHtml(array $data, string $type)
    {
     
        $styles = '
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; font-size: 10pt; line-height: 1.6; }
                h1 { text-align: center; color: #1e3a8a; border-bottom: 3px solid #3b82f6; padding-bottom: 10px; margin-bottom: 25px; }
                h2 { color: #374151; border-left: 5px solid #60a5fa; padding-left: 10px; margin-top: 20px; margin-bottom: 15px; background-color: #eff6ff; padding: 5px 10px; }
                .header-info { margin-bottom: 20px; font-size: 9pt; }
                .data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                .data-table th, .data-table td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
                .data-table th { background-color: #d1d5db; color: #1f2937; font-weight: bold; }
                .summary { background-color: #f3f4f6; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
                .summary p { margin: 5px 0; }
                .footer { text-align: center; margin-top: 30px; font-size: 8pt; color: #6b7280; }
            </style>';
            
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">' . $styles . '</head><body>';





    
        
        $html .= "<h1>Reporte de Gestión Dolibarr - {$type}</h1>";
        $html .= '<div class="header-info">';
        $html .= '<p><strong>Fecha de Generación:</strong> ' . now()->format('d/m/Y H:i:s') . '</p>';
        $html .= '<p><strong>Generado por:</strong> Sistema de Reportes Madera Viva</p>';
        $html .= '</div>';


      
        switch ($type) {
            case 'CRM':
                $html .= $this->buildCrmHtml($data);
                break;
            case 'SCM':
                $html .= $this->buildScmHtml($data);
                break;
            case 'ERP':
                $html .= $this->buildErpHtml($data);
                break;
            default:
                // Si el tipo no es reconocido, muestra los datos sin formato
                $html .= '<h2>Datos Recibidos (Sin Formato Específico)</h2>';
                $html .= '<pre>' . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . '</pre>';
                break;
        }

        $html .= '<div class="footer">Documento generado automáticamente por el API de reportes de Madera Viva.</div>';
        $html .= '</body></html>';
        
        return $html;
    }


    /****FINALIZA AQUI***** */





    
    // --- FUNCIONES AUXILIARES PARA GENERAR EL CONTENIDO ESPECÍFICO ---

    /**
     * Genera el HTML específico para el reporte CRM.
     * (Asume una estructura de datos común para Dolibarr: 'clientes_totales' con una lista de terceros)
     */
    private function buildCrmHtml(array $data)
{
    // --- CLIENTES (usuarios locales) ---
    $clientes = \App\Models\User::select('id', 'name', 'email', 'address', 'phone')->get();
    $totalClientes = $clientes->count();

    // --- PROVEEDORES (tabla suppliers) ---
    $proveedores = \App\Models\Supplier::where('active', 1)->get();
    $totalProveedores = $proveedores->count();

    // --- INICIO HTML ---
    $html  = '<h2>👥 Resumen CRM</h2>';
    $html .= '<div class="summary">';
    $html .= '<p>Clientes Totales (usuarios registrados): <strong>' . $totalClientes . '</strong></p>';
    $html .= '<p>Proveedores Activos: <strong>' . $totalProveedores . '</strong></p>';
    $html .= '</div>';

    // --- TABLA CLIENTES ---
    $html .= '<h3>📋 Clientes</h3>';
    $html .= '<table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Dirección</th>
                <th>Teléfono</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($clientes as $c) {
        $html .= '<tr>
            <td>' . $c->id . '</td>
            <td><strong>' . $c->name . '</strong></td>
            <td>' . $c->email . '</td>
            <td>' . ($c->address ?? 'Sin dirección') . '</td>
            <td>' . ($c->phone   ?? 'Sin teléfono') . '</td>
        </tr>';
    }
    $html .= '</tbody></table>';

    // --- TABLA PROVEEDORES ---
    $html .= '<h3>🏢 Proveedores</h3>';
    $html .= '<table class="data-table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Empresa</th>
                <th>Email</th>
                <th>Teléfono</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($proveedores as $p) {
        $html .= '<tr>
            <td><strong>' . $p->name . '</strong></td>
            <td>' . ($p->company ?? 'N/A') . '</td>
            <td>' . $p->email . '</td>
            <td>' . ($p->phone   ?? 'N/A') . '</td>
        </tr>';
    }
    $html .= '</tbody></table>';

    return $html;
}

    /**
     * Genera el HTML específico para el reporte SCM.
     * (Asume una estructura de datos común para Dolibarr: 'inventario' con una lista de productos)
     */
    private function buildScmHtml(array $data) {
        $html = '';
        $productos = $data['inventario']['data'] ?? [];
        $stockBajo = $data['stock_bajo']['data'] ?? [];
        
        // Resumen
        $html .= '<h2>Resumen SCM (Supply Chain Management)</h2>';
        $html .= '<div class="summary"><p>Total de Productos/Servicios: <strong>' . count($productos) . '</strong></p>';
        $html .= '<p>Productos con Stock Crítico: <strong>' . count($stockBajo) . '</strong></p></div>';

        // Tabla de Productos con Stock Bajo
        $html .= '<h2>📦 Stock de Productos</h2>';
        $html .= '<table class="data-table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Stock</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>';

        $productos = $data['inventario']['data'] ?? [];
        foreach (array_slice($productos, 0, 30) as $producto) {
            $stock = $producto['stock_reel'] ?? 0;
            $estado = $stock > 0 ? '✅ Disponible' : '❌ Sin stock';
            $rowColor = $stock == 0 ? 'style="background-color: #ffe6e6;"' : '';
            $html .= "<tr $rowColor>
                <td><strong>" . ($producto['label'] ?? 'Sin nombre') . "</strong></td>
                <td>$stock</td>
                <td>$estado</td>
            </tr>";
        }

        $html .= '</tbody></table>';

        return $html;
    }

    /**
     * Genera el HTML específico para el reporte ERP.
     * (Asume una estructura de datos común para Dolibarr: 'facturas_pendientes' con una lista de facturas)
     */
    private function buildErpHtml(array $data)
{
    // Usamos los mismos datos que ya generas en ERPReportController::getERPReport()
    $erp = $data['erp'] ?? [];

    // Resumen financiero
    $resumen = $erp['resumen_financiero'] ?? [];
    $ventasTotales = $resumen['ventas_totales'] ?? '0.00';
    $totalPedidosPagados = $resumen['total_pedidos_pagados'] ?? 0;
    $descuentosTotales = $resumen['descuentos_totales'] ?? '0.00';
    $ticketPromedio = $resumen['ticket_promedio'] ?? '0.00';

    // Listados
    $pedidosActivos = $erp['pedidos_activos']['data'] ?? [];
    $productosMasVendidos = $erp['productos_mas_vendidos']['data'] ?? [];
    $mejoresClientes = $erp['mejores_clientes']['data'] ?? [];

    // --- HTML ---
    $html = '<h2>💰 Resumen Financiero (ERP)</h2>';
    $html .= '<div class="summary">';
    $html .= '<p>Ventas Totales: <strong>$' . $ventasTotales . '</strong></p>';
    $html .= '<p>Pedidos Pagados: <strong>' . $totalPedidosPagados . '</strong></p>';
    $html .= '<p>Descuentos Aplicados: <strong>$' . $descuentosTotales . '</strong></p>';
    $html .= '<p>Ticket Promedio: <strong>$' . $ticketPromedio . '</strong></p>';
    $html .= '</div>';

    // Tabla: Pedidos Activos
    $html .= '<h3>📋 Pedidos Activos</h3>';
    $html .= '<table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Cliente ID</th>
                <th>Total</th>
                <th>Estado</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>';
    foreach (array_slice($pedidosActivos, 0, 20) as $p) {
        $estado = $p['status'] ?? 'desconocido';
        $fecha = isset($p['created_at']) ? date('d/m/Y', strtotime($p['created_at'])) : 'N/A';
        $html .= "<tr>
            <td>{$p['id']}</td>
            <td>{$p['user_id']}</td>
            <td><strong>$" . number_format($p['total'] ?? 0, 2) . "</strong></td>
            <td>$estado</td>
            <td>$fecha</td>
        </tr>";
    }
    $html .= '</tbody></table>';

    // Tabla: Productos Más Vendidos
    $html .= '<h3>🏆 Productos Más Vendidos</h3>';
    $html .= '<table class="data-table">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Unidades Vendidas</th>
                <th>Ingresos Totales</th>
            </tr>
        </thead>
        <tbody>';
    foreach ($productosMasVendidos as $prod) {
        $html .= "<tr>
            <td><strong>{$prod['name']}</strong></td>
            <td>{$prod['total_vendido']}</td>
            <td><strong>$" . number_format($prod['ingresos_totales'], 2) . "</strong></td>
        </tr>";
    }
    $html .= '</tbody></table>';

    // Tabla: Mejores Clientes
    $html .= '<h3>👑 Mejores Clientes</h3>';
    $html .= '<table class="data-table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Email</th>
                <th>Pedidos</th>
                <th>Total Gastado</th>
            </tr>
        </thead>
        <tbody>';
    foreach ($mejoresClientes as $c) {
        $html .= "<tr>
            <td><strong>{$c['name']}</strong></td>
            <td>{$c['email']}</td>
            <td>{$c['total_pedidos']}</td>
            <td><strong>$" . number_format($c['total_gastado'], 2) . "</strong></td>
        </tr>";
    }
    $html .= '</tbody></table>';

    return $html;
}
}