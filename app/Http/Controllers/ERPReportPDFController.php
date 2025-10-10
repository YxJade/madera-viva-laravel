<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ERPReportPDFController extends Controller
{
    public function generateERPReportPDF(Request $request)
    {
        try {
            // Obtener datos del ERP
            $erpData = $this->getERPData();
            
            $data = [
                'title' => 'Reporte ERP - Finanzas',
                'date' => now()->format('d/m/Y H:i:s'),
                'erpData' => $erpData
            ];

            $pdf = PDF::loadView('reports.erp-pdf', $data);
            $pdf->setPaper('A4', 'portrait');
            
            return $pdf->download('Reporte_ERP_Finanzas_' . now()->format('Y-m-d') . '.pdf');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getERPData()
    {
        // 1. VENTAS TOTALES
        $ventasTotales = DB::table('orders')
            ->where('status', 'paid')
            ->sum('total');

        // 2. PEDIDOS ACTIVOS
        $pedidosActivos = DB::table('orders')
            ->select('id', 'user_id', 'total', 'status', 'created_at')
            ->whereIn('status', ['pending', 'paid'])
            ->get();

        // 3. PRODUCTOS MÁS VENDIDOS
        $productosMasVendidos = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select(
                'products.name',
                'products.id',
                DB::raw('SUM(order_items.quantity) as total_vendido'),
                DB::raw('SUM(order_items.unit_price * order_items.quantity) as ingresos_totales')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_vendido')
            ->limit(10)
            ->get();

        // 4. MEJORES CLIENTES
        $mejoresClientes = DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->select(
                'users.id',
                'users.name',
                'users.email',
                DB::raw('COUNT(orders.id) as total_pedidos'),
                DB::raw('SUM(orders.total) as total_gastado')
            )
            ->where('orders.status', 'paid')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('total_gastado')
            ->limit(10)
            ->get();

        // 5. DESCUENTOS APLICADOS
        $descuentosTotales = DB::table('orders')
            ->where('status', 'paid')
            ->sum('discount_total');

        // 6. VENTAS MENSUALES
        $ventasMensuales = DB::table('orders')
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as total_pedidos'),
                DB::raw('SUM(total) as total_ventas')
            )
            ->where('status', 'paid')
            ->groupBy(DB::raw('YEAR(created_at), MONTH(created_at)'))
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->limit(6)
            ->get();

        return [
            'resumen_financiero' => [
                'ventas_totales' => number_format($ventasTotales, 2),
                'ventas_totales_numero' => $ventasTotales,
                'total_pedidos_pagados' => DB::table('orders')->where('status', 'paid')->count(),
                'pedidos_pendientes' => DB::table('orders')->where('status', 'pending')->count(),
                'descuentos_totales' => number_format($descuentosTotales, 2),
                'ticket_promedio' => $pedidosActivos->count() > 0 ? 
                    number_format($ventasTotales / $pedidosActivos->count(), 2) : 0
            ],
            'pedidos_activos' => [
                'total' => $pedidosActivos->count(),
                'data' => $pedidosActivos
            ],
            'productos_mas_vendidos' => $productosMasVendidos,
            'mejores_clientes' => $mejoresClientes,
            'ventas_mensuales' => $ventasMensuales
        ];
    }
}