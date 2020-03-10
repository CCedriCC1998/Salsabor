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
//Recherche des tâches
$details["count"] = $db->query("SELECT * FROM tasks
					WHERE ((task_token LIKE '%USR%' AND task_target = '$user_id')
					OR (task_token LIKE '%PRD%' AND task_target IN (SELECT id_produit_adherent FROM produits_adherents WHERE id_user_foreign = '$user_id'))
					OR (task_token LIKE '%TRA%' AND task_target IN (SELECT id_transaction FROM transactions WHERE payeur_transaction = '$user_id')))
						AND task_state = 0")->rowCount();

//Enfin, on obtient l'historique de tous les achats (mêmes les forfaits d'autres personnes)
//LEFT JOIN permet d'associer les tables transaction et users même si le user n'a pas encore de champ transaction
$queryAchats = $db->query("SELECT *, CONCAT(user_prenom, ' ', user_nom) AS handler FROM transactions t
						LEFT JOIN users u ON t.transaction_handler = u.user_id
						WHERE id_transaction IN (SELECT id_transaction_foreign FROM produits_adherents WHERE id_user_foreign = '$user_id') OR payeur_transaction='$user_id'
						ORDER BY date_achat DESC");

//n'a pas l'air utilisé
$queryTransactions = $db->query("SELECT * FROM produits_adherents WHERE id_user_foreign = '$user_id'");

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
		<link rel="stylesheet" href="assets/css/bootstrap-slider.min.css">
		<?php include "scripts.php";?>
		<script src="assets/js/products.js"></script>
		<script src="assets/js/maturities.js"></script>
		<script src="assets/js/bootstrap-slider.min.js"></script>
		<script src="assets/js/circle-progress.js"></script>
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
						<li role="presentation"><a href="user/<?php echo $user_id;?>/historique">Participations</a></li>
						<li role="presentation" class="active"><a href="user/<?php echo $user_id;?>/achats">Achats</a></li>
						<li role="presentation"><a href="user/<?php echo $user_id;?>/reservations">Réservations</a></li>
						<li role="presentation"><a href="user/<?php echo $user_id;?>/taches">Tâches</a></li>
					</ul>
					<div>

						<?php while($achats = $queryAchats->fetch(PDO::FETCH_ASSOC)){
	$productQty = $db->query("SELECT id_produit_adherent FROM produits_adherents WHERE id_transaction_foreign='$achats[id_transaction]'")->rowCount();
	$handler = ($achats["handler"]!=null)?$achats["handler"]:"Pas de vendeur";?>

						<div class="panel panel-purchase" id="purchase-<?php echo $achats["id_transaction"];?>">
							<div class="panel-heading container-fluid" onClick="displayPurchase('<?php echo $achats["id_transaction"];?>')">
								<p class="purchase-id col-xs-3">Transaction <?php echo $achats["id_transaction"];?></p>
								<p class="col-xs-2"><?php echo $productQty;?> produit(s)</p>
								<p class="purchase-sub col-xs-4">
									<span class="modal-editable-<?php echo $achats["id_transaction"];?>" data-field="date_achat" data-name="Date" id="date-<?php echo $achats["id_transaction"];?>"><?php echo date_create($achats["date_achat"])->format('d/m/Y');?></span> -

									<span class="modal-editable-<?php echo $achats["id_transaction"];?>" data-field="transaction_handler" data-name="Vendeur" data-complete="true" data-complete-filter="staff" id="handler-<?php echo $achats["id_transaction"];?>"><?php echo $handler;?></span> -

									<span class="modal-editable-<?php echo $achats["id_transaction"];?>" data-field="prix_total" data-name="Prix" id="price-<?php echo $achats["id_transaction"];?>"><?php echo $achats["prix_total"];?></span> €</p>

								<!--Les glyphes présents sur chaque bande de transaction-->
								<span class="glyphicon glyphicon-pencil glyphicon-button glyphicon-button-alt glyphicon-button-big col-xs-1" id="edit-<?php echo $achats["id_transaction"];?>" data-toggle="modal" data-target="#edit-modal" data-entry="<?php echo $achats["id_transaction"];?>" data-table="transactions" title="Modifier les détails de la transaction"></span>
								<span class="glyphicon glyphicon-briefcase glyphicon-button glyphicon-button-alt glyphicon-button-big create-contract col-xs-1" id="create-contract-<?php echo $achats["id_transaction"];?>" data-transaction="<?php echo $achats["id_transaction"];?>"title="Afficher le contrat"></span>
								<span class="glyphicon glyphicon-file glyphicon-button glyphicon-button-alt glyphicon-button-big create-invoice col-xs-1" id="create-invoice-<?php echo $achats["id_transaction"];?>" data-transaction="<?php echo $achats["id_transaction"];?>" title="Afficher la facture"></span>
							</div>
							<!--Pour faire le bordereau déroulant-->
							<div class="panel-body collapse" id="body-purchase-<?php echo $achats["id_transaction"];?>">
							</div>
						</div>
						<?php } ?>
					</div>
				</div>
			</div>
		</div>
		<?php include "inserts/modal_product.php";?>
		<?php include "inserts/sub_modal_product.php";?>
		<?php include "inserts/edit_modal.php";?>
		<?php include "inserts/delete_modal.php";?>
		<script>
			//Pour savoir quelle transaction est sélectionner ?
			$(document).ready(function(){
				var m, re = /purchase-([a-z0-9]+)/i;
				if((m = re.exec(top.location.hash)) !== null){
					var target_transaction = m[1];
					$("#purchase-"+target_transaction+">div").click();
				}
			})
			//Ouvre une fenêtre pour voir la facture
			$(".create-invoice").click(function(e){
				e.stopPropagation();
				var transaction_id = document.getElementById($(this).attr("id")).dataset.transaction;
				window.open("create_invoice.php?transaction="+transaction_id, "_blank", "location=yes,height=570,width=520,scrollbars=yes,status=yes");
			})
			//Ouvre la fenêtre pour voir le contrat
			$(".create-contract").click(function(e){
				e.stopPropagation();
				var transaction_id = document.getElementById($(this).attr("id")).dataset.transaction;
				window.open("create_contract.php?transaction="+transaction_id, "_blank", "location=yes,height=570,width=520,scrollbars=yes,status=yes");
			})
		</script>
	</body>
</html>
