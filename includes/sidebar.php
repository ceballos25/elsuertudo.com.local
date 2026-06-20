<?php
use App\Core\Auth;
use App\Core\SiteConfig;

$siteName = SiteConfig::name();
$siteLogo = SiteConfig::logo();
$currentPage = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

function isActive(string $fileName, string $currentPage): string
{
    return $currentPage === $fileName ? 'active' : '';
}

function isOpen(array $files, string $currentPage): string
{
    return in_array($currentPage, $files, true) ? 'in show' : '';
}

$ventasPages = ['ventas.php', 'numeros-vendidos.php', 'numeros.php'];
?>

<aside class="left-sidebar">
  <div class="left-sidebar-shell">
    <div class="brand-logo d-flex align-items-center justify-content-between">
      <a href="../index.php" class="text-nowrap logo-img d-flex justify-content-center w-100">
        <img style="width:70%; object-fit:contain;" src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>" />
      </a>
      <div class="close-btn d-xl-none d-block sidebartoggler cursor-pointer" id="sidebarCollapse">
        <i class="ti ti-x fs-6"></i>
      </div>
    </div>

    <nav class="sidebar-nav scroll-sidebar">
      <ul id="sidebarnav">

        <li class="nav-small-cap">
          <span class="hide-menu">Principal</span>
        </li>

        <li class="sidebar-item <?= isActive('dashboard.php', $currentPage); ?>">
          <a class="sidebar-link" href="dashboard.php"><i class="ti ti-home"></i><span class="hide-menu">Dashboard</span></a>
        </li>

        <li class="sidebar-item <?= isActive('vender.php', $currentPage); ?>">
          <a class="sidebar-link" href="vender.php"><i class="ti ti-shopping-cart"></i><span class="hide-menu">Vender</span></a>
        </li>

        <li class="sidebar-item <?= isActive('reservations.php', $currentPage); ?>">
          <a class="sidebar-link" href="reservations.php"><i class="ti ti-clock"></i><span class="hide-menu">Reservas</span></a>
        </li>

        <li><span class="sidebar-divider lg"></span></li>

        <li class="nav-small-cap">
          <span class="hide-menu">Terceros</span>
        </li>

        <li class="sidebar-item <?= isActive('clientes.php', $currentPage); ?>">
          <a class="sidebar-link" href="clientes.php"><i class="ti ti-users"></i><span class="hide-menu">Clientes</span></a>
        </li>

        <li><span class="sidebar-divider lg"></span></li>

        <li class="nav-small-cap">
          <span class="hide-menu">Ventas e informes</span>
        </li>

        <li class="sidebar-item <?= isOpen($ventasPages, $currentPage) ? 'selected' : '' ?>">
          <a class="sidebar-link justify-content-between has-arrow" href="javascript:void(0)" aria-expanded="<?= in_array($currentPage, $ventasPages, true) ? 'true' : 'false' ?>">
            <div class="d-flex align-items-center gap-3">
              <span class="d-flex"><i class="ti ti-box"></i></span>
              <span class="hide-menu">Ventas & Números</span>
            </div>
          </a>
          <ul aria-expanded="<?= in_array($currentPage, $ventasPages, true) ? 'true' : 'false' ?>" class="first-level <?= isOpen($ventasPages, $currentPage); ?>">
            <li class="sidebar-item <?= isActive('ventas.php', $currentPage); ?>">
              <a class="sidebar-link" href="ventas.php"><span class="hide-menu">Ventas</span></a>
            </li>
            <li class="sidebar-item <?= isActive('numeros-vendidos.php', $currentPage); ?>">
              <a class="sidebar-link" href="numeros-vendidos.php"><span class="hide-menu">Números Vendidos</span></a>
            </li>
            <li class="sidebar-item <?= isActive('numeros.php', $currentPage); ?>">
              <a class="sidebar-link" href="numeros.php"><span class="hide-menu">Números</span></a>
            </li>
          </ul>
        </li>

        <li><span class="sidebar-divider lg"></span></li>

        <li class="nav-small-cap">
          <span class="hide-menu">Configuración</span>
        </li>

        <li class="sidebar-item <?= isActive('rifas.php', $currentPage); ?>">
          <a class="sidebar-link" href="rifas.php"><i class="ti ti-ticket"></i><span class="hide-menu">Rifas</span></a>
        </li>

        <?php if (Auth::isAdmin()): ?>
        <li class="sidebar-item <?= isActive('settings.php', $currentPage); ?>">
          <a class="sidebar-link" href="settings.php"><i class="ti ti-settings"></i><span class="hide-menu">Mi Negocio</span></a>
        </li>

        <li class="sidebar-item <?= isActive('usuarios.php', $currentPage); ?>">
          <a class="sidebar-link" href="usuarios.php"><i class="ti ti-shield-lock"></i><span class="hide-menu">Usuarios</span></a>
        </li>
        <?php endif; ?>

      </ul>
    </nav>
  </div>
</aside>
