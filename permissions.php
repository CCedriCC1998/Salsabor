<?php
session_start();
if(!isset($_SESSION["username"])){
	header('location: portal');
}
require_once 'functions/db_connect.php';
$db = PDOFactory::getConnection();
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Permissions | Salsabor</title>
		<?php include "styles.php";?>
		<?php include "scripts.php";?>
		<script src="assets/js/tags.js"></script>
	</head>
	<body>
		<?php include "nav.php";?>
		<div class="container-fluid">
			<div class="row">
				<?php include "side-menu.php";?>
				<div class="col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
					<legend><span class="glyphicon glyphicon-eye-close"></span> Permissions</legend>
					<p class="sub-legend">Organisez l'accès aux différentes sections de l'application</p>
					<p class="help-block">Par défaut, toutes les sections sont disponibles pour tout le monde. En ajoutant une étiquette, vous restreignez l'accès aux seuls utilisateurs associés à cette étiquette.</p>
					<div class="pages-container"></div>
				</div>
			</div>
		</div>
		<?php include "inserts/sub_modal_product.php";?>
		<script>
			$(document).ready(function(){
				$.get("functions/fetch_pages.php").done(function(data){
					displayPages(data);
				})
			})

			function displayPages(data){
				console.log(data);
				var pages = JSON.parse(data), contents = "";
				for(var i = 0; i < pages.length; i++){
					contents += "<div class='col-sm-12'>";
					contents += "<p><span class='glyphicon glyphicon-"+pages[i].page_glyph+"'></span> "+pages[i].page_name;
					for(var j = 0; j < pages[i].labels.length; j++){
						contents += "<span class='label label-salsabor label-clickable label-deletable' title='Supprimer l&apos;étiquette' id='page-tag-"+pages[i].labels[j].entry_id+"' data-target='"+pages[i].labels[j].entry_id+"' data-targettype='page' style='background-color:"+pages[i].labels[j].tag_color+"'>"+pages[i].labels[j].rank_name+"</span>";
					}
					contents += "<span class='label label-default label-clickable label-add trigger-sub' id='label-add-"+pages[i].id+"' data-subtype='user-tags' data-targettype='page' title='Ajouter une étiquette'>+</span>";
					contents += "</p>";
					contents += "</div>";
				}
				$(".pages-container").append(contents);
			}
		</script>
	</body>
</html>
