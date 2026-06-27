<?php
require_once "../config/config.php";
$page_title = "Números Vendidos";
include_once ROOT_PATH . "/includes/head.php";
?>

<div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-sidebartype="full">
    <?php include_once ROOT_PATH . "/includes/sidebar.php"; ?>

    <div class="body-wrapper">
        <?php include_once ROOT_PATH . "/includes/header.php"; ?>

        <div class="body-wrapper-inner">
            <div class="container-fluid" style="padding: 0.5rem;">

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body py-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small fw-bold" for="searchNumeros">Buscador</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white"><i class="ti ti-search"></i></span>
                                    <input type="search"
                                           id="searchNumeros"
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
                                <select id="filterVendedor" class="form-select form-select-sm">
                                    <option value="">Todos los vendedores</option>
                                </select>
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
                            <div class="col-md-2">
                                <label class="form-label small fw-bold">Rifa</label>
                                <select id="filterRifa" class="form-select form-select-sm">
                                    <option value="">Todas las rifas</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <div class="d-flex gap-1">
                                    <button class="btn btn-outline-secondary btn-sm w-100" onclick="limpiarFiltros()" title="Limpiar">
                                        <i class="ti ti-refresh"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-end p-2 border-bottom bg-light">
                            <button class="btn btn-success btn-sm" onclick="abrirModalImagen()">
                                <i class="ti ti-share"></i> Compartir vendidos
                            </button>
                        </div>
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
                                    <tr><td colspan="8" class="text-center py-5">Cargando datos...</td></tr>
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

<div class="modal fade" id="modalImagenRifa" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title d-flex align-items-center gap-2">
          <i class="ti ti-photo"></i>
          Compartir números vendidos
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="card mb-3">
          <div class="card-body py-3">
            <div class="row g-3 align-items-end">
              <div class="col-md-6">
                <label class="form-label fw-bold">Rifa</label>
                <select id="imagenRifaSelect" class="form-select form-select-sm"></select>
              </div>
            </div>
          </div>
        </div>
        <div class="card">
          <div class="card-body text-center">
            <div id="previewImagenRifa" class="ratio ratio-1x1">
              <div class="d-flex justify-content-center align-items-center text-muted">
                Selecciona una rifa para generar la imagen
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer bg-light">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button class="btn btn-success" onclick="compartirImagenWhatsApp()">
          <i class="ti ti-share"></i> Compartir
        </button>
      </div>
    </div>
  </div>
</div>

<div id="canvasImagenRifa"
     style="position: fixed; left: -9999px; top: 0; width: 1080px; padding: 30px; background: #ffffff; font-family: Arial, sans-serif;">
</div>

<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>

<?php
$extra_js = '<script src="' . ASSETS_URL . '/js/numeros-vendidos.js?v=23"></script>';
include_once ROOT_PATH . "/includes/footer.php";
?>
