<?php
session_start();
if(!isset($_SESSION["username"])){
	header('location: portal');
}
require_once 'functions/db_connect.php';
$db = PDOFactory::getConnection();

$display = $_GET["display"];
$archive = $_GET["archive"];

$count = $db->query("SELECT passage_id FROM participations pr
								LEFT JOIN sessions s ON pr.session_id = s.session_id
								LEFT JOIN rooms r ON s.session_room = r.room_id
								LEFT JOIN locations l ON r.room_location = l.location_id
								WHERE (pr.status != 2 OR (pr.status = 2 AND (produit_adherent_id IS NULL OR produit_adherent_id = '' OR produit_adherent_id = 0)))
								AND location_id = $_SESSION[location]
								AND pr.archived = $archive")->rowCount();
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Participations irrégulières | Salsabor</title>
		<base href="../../../">
		<?php include "styles.php";?>
		<?php include "scripts.php";?>
		<script src="assets/js/products.js"></script>
		<script src="assets/js/participations.js"></script>
		<script src="assets/js/jquery.waypoints.min.js"></script>
		<script>
			$(document).ready(function(){
				<?php if($display == "all"){ ?>
				displayIrregularParticipations(0, <?php echo $archive;?>);
				<?php } else { ?>
				displayIrregularUsers(<?php echo $archive;?>);
				<?php } ?>
			}).on('show.bs.collapse', '.panel-collapse', function(){
				var user_id = document.getElementById($(this).attr("id")).dataset.user;
				displayIrregularUserParticipations(user_id, <?php echo $archive;?>);
			}).on('click', '.glyphicon-button-alt', function(e){
				e.stopPropagation();
				var user_id = document.getElementById($(this).attr("id")).dataset.user;
				window.top.location = "user/"+user_id+"/historique";
			})
		</script>
	</head>
	<body>
		<?php include "nav.php";?>
		<div class="container-fluid">
			<div class="row">
				<?php include "side-menu.php";?>
				<div class="col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
					<legend><span class="glyphicon glyphicon-bishop"></span> Participations irrégulières</legend>
					<ul class="nav nav-tabs">
						<li role="presentation" <?php if($display == "all") echo "class='active'";?>>
							<a href="regularisation/participations/all/0">Tout afficher</a>
						</li>
						<li role="presentation" <?php if($display == "user") echo "class='active'";?>><a href="regularisation/participations/user/0">Par utilisateur</a></li>
						<li role="presentation" >
							<a href="suivi_utilisateur.php">Suivi par utilisateur</a>
						</li>
						<div class="btn-group float-right">
							<?php if($archive == 0){ ?>
							<a href="regularisation/participations/<?php echo $display;?>/1" class="btn btn-primary">Afficher les participations archivées</a>
							<?php } else { ?>
							<a href="regularisation/participations/<?php echo $display;?>/0" class="btn btn-primary">Afficher les participations non-archivées</a>
							<?php } ?>
						</div>
					</ul>

					<?php if($display == "all"){
	if($archive == 0){ ?>
					<p class="sub-legend irregular-participations-title"><span><?php echo $count;?></span> participations irrégulières.</p>
					<?php } else {?>
					<p class="sub-legend irregular-participations-title"><span><?php echo $count;?></span> participations irrégulières archivées.</p>
					<?php } ?>
					<div class="container-fluid irregular-sessions-container">
						<ul class="irregulars-list">

						</ul>
					</div>
					<?php } ?>
				</div>
			</div>
		</div>
		<?php include "inserts/sub_modal_product.php";?>
		<?php include "inserts/delete_modal.php";?>
		<?php include "inserts/archive_modal.php";?>
	</body>
</html>
