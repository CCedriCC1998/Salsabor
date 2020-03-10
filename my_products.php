<?php
session_start();
if(!isset($_SESSION["username"])){
	header('location: portal');
}
require_once 'functions/db_connect.php';
$db = PDOFactory::getConnection();
$user_id = $_SESSION["user_id"];
$user_details = $db->query("SELECT * FROM users WHERE user_id = '$user_id'")->fetch(PDO::FETCH_ASSOC);
$details["count"] = $db->query("SELECT * FROM tasks
					WHERE ((task_token LIKE '%USR%' AND task_target = '$user_id')
					OR (task_token LIKE '%PRD%' AND task_target IN (SELECT id_produit_adherent FROM produits_adherents WHERE id_user_foreign = '$user_id'))
					OR (task_token LIKE '%TRA%' AND task_target IN (SELECT id_transaction FROM transactions WHERE payeur_transaction = '$user_id')))
						AND task_state = 0")->rowCount();

//Enfin, on obtient l'historique de tous les achats (mêmes les forfaits d'autres personnes)
$queryAchats = $db->query("SELECT * FROM transactions
						WHERE id_transaction IN (SELECT id_transaction_foreign FROM produits_adherents WHERE id_user_foreign = '$user_id') OR payeur_transaction='$user_id'
						ORDER BY date_achat DESC");

$queryTransactions = $db->query("SELECT * FROM produits_adherents WHERE id_user_foreign = '$user_id'");
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Mon profil | Salsabor</title>
		<base href="../">
		<?php include "styles.php";?>
		<?php include "scripts.php";?>
		<script src="assets/js/products.js"></script>
	</head>
	<body>
		<?php include "my-nav.php";?>
		<div class="container-fluid">
			<div class="row">
				<?php include "side-menu.php";?>
				<div class="col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
					<legend><span class="glyphicon glyphicon-th"></span> Ma conso</legend>
					<p class="sub-legend">Consultez vos produits et leur état</p>
					<?php while($achats = $queryAchats->fetch(PDO::FETCH_ASSOC)){
	$productQty = $db->query("SELECT id_produit_adherent FROM produits_adherents WHERE id_transaction_foreign='$achats[id_transaction]'")->rowCount();?>
					<div class="panel panel-purchase" id="purchase-<?php echo $achats["id_transaction"];?>">
						<a class="panel-heading-container" onClick="displayPurchase('<?php echo $achats["id_transaction"];?>')">
							<div class="panel-heading container-fluid">
								<p class="purchase-id col-lg-5">Transaction <?php echo $achats["id_transaction"];?></p>
								<p class="col-lg-3">Contient <?php echo $productQty;?> produit(s)</p>
								<p class="purchase-sub col-lg-4">Effectuée le <?php echo date_create($achats["date_achat"])->format('d/m/Y');?> - <?php echo $achats["prix_total"];?> €</p>
								<!--<button class="btn btn-default fetch-purchase" onClick="displayPurchase('<?php echo $achats["id_transaction"];?>')">Détails</button>-->
								<!--<a href="purchase_details.php?id=<?php echo $achats["id_transaction"];?>&status=<?php echo $status;?>" class="btn btn-default"><span class="glyphicon glyphicon-search"></span> Détails...</a>-->
							</div>
						</a>
						<div class="panel-body collapse" id="body-purchase-<?php echo $achats["id_transaction"];?>">
						</div>
					</div>
					<?php } ?>
				</div>
			</div>
		</div>
		<?php include "inserts/modal_product.php";?>
		<?php include "inserts/modal_maturity.php";?>
		<style>
			.modal-actions-container, .session-options{
				display: none;
			}
		</style>
	</body>
</html>

