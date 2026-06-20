<?php
require_once "../config/config.php";

use App\Core\Auth;

$page_title = "Gestión de Reservas";
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
                                <label class="form-label small fw-bold">Buscador</label>
                                <input type="text" id="searchReservas" class="form-control form-control-sm" placeholder="Nombre, Teléfono, Token...">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Rango Fechas (Desde - Hasta)</label>
                                <div class="input-group input-group-sm">
                                    <input type="date" id="fecha_inicio_res" class="form-control">
                                    <input type="date" id="fecha_fin_res" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Periodo Rápido</label>
                                <select id="filterPeriodoRes" class="form-select form-select-sm">
                                    <option value="">Seleccionar...</option>
                                    <option value="today">Hoy</option>
                                    <option value="yesterday">Ayer</option>
                                    <option value="week">Esta Semana</option>
                                    <option value="month">Este Mes</option>
                                    <option value="year">Este Año</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Estado</label>
                                <select id="filterEstadoRes" class="form-select form-select-sm">
                                    <option value="pendientes" selected>Pendientes</option>
                                    <option value="completadas">Completadas</option>
                                    <option value="todas">Todas (sin canceladas)</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Rifa</label>
                                <select id="filterRifaRes" class="form-select form-select-sm">
                                    <option value="">Todas las rifas</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <div class="d-flex gap-1">
                                    <button class="btn btn-outline-secondary btn-sm w-100" onclick="limpiarFiltrosReservas()" title="Limpiar">
                                        <i class="ti ti-refresh"></i>
                                    </button>
                                </div>
                            </div>

                            <?php if (Auth::isAdmin()): ?>
                            <div class="col-md-2">
                                <div class="d-flex gap-1">
                                    <button
                                        class="btn btn-danger btn-sm"
                                        onclick="liberarReservasMasivo()"
                                        title="Liberar todas las reservas activas">
                                        <i class="ti ti-trash"></i> Liberar reservas
                                    </button>
                                </div>
                            </div>
                            <?php endif; ?>
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
                                        <th class="col-metodo">Estado</th>
                                        <th class="col-fecha">Fecha</th>
                                        <th class="col-acciones text-end">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="bodyTablaReservas">
                                    <tr><td colspan="8" class="text-center py-5">Cargando datos...</td></tr>
                                </tbody>
                            </table>
                        </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted" id="infoPaginacionRes"></small>
                            <nav><ul class="pagination pagination-sm mb-0" id="contenedorPaginacionRes"></ul></nav>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

    <!-- Modal Recibo / Confirmación -->
    <div class="modal fade"
        id="modalReciboRes"
        tabindex="-1"
        data-bs-backdrop="static"
        data-bs-keyboard="false"
        aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Comprobante de Reserva</h5>

                    <!-- ✅ CIERRE INTENCIONAL -->
                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Cerrar">
                    </button>
                </div>

                <div class="modal-body" id="cuerpoReciboRes"></div>

            </div>
        </div>
    </div>


<?php
$extra_js = '<script src="' . ASSETS_URL . '/js/reservas.js?v=23"></script>';
include_once ROOT_PATH . "/includes/footer.php";
?>
