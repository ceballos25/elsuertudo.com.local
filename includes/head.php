<?php
use App\Core\Auth;
use App\Core\SiteConfig;

$siteName = SiteConfig::name();$siteLogo = SiteConfig::logo();
$siteFavicon = SiteConfig::favicon();
?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' . htmlspecialchars($siteName) : htmlspecialchars($siteName); ?></title>
  <link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars($siteFavicon) ?>" />
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/styles.min-v2.css" />
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/theme-base.css?v=11" />
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/admin-numbers.css?v=4" />
  <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/admin-tables.css?v=21" />
  <style>
    /* Sidebar: altura completa y scroll solo si hace falta */
    .left-sidebar {
      top: 0;
      height: 100vh;
      height: 100dvh;
    }
    .left-sidebar-shell {
      display: flex;
      flex-direction: column;
      height: 100%;
      overflow: hidden;
    }
    .left-sidebar .brand-logo {
      flex-shrink: 0;
      min-height: 70px;
      padding-top: 12px;
      padding-bottom: 12px;
    }
    .left-sidebar .scroll-sidebar {
      flex: 1 1 auto;
      min-height: 0;
      height: auto !important;
      max-height: none !important;
      overflow-x: hidden;
      overflow-y: auto;
      padding: 0 16px 20px;
      -webkit-overflow-scrolling: touch;
    }
    .left-sidebar .nav-small-cap {
      margin-top: 12px;
    }
    .left-sidebar #sidebarnav > .nav-small-cap:first-child {
      margin-top: 4px;
    }
    .sidebar-nav ul.first-level { display: none; }
    .sidebar-nav ul.first-level.in,
    .sidebar-nav ul.first-level.show { display: block; }
    @media (max-width: 1199px) {
      #main-wrapper.show-sidebar .left-sidebar { z-index: 1100; }
      .left-sidebar .scroll-sidebar {
        padding-bottom: 28px;
      }
    }
    /* Alertify por encima de modales Bootstrap */
    .alertify-notifier { z-index: 20000 !important; }
  </style>
  <!-- AlertifyJS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/alertify.min.css"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/themes/bootstrap.min.css"/>

<script src="https://cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/alertify.min.js"></script>

  <?php if(isset($extra_css)) echo $extra_css; ?>
  <script>
    window.SITE_URL = <?= json_encode(BASE_URL) ?>;
    window.API_URL = '<?= BASE_URL ?>/front/ajax/api.php';
    window.APP_USER = {
      id: <?= Auth::check() ? (int) Auth::userId() : 0 ?>,
      email: <?= json_encode(Auth::check() ? Auth::email() : '') ?>,
      role: <?= json_encode(Auth::check() ? Auth::role() : '') ?>,
      isAdmin: <?= Auth::check() && Auth::isAdmin() ? 'true' : 'false' ?>
    };
  </script>
  <script src="<?= ASSETS_URL ?>/js/api.js?v=3"></script>
</head>

<body>