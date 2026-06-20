<?php
require_once "../config/config.php";

use App\Core\Auth;

if (!Auth::isAdmin()) {
    header('Location: ' . BASE_URL . '/front/dashboard.php');
    exit;
}

$page_title = "Usuarios del Sistema";
include_once ROOT_PATH . "/includes/head.php";
?>

<div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-sidebartype="full">
    <?php include_once ROOT_PATH . "/includes/sidebar.php"; ?>

    <div class="body-wrapper">
        <?php include_once ROOT_PATH . "/includes/header.php"; ?>

        <div class="body-wrapper-inner">
            <div class="container-fluid pt-3">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-0 fw-bold"><i class="ti ti-shield-lock me-1"></i> Usuarios</h2>
                        <p class="text-muted small mb-0">Solo administradores pueden crear y gestionar cuentas</p>
                    </div>
                    <button class="btn btn-primary" onclick="abrirModalUsuario()">
                        <i class="ti ti-plus"></i> Nuevo usuario
                    </button>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="admin-table-wrap">
                        <div class="table-responsive admin-table-scroll">
                            <table class="table table-hover table-striped align-middle mb-0 table-admin table-mobile-cards">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4 col-cliente">Email / Usuario</th>
                                        <th class="col-sm">Rol</th>
                                        <th class="col-estado">Estado</th>
                                        <th class="col-fecha">Creado</th>
                                        <th class="text-end pe-4 col-acciones">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="bodyUsuarios">
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">Cargando usuarios...</td>
                                    </tr>
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

<!-- Modal crear / editar -->
<div class="modal fade" id="modalUsuario" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalUsuarioTitulo">Nuevo usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="usuarioFeedback" class="alert d-none mb-3" role="alert"></div>
                <input type="hidden" id="usuarioId">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email / Usuario</label>
                    <input type="text" id="usuarioEmail" class="form-control" placeholder="nombre.usuario" autocomplete="off">
                </div>
                <div class="mb-3" id="grupoPassword">
                    <label class="form-label fw-semibold">Contraseña</label>
                    <input type="password" id="usuarioPassword" class="form-control" minlength="6" autocomplete="new-password">
                    <div class="form-text">Mínimo 6 caracteres</div>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold">Rol</label>
                    <select id="usuarioRol" class="form-select">
                        <option value="vendedor">Vendedor</option>
                        <option value="administrador">Administrador</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btnGuardarUsuario" class="btn btn-primary" onclick="guardarUsuario()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal cambiar contraseña (admin) -->
<div class="modal fade" id="modalPasswordAdmin" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cambiar contraseña</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="passwordAdminFeedback" class="alert d-none mb-3" role="alert"></div>
                <input type="hidden" id="passwordAdminId">
                <p class="text-muted small" id="passwordAdminEmail"></p>
                <div class="mb-0">
                    <label class="form-label fw-semibold">Nueva contraseña</label>
                    <input type="password" id="passwordAdminNueva" class="form-control" minlength="6" autocomplete="new-password">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btnGuardarPasswordAdmin" class="btn btn-primary" onclick="guardarPasswordAdmin()">Actualizar</button>
            </div>
        </div>
    </div>
</div>

<?php
$extra_js = '<script src="' . ASSETS_URL . '/js/usuarios.js?v=2"></script>';
include_once ROOT_PATH . "/includes/footer.php";
?>
