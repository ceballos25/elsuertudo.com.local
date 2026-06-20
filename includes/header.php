<?php
use App\Core\Auth;
?>
      <!--  Header Start -->
      <header class="app-header">
        <nav class="navbar navbar-expand-lg navbar-light">
          <ul class="navbar-nav">
            <li class="nav-item d-block d-xl-none">
              <a class="nav-link sidebartoggler " id="headerCollapse" href="javascript:void(0)">
                <i class="ti ti-menu-2"></i>
              </a>
            </li>
          </ul>
          <div class="navbar-collapse justify-content-end px-0" id="navbarNav">
            <ul class="navbar-nav flex-row ms-auto align-items-center justify-content-end">
              <?php if (Auth::check()): ?>
              <li class="nav-item d-none d-md-block me-2">
                <span class="small text-muted">
                  <?= htmlspecialchars(Auth::email()) ?>
                  <span class="badge <?= Auth::isAdmin() ? 'bg-primary' : 'bg-secondary' ?> ms-1">
                    <?= Auth::isAdmin() ? 'Admin' : 'Vendedor' ?>
                  </span>
                </span>
              </li>
              <?php endif; ?>
              <li class="nav-item dropdown">
                <a class="nav-link " href="javascript:void(0)" id="drop2" data-bs-toggle="dropdown"
                  aria-expanded="false">
                  <img src="<?= ASSETS_URL ?>/images/profile/user-1.jpg" alt="" width="35" height="35" class="rounded-circle">
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-animate-up" aria-labelledby="drop2">
                  <div class="message-body">
                    <a href="javascript:void(0)" class="btn btn-outline-secondary mx-3 mt-2 d-block" onclick="abrirModalMiPassword()">
                      <i class="ti ti-key"></i> Mi contraseña
                    </a>
                    <a href="<?= BASE_URL ?>/logout.php" class="btn btn-outline-primary mx-3 mt-2 mb-2 d-block">Salir</a>
                  </div>
                </div>
              </li>
            </ul>
          </div>
        </nav>
      </header>
      <!--  Header End -->

      <!-- Modal: cambiar mi contraseña -->
      <div class="modal fade" id="modalMiPassword" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Cambiar mi contraseña</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label fw-semibold">Contraseña actual</label>
                <input type="password" id="miPasswordActual" class="form-control" autocomplete="current-password">
              </div>
              <div class="mb-0">
                <label class="form-label fw-semibold">Nueva contraseña</label>
                <input type="password" id="miPasswordNueva" class="form-control" minlength="6" autocomplete="new-password">
                <div class="form-text">Mínimo 6 caracteres</div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
              <button type="button" class="btn btn-primary" onclick="guardarMiPassword()">Actualizar</button>
            </div>
          </div>
        </div>
      </div>
