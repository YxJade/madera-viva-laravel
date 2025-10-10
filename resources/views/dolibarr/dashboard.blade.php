<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Dolibarr - Madera Viva</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>📊</text></svg>">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 20px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container { 
            max-width: 1400px; 
            margin: 0 auto; 
        }
        .card { 
            background: white; 
            padding: 25px; 
            margin: 15px 0; 
            border-radius: 12px; 
            box-shadow: 0 8px 25px rgba(0,0,0,0.1); 
            border-left: 5px solid #007bff;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.15);
        }
        button { 
            padding: 12px 20px; 
            margin: 8px; 
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,123,255,0.3);
        }
        button:hover { 
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,123,255,0.4);
        }
        .success { 
            color: #28a745; 
            font-weight: bold;
        }
        .error { 
            color: #dc3545; 
            font-weight: bold;
        }
        
        /* ESTILOS MEJORADOS PARA RESULTADOS VISUALES */
        .result {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-top: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border-left: 5px solid #28a745;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin: 25px 0;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: scale(1.05);
        }

        .stat-icon {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .stat-number {
            font-size: 2.8em;
            font-weight: bold;
            margin: 15px 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .stat-label {
            font-size: 1em;
            opacity: 0.95;
            font-weight: 600;
        }

        .data-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
            border-left: 4px solid #17a2b8;
        }

        .data-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e9ecef;
            transition: background-color 0.2s ease;
        }

        .data-item:hover {
            background-color: #ffffff;
            border-radius: 6px;
            padding-left: 10px;
            padding-right: 10px;
        }

        .data-item:last-child {
            border-bottom: none;
        }

        .badge {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: bold;
        }

        .json-toggle {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 15px;
            transition: all 0.3s ease;
        }

        .json-toggle:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .json-view {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            border-radius: 8px;
            margin-top: 15px;
            display: none;
            max-height: 400px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }

        .loading {
            color: #6c757d;
            font-style: italic;
            text-align: center;
            padding: 30px;
            font-size: 1.1em;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin: 25px 0;
        }

        .stats .stat-card {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        }

        h1 {
            color: white;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5em;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        h2 {
            color: #2c3e50;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        h3 {
            color: #495057;
            margin-bottom: 15px;
        }

        h4 {
            color: #6c757d;
            margin-bottom: 15px;
            font-size: 1.1em;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .stats-grid,
            .stats {
                grid-template-columns: 1fr;
            }
            
            .card {
                padding: 15px;
            }
            
            button {
                width: 100%;
                margin: 5px 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Dashboard Dolibarr API - Madera Viva</h1>
        
        <div class="stats">
            <div class="stat-card">
                <h3>👥 Usuarios</h3>
                <div id="userCount">-</div>
            </div>
            <div class="stat-card">
                <h3>📦 Productos</h3>
                <div id="productCount">-</div>
            </div>
            <div class="stat-card">
                <h3>🏢 Terceros</h3>
                <div id="thirdpartyCount">-</div>
            </div>
            <div class="stat-card">
                <h3>🔗 Estado</h3>
                <div id="connectionStatus">-</div>
            </div>
        </div>

        <div class="card">
            <h2>🔗 Conexión y Sistema</h2>
            <button onclick="window.testConnection()">Probar Conexión</button>
            <button onclick="window.getSystemStatus()">Estado del Sistema</button>
        </div>

        <div class="card">
            <h2>👥 Gestión de Usuarios</h2>
            <button onclick="window.getUsers()">Obtener Usuarios</button>
            <button onclick="window.getContacts()">Obtener Contactos</button>
        </div>

        <div class="card">
            <h2>📦 Inventario y Productos</h2>
            <button onclick="window.getProducts()">Obtener Productos</button>
            <button onclick="window.getCategories()">Obtener Categorías</button>
        </div>

        <div class="card">
            <h2> Terceros (Clientes y Proveedores)</h2>
            <button onclick="window.getThirdParties()">Obtener Terceros</button>
            <button onclick="window.getSuppliers()">Obtener Proveedores</button>
        </div>

        <div class="card">
            <h2>📊 Ventas y Compras</h2>
            <button onclick="window.getOrders()">Obtener Pedidos</button>
            <button onclick="window.getInvoices()">Obtener Facturas</button>
        </div>

        <!-- 🔄 SECCIÓN DE SINCRONIZACIÓN -->
        <div class="card">
            <h2>🔄 Sincronización de Datos</h2>
            <button onclick="syncProducts()" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); margin: 8px;">
                Sincronizar Productos a Dolibarr
            </button>
            <button onclick="createClients()" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); margin: 8px;">
                Crear Clientes de Ejemplo
            </button>
        </div>

        <div class="card">
            <h2>🧪 Pruebas Directas</h2>
            <button onclick="testCreateClient()" style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); color: black; margin: 8px;">
                Probar Crear Cliente Simple
            </button>
        </div>


                <!-- 👥 Empleados -->
        <div class="card">
            <h2>👥 Empleados</h2>
            <button onclick="getEmployees()" style="background: linear-gradient(135deg, #6f42c1 0%, #5a2d9c 100%); color: white; margin: 8px;">
                Ver Empleados
            </button>
            <div id="employees-result" class="result" style="margin-top: 20px;">
                <p class="loading">Haz clic para ver empleados...</p>
            </div>
        </div>

        <!-- 📊 SECCIÓN DE REPORTES MEJORADA -->
        <div class="card">
            <h2>📊 Reportes Empresariales - Vista Mejorada</h2>
            <button onclick="getCRMReport()" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); margin: 8px;">
                👥 Reporte CRM (Clientes)
            </button>
            <button onclick="getSCMReport()" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); margin: 8px;">
                📦 Reporte SCM (Inventario)
            </button>
            <button onclick="getERPReport()" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); margin: 8px;">
                💰 Reporte ERP (Finanzas)
            </button>
            <button onclick="getUnifiedReport()" style="background: linear-gradient(135deg, #6f42c1 0%, #5a2d9c 100%); margin: 8px;">
                📊 Reporte Unificado
            </button>
        </div>


        

        <!-- 📄 Botones de descarga de PDF -->
        <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #e9ecef;">
            <h4 style="color: #495057; margin-bottom: 10px;">📄 Descargar Reporte en PDF</h4>
            <button onclick="downloadPDF('crm')" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); color: white; margin: 4px;">
                📄 CRM
            </button>
            <button onclick="downloadPDF('scm')" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; margin: 4px;">
                📄 SCM
            </button>

            <button onclick="downloadPDF('erp')" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; margin: 4px;">
                📄 ERP
            </button>

            <button onclick="downloadPDF('unified')" style="background: linear-gradient(135deg, #6f42c1 0%, #5a2d9c 100%); color: white; margin: 4px;">
                📄 Unificado
            </button>
        </div>





        
    

        <!-- SECCIÓN DE RESULTADOS MEJORADA -->
        <div id="result" class="result">
            <p class="loading">🎯 Haz clic en cualquier botón para comenzar...</p>
        </div>
    </div>

    <!-- TEMPLATES PARA DIFERENTES TIPOS DE RESULTADOS -->
    <template id="template-stats">
        <div class="stats-container">
            <h3>📊 Estadísticas Consolidadas</h3>
            <div class="stats-grid" id="stats-grid">
                <!-- Se llenará dinámicamente -->
            </div>
        </div>
    </template>

    <template id="template-crm">
        <div class="crm-container">
            <h3>👥 Reporte CRM - Gestión de Clientes</h3>
            <div class="data-section" id="crm-stats">
                <!-- Estadísticas CRM -->
            </div>
            <div class="data-section" id="crm-clients">
                <!-- Lista de clientes -->
            </div>
        </div>
    </template>

<script>
    // Función helper para construir URLs API
    function dolibarrUrl(endpoint) {
        return '/api/dolibarr' + endpoint;
    }

    // 🔹 FUNCIÓN MEJORADA: Mostrar resultados visualmente
    function showVisualResult(data, type = '') {
    window.lastReportData = data; // ✅ Guardar datos para descarga de PDF

    const resultDiv = document.getElementById('result');
    
    // Determinar el tipo de datos y mostrar la plantilla correspondiente
    if (type === 'stats' || data.resumen_consolidado) {
        showStatsTemplate(data);
    } else if (data.clientes_totales || data.crm) {
        showCRMTemplate(data);
    } else if (data.inventario || data.scm) {
        showSCMTemplate(data);
    } else if (data.ventas_totales || data.erp) {
        showERPTemplate(data);
    } else {
        // Fallback: mostrar JSON con toggle
        showJSONWithToggle(data);
    }
}

    // 🔹 PLANTILLA PARA ESTADÍSTICAS
    function showStatsTemplate(data) {
        const template = document.getElementById('template-stats').content.cloneNode(true);
        const statsGrid = template.getElementById('stats-grid');
        
        const stats = [
            { icon: '👥', label: 'Total Clientes', value: data.resumen_consolidado?.total_clientes || data.total_clientes || 'N/A' },
            { icon: '📦', label: 'Total Productos', value: data.resumen_consolidado?.total_productos || data.total_productos || 'N/A' },
            { icon: '💰', label: 'Ventas Totales', value: data.resumen_consolidado?.ventas_totales || data.ventas_totales || 'N/A' },
            { icon: '🛒', label: 'Pedidos Activos', value: data.resumen_consolidado?.pedidos_activos || data.pedidos_activos || 'N/A' },
            { icon: '🏢', label: 'Proveedores', value: data.resumen_consolidado?.total_proveedores || data.total_proveedores || 'N/A' },
            { icon: '📊', label: 'Facturas', value: data.resumen_consolidado?.total_facturas || data.total_facturas || 'N/A' }
        ];
        
        stats.forEach(stat => {
            if (stat.value !== 'N/A') {
                const statCard = document.createElement('div');
                statCard.className = 'stat-card';
                statCard.innerHTML = `
                    <div class="stat-icon">${stat.icon}</div>
                    <div class="stat-number">${stat.value}</div>
                    <div class="stat-label">${stat.label}</div>
                `;
                statsGrid.appendChild(statCard);
            }
        });
        
        const resultDiv = document.getElementById('result');
        resultDiv.innerHTML = '';
        resultDiv.appendChild(template);
        
        // Agregar toggle para JSON completo
        addJSONToggle(data);
    }

    // 🔹 PLANTILLA PARA CRM
    function showCRMTemplate(data) {
        const resultDiv = document.getElementById('result');
        const clientes = data.clientes_totales?.data || data.data || [];
        
        resultDiv.innerHTML = `
            <div class="crm-container">
                <h3>👥 Reporte CRM - Gestión de Clientes</h3>
                
                <div class="data-section">
                    <h4>📊 Resumen de Clientes</h4>
                    <div class="data-item">
                        <span>Clientes Totales:</span>
                        <strong>${clientes.length}</strong>
                    </div>
                    <div class="data-item">
                        <span>Mejores Clientes:</span>
                        <strong>${data.mejores_clientes?.data?.length || 0}</strong>
                    </div>
                    <div class="data-item">
                        <span>Clientes Nuevos:</span>
                        <strong>${data.clientes_nuevos?.data?.length || 0}</strong>
                    </div>
                </div>
                
                <div class="data-section">
                    <h4>👤 Lista de Clientes (${clientes.length})</h4>
                    ${renderClientesList(clientes)}
                </div>
            </div>
        `;
        
        addJSONToggle(data);
    }

    // 🔹 PLANTILLA PARA SCM
    function showSCMTemplate(data) {
        const resultDiv = document.getElementById('result');
        const productos = data.inventario?.data || data.data || [];
        
        resultDiv.innerHTML = `
            <div class="scm-container">
                <h3>📦 Reporte SCM - Gestión de Inventario</h3>
                
                <div class="data-section">
                    <h4>📊 Resumen de Inventario</h4>
                    <div class="data-item">
                        <span>Total Productos:</span>
                        <strong>${productos.length}</strong>
                    </div>
                    <div class="data-item">
                        <span>Productos Activos:</span>
                        <strong>${productos.filter(p => p.status === '1').length}</strong>
                    </div>
                    <div class="data-item">
                        <span>Stock Bajo:</span>
                        <strong>${data.stock_bajo?.data?.length || 0}</strong>
                    </div>
                </div>
                
                <div class="data-section">
                    <h4>📋 Lista de Productos (${productos.length})</h4>
                    ${renderProductosList(productos)}
                </div>
            </div>
        `;
        
        addJSONToggle(data);
    }

    // 🔹 PLANTILLA PARA ERP
    // 🔹 PLANTILLA MEJORADA PARA ERP
function showERPTemplate(data) {
    const resultDiv = document.getElementById('result');
    const erpData = data.erp || data;
    
    resultDiv.innerHTML = `
        <div class="erp-container">
            <h3>💰 Reporte ERP - Gestión Financiera</h3>
            
            <div class="data-section">
                <h4>📊 Resumen Financiero</h4>
                <div class="data-item">
                    <span>Ventas Totales:</span>
                    <strong>$${erpData.resumen_financiero?.ventas_totales || '0.00'}</strong>
                </div>
                <div class="data-item">
                    <span>Pedidos Pagados:</span>
                    <strong>${erpData.resumen_financiero?.total_pedidos_pagados || 0}</strong>
                </div>
                <div class="data-item">
                    <span>Descuentos Totales:</span>
                    <strong>$${erpData.resumen_financiero?.descuentos_totales || '0.00'}</strong>
                </div>
                <div class="data-item">
                    <span>Ticket Promedio:</span>
                    <strong>$${erpData.resumen_financiero?.ticket_promedio || '0.00'}</strong>
                </div>
            </div>
            
            <div class="data-section">
                <h4>📋 Pedidos Activos (${erpData.pedidos_activos?.total || 0})</h4>
                ${renderPedidosList(erpData.pedidos_activos?.data || [])}
            </div>
            
            <div class="data-section">
                <h4>🏆 Productos Más Vendidos</h4>
                ${renderProductosVendidos(erpData.productos_mas_vendidos?.data || [])}
            </div>
            
            <div class="data-section">
                <h4>👑 Mejores Clientes</h4>
                ${renderMejoresClientes(erpData.mejores_clientes?.data || [])}
            </div>
        </div>
    `;
    
    addJSONToggle(data);
}

// 🔹 FUNCIÓN AUXILIAR: Renderizar lista de pedidos
function renderPedidosList(pedidos) {
    if (pedidos.length === 0) return '<p style="text-align: center; color: #6c757d;">No hay pedidos activos</p>';
    
    return pedidos.slice(0, 10).map(pedido => `
        <div class="data-item">
            <div>
                <strong>Pedido #${pedido.id}</strong><br>
                <small>Cliente ID: ${pedido.user_id} | Total: $${pedido.total} | Estado: ${pedido.status}</small>
            </div>
            <span class="badge">${new Date(pedido.created_at).toLocaleDateString()}</span>
        </div>
    `).join('');
}

// 🔹 FUNCIÓN AUXILIAR: Renderizar productos más vendidos
function renderProductosVendidos(productos) {
    if (productos.length === 0) return '<p style="text-align: center; color: #6c757d;">No hay datos de ventas</p>';
    
    return productos.map((producto, index) => `
        <div class="data-item">
            <div>
                <strong>${index + 1}. ${producto.name}</strong><br>
                <small>Vendidos: ${producto.total_vendido} unidades | Ingresos: $${producto.ingresos_totales}</small>
            </div>
            <span class="badge">ID: ${producto.id}</span>
        </div>
    `).join('');
}

// 🔹 FUNCIÓN AUXILIAR: Renderizar mejores clientes
function renderMejoresClientes(clientes) {
    if (clientes.length === 0) return '<p style="text-align: center; color: #6c757d;">No hay datos de clientes</p>';
    
    return clientes.map((cliente, index) => `
        <div class="data-item">
            <div>
                <strong>${index + 1}. ${cliente.name}</strong><br>
                <small>${cliente.email} | Pedidos: ${cliente.total_pedidos}</small>
            </div>
            <span class="badge">$${cliente.total_gastado}</span>
        </div>
    `).join('');
}

    // 🔹 FUNCIÓN AUXILIAR: Renderizar lista de clientes
    function renderClientesList(clientes) {
        if (clientes.length === 0) return '<p style="text-align: center; color: #6c757d;">No hay clientes registrados</p>';
        
        return clientes.slice(0, 20).map(cliente => `
            <div class="data-item">
                <div>
                    <strong>${cliente.name || cliente.nom || 'Sin nombre'}</strong><br>
                    <small>${cliente.email || cliente.mail || 'Sin email'} | ID: ${cliente.id}</small>
                </div>
                <span class="badge">${cliente.client === '1' || cliente.client === 1 ? 'Cliente' : 'Prospecto'}</span>
            </div>
        `).join('');
    }

    // 🔹 FUNCIÓN AUXILIAR: Renderizar lista de productos
    // 🔹 FUNCIÓN AUXILIAR MEJORADA: Renderizar lista de productos
    function renderProductosList(productos, showStock = false) {
        if (productos.length === 0) return '<p style="text-align: center; color: #6c757d;">No hay productos registrados</p>';
        
        return productos.slice(0, 50).map(producto => {
            const stockStatus = showStock ? 
                `<span style="color: ${producto.stock < 5 ? '#dc3545' : producto.stock < 10 ? '#ffc107' : '#28a745'}; font-weight: bold;">
                    Stock: ${producto.stock || 0}
                </span>` : '';
            
            return `
                <div class="data-item">
                    <div>
                        <strong>${producto.name || producto.label || 'Sin nombre'}</strong><br>
                        <small>
                            Ref: ${producto.ref || producto.id || 'N/A'} | 
                            Precio: $${producto.price || '0.00'} | 
                            ${producto.brand ? `Marca: ${producto.brand} | ` : ''}
                            Categoría: ${producto.category_id || 'N/A'}
                        </small>
                    </div>
                    <div>
                        ${stockStatus}
                        <span class="badge">${producto.status === '1' || producto.active ? 'Activo' : 'Inactivo'}</span>
                    </div>
                </div>
            `;
        }).join('');
    }

    // 🔹 FUNCIÓN: Mostrar JSON con toggle
    function showJSONWithToggle(data) {
        const resultDiv = document.getElementById('result');
        resultDiv.innerHTML = `
            <div class="json-fallback">
                <h3>📋 Datos en Formato JSON</h3>
                <pre class="json-view">${JSON.stringify(data, null, 2)}</pre>
            </div>
        `;
        addJSONToggle(data);
    }

    // 🔹 FUNCIÓN: Agregar toggle para JSON
    function addJSONToggle(data) {
        const resultDiv = document.getElementById('result');
        const existingToggle = resultDiv.querySelector('.json-toggle');
        const existingJsonView = resultDiv.querySelector('.json-view');
        
        if (!existingToggle) {
            const toggleBtn = document.createElement('button');
            toggleBtn.className = 'json-toggle';
            toggleBtn.textContent = '📋 Ver JSON Completo';
            toggleBtn.onclick = () => toggleJSONView(data);
            
            resultDiv.appendChild(toggleBtn);
        }
        
        if (!existingJsonView) {
            const jsonView = document.createElement('pre');
            jsonView.className = 'json-view';
            jsonView.textContent = JSON.stringify(data, null, 2);
            resultDiv.appendChild(jsonView);
        }
    }

    // 🔹 FUNCIÓN: Toggle vista JSON
    function toggleJSONView(data) {
        const jsonView = document.querySelector('.json-view');
        const toggleBtn = document.querySelector('.json-toggle');
        
        if (jsonView.style.display === 'block') {
            jsonView.style.display = 'none';
            toggleBtn.textContent = '📋 Ver JSON Completo';
        } else {
            jsonView.style.display = 'block';
            toggleBtn.textContent = '👁 Ocultar JSON';
        }
    }

    // 🔹 FUNCIONES DE REPORTES MEJORADAS
        async function getCRMReport() {
    const resultDiv = document.getElementById('result');
    resultDiv.innerHTML = '<p class="loading">⏳ Cargando CRM local...</p>';

    try {
        // 1. USUARIOS
        const resUsers = await fetch('/api/users');
        const users = await resUsers.json(); // ← JSON directo

        // 2. PROVEEDORES
        const resSupp = await fetch('/api/suppliers');
        const suppliers = await resSupp.json();

        // 3. HTML igual que en el PDF
        let html = `
            <div class="crm-container">
                <h3>👥 Reporte CRM - Gestión de Clientes</h3>
                <div class="data-section">
                    <h4>📊 Resumen CRM</h4>
                    <div class="data-item">
                        <span>Clientes (usuarios registrados):</span>
                        <strong>${users.length}</strong>
                    </div>
                    <div class="data-item">
                        <span>Proveedores activos:</span>
                        <strong>${suppliers.length}</strong>
                    </div>
                </div>

                <div class="data-section">
                    <h4>📋 Clientes</h4>
                    <table class="data-table">
                        <thead>
                            <tr><th>ID</th><th>Nombre</th><th>Email</th><th>Dirección</th><th>Teléfono</th></tr>
                        </thead>
                        <tbody>
        `;
        users.slice(0, 50).forEach(u => {
            html += `<tr>
                <td>${u.id}</td>
                <td><strong>${u.name}</strong></td>
                <td>${u.email}</td>
                <td>${u.address || 'Sin dirección'}</td>
                <td>${u.phone || 'Sin teléfono'}</td>
            </tr>`;
        });
        html += `</tbody></table></div>`;

        html += `<div class="data-section"><h4>🏢 Proveedores</h4>
            <table class="data-table">
                <thead><tr><th>Nombre</th><th>Empresa</th><th>Email</th><th>Teléfono</th></tr></thead>
                <tbody>`;
        suppliers.slice(0, 50).forEach(s => {
            html += `<tr>
                <td><strong>${s.name}</strong></td>
                <td>${s.company || 'N/A'}</td>
                <td>${s.email}</td>
                <td>${s.phone || 'N/A'}</td>
            </tr>`;
        });
        html += `</tbody></table></div></div>`;

        // 4. Pintar resultado
        resultDiv.innerHTML = html;

        // 5. Guardar para PDF
        window.lastReportData = {
            clientes_totales: { data: users },
            proveedores: { data: suppliers }
        };

    } catch (err) {
        resultDiv.innerHTML = `<div class="error">❌ Error: ${err.message}</div>`;
    }
}




        async function getSCMReport() {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = '<p class="loading">📦 Generando reporte SCM visual...</p>';

            try {
                // Obtener productos desde la base de datos local
                const response = await fetch('/api/products');
                const products = await response.json();

                if (!response.ok) throw new Error('Error al cargar productos');

                // Procesar datos para el reporte SCM
                const scmData = {
                    inventario: {
                        data: products,
                        total: products.length
                    },
                    stock_bajo: {
                        data: products.filter(p => p.stock < 10),
                        total: products.filter(p => p.stock < 10).length
                    },
                    productos_activos: {
                        data: products.filter(p => p.active === 1 || p.active === true),
                        total: products.filter(p => p.active === 1 || p.active === true).length
                    },
                    valor_inventario: products.reduce((sum, p) => sum + (p.price * p.stock), 0),
                    categorias_totales: [...new Set(products.map(p => p.category_id))].length
                };

                // Mostrar resultado visual
                showVisualResult(scmData, 'scm');
                
                // Guardar datos para PDF
                window.lastReportData = scmData;

            } catch (error) {
                console.error('Error con API local:', error);
                // Fallback a Dolibarr si es necesario
                try {
                    const response = await axios.get('/api/dolibarr/reports/scm');
                    showVisualResult(response.data, 'scm');
                } catch (dolibarrError) {
                    showError(dolibarrError);
                }
            }
        }

    async function getERPReport() {
    showLoading('💰 Generando reporte ERP financiero...');
    try {
        // Primero intenta con la API local
        const response = await fetch('/api/erp-report');
        const data = await response.json();
        
        if (!response.ok) throw new Error(data.message || 'Error en reporte ERP');
        
        showVisualResult(data, 'erp');
        
    } catch (error) {
        console.error('Error con API local, intentando con Dolibarr:', error);
        // Fallback a Dolibarr si es necesario
        try {
            const response = await axios.get('/api/dolibarr/reports/erp');
            showVisualResult(response.data, 'erp');
        } catch (dolibarrError) {
            showError(dolibarrError);
        }
    }
}

    async function getUnifiedReport() {
        showLoading('🎯 Generando reporte unificado visual...');
        try {
            const response = await axios.get('/api/dolibarr/reports/unified');
            showVisualResult(response.data, 'stats');
        } catch (error) {
            showError(error);
        }
    }

    // 🔹 FUNCIÓN: Probar conexión
    async function testConnection() {
        showLoading('🔍 Probando conexión con Dolibarr...');
        try {
            const response = await axios.get(dolibarrUrl('/test'));
            document.getElementById('connectionStatus').innerHTML = '<span class="success">✅ Conectado</span>';
            showVisualResult(response.data, 'success');
            console.log('✅ Conexión exitosa:', response.data);
        } catch (error) {
            document.getElementById('connectionStatus').innerHTML = '<span class="error">❌ Error</span>';
            showError(error);
            console.error('❌ Error de conexión:', error);
        }
    }

    // 🔹 FUNCIÓN: Probar crear cliente simple
    async function testCreateClient() {
        showLoading('🧪 Probando creación de cliente simple...');
        try {
            const response = await axios.post(dolibarrUrl('/test-create-client'));
            showVisualResult(response.data, 'success');
            console.log('✅ Resultado prueba cliente:', response.data);
        } catch (error) {
            showError(error);
            console.error('❌ Error en prueba cliente:', error);
        }
    }

    // 🔹 FUNCIÓN: Obtener estado del sistema
    async function getSystemStatus() {
        showLoading('📊 Obteniendo estado del sistema...');
        try {
            const response = await axios.get(dolibarrUrl('/status'));
            showVisualResult(response.data);
            console.log('✅ Estado del sistema:', response.data);
        } catch (error) {
            showError(error);
            console.error('❌ Error estado sistema:', error);
        }
    }

    // 🔹 FUNCIÓN: Obtener usuarios
    async function getUsers() {
        showLoading('👥 Obteniendo usuarios...');
        try {
            const response = await axios.get(dolibarrUrl('/users'));
            const count = response.data.data ? response.data.data.length : 0;
            document.getElementById('userCount').textContent = count;
            showVisualResult(response.data);
            console.log('✅ Usuarios obtenidos:', count);
        } catch (error) {
            showError(error);
            console.error('❌ Error usuarios:', error);
        }
    }

    // 🔹 FUNCIÓN: Obtener productos
    async function getProducts() {
        showLoading('📦 Obteniendo productos...');
        try {
            const response = await axios.get(dolibarrUrl('/products'));
            const count = response.data.data ? response.data.data.length : 0;
            document.getElementById('productCount').textContent = count;
            showVisualResult(response.data);
            console.log('✅ Productos obtenidos:', count);
        } catch (error) {
            showError(error);
            console.error('❌ Error productos:', error);
        }
    }

    // 🔹 FUNCIÓN: Obtener terceros
    async function getThirdParties() {
        showLoading('🏢 Obteniendo terceros...');
        try {
            const response = await axios.get(dolibarrUrl('/thirdparties'));
            const count = response.data.data ? response.data.data.length : 0;
            document.getElementById('thirdpartyCount').textContent = count;
            showVisualResult(response.data);
            console.log('✅ Terceros obtenidos:', count);
        } catch (error) {
            showError(error);
            console.error('❌ Error terceros:', error);
        }
    }

    // 🔹 FUNCIÓN: Obtener contactos
    async function getContacts() {
        showLoading('📇 Obteniendo contactos...');
        try {
            const response = await axios.get(dolibarrUrl('/contacts'));
            showVisualResult(response.data);
            console.log('✅ Contactos obtenidos');
        } catch (error) {
            showError(error);
            console.error('❌ Error contactos:', error);
        }
    }

    // 🔹 FUNCIÓN: Obtener categorías
    async function getCategories() {
        showLoading('🏷 Obteniendo categorías...');
        try {
            const response = await axios.get(dolibarrUrl('/categories'));
            showVisualResult(response.data);
            console.log('✅ Categorías obtenidas');
        } catch (error) {
            showError(error);
            console.error('❌ Error categorías:', error);
        }
    }

    // 🔹 FUNCIÓN: Obtener proveedores
    async function getSuppliers() {
        showLoading('🏭 Obteniendo proveedores...');
        try {
            const response = await axios.get(dolibarrUrl('/suppliers'));
            showVisualResult(response.data);
            console.log('✅ Proveedores obtenidos');
        } catch (error) {
            showError(error);
            console.error('❌ Error proveedores:', error);
        }
    }

    // 🔹 FUNCIÓN: Obtener pedidos
    async function getOrders() {
        showLoading('📋 Obteniendo pedidos...');
        try {
            const response = await axios.get(dolibarrUrl('/orders'));
            showVisualResult(response.data);
            console.log('✅ Pedidos obtenidos');
        } catch (error) {
            showError(error);
            console.error('❌ Error pedidos:', error);
        }
    }

    // 🔹 FUNCIÓN: Obtener facturas
    async function getInvoices() {
        showLoading('🧾 Obteniendo facturas...');
        try {
            const response = await axios.get(dolibarrUrl('/invoices'));
            showVisualResult(response.data);
            console.log('✅ Facturas obtenidas');
        } catch (error) {
            showError(error);
            console.error('❌ Error facturas:', error);
        }
    }

    // 🔹 FUNCIÓN: Sincronizar productos
    async function syncProducts() {
        showLoading('🔄 Sincronizando productos a Dolibarr...');
        try {
            const response = await axios.post(dolibarrUrl('/sync-products'));
            showVisualResult(response.data, 'success');
            console.log('✅ Productos sincronizados:', response.data);
            
            // Actualizar contador después de sincronizar
            setTimeout(() => getProducts(), 6000);
        } catch (error) {
            showError(error);
            console.error('❌ Error sincronizando productos:', error);
        }
    }

    // 🔹 FUNCIÓN: Crear clientes de ejemplo
    async function createClients() {
        showLoading('👥 Creando clientes de ejemplo...');
        try {
            const response = await axios.post(dolibarrUrl('/create-clients'));
            showVisualResult(response.data, 'success');
            console.log('✅ Clientes creados:', response.data);
            
            // Actualizar contador después de crear clientes
            setTimeout(() => getThirdParties(), 6000);
        } catch (error) {
            showError(error);
            console.error('❌ Error creando clientes:', error);
        }
    }

    // 🔹 PLANTILLA MEJORADA PARA SCM
function showSCMTemplate(data) {
    const resultDiv = document.getElementById('result');
    const productos = data.inventario?.data || data.data || [];
    const stockBajo = data.stock_bajo?.data || [];
    const productosActivos = data.productos_activos?.data || productos.filter(p => p.active);
    
    resultDiv.innerHTML = `
        <div class="scm-container">
            <h3>📦 Reporte SCM - Gestión de Inventario</h3>
            
            <div class="data-section">
                <h4>📊 Resumen de Inventario</h4>
                <div class="data-item">
                    <span>Total Productos:</span>
                    <strong>${productos.length}</strong>
                </div>
                <div class="data-item">
                    <span>Productos Activos:</span>
                    <strong>${productosActivos.length}</strong>
                </div>
                <div class="data-item">
                    <span>Stock Bajo (< 10 unidades):</span>
                    <strong style="color: ${stockBajo.length > 0 ? '#dc3545' : '#28a745'}">${stockBajo.length}</strong>
                </div>
                <div class="data-item">
                    <span>Valor Total Inventario:</span>
                    <strong>$${data.valor_inventario ? data.valor_inventario.toFixed(2) : '0.00'}</strong>
                </div>
                <div class="data-item">
                    <span>Categorías Totales:</span>
                    <strong>${data.categorias_totales || 'N/A'}</strong>
                </div>
            </div>
            
            ${stockBajo.length > 0 ? `
            <div class="data-section" style="border-left-color: #dc3545;">
                <h4>⚠ Productos con Stock Bajo</h4>
                ${renderProductosList(stockBajo, true)}
            </div>
            ` : ''}
            
            <div class="data-section">
                <h4>📋 Inventario Completo (${productos.length})</h4>
                ${renderProductosList(productos)}
            </div>
        </div>
    `;
    
    addJSONToggle(data);
}

    // 🔹 FUNCIÓN: Mostrar loading
    function showLoading(message) {
        document.getElementById('result').innerHTML = '<p class="loading">⏳ ' + message + '</p>';
    }

    // 🔹 FUNCIÓN: Mostrar error
    function showError(error) {
        const resultDiv = document.getElementById('result');
        const errorMessage = error.response && error.response.data && error.response.data.message 
            ? error.response.data.message 
            : error.message 
            ? error.message 
            : 'Error desconocido';
        resultDiv.innerHTML = '<div class="error"><strong>❌ Error:</strong> ' + errorMessage + '</div>';
    }

    // Hacer las funciones globales
    window.testConnection = testConnection;
    window.getSystemStatus = getSystemStatus;
    window.getUsers = getUsers;
    window.getProducts = getProducts;
    window.getThirdParties = getThirdParties;
    window.getContacts = getContacts;
    window.getCategories = getCategories;
    window.getSuppliers = getSuppliers;
    window.getOrders = getOrders;
    window.getInvoices = getInvoices;
    window.syncProducts = syncProducts;
    window.createClients = createClients;
    window.testCreateClient = testCreateClient;
    window.getCRMReport = getCRMReport;
    window.getSCMReport = getSCMReport;
    window.getERPReport = getERPReport;
    window.getUnifiedReport = getUnifiedReport;
    window.showVisualResult = showVisualResult;

    window.downloadPDF = downloadPDF;
    // Reemplazar la función showResult original
    window.showResult = showVisualResult;

    // Cargar estadísticas automáticamente al iniciar
    window.addEventListener('load', function() {
        console.log('Página cargada, iniciando pruebas automáticas...');
        testConnection();
        setTimeout(function() { getUsers(); }, 1000);
        setTimeout(function() { getProducts(); }, 2000);
        setTimeout(function() { getThirdParties(); }, 3000);
    });



            /****LA DE STOCK PARA MOSTRAR,CARGAR, ETC...*/
            // Función para cargar stock de productos
        // async function getStockReport() {
        //     const resultDiv = document.getElementById('stock-result');
        //     resultDiv.innerHTML = '<p class="loading">⏳ Cargando stock...</p>';

        //     try {
        //         const response = await fetch('/api/products/stock');
        //         const data = await response.json();

        //         if (!response.ok) throw new Error('Error al cargar stock');

        //         let html = `
        //             <h3>📦 Stock Actual</h3>
        //             <table class="data-table">
        //                 <thead>
        //                     <tr>
        //                         <th>Producto</th>
        //                         <th>Stock</th>
        //                         <th>Estado</th>
        //                     </tr>
        //                 </thead>
        //                 <tbody>
        //         `;

        //         data.forEach(product => {
        //             const status = product.stock > 0 ? '✅ Disponible' : '❌ Sin stock';
        //             const rowColor = product.stock === 0 ? 'style="background-color: #ffe6e6;"' : '';
        //             html += `
        //                 <tr ${rowColor}>
        //                     <td><strong>${product.name}</strong></td>
        //                     <td>${product.stock}</td>
        //                     <td>${status}</td>
        //                 </tr>
        //             `;
        //         });

        //         html += `
        //                 </tbody>
        //             </table>
        //         `;

        //         resultDiv.innerHTML = html;

        //     } catch (error) {
        //         resultDiv.innerHTML = `<div class="error">❌ Error: ${error.message}</div>`;
        //     }
        // }

        ////****HASTA AQUI LLEGA LA DE STOCK**** */




        /******EMPLEADOS****** */
        // 👥 Función para cargar empleados
        async function getEmployees() {
            const resultDiv = document.getElementById('employees-result');
            resultDiv.innerHTML = '<p class="loading">⏳ Cargando empleados...</p>';

            try {
                const response = await fetch('/api/employees');
                const data = await response.json();

                if (!response.ok) throw new Error('Error al cargar empleados');

                let html = `
                    <h3>👥 Lista de Empleados</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Puesto</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                data.forEach(emp => {
                    const estado = emp.active ? '✅ Activo' : '❌ Inactivo';
                    html += `
                        <tr>
                            <td><strong>${emp.name}</strong></td>
                            <td>${emp.email}</td>
                            <td>${emp.position || 'Sin puesto'}</td>
                            <td>${estado}</td>
                        </tr>
                    `;
                });

                html += `
                        </tbody>
                    </table>
                `;

                resultDiv.innerHTML = html;

            } catch (error) {
                resultDiv.innerHTML = `<div class="error">❌ Error: ${error.message}</div>`;
            }
        }

        /****HASTA AQUI LLEGA EMPLEADOS*** */



    /////////************GENERAR PDF */
    async function downloadPDF(type) {
    const resultDiv = document.getElementById('result');
    const data = window.lastReportData; // Guardamos el último reporte visualizado

    if (!data) {
        alert('⚠️ No hay datos para generar el PDF. Primero genera un reporte.');
        return;
    }

    const payload = {
        reportData: data
    };

    try {
        const response = await fetch(`/api/dolibarr/reports/generate-pdf/${type}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/pdf'
            },
            body: JSON.stringify(payload)
        });

        if (!response.ok) {
            throw new Error('Error al generar el PDF');
        }

        // Descargar el archivo
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `Reporte_MaderaViva_${type.toUpperCase()}_${new Date().toISOString().slice(0, 10)}.pdf`;
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);

    } catch (error) {
        alert('❌ Error al descargar el PDF: ' + error.message);
    }
}


    
</script>
</body>
</html>