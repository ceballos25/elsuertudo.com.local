<?php
require_once "../config/config.php";
$page_title = "Dashboard Principal";
$extra_css = '<link rel="stylesheet" href="' . ASSETS_URL . '/css/dashboard.css?v=1">';
include_once ROOT_PATH . "/includes/head.php";
?>

<div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-sidebartype="full">
    <?php include_once ROOT_PATH . "/includes/sidebar.php"; ?>

    <div class="body-wrapper">
        <?php include_once ROOT_PATH . "/includes/header.php"; ?>

        <div class="body-wrapper-inner">
            <div class="container-fluid dash-page">

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-3">
                        <div class="d-flex flex-column flex-xl-row justify-content-between align-items-center gap-3">
                            <div class="w-100 w-xl-auto text-center text-xl-start">
                                <h3 class="mb-1 fw-bold text-dark text-nowrap"><i class="ti ti-chart-pie-filled text-primary me-2"></i>Dashboard</h3>
                                <p class="dash-filter-resumen mb-0" id="dashFilterResumen">Cargando filtros...</p>
                            </div>
                            
                            <div class="row g-2 w-100 w-xl-auto align-items-center justify-content-end">
                                <div class="col-12 col-md-auto">
                                    <select id="filterRifa" class="form-select form-select-sm fw-bold border-secondary-subtle text-dark dash-filter-rifa"></select>
                                </div>
                                <div class="col-6 col-md-auto">
                                    <select id="filterPeriodo" class="form-select form-select-sm bg-light text-dark fw-medium dash-filter-periodo">
                                        <option value="mes" selected>📅 Este Mes</option>
                                        <option value="semana">📅 Esta Semana</option>
                                        <option value="hoy">📅 Hoy</option>
                                        <option value="ayer">📅 Ayer</option>
                                        <option value="ano">📅 Este Año</option>
                                        <option value="">⚙️ Rango</option>
                                    </select>
                                </div>
                                <div class="col-6 col-md-auto d-flex gap-2">
                                    <button class="btn btn-dark btn-sm flex-grow-1 px-3" onclick="cargarDashboard()" title="Filtrar">
                                        <i class="ti ti-filter d-md-none me-1"></i> <span class="d-none d-md-inline">Filtrar</span>
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm px-3" onclick="limpiarFiltrosDashboard()" title="Limpiar">
                                        <i class="ti ti-refresh"></i>
                                    </button>
                                </div>
                                <div class="col-12 col-md-auto">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white text-muted border-end-0"><i class="ti ti-calendar"></i></span>
                                        <input type="date" id="filterDesde" class="form-control" title="Desde">
                                        <span class="input-group-text bg-light border-start-0 border-end-0 text-muted px-1">-</span>
                                        <input type="date" id="filterHasta" class="form-control" title="Hasta">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="dashContent">

                <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-5 mb-3 g-3">
                    <div class="col">
                        <div class="card dash-kpi-card dash-kpi-card--ventas">
                            <div class="card-body">
                                <div class="dash-kpi-top">
                                    <span class="dash-kpi-label">Ventas Totales</span>
                                    <div class="dash-kpi-icon"><i class="ti ti-currency-dollar fs-5"></i></div>
                                </div>
                                <div class="dash-kpi-value" id="kpiVentas">$0</div>
                                <div class="dash-kpi-meta" id="kpiVentasMeta">0 transacciones</div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card dash-kpi-card dash-kpi-card--vendidos">
                            <div class="card-body">
                                <div class="dash-kpi-top">
                                    <span class="dash-kpi-label">Números Vendidos</span>
                                    <div class="dash-kpi-icon"><i class="ti ti-check fs-5"></i></div>
                                </div>
                                <div class="dash-kpi-value" id="kpiVendidos">0</div>
                                <div class="dash-kpi-meta" id="kpiVendidosMeta">0% del total</div>
                                <div class="dash-kpi-bar"><div class="dash-kpi-bar-fill" id="kpiVendidosBar"></div></div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card dash-kpi-card dash-kpi-card--reservados">
                            <div class="card-body">
                                <div class="dash-kpi-top">
                                    <span class="dash-kpi-label">Números Reservados</span>
                                    <div class="dash-kpi-icon"><i class="ti ti-lock fs-5"></i></div>
                                </div>
                                <div class="dash-kpi-value" id="kpiReservados">0</div>
                                <div class="dash-kpi-meta" id="kpiReservadosMeta">0% del total</div>
                                <div class="dash-kpi-bar"><div class="dash-kpi-bar-fill" id="kpiReservadosBar"></div></div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card dash-kpi-card dash-kpi-card--disponibles">
                            <div class="card-body">
                                <div class="dash-kpi-top">
                                    <span class="dash-kpi-label">Disponibles</span>
                                    <div class="dash-kpi-icon"><i class="ti ti-box fs-5"></i></div>
                                </div>
                                <div class="dash-kpi-value" id="kpiDisponibles">0</div>
                                <div class="dash-kpi-meta" id="kpiDisponiblesMeta">0% del total</div>
                                <div class="dash-kpi-bar"><div class="dash-kpi-bar-fill" id="kpiDisponiblesBar"></div></div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card dash-kpi-card dash-kpi-card--clientes">
                            <div class="card-body">
                                <div class="dash-kpi-top">
                                    <span class="dash-kpi-label">Clientes</span>
                                    <div class="dash-kpi-icon"><i class="ti ti-users fs-5"></i></div>
                                </div>
                                <div class="dash-kpi-value" id="kpiClientes">0</div>
                                <div class="dash-kpi-meta">Registrados en el sistema</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4 dash-distribucion">
                    <div class="card-body py-3 px-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <span class="text-muted fw-bold text-uppercase small">Distribución de números</span>
                            <span class="text-muted small" id="kpiTotalNumeros">0 números en total</span>
                        </div>
                        <div class="dash-stack-bar mb-2">
                            <div class="dash-stack-seg dash-stack-seg--vendidos" id="stackVendidos" title="Vendidos"></div>
                            <div class="dash-stack-seg dash-stack-seg--reservados" id="stackReservados" title="Reservados"></div>
                            <div class="dash-stack-seg dash-stack-seg--disponibles" id="stackDisponibles" title="Disponibles"></div>
                        </div>
                        <div class="d-flex flex-wrap gap-3 small text-muted">
                            <span><span class="dash-legend-dot dash-legend-dot--vendidos"></span>Vendidos <strong id="legendVendidos">0</strong></span>
                            <span><span class="dash-legend-dot dash-legend-dot--reservados"></span>Reservados <strong id="legendReservados">0</strong></span>
                            <span><span class="dash-legend-dot dash-legend-dot--disponibles"></span>Disponibles <strong id="legendDisponibles">0</strong></span>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 border-0">
                        <h5 class="dash-section-title mb-0"><i class="ti ti-chart-area-line"></i> Comportamiento de Ventas</h5>
                    </div>
                    <div class="card-body pt-0">
                        <div id="chartTendencia" class="dash-chart-tendencia"></div>
                    </div>
                </div>

                <div class="row mb-4 g-3">
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white py-3 border-0 text-center">
                                <h6 class="fw-bold mb-0 text-uppercase text-muted small">CANTIDAD DE VENTAS</h6>
                            </div>
                            <div class="card-body d-flex justify-content-center align-items-center">
                                <div id="chartMediosTransacciones" class="dash-chart-wrap"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white py-3 border-0 text-center">
                                <h6 class="fw-bold mb-0 text-uppercase text-muted small">CANTIDAD DE NÚMEROS</h6>
                            </div>
                            <div class="card-body d-flex justify-content-center align-items-center">
                                <div id="chartMediosTickets" class="dash-chart-wrap"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white py-3 border-0 text-center">
                                <h6 class="fw-bold mb-0 text-uppercase text-muted small">DINERO RECAUDADO ($)</h6>
                            </div>
                            <div class="card-body d-flex justify-content-center align-items-center">
                                <div id="chartMediosDinero" class="dash-chart-wrap"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4 g-3">
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white py-3 border-0">
                                <h5 class="dash-section-title mb-0"><i class="ti ti-crown"></i> Top 5 Clientes VIP</h5>
                            </div>
                            <div class="card-body pt-0">
                                <div id="chartTopClientes" class="dash-chart-md"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white py-3 border-0">
                                <h5 class="dash-section-title mb-0"><i class="ti ti-package"></i> Preferencia de Compra</h5>
                            </div>
                            <div class="card-body pt-0">
                                <div id="chartPaquetes" class="dash-chart-md"></div>
                            </div>
                        </div>
                    </div>
                </div>

                
                
                <div class="row mb-4 g-3">
                    <div class="col-lg-12">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white py-3 border-0">
                                <h5 class="dash-section-title mb-0"><i class="ti ti-flame"></i> Intensidad de Ventas (Día vs Hora)</h5>
                            </div>
                            <div class="card-body pt-0">
                                <div id="chartHeatmap" class="dash-chart-md"></div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="dash-section-title mb-0"><i class="ti ti-receipt"></i> Últimas Transacciones</h5>
                    </div>
                    <div class="admin-table-wrap">
                    <div class="table-responsive admin-table-scroll">
                        <table class="table table-hover align-middle mb-0 table-admin table-mobile-cards">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 col-codigo">Código</th>
                                    <th class="col-cliente">Cliente</th>
                                    <th class="col-rifa">Rifa</th>
                                    <th class="col-total">Total</th>
                                    <th class="text-end pe-4 col-fecha">Fecha</th>
                                </tr>
                            </thead>
                            <tbody id="tablaUltimasVentas">
                                <tr><td colspan="5" class="text-center py-4 text-muted">Cargando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    </div>
                </div>

                </div>

            </div>
        </div>
    </div>
</div>

<?php
$extra_js = '<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>'
    . '<script src="' . ASSETS_URL . '/js/dashboard.js?v=7"></script>';
include_once ROOT_PATH . "/includes/footer.php";
?>