<?php
require_once "../config/config.php";
$page_title = "Gestión de Ventas";
include_once ROOT_PATH . "/includes/head.php";
?>

<div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-sidebartype="full">
    <?php include_once ROOT_PATH . "/includes/sidebar.php" ?>

    <div class="body-wrapper">
        <?php include_once ROOT_PATH . "/includes/header.php" ?>

        <div class="body-wrapper-inner">
            <div class="container-fluid" style="padding: 0.5rem;">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body py-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold" for="searchVentas">Buscador</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white"><i class="ti ti-search"></i></span>
                                    <input type="search"
                                           id="searchVentas"
                                           class="form-control form-control-sm"
                                           placeholder="Nombre, celular, código o número exacto (ej. 00, 33)"
                                           autocomplete="off"
                                           enterkeyhint="search">
                                </div>
                            </div>                           
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Rango Fechas (Desde - Hasta)</label>
                                <div class="input-group input-group-sm">
                                    <input type="date" id="fecha_inicio" class="form-control">
                                    <input type="date" id="fecha_fin" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Vendedor</label>
                                <div class="input-group input-group-sm">
                                    <select id="filterVendedor" class="form-select form-select-sm">
                                        <option value="">Todos los vendedores</option>
                                    </select> 
                                </div>
                            </div>                            
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Periodo Rápido</label>
                                <select id="filterPeriodo" class="form-select form-select-sm">
                                    <option value="">Seleccionar...</option>
                                    <option value="today">Hoy</option>
                                    <option value="yesterday">Ayer</option>
                                    <option value="week">Esta Semana</option>
                                    <option value="month">Este Mes</option>
                                    <option value="year">Este Año</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Método de Pago</label>
                                <select id="filterMetodoPago" class="form-select form-select-sm">
                                    <option value="">Todos los pagos</option>
                                    <option value="Manual">Manual</option>
                                    <option value="Página Web">Página Web</option>
                                </select>
                            </div>                            
                            <div class="col-md-2 d-none">
                                <label class="form-label small fw-bold">Rifa</label>
                                <select id="filterRifa" class="form-select form-select-sm">
                                    <option value="">Todas las rifas</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <div class="d-flex gap-1">
                                    <button class="btn btn-outline-secondary btn-sm w-100" onclick="limpiarFiltrosVentas()" title="Limpiar">
                                        <i class="ti ti-refresh"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="admin-table-wrap">
                        <div class="table-responsive admin-table-scroll">
                            <table class="table table-hover table-striped align-middle mb-0 table-admin table-mobile-cards">
                                <thead class="table-light sticky-top" style="z-index: 10;">
                                    <tr>
                                        <th class="col-cliente">Cliente</th>
                                        <th class="col-codigo">Código</th>
                                        <th class="col-rifa">Nums/Rifa</th>
                                        <th class="col-total">Total</th>
                                        <th class="col-metodo">Método</th>
                                        <th class="col-fecha">Fecha</th>
                                        <th class="col-acciones text-end">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="bodyTabla">
                                    <tr><td colspan="7" class="text-center py-5">Cargando datos...</td></tr>
                                </tbody>
                            </table>
                        </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted" id="infoPaginacion"></small>
                            <nav><ul class="pagination pagination-sm mb-0" id="contenedorPaginacion"></ul></nav>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRecibo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Comprobante de Venta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="cuerpoRecibo"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalGestionVenta" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <div>
                    <h5 class="modal-title mb-0">Gestionar venta</h5>
                    <small class="text-muted">Modifica el cliente o libera números sin anular toda la venta</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="gestionFeedback" class="alert d-none mb-3" role="alert"></div>

                <div id="gestionResumen" class="card border-0 bg-light mb-3">
                    <div class="card-body py-2 small"></div>
                </div>

                <div class="card border mb-4">
                    <div class="card-header py-2 bg-white">
                        <h6 class="mb-0 fw-bold"><i class="ti ti-user"></i> Cliente actual</h6>
                    </div>
                    <div class="card-body py-2">
                        <div id="gestionClienteActual" class="small"></div>
                    </div>
                </div>

                <div class="card border mb-4" data-admin-only>
                    <div class="card-header py-2 bg-white">
                        <h6 class="mb-0 fw-bold"><i class="ti ti-user-edit"></i> Cambiar cliente</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            Ingresa el celular del nuevo cliente. Si ya existe, se cargará su nombre automáticamente; si no, escribe el nombre para crearlo.
                        </p>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold" for="gestionTelefono">Celular del nuevo cliente</label>
                                <input type="text" id="gestionTelefono" class="form-control form-control-sm" maxlength="10" inputmode="numeric" placeholder="10 dígitos" autocomplete="off">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold" for="gestionNombre">Nombre del cliente</label>
                                <input type="text" id="gestionNombre" class="form-control form-control-sm" placeholder="Nombre completo" autocomplete="off">
                            </div>
                        </div>
                        <div id="gestionClienteEstado" class="small mt-2 d-none"></div>
                        <div id="gestionVistaPrevia" class="alert alert-info border-0 py-2 px-3 small mt-3 d-none"></div>
                        <button type="button" id="btnGuardarCliente" class="btn btn-primary btn-sm mt-2" onclick="confirmarCambioCliente()" disabled>
                            <i class="ti ti-switch-horizontal"></i> Cambiar cliente
                        </button>
                    </div>
                </div>

                <div class="card border mb-2" data-admin-only>
                    <div class="card-header py-2 bg-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold"><i class="ti ti-ticket"></i> Liberar números</h6>
                        <small class="text-muted">Parcial</small>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-2">
                            <i class="ti ti-click"></i> Toca el <strong>cuadro ☑</strong> de cada número que quieres liberar.
                        </p>
                        <div id="gestionNumeros" class="ticket-release-grid mb-3"></div>
                        <button type="button" id="btnLiberarNumeros" class="btn btn-warning btn-sm" onclick="liberarNumerosSeleccionados()" disabled>
                            <i class="ti ti-lock-open"></i> Liberar seleccionados
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between bg-light">
                <button type="button" class="btn btn-outline-danger btn-sm" data-admin-only onclick="confirmarAnularVentaCompleta()">
                    <i class="ti ti-trash"></i> Anular venta completa
                </button>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php
$extra_js = '<script src="' . ASSETS_URL . '/js/ventas.js?v=17"></script>';
include_once ROOT_PATH . "/includes/footer.php";
?>