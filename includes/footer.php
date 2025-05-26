<?php
// includes/footer.php

// Base URL to ensure correct paths from any subfolder
$base = '/freelancer_platform/';
?>
    </div> <!-- /.container -->

    <footer class="text-center py-3 bg-light mt-5">
      <small>&copy; <?= date('Y') ?> FreelanceApp</small>
    </footer>

    <!-- Bootstrap JS bundle (includes Popper) -->
    <script src="<?= $base ?>assets/js/bootstrap.bundle.min.js"></script>
    <!-- Your custom JavaScript -->
    <script src="<?= $base ?>assets/js/custom.js"></script>
</body>
</html>
