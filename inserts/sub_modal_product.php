<?php if(preg_match("/historique/",$_SERVER["REQUEST_URI"],$matches) || preg_match("/participations/",$_SERVER["REQUEST_URI"],$matches) || preg_match("/cours/",$_SERVER["REQUEST_URI"],$matches) || preg_match("/taches/",$_SERVER["REQUEST_URI"],$matches) || preg_match("/achats/",$_SERVER["REQUEST_URI"],$matches) || preg_match("/echeances/",$_SERVER["REQUEST_URI"],$matches)){
	$lg_width = "3";
	$xs_width = "5";
} else {
	$lg_width = "7";
	$xs_width = "5";
}?>
<div class="sub-modal">
	<div class="sub-modal-header">
		<button type="button" class="close sub-modal-close" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		<p class="sub-modal-title"></p>
	</div>
	<div class="sub-modal-body container-fluid">
	</div>
	<div class="sub-modal-footer"></div>
</div>
