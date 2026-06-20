<?php
require_once "../config/config.php";
$page_title = "Configuración del Sistema";
include_once ROOT_PATH . "/includes/head.php";
?>

<div class="page-wrapper" id="main-wrapper" data-layout="vertical">
<?php include_once ROOT_PATH . "/includes/sidebar.php"; ?>
<div class="body-wrapper">
<?php include_once ROOT_PATH . "/includes/header.php"; ?>

<div class="container-fluid pt-3">

    <div class="d-flex align-items-center mb-4">
        <h2 class="fw-bold mb-0">
            <i class="ti ti-settings me-1"></i> Mi Negocio
        </h2>
    </div>

    <!-- DESKTOP TABLE -->
    <div class="card border-0 shadow-sm d-none d-md-block">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0 table-admin">
                    <thead class="table-light">
                        <tr>
                            <th class="col-titulo">Configuración</th>
                            <th class="col-text">Valor</th>
                            <th class="text-end col-acciones">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="bodySettings">
                        <tr>
                            <td colspan="3" class="text-center py-5 text-muted">
                                Cargando configuraciones...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MOBILE CARDS -->
    <div class="d-md-none mt-2" id="settingsCards"></div>

</div>
</div>
</div>

<!-- MODAL EDITAR -->
<div class="modal fade" id="modalSetting" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title fw-bold">Editar configuración</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <input type="hidden" id="id_setting">

        <!-- INPUT DINÁMICO -->
        <div id="inputContainer"></div>

        <!-- PREVIEW IMAGEN -->
        <div id="imagePreview" class="mt-3 d-none">
            <label class="small fw-bold mb-1">Vista previa</label>
            <img id="previewImg" class="img-fluid rounded">
        </div>

      </div>

      <div class="modal-footer">
        <button class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" onclick="guardarSetting()">
            Guardar cambios
        </button>
      </div>

    </div>
  </div>
</div>

<?php
$extra_js = '<script src="' . ASSETS_URL . '/js/settings.js"></script>';
include_once ROOT_PATH . "/includes/footer.php";
?>
