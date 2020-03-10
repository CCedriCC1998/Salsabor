<?php
session_start();
if(!isset($_SESSION["username"])){
	header('location: portal');
}
require_once 'functions/db_connect.php';
$db = PDOFactory::getConnection();

$transactions = $db->query("SELECT id_transaction, payeur_transaction, CONCAT(u.user_prenom, ' ', u.user_nom) AS identity, date_achat, prix_total FROM transactions t
JOIN users u ON t.payeur_transaction = u.user_id
ORDER BY date_achat DESC LIMIT 10");

$inscriptions = $db->query("SELECT user_id, CONCAT(user_prenom, ' ', user_nom) AS identity FROM users ORDER BY user_id DESC LIMIT 10");
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Historique | Salsabor</title>
		<?php include "styles.php";?>
		<?php include "scripts.php";?>
	</head>
	<body>
		<?php include "nav.php";?>
		<div class="container-fluid">
			<div class="row">
				<?php include "side-menu.php";?>
				<div class="col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
					<legend><span class="glyphicon glyphicon-book"></span> Historique</legend>
					<p class="sub-legend">Achats récents</p>
					<?php while($transaction = $transactions->fetch()){ ?>
					<div class="history-entry">
					<p class="bf">Transaction <a href="user/<?php echo $transaction["payeur_transaction"];?>/achats#purchase-<?php echo $transaction["id_transaction"];?>" target="_blank"><?php echo $transaction["id_transaction"];?></a></p>
					<p>De <?php echo $transaction["identity"];?>, réalisée le <?php echo $transaction["date_achat"];?></p>
					</div>
					<?php } ?>
					<p class="sub-legend">Nouveaux inscrits</p>
					<?php while($inscription = $inscriptions->fetch()){ ?>
					<div class="history-entry">
						<p><?php echo $inscription["identity"];?></p>
					</div>
					<?php } ?>
				</div>
			</div>
		</div>
		<style>
			.history-entry{
				border-bottom: 1px solid black;
			}
		</style>
		<script>
			$(document).ready(function(){
				fetchLogs($(".logs-container"), null, 0);
			})
		</script>
	</body>
</html>
