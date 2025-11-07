<?php
// Footer scripts include for backend pages
// Renders window.ecorideUser and includes navbar + common scripts
?>
<script>
window.ecorideUser = <?= isset($_SESSION['user']) ? json_encode($_SESSION['user'], JSON_UNESCAPED_UNICODE) : 'null' ?>;
</script>
<script src="/assets/js/navbar.js"></script>
<script src="/assets/js/script.js"></script>
<script>
// Header offset helper for backend pages (same logic as frontend)
(function(){
	function debounce(fn, wait){ var t=null; return function(){ clearTimeout(t); t=setTimeout(fn, wait||50); }; }
	function adjustHeaderOffset(){
		try{
			var header = document.querySelector('header.container-header');
			var main = document.querySelector('main');
			if (!header || !main) return;
			var rect = header.getBoundingClientRect();
			var cs = window.getComputedStyle(header);
			var offset = Math.ceil(rect.height) || parseInt(cs.height) || 0;
			offset = offset + 12;
			if (cs.position === 'fixed' || cs.position === 'sticky' || main.style.paddingTop === '' || parseInt(getComputedStyle(main).paddingTop) < offset) {
				main.style.paddingTop = offset + 'px';
				document.documentElement.style.setProperty('--header-offset', offset + 'px');
			}
		}catch(e){}
	}
	var deb = debounce(adjustHeaderOffset, 60);
	document.addEventListener('DOMContentLoaded', function(){ adjustHeaderOffset(); var header=document.querySelector('header.container-header'); if (header && window.MutationObserver){ var mo=new MutationObserver(deb); mo.observe(header,{childList:true,subtree:true,attributes:true}); setTimeout(function(){ try{ mo.disconnect(); }catch(e){} },5000); } });
	window.addEventListener('resize', deb);
	window.addEventListener('orientationchange', deb);
})();
</script>
