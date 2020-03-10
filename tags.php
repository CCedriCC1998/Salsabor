<?php
session_start();
if(!isset($_SESSION["username"])){
	header('location: portal');
}
require_once 'functions/db_connect.php';
$db = PDOFactory::getConnection();
$type = $_GET["type"];
if($type == "users"){
	$legend_word = "utilisateurs";
	$query_type = "user";
} else {
	$legend_word = "cours et produits";
	$query_type = "session";
}
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>&Eacute;tiquettes | Salsabor</title>
		<base href="../">
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
					<legend><span class="glyphicon glyphicon-tags"></span> &Eacute;tiquettes</legend>
					<ul class="nav nav-tabs">
						<li role="presentation" <?php if($type == "users") echo "class='active'";?>><a href="tags/users">Utilisateurs</a></li>
						<li role="presentation" <?php if($type == "sessions") echo "class='active'";?>><a href="tags/sessions">Cours</a></li>
					</ul>
					<p class="sub-legend">Liste des étiquettes <?php echo $legend_word;?></p>
					<div class="tags-container col-sm-12"></div>
				</div>
			</div>
		</div>
		<?php include "inserts/sub_modal_product.php";?>
		<?php include "inserts/delete_modal.php";?>
		<script>
			$(document).ready(function(){
				var tag_type = '<?php echo $query_type;?>';
				$.when(fetchTags(tag_type)).done(function(data){
					var tags = JSON.parse(data), body = "";
					body += "<div class='col-xs-12'>";
					body += "<p class='col-xs-4'>Etiquette</p>";
					body += "<p class='col-xs-2'>Editer</p>";
					if(tag_type == "user")
						body += "<p class='col-xs-4'>Tâches 'infos. manquantes'</p>";
					body += "<p class='col-xs-1'>Supprimer</p></div>";
					for(var i = 0; i < tags.length; i++){
						body += "<h4><div class='col-xs-12' id='tagline-"+tags[i].rank_id+"'><span class='label col-xs-4 label-clickable label-restyle' id='tag-"+tags[i].rank_id+"' data-tag='"+tags[i].rank_id+"' data-tagtype='"+tag_type+"' style='background-color:"+tags[i].color+"'>";
						if(tags[i].is_mandatory == 1)
							body += "<span class='glyphicon glyphicon-star' title='Etiquette obligatoire'></span> ";
						body += tags[i].rank_name+"</span>";

						body += "<p class='col-xs-2'><span class='glyphicon glyphicon-pencil glyphicon-button glyphicon-button-alt trigger-sub' id='edit-"+tags[i].rank_id+"' data-subtype='edit-tag' data-tagtype='"+tag_type+"' data-target='"+tags[i].rank_id+"' title='Editer l&apos;étiquette'></span></p>";

						if(tag_type == "user"){
							body += "<p class='col-xs-4'><span class='glyphicon glyphicon-list-alt glyphicon-button glyphicon-button-alt mid-button";
							if(tags[i].mid == 0){
								body += " glyphicon-button-disabled";
							} else {
								body += " glyphicon-button-enabled";
							}
							body += "' id='mid-"+tags[i].rank_id+"' data-target='"+tags[i].rank_id+"' title='Indiquer l&apos;étiquette comme celle par défaut pour les tâches de type &apos;Informations manquantes&apos;'></span></p>";
						}
						body += "<p class='col-xs-1'><span class='glyphicon glyphicon-trash glyphicon-button glyphicon-button-alt' id='delete-tag-"+tags[i].rank_id+"' data-toggle='modal' data-target='#delete-modal' data-entry='"+tags[i].rank_id+"' data-table='tags_"+tag_type+"' data-delete='#tagline-"+tags[i].rank_id+"' data-title='Supprimer l&apos;étiquette "+tags[i].rank_name+"'></span></p>";
						body += "</div></h4>";
					}
					body += "<h4 class='new-label-space'><div class='col-sm-12'><span class='label col-xs-4 label-default label-clickable label-new-tag' id='label-new' data-tagtype='"+tag_type+"'>Créer une étiquette</span></div></h4>";
					body += "";
					$(".tags-container").append(body);
				})
			})
		</script>
	</body>
</html>
