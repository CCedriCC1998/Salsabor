<?php
session_start();
if(!isset($_SESSION["username"])){
	header('location: portal');
}
require_once 'functions/db_connect.php';
$db = PDOFactory::getConnection();

// Get all products with a negative amount of hours remaining

$queryIrregulars = $db->query("SELECT * FROM produits_adherents pa
								JOIN users u ON id_user_foreign = u.user_id
								JOIN produits p ON id_produit_foreign = p.product_id
								JOIN transactions t ON id_transaction_foreign = t.id_transaction
								WHERE volume_cours < '0.00' AND product_size != 0
								ORDER BY volume_cours ASC");
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Forfaits irréguliers | Salsabor</title>
		<base href="../">
		<?php include "styles.php";?>
		<?php include "scripts.php";?>
		<script src="assets/js/products.js"></script>
		<script src="assets/js/participations.js"></script>
		<script>
			$(document).ready(function(){
				$("#product-modal").on("hidden.bs.modal", function(){
					console.log("Modal closed");
					$(".item-expired").remove();
					$(".item-active").remove();
				})
			})
		</script>
	</head>
	<body>
		<?php include "nav.php";?>
		<div class="container-fluid">
			<div class="row">
				<?php include "side-menu.php";?>
				<div class="col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
					<legend><span class="glyphicon glyphicon-queen"></span> Forfaits en sur-consommation</legend>
					<p class="sub-legend"><?php echo $queryIrregulars->rowCount();?> forfaits concernés.</p>
					<div class="container-fluid irregulars-container">
						<ul class="purchase-inside-list purchase-product-list">
							<?php while($irregulars = $queryIrregulars->fetch(PDO::FETCH_ASSOC)){ ?>
							<li class="purchase-item panel-item item-overconsumed container-fluid" id="purchase-item-<?php echo $irregulars["id_produit_adherent"];?>" data-toggle='modal' data-target="#product-modal" data-argument="<?php echo $irregulars["id_produit_adherent"];?>">
								<p class="col-xs-12 col-sm-12 col-lg-3 panel-item-title"><?php echo $irregulars["product_name"];?></p>
								<p class="col-xs-12 col-sm-4 col-lg-3 purchase-product-owner"><?php echo $irregulars["user_prenom"]." ".$irregulars["user_nom"];?></p>
								<p class="col-xs-12 col-sm-4 col-lg-3 purchase-product-hours"><?php echo -1 * $irregulars["volume_cours"];?> heures en excès</p>
								<p class="col-xs-12 col-sm-4 col-lg-1 purchase-price align-right"><?php echo $irregulars["prix_achat"];?> €</p>
							</li>
							<?php } ?>
						</ul>
					</div>
				</div>
			</div>
		</div>
		<?php include "inserts/modal_product.php";?>
	</body>
</html>
