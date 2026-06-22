<?php
require_once "../config/config.php";
$page_title = "Nueva Venta";
$extra_css = '<link rel="stylesheet" href="' . ASSETS_URL . '/css/vender.css?v=1">';
include_once ROOT_PATH . "/includes/head.php";
?>

<div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full" data-sidebar-position="fixed" data-header-position="fixed">
    <?php include_once ROOT_PATH . "/includes/sidebar.php" ?>
    
    <div class="body-wrapper bg-light min-vh-100">
        <?php include_once ROOT_PATH . "/includes/header.php" ?>
        
        <div class="body-wrapper-inner">
            <div class="container-xxl p-2 p-lg-4 pb-5 mb-5"> 

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0 fw-bold text-dark">Registrar Venta</h4>
                    <button class="btn btn-light border shadow-sm px-3 text-danger fw-bold rounded-pill" onclick="location.reload()">
                        <i class="ti ti-refresh"></i>
                    </button>
                </div>

                <div class="row g-3">
                    
                    <div class="col-lg-8">
                        
                        <div class="card border-0 shadow-sm rounded-4 mb-3">
                            <div class="card-header bg-white border-bottom py-3">
                                <h6 class="mb-0 fw-bold text-primary"><span class="badge bg-primary rounded-pill me-2">1</span>Cliente</h6>
                            </div>
                            <div class="card-body p-3 p-lg-4">
                                
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-muted">BUSCAR (Opcional)</label>
                                    <select id="buscadorCliente" class="form-control w-100"></select>
                                </div>

                                <div class="bg-white p-1 rounded-3">
                                    <form id="formClienteVenta">
                                        <input type="hidden" id="idCliente" name="id_customer">

                                        <div class="row g-3">

                                            <!-- CELULAR -->
                                            <div class="col-12 col-md-6">
                                                <label class="small fw-bold text-dark mb-1">
                                                    Celular <span class="text-danger">*</span>
                                                </label>
                                                <input
                                                    type="tel"
                                                    class="form-control shadow-sm"
                                                    id="celularCliente"                                                    
                                                    required
                                                >
                                            </div>

                                            <!-- NOMBRE -->
                                            <div class="col-12 col-md-6">
                                                <label class="small fw-bold text-dark mb-1">
                                                    Nombre <span class="text-danger">*</span>
                                                </label>
                                                <input
                                                    type="text"
                                                    class="form-control shadow-sm text-capitalize"
                                                    id="nombreCliente"                                                    
                                                    required
                                                >
                                            </div>

                                        </div>

                                        <div class="d-flex justify-content-end mt-3">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-secondary rounded-pill fw-bold"
                                                id="btnLimpiarCliente"
                                                onclick="resetClienteForm()"
                                            >
                                                <i class="ti ti-eraser me-1"></i> Limpiar
                                            </button>
                                        </div>
                                    </form>

                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold text-primary"><span class="badge bg-primary rounded-pill me-2">2</span>Números</h6>                                
                            </div>
                            
                            <div class="card-body p-3 p-lg-4">
                                
                                <div class="mb-3">
                                    <select class="form-select form-select fw-bold shadow-sm text-dark border-secondary" id="selectRifa">
                                        <option value="">Seleccionar Sorteo...</option>
                                    </select>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-8 col-lg-9">
                                        <div class="input-group input-group-lg shadow-sm">                                    
                                            <span class="input-group-text bg-white border-end-0 text-muted ps-3">
                                                <i class="ti ti-search"></i>
                                            </span>
                                            <input type="tel" class="form-control border-start-0 text-dark" id="buscarNumeroInput" placeholder="Buscar #...">
                                        </div>
                                    </div>
                                    <div class="col-4 col-lg-3">
                                        <button class="btn btn-outline-dark btn-lg w-100 shadow-sm fw-bold" onclick="seleccionarAlAzar()">
                                            <i class="ti ti-arrows-split"></i> <span class="d-none d-sm-inline">Azar</span>
                                        </button>
                                    </div>
                                </div>

                                <div class="bg-light rounded-3 p-2 border">
                                    <div id="gridNumeros" class="d-flex flex-wrap gap-2 justify-content-center align-content-start" 
                                        style="min-height: 200px; max-height: 50vh; overflow-y: auto;">
                                        
                                        <div class="text-center text-muted opacity-50 py-5 w-100">
                                            <i class="ti ti-ticket fs-1 mb-2 d-block"></i>
                                            Selecciona un sorteo
                                        </div>

                                    </div>
                                </div>


                            <ul id="paginacionContainer" class="pagination pagination-sm justify-content-center mt-3">
                            </ul>

                            </div>
                        </div>
                    </div>

 <div class="col-lg-4 d-none d-lg-block">
    <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 90px;">
        <div class="card-header text-white py-3 rounded-top-4">
            <h6 class="mb-0 fw-bold">
                <i class="ti ti-receipt-2 me-2"></i>Resumen
            </h6>
        </div>

        <div class="card-body p-0 bg-white">
            <ul class="list-group list-group-flush" id="listaCarritoDesktop" style="max-height: 300px; overflow-y: auto;">
                <li class="list-group-item text-center text-muted py-5 border-0">
                    <small>Sin selección</small>
                </li>
            </ul>
        </div>

        <div class="card-footer bg-light p-4 border-top">
            <div class="d-flex justify-content-between align-items-end mb-3">
                <span class="h6 mb-0 text-muted">
                    Total a Pagar
                    <small class="ms-1 text-muted">
                        (<span id="lblCantidadDesktop">0</span> nums)
                    </small>
                </span>
                <span class="h2 mb-0 fw-bolder text-primary" id="lblTotalDesktop">$0</span>
            </div>
        </div>

        <button
            class="btn btn-success w-100 py-2 fw-bold rounded-3 shadow"
            onclick="procesarVenta()"
        >
            CONFIRMAR VENTA
        </button>
    </div>
</div>

</div> 

<div class="d-lg-none vender-mobile-spacer"></div>

</div>
</div>
</div>
</div>

<!-- ===== MOBILE FIXED BAR ===== -->
<div class="fixed-bottom vender-mobile-bar d-lg-none">
    <div
        class="vender-mobile-bar__info"
        data-bs-toggle="modal"
        data-bs-target="#modalResumenMobile"
        role="button"
        aria-label="Ver resumen de números"
    >
        <span class="vender-mobile-bar__total" id="lblTotalMobile">$0</span>
        <span class="vender-mobile-bar__meta">
            <span id="lblCantidadMobile">0</span> nums · tocar para ver <i class="ti ti-chevron-up"></i>
        </span>
    </div>

    <button
        type="button"
        class="btn btn-success vender-mobile-bar__btn shadow-sm"
        onclick="procesarVentaMobile()"
    >
        Confirmar <i class="ti ti-check ms-1"></i>
    </button>
</div>

<!-- ===== MODAL RESUMEN MOBILE ===== -->
<div class="modal fade" id="modalResumenMobile" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-primary">Tu Selección</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <ul class="list-group list-group-flush" id="listaCarritoMobile">
                    <li class="list-group-item text-center text-muted py-4">
                        <small>Cargando lista...</small>
                    </li>
                </ul>
            </div>

            <div class="modal-footer border-top-0 pt-0">
                <button
                    type="button"
                    class="btn btn-light w-100 fw-bold rounded-pill text-primary"
                    data-bs-dismiss="modal"
                >
                    Seguir comprando
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ================= MODAL NÚMERO NO DISPONIBLE ================= -->
<div class="modal fade" id="criticalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-body text-center p-4">

                <div class="mb-3">
                    <span class="display-4 pulse-hand">🖐️</span>
                </div>

                <h5 class="fw-bold mb-2 text-dark">
                    Número no disponible
                </h5>

                <p class="text-muted mb-4" id="criticalModalMsg"></p>

                <button
                    type="button"
                    class="btn btn-success w-100 fw-bold rounded-pill"
                    data-bs-dismiss="modal"
                >
                    Elegir otro número 🍀
                </button>

            </div>
        </div>
    </div>
</div>




<?php
$extra_js = '
<link href="' . ASSETS_URL . '/libs/select2/css/select2.min.css" rel="stylesheet" />
<link href="' . ASSETS_URL . '/libs/select2/css/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="' . ASSETS_URL . '/libs/select2/js/select2.min.js"></script>
<script src="' . ASSETS_URL . '/js/departamentos-ciudades.js"></script>
<script src="' . ASSETS_URL . '/js/vender.js?v=6"></script> 
';
include_once ROOT_PATH . "/includes/footer.php";
?>