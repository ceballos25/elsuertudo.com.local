<?php
require_once "../config/config.php";
$page_title = "Gestión de Números";
include_once ROOT_PATH . "/includes/head.php";
?>

<div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-sidebartype="full">
    <?php include_once ROOT_PATH . "/includes/sidebar.php"; ?>

    <div class="body-wrapper">
        <?php include_once ROOT_PATH . "/includes/header.php"; ?>

        <div class="body-wrapper-inner">
            <div class="container-fluid" style="padding-top: 20px;">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0 fw-bold"><i class="ti ti-list-numbers me-1"></i>Gestión de Números</h2>
                </div>

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body py-3">
                        <div class="row g-2 align-items-end">
                            
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Rifa</label>
                                <select id="filterRifa" class="form-select form-select-sm">
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Estado</label>
                                <select id="filterEstado" class="form-select form-select-sm">
                                    <option value="">Todos</option>
                                    <option value="0">Disponibles</option>
                                    <option value="1">Vendidos</option>
                                    <option value="2">Reservados</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Buscar Número</label>
                                <input type="text" id="searchNumeros" class="form-control form-control-sm" placeholder="Ej: 15...">
                            </div>

                            <div class="col-md-2">
                                <button class="btn btn-outline-secondary btn-sm w-100" onclick="limpiarFiltrosNumeros()">
                                    <i class="ti ti-refresh"></i> Recargar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm m-2">
                    <div class="card-body m-0">
                        <button class="btn btn-success btn-sm"
                                onclick="abrirModalImagenReservados()">
                          Compartir disponibles
                        </button>
                        <div class="admin-table-wrap">
                        <div class="table-responsive admin-table-scroll">
                            <table class="table table-hover table-striped align-middle mb-0 table-admin table-mobile-cards">
                                <thead class="table-light sticky-top" style="z-index: 10;">
                                    <tr>
                                        <th class="ps-5 text-start">Número</th>
                                        <th class="text-center">Estado Actual</th>
                                        <th class="text-end pe-5">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="bodyTablaNumeros">
                                    <tr><td colspan="3" class="text-center py-5 text-muted">Cargando...</td></tr>
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

<div class="modal fade" id="modalImagenReservados" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header bg-light">
        <h5 class="modal-title d-flex align-items-center gap-2">
          <i class="ti ti-photo"></i>
          Compartir números Disponibles
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <div class="card mb-3">
          <div class="card-body py-3">
            <label class="form-label fw-bold">Rifa</label>
            <select id="imagenRifaSelectNums" class="form-select form-select-sm">
              <option value="">Selecciona una rifa</option>
            </select>
          </div>
        </div>

        <div class="card">
          <div class="card-body text-center">
            <div id="previewImagenReservados" class="ratio ratio-1x1">
              <div class="d-flex justify-content-center align-items-center text-muted">
                Selecciona una rifa
              </div>
            </div>
          </div>
        </div>

      </div>

      <div class="modal-footer bg-light">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">
          Cerrar
        </button>
        <button class="btn btn-success" onclick="compartirImagenReservados()">
          <i class="ti ti-share"></i> Compartir
        </button>
      </div>

    </div>
  </div>
</div>

<!-- CONTENEDOR OCULTO -->
<div id="canvasImagenReservados"
     style="position:fixed;left:-9999px;top:0;width:1080px;
            padding:30px;background:#ffffff;font-family:Arial,sans-serif;">
</div>

<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>

<?php
$extra_js = '<script src="' . ASSETS_URL . '/js/numeros.js?v=6"></script>';
include_once ROOT_PATH . "/includes/footer.php";
?>