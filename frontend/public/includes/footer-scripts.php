<?php
// footer-scripts include for frontend pages
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
?>
<script>
    window.ecorideUser = <?php
        if (isset($_SESSION['user'])) {
            $u = $_SESSION['user'];
            echo json_encode([
                'email' => $u['email'] ?? null,
                'pseudo' => $u['pseudo'] ?? null,
                'type' => $u['type'] ?? null
            ]);
        } else {
            echo 'null';
        }
    ?>;
</script>
<script src="/assets/js/navbar.js"></script>
<script src="/assets/js/script.js"></script>
