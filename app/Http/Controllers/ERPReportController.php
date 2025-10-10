<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ERPReportController extends Controller
{
    public function getERPReport()
    {
        try {
            // 1. VENTAS TOTALES (suma de todos los pedidos)
            $ventasTotales = DB::table('orders')
                ->where('status', 'paid')
                ->sum('total');

            // 2. PEDIDOS ACTIVOS
            $pedidosActivos = DB::table('orders')
                ->select('id', 'user_id', 'total', 'status', 'created_at')
                ->whereIn('status', ['pending', 'paid'])
                ->get();

            // 3. DETALLE DE PEDIDOS CON PRODUCTOS
            $detallePedidos = DB::table('orders')
                ->join('order_items', 'orders.id', '=', 'order_items.order_id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->select(
                    'orders.id as order_id',
                    'orders.total as order_total',
                    'orders.status',
                    'orders.created_at',
                    'products.name as product_name',
                    'order_items.quantity',
                    'order_items.unit_price',
                    'order_items.discount'
                )
                ->whereIn('orders.status', ['pending', 'paid'])
                ->get();

            // 4. PRODUCTOS MÁS VENDIDOS
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

            // 5. ESTADÍSTICAS MENSUALES
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
                ->get();

            // 6. CLIENTES QUE MÁS COMPRAN
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

            // 7. DESCUENTOS APLICADOS
            $descuentosTotales = DB::table('orders')
                ->where('status', 'paid')
                ->sum('discount_total');

            $response = [
                'success' => true,
                'erp' => [
                    'resumen_financiero' => [
                        'ventas_totales' => number_format($ventasTotales, 2),
                        'ventas_totales_numero' => $ventasTotales,
                        'total_pedidos_pagados' => DB::table('orders')->where('status', 'paid')->count(),
                        'descuentos_totales' => number_format($descuentosTotales, 2),
                        'ticket_promedio' => $pedidosActivos->count() > 0 ? 
                            number_format($ventasTotales / $pedidosActivos->count(), 2) : 0
                    ],
                    'pedidos_activos' => [
                        'total' => $pedidosActivos->count(),
                        'data' => $pedidosActivos
                    ],
                    'productos_mas_vendidos' => [
                        'data' => $productosMasVendidos
                    ],
                    'mejores_clientes' => [
                        'data' => $mejoresClientes
                    ],
                    'ventas_mensuales' => [
                        'data' => $ventasMensuales
                    ],
                    'detalle_pedidos' => [
                        'data' => $detallePedidos
                    ]
                ]
            ];

            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte ERP: ' . $e->getMessage()
            ], 500);
        }
    }
}