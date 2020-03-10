<?php
include "db_connect.php";
$db = PDOFactory::getConnection();

parse_str($_GET["filter_token"], $filter_token);

$query = "SELECT *, pa.actif AS produit_adherent_actif, pa.date_activation AS produit_adherent_activation, CONCAT(user_prenom, ' ', user_nom) AS user, user_id, date_prolongee, date_fin_utilisation, date_expiration, date_achat, lock_status, lock_dates
					FROM produits_adherents pa
					JOIN produits p
						ON pa.id_produit_foreign = p.product_id
					LEFT JOIN transactions t
						ON pa.id_transaction_foreign = t.id_transaction
					LEFT JOIN users u
						ON pa.id_user_foreign = u.user_id";

if(isset($filter_token["purchase_id"])){
	$query .= " WHERE id_transaction_foreign = '$filter_token[purchase_id]'
				ORDER BY prix_achat DESC";
}
if(isset($filter_token["product_id"])){
	$query .= " WHERE id_produit_adherent = '$filter_token[product_id]'
				ORDER BY prix_achat DESC";
}
if(isset($filter_token["user_id"])){
	$query .= " WHERE id_user_foreign = '$filter_token[user_id]'
				ORDER BY date_achat DESC";
}
if(isset($filter_token["participation_id"])){
	$query .= " WHERE id_user_foreign = (SELECT user_id FROM participations WHERE passage_id = '$filter_token[participation_id]') AND product_size IS NOT NULL";
}
$load = $db->query($query);

$products_list = array();
while($product = $load->fetch(PDO::FETCH_ASSOC)){
	$p = array(
		"id" => $product["id_produit_adherent"],
		"recipient" => $product["id_user_foreign"],
		"transaction_id" => $product["id_transaction_foreign"],
		"date_achat" => $product["date_achat"],
		"product_name" => $product["product_name"],
		"activation" => $product["produit_adherent_activation"],
		"expiration" => max($product["date_prolongee"], $product["date_expiration"]),
		"usage_date" => $product["date_fin_utilisation"],
		"display_expiration" => max($product["date_prolongee"], $product["date_expiration"],$product["date_fin_utilisation"]),
		"extended" => $product["date_prolongee"],
		"remaining_hours" => $product["volume_cours"],
		"price" => $product["prix_achat"],
		"product_size" => $product["product_size"],
		"user" => (isset($product["user"]))?$product["user"]:"Pas d'utilisateur",
		"status" => $product["produit_adherent_actif"],
		"lock_status" => $product["lock_status"],
		"lock_dates" => $product["lock_dates"]
	);
	array_push($products_list, $p);
}
// This fetches products for banners and modals. For the modal, there's no for loop so we send the 1st (and only) entry
if(isset($filter_token["product_id"]))
	echo json_encode($products_list[0]);
else
	echo json_encode($products_list);
?>
