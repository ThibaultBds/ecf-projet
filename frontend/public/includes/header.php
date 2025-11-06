<?php
// header include for frontend pages
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
?>
<header class="container-header">
    <h1>
        <a href="/" class="site-brand" style="color:inherit;text-decoration:none;display:flex;align-items:center;gap:10px;">
            <span class="material-icons">eco</span>
            EcoRide
        </a>
    </h1>
    <nav id="navbar"></nav>
</header>
