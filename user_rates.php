<?php
session_start();
if(!isset($_SESSION["username"])){
	header('location: portal');
}
require_once 'functions/db_connect.php';
$db = PDOFactory::getConnection();
$user_id = $_GET['id'];

// User details
$details = $db->query("SELECT * FROM users u
						LEFT JOIN locations l ON u.user_location = l.location_id
						WHERE user_id='$user_id'")->fetch(PDO::FETCH_ASSOC);

$details["count"] = $db->query("SELECT * FROM tasks
					WHERE ((task_token LIKE '%USR%' AND task_target = '$user_id')
					OR (task_token LIKE '%PRD%' AND task_target IN (SELECT id_produit_adherent FROM produits_adherents WHERE id_user_foreign = '$user_id'))
					OR (task_token LIKE '%TRA%' AND task_target IN (SELECT id_transaction FROM transactions WHERE payeur_transaction = '$user_id')))
						AND task_state = 0")->rowCount();

//Enfin, on obtient l'historique de tous les achats (mêmes les forfaits d'autres personnes)

$queryRates = $db->query("SELECT * FROM teacher_rates WHERE user_id_foreign = '$user_id'");

$is_teacher = $db->query("SELECT * FROM assoc_user_tags ur
								JOIN tags_user tu ON tu.rank_id = ur.tag_id_foreign
								WHERE rank_name = 'Professeur' AND user_id_foreign = '$user_id'")->rowCount();
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Achats - <?php echo $details["user_prenom"]." ".$details["user_nom"];?> | Salsabor</title>
		<base href="../../">
		<?php include "styles.php";?>
		<?php include "scripts.php";?>
		<script src="assets/js/products.js"></script>
		<script src="assets/js/maturities.js"></script>
		<script src="assets/js/bootstrap-slider.min.js"></script>
	</head>
	<body>
		<?php include "nav.php";?>
		<div class="container-fluid">
			<div class="row">
				<?php include "side-menu.php";?>
				<div class="col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
					<?php include "inserts/user_banner.php";?>
					<ul class="nav nav-tabs">
						<li role="presentation" class="visible-xs-block"><a href="user/<?php echo $user_id;?>">Infos perso</a></li>
						<li role="presentation" class="hidden-xs"><a href="user/<?php echo $user_id;?>">Informations personnelles</a></li>
						<?php if($is_teacher == 1){ ?>
						<!--<li role="presentation"><a>Cours donnés</a></li>-->
						<li role="presentation" class="active"><a href="user/<?php echo $user_id;?>/tarifs">Tarifs</a></li>
						<li role="presentation"><a href="user/<?php echo $user_id;?>/facturation">Facturation</a></li>
						<!--<li role="presentation"><a>Statistiques</a></li>-->
						<?php } ?>
						<li role="presentation"><a href="user/<?php echo $user_id;?>/abonnements">Abonnements</a></li>
						<li role="presentation"><a href="user/<?php echo $user_id;?>/historique">Participations</a></li>
						<li role="presentation"><a href="user/<?php echo $user_id;?>/achats">Achats</a></li>
						<li role="presentation"><a href="user/<?php echo $user_id;?>/reservations">Réservations</a></li>
						<li role="presentation"><a href="user/<?php echo $user_id;?>/taches">Tâches</a></li>
					</ul>
					<div>
						<button class="btn btn-primary maturities-button" data-toggle="modal" data-target="#add-modal">Ajouter un tarif</button>
						<div class="rates-list">
							<?php while($rate = $queryRates->fetch(PDO::FETCH_ASSOC)){ ?>
							<div class="rate-entity-<?php echo $rate["rate_id"];?>" id="rate-<?php echo $rate["rate_id"];?>">
								<p class="rate-title panel-item-title bf col-xs-7 modal-editable-<?php echo $rate["rate_id"];?>" id="editable-title-<?php echo $rate["rate_id"];?>" data-field="rate_title" data-name="Définition"><?php echo $rate["rate_title"];?></p>
								<p class="rate-value col-xs-3"><span class="modal-editable-<?php echo $rate["rate_id"];?>" id="editable-value-<?php echo $rate["rate_id"];?>" data-field="rate_value" data-name="Valeur"><?php echo $rate["rate_value"];?></span> € / <span class="modal-editable-<?php echo $rate["rate_id"];?>" id="editable-ratio-<?php echo $rate["rate_id"];?>" data-field="rate_ratio" data-name="Ratio"><?php echo $rate["rate_ratio"];?></span></p>
								<p class="col-xs-1">
									<span class="glyphicon glyphicon-pencil glyphicon-button glyphicon-button-alt edit-rate" id="edit-'<?php echo $rate["rate_id"];?>'" data-toggle="modal" data-target="#edit-modal" data-entry="<?php echo $rate["rate_id"];?>" data-table="teacher_rates" title="Modifier le tarif"></span>
								</p>
								<p class="col-xs-1">
									<span class="glyphicon glyphicon-trash glyphicon-button glyphicon-button-alt delete-rate" id="delete-<?php echo $rate["rate_id"];?>" data-toggle="modal" data-target="#delete-modal" data-entry="<?php echo $rate["rate_id"];?>" data-delete=".rate-entity-<?php echo $rate["rate_id"];?>" data-table="teacher_rates" title="Supprimer le tarif <?php echo $rate["rate_title"];?> de <?php echo $details["user_prenom"]." ".$details["user_nom"];?>"></span>
								</p>
							</div>
							<?php } ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php include "inserts/edit_modal.php";?>
		<?php include "inserts/delete_modal.php";?>
		<!-- Add modal is specific -->
		<div class="modal fade" id="add-modal" tabindex="-1" role="dialog">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						<h4 class="modal-title">Ajouter un tarif</h4>
					</div>
					<div class="modal-body container-fluid">
						<div class="add-form-space">
							<form class="form-horizontal" id="modal-add-form">
								<div class="form-group">
									<label for="rate_title" class="col-lg-4 control-label">Définition</label>
									<div class="col-lg-8">
										<input type="text" class="form-control" name="rate_title" value="">
									</div>
								</div>
								<div class="form-group">
									<label for="rate_value" class="col-lg-4 control-label">Value</label>
									<div class="col-lg-8">
										<input type="text" class="form-control" name="rate_value" value="">
									</div>
								</div>
								<div class="form-group">
									<label for="rate_ratio" class="col-lg-4 control-label">Ratio</label>
									<div class="col-lg-8">
										<select name="rate_ratio" class="form-control">
											<option value="heure">heure</option>
											<option value="personne">personne</option>
											<option value="prestation">prestation</option>
										</select>
									</div>
								</div>
								<div class="form-group">
									<input type="hidden" name="user_id_foreign" value="<?php echo $user_id;?>">
								</div>
							</form>
						</div>
					</div>
					<div class="modal-footer">
						<button class="btn btn-primary add-data">Ajouter le tarif</button>
					</div>
				</div>
			</div>
		</div>

		<script>
			$(document).ready(function(){
				console.log("ready");
			}).on('show.bs.modal', '#add-modal', function(){
				console.log("hey");
				$.get("functions/fetch_rates_list.php").done(function(data){
					var rates_list = JSON.parse(data);
					var autocomplete_list = [];
					for(var i = 0; i < rates_list.length; i++){
						autocomplete_list.push(rates_list[i].rate_title);
					}
					console.log(autocomplete_list);
					$("input[name='rate_title']").textcomplete('destroy');
					$("input[name='rate_title']").textcomplete([{
						match: /(^|\b)(\w{2,})$/,
						search: function(term, callback){
							callback($.map(autocomplete_list, function(item){
								return item.toLowerCase().indexOf(term.toLocaleLowerCase()) === 0 ? item : null;
							}));
						},
						replace: function(item){
							return item;
						}
					}]);
				});
			}).on('click', '.add-data', function(){
				var values = $("#modal-add-form").serialize();
				var rate_title = $("input[name='rate_title']").val(), rate_value = $("input[name='rate_value']").val(), rate_ratio = $("select[name='rate_ratio']").val();
				/*console.log(values);*/
				$.when(addEntry("teacher_rates", values)).done(function(data){
					var new_rate = "<div class='rate-entity-"+data+"' id='rate-"+data+"'>";
					new_rate += "<p class='rate-title panel-item-title bf col-xs-7 modal-editable-"+data+"' id='editable-title-"+data+"' data-field='rate_title' data-name='Définition'>"+rate_title+"</p>";
					new_rate += "<p class='rate-value col-xs-3'>";
					new_rate += "<span class='modal-editable-"+data+"' id='editable-value-"+data+"' data-field='rate_value' data-name='Valeur'>"+rate_value+"</span>";
					new_rate += "€ / ";
					new_rate += "<span class='modal-editable-"+data+"' id='editable-ratio-"+data+"' data-field='rate_ratio' data-name='Ratio'>"+rate_ratio+"</span>";
					new_rate += "</p>";
					new_rate += "<p class='col-xs-1'>";
					new_rate += "<span class='glyphicon glyphicon-pencil glyphicon-button glyphicon-button-alt edit-rate' id='edit-"+data+"' data-toggle='modal' data-target='#edit-modal' data-entry='"+data+"' data-table='teacher_rates' title='Modifier le tarif'>";
					new_rate += "</p>";
					new_rate += "<p class='col-xs-1'>";
					new_rate += "<span class='glyphicon glyphicon-trash glyphicon-button glyphicon-button-alt delete-rate' id='delete-"+data+"' data-toggle='modal' data-target='#delete-modal' data-entry='"+data+"' data-delete='rate-entity-"+data+"' data-table='teacher_rates' title='Supprimer le tarif'>";
					new_rate += "</p>";
					$(".rates-list").append(new_rate);
					showNotification("Tarif ajouté", "success");
					// Empty the fields
					$("input[name='rate_title']").val("");
					$("input[name='rate_value']").val("");
					$("#add-modal").modal('hide');
				})
			})
		</script>
	</body>
</html>
