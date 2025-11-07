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
<script>
// Header offset helper: ensures main is not hidden under a fixed/sticky header
(function(){
    function debounce(fn, wait){
        var t=null; return function(){ clearTimeout(t); t=setTimeout(fn, wait||50); };
    }

    function adjustHeaderOffset(){
        try{
            var header = document.querySelector('header.container-header');
            var main = document.querySelector('main');
            if (!header || !main) return;

            var rect = header.getBoundingClientRect();
            var cs = window.getComputedStyle(header);
            var offset = Math.ceil(rect.height) || parseInt(cs.height) || 0;

            // Add small breathing room
            offset = offset + 12;

            // Only apply if the computed layout would overlap (fixed/sticky) or main has no top spacing
            if (cs.position === 'fixed' || cs.position === 'sticky' || main.style.paddingTop === '' || parseInt(getComputedStyle(main).paddingTop) < offset) {
                main.style.paddingTop = offset + 'px';
                document.documentElement.style.setProperty('--header-offset', offset + 'px');
            }
        }catch(e){/* silent */}
    }

    var deb = debounce(adjustHeaderOffset, 60);
    document.addEventListener('DOMContentLoaded', function(){
        adjustHeaderOffset();
        // navbar.js may inject content; observe header for changes
        var header = document.querySelector('header.container-header');
        if (header && window.MutationObserver) {
            var mo = new MutationObserver(deb);
            mo.observe(header, { childList: true, subtree: true, attributes: true });
            // stop observing after some time to avoid forever observer
            setTimeout(function(){ try{ mo.disconnect(); }catch(e){} }, 5000);
        }
    });
    window.addEventListener('resize', deb);
    window.addEventListener('orientationchange', deb);
})();
</script>
