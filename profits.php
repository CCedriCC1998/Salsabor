<?php
session_start();
if(!isset($_SESSION["username"])){
	header('location: portal');
}
require_once 'functions/db_connect.php';
$db = PDOFactory::getConnection();

$unique = $db->query("SELECT DISTINCT user_id FROM participations WHERE passage_date > '2015-09-01 00:00:00'");

$members = $db->query("SELECT user_id, prix_total FROM participations pr
						JOIN transactions t ON t.payeur_transaction = pr.user_id
						WHERE passage_date > '2015-09-01 00:00:00'
						AND prix_total > 150.00
						GROUP BY user_id");

$sum = $db->query("SELECT SUM(prix_total) FROM transactions
						WHERE date_achat > '2015-08-15 00:00:00'")->fetch(PDO::FETCH_ASSOC);
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Rentabilité | Salsabor</title>
		<?php include "styles.php";?>
		<?php include "scripts.php";?>
	</head>
	<body>
		<?php include "nav.php";?>
		<div class="container-fluid">
			<div class="row">
				<?php include "side-menu.php";?>
				<div class="col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
					<legend><span class="glyphicon glyphicon-usd"></span> Rentabilité
					</legend>
					<div id="results-list" class="container-fluid">
						<p class="sub-legend">Participations uniques</p>
						<p><?php echo $unique->rowCount();?> participations uniques depuis le 01/09/2015.</p>

						<p class="sub-legend">Adhérents (participants avec au moins 140€ de dépenses)</p>
						<p><?php echo $members->rowCount();?> adhérents avec au moins 140€ de dépenses.</p>

						<p class="sub-legend">Somme de toutes les transactions depuis le 15/08/2015</p>
						<p><?php echo $sum["SUM(prix_total)"];?> €</p>

						<p class="sub-legend">Prix moyens (produits achetés après le 15/08/2015)</p>
						<table class="table">
							<thead>
								<th>Nom</th>
								<th>Prix fixé</th>
								<th>Nb. produits</th>
								<th>Prix réel</th>
								<th>Nb. part.</th>
								<th>Revenu</th>
							</thead>
							<tbody>
								<?php $unlimited = $db->query("SELECT product_id, product_name, product_price, product_size FROM produits");

								while($product = $unlimited->fetch(PDO::FETCH_ASSOC)){
									$query = "SELECT prix_achat, date_achat, COUNT(pr.passage_id) AS count_participations, prix_achat/COUNT(pr.passage_id) AS mean_value FROM produits_adherents pa
													LEFT JOIN transactions t ON pa.id_transaction_foreign = t.id_transaction
													LEFT JOIN participations pr ON pr.produit_adherent_id = pa.id_produit_adherent
													WHERE id_produit_foreign = $product[product_id]";
									if($product["product_id"] == 15)
										$query .= " AND prix_achat > 0.00";
									$query .= " GROUP BY produit_adherent_id";
									$mean = $db->query($query);
									$value = 0; $total_selling_price = 0; $total_participations = 0;
									while($single = $mean->fetch(PDO::FETCH_ASSOC)){
										$value += $single["mean_value"];
										$total_selling_price += $single["prix_achat"];
										$total_participations += $single["count_participations"];
									}
									$count = $mean->rowCount();
									if($count != 0){
										$mean_value = $value / $count;
										$mean_selling_price = $total_selling_price / $count;
									} else {
										$mean_value = 0.00;
										$mean_selling_price = $total_selling_price / 1;
									}
									echo "<tr>";
									echo "<td>".$product["product_name"]."</td>";
									echo "<td>".number_format($product["product_price"])." €</td>";
									echo "<td>".$count."</td>";
									echo "<td>".number_format($mean_selling_price, 2)." €</td>";
									echo "<td>".$total_participations."</td>";
									if($product["product_size"] != 0){
										if($product["product_size"] > 0){
											$price = $mean_selling_price * $total_participations / $product["product_size"];
											echo "<td>".number_format($price, 2)." €</td>";
										} else {
											echo "<td> -- € </td>";
										}
									} else {
										if($total_participations > 0){
											$price = ($mean_selling_price * $count / $total_participations);
											echo "<td>".number_format($price, 2)." €</td>";
										} else {
											$price = 0;
											echo "<td> -- € </td>";
										}
									}
									echo "</tr>";
								}
								?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</body>
</html>
