<?php
include "db_connect.php";
$db = PDOFactory::getConnection();

$product_id = $_GET["product_id"];

$product_details = $db->query("SELECT product_size, pa.date_activation AS produit_adherent_activation, date_prolongee, date_fin_utilisation, date_expiration FROM produits_adherents pa
						JOIN produits p
							ON pa.id_produit_foreign = p.product_id
						JOIN transactions t
							ON pa.id_transaction_foreign = t.id_transaction
						WHERE id_produit_adherent = '$product_id'")->fetch(PDO::FETCH_ASSOC);

$participations = $db->query("SELECT * FROM participations pr
							JOIN sessions s ON pr.session_id = s.session_id
							WHERE produit_adherent_id = '$product_id'
							AND (status = 0 OR status = 2)
							ORDER BY session_start ASC");

$remaining_hours = $product_details["product_size"];
$date_fin_utilisation = max($product_details["date_prolongee"], $product_details["date_expiration"], $product_details["date_fin_utilisation"]);
$participations_list = array();

while($participation = $participations->fetch(PDO::FETCH_ASSOC)){
	$p = array();
	$p["id"] = $participation["passage_id"];
	$p["title"] = $participation["session_name"];
	$p["start"] = $participation["session_start"];
	$p["end"] = $participation["session_end"];
	$p["duration"] = $participation["session_duration"];

	if($p["start"] > $date_fin_utilisation || $p["start"] < $product_details["produit_adherent_activation"] || ($remaining_hours <= 0 && $product_details["product_size"] != "0")){
		$p["valid"] = "2"; // The session happened after the product expired or before it activated or the product didn't have any hours left.
		if($p["start"] > $date_fin_utilisation){
			$p["reason"] = "Start (".$p["start"].") is after the expiration date (".$date_fin_utilisation.")";
		}
		if($p["start"] < $product_details["produit_adherent_activation"]){
			$p["reason"] = "Start is before the activation date (".$product_details["produit_adherent_activation"].")";
		}
	} else {
		$p["valid"] = "1";
	}
	$p["status"] = $participation["status"];
	array_push($participations_list, $p);

	$remaining_hours -= floatval($participation["session_duration"]);
}

echo json_encode($participations_list);
?>
