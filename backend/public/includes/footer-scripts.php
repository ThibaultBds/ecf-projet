<?php
// Footer scripts include for backend pages
// Renders window.ecorideUser and includes navbar + common scripts
?>
<script>
window.ecorideUser = <?= isset($_SESSION['user']) ? json_encode($_SESSION['user'], JSON_UNESCAPED_UNICODE) : 'null' ?>;
</script>
<script src="/assets/js/navbar.js"></script>
<script src="/assets/js/script.js"></script>
