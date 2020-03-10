<?php
session_start();
if(!isset($_SESSION["username"])){
	header('location: portal');
}
require_once 'functions/db_connect.php';
$db = PDOFactory::getConnection();

$queryIrregulars = $db->query("SELECT * FROM participations pr
								JOIN users u ON pr.user_id = u.user_id
								JOIN sessions s ON pr.session_id = s.session_id
								WHERE produit_adherent_id IS NULL OR produit_adherent_id = '' OR produit_adherent_id = 0
								ORDER BY user_nom, session_start ASC");
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Template | Salsabor</title>
		<base href="../">
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
					<legend><span class="glyphicon glyphicon-pawn"></span> Participations non associées à un forfait</legend>
					<div class="col-lg-8 irregulars-container">
						<button class='btn btn-default btn-modal btn-link-all' id='link-all' onclick='linkAll()' title='Délier tous les cours hors forfait'><span class='glyphicon glyphicon-arrow-right'></span> Associer toutes les participations irrégulières</button>
						<ul class="irregulars-list">
							<?php
							$currentUser = "";
							while($irregulars = $queryIrregulars->fetch(PDO::FETCH_ASSOC)){
								if($currentUser != $irregulars["user_nom"]){
									echo "<a href='user/".$irregulars["user_id"]."' class='sub-legend'>".$irregulars["user_prenom"]." ".$irregulars["user_nom"]."</a>";
								}
							?>
							<li class="irregular-participation" id="participation-<?php echo $irregulars["passage_id"];?>" data-argument="<?php echo $irregulars["passage_id"];?>">
								<p><?php echo $irregulars["user_prenom"]." ".$irregulars["user_nom"];?> au cours de <?php echo $irregulars["session_name"];?> du <?php echo date_create($irregulars["session_start"])->format("d/m/Y\ \à\ H:i");?></p>
							</li>
							<?php $currentUser = $irregulars["user_nom"];
							} ?>
						</ul>
					</div>
					<div class="col-lg-3 irregulars-target-container">
						Forfaits de l'adhérent associé au passage sélectionné
					</div>
				</div>
			</div>
		</div>
		<?php include "inserts/delete_modal.php";?>
		<script>
			$(document).on("click", ".irregular-participation", function(){
				var participation_id = document.getElementById($(this).attr("id")).dataset.argument;
				$(".irregular-participation").removeClass("focused");
				$(this).addClass("focused");
				var token = {};
				token["participation_id"] = participation_id;
				$.when(fetchProducts($.param(token))).done(function(data){
					var construct = displayEligibleProducts(data);
					construct += "<button class='btn btn-default btn-modal set-participation-product' id='btn-product-report' data-session='"+participation_id+"'><span class='glyphicon glyphicon-credit-card'></span> Associer</button> ";
					construct += "<button class='btn btn-danger pre-delete' data-session='"+participation_id+"' id='btn-record-delete'><span class='glyphicon glyphicon-trash'></span> Supprimer</button>";
					$(".irregulars-target-container").html(construct);
				})
			}).on("click", ".pre-delete", function(){
				$(this).addClass("delete-participation");
				$(this).removeClass("pre-delete");
				$(this).html("<span class='glyphicon glyphicon-trash'></span> Confirmer</button>");
			})
		</script>
	</body>
</html>
