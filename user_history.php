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

$is_teacher = $db->query("SELECT * FROM assoc_user_tags ur
								JOIN tags_user tu ON tu.rank_id = ur.tag_id_foreign
								WHERE rank_name = 'Professeur' AND user_id_foreign = '$user_id'")->rowCount();
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Participations de <?php echo $details["user_prenom"]." ".$details["user_nom"];?> | Salsabor</title>
		<base href="../../">
		<?php include "styles.php";?>
		<?php include "scripts.php";?>
		<script src="assets/js/products.js"></script>
		<script src="assets/js/participations.js"></script>
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
						<li role="presentation"><a href="user/<?php echo $user_id;?>/tarifs">Tarifs</a></li>
						<li role="presentation"><a href="user/<?php echo $user_id;?>/facturation">Facturation</a></li>
						<!--<li role="presentation"><a>Statistiques</a></li>-->
						<?php } ?>
						<li role="presentation"><a href="user/<?php echo $user_id;?>/abonnements">Abonnements</a></li>
						<li role="presentation" class="active"><a href="user/<?php echo $user_id;?>/historique">Participations</a></li>
						<li role="presentation"><a href="user/<?php echo $user_id;?>/achats">Achats</a></li>
						<li role="presentation"><a href="user/<?php echo $user_id;?>/reservations">Réservations</a></li>
						<li role="presentation"><a href="user/<?php echo $user_id;?>/taches">Tâches</a></li>
					</ul>
					<div class="container-fluid">
						<div class="form-group">
							<label for="" class="control-label col-xs-6">Date de début</label>
							<label for="" class="control-label col-xs-6">Date de fin</label>
							<div class="col-xs-6">
								<input type="text" class="form-control date-filter" id="datepicker-start">
							</div>
							<div class="col-xs-6">
								<input type="text" class="form-control date-filter" id="datepicker-end">
							</div>
						</div>
						<p class="col-xs-3 participation-type" id="type-total"><span class="participation-count" id="total-count"></span> Participations</p>
						<p class="col-xs-3 participation-type" id="type-valid"><span class="participation-count" id="valid-count"></span> Participations valides</p>
						<p class="col-xs-3 participation-type" id="type-pending"><span class="participation-count" id="pending-count"></span> Participations en attente</p>
						<p class="col-xs-3 participation-type" id="type-over"><span class="participation-count" id="over-count"></span> Participations irrégulières</p>
					</div>
					<div class="container-fluid participations-list-container">
						<!--<button class='btn btn-default btn-modal btn-link-all' id='link-all' onclick='linkAll()' title='Délier tous les cours hors forfait'><span class='glyphicon glyphicon-arrow-right'></span> Associer toutes les participations irrégulières</button>-->
						<ul class="participations-list">
						</ul>
					</div>
				</div>
			</div>
		</div>
		<?php include "inserts/sub_modal_product.php";?>
		<?php include "inserts/edit_modal.php";?>
		<?php include "inserts/delete_modal.php";?>
		<style>
			.control-label{
				text-align: center;
			}
		</style>
		<script>
			$(document).ready(function(){
				$("#datepicker-start").datetimepicker({
					format: "DD/MM/YYYY",
					defaultDate: moment().subtract(1, 'year'),
					locale: "fr",
					sideBySide: true,
					stepping: 15
				}).on('dp.change', function(e){
					fetchUserParticipations(<?php echo $user_id;?>);
				})
				$("#datepicker-end").datetimepicker({
					format: "DD/MM/YYYY",
					locale: "fr",
					sideBySide: true,
					stepping: 15
				}).on('dp.change', function(e){
					fetchUserParticipations(<?php echo $user_id;?>);
				})
				fetchUserParticipations(<?php echo $user_id;?>);
			}).on('change', '.date', function(){
				console.log("changed");
			})
			$(".participation-type").click(function(){
				var id = $(this).attr("id");
				if(id == "type-total"){
					$(".panel-record").show();
				} else {
					$(".panel-record").hide();
					if(id == "type-valid"){
						$(".status-success").show();
					}
					if(id == "type-pending"){
						$(".status-pre-success").show();
					}
					if(id == "type-over"){
						$(".status-partial-success").show();
						$(".status-over").show();
					}
				}
			})
			function fetchUserParticipations(user_id){
				var filters = [];
				$(".date-filter").each(function(){
					if($(this).val() != "")
						filters.push(moment($(this).val(), "DD/MM/YYYY").format("YYYY-MM-DD"));
				})
				$.get("functions/fetch_user_participations.php", {user_id : user_id, filters : filters}).done(function(data){
					displayUserParticipations(data);
				})
			}
		</script>
	</body>
</html>
