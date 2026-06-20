<script src="<?= ASSETS_URL ?>/libs/jquery/dist/jquery.min.js"></script>
  <script src="<?= ASSETS_URL ?>/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?= ASSETS_URL ?>/js/admin.js?v=11"></script>
  <script src="<?= ASSETS_URL ?>/libs/simplebar/dist/simplebar.js"></script>
  <?php include_once "pagination_script.php"; ?>
  <?php include_once "preloader.php"; ?>
  <?php include_once "btn-share.php"; ?>
  <?php include_once "modal-global.php"; ?>
  <?php if(isset($extra_js)) echo $extra_js; ?>


</body>
</html>
