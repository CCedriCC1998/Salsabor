<?php
include "db_connect.php";
$db = PDOFactory::getConnection();

$participation_id = $_GET["participation_id"];

$load = $db->query("SELECT *, pa.date_activation AS produit_adherent_activation
							FROM participations pr
							JOIN sessions s ON pr.session_id = s.session_id
							LEFT JOIN produits_adherents pa ON pr.produit_adherent_id = pa.id_produit_adherent
							LEFT JOIN produits p ON pa.id_produit_foreign = p.product_id
							LEFT JOIN transactions t ON pa.id_transaction_foreign = t.id_transaction
							WHERE pr.passage_id='$participation_id'
							ORDER BY session_start DESC");

$details = $load->fetch(PDO::FETCH_ASSOC);

$p = array();
$p["id"] = $details["passage_id"];
$p["date"] = $details["session_start"];
$p["cours_name"] = $details["session_name"];
$p["hour_start"] = $details["session_start"];
$p["hour_end"] = $details["session_end"];
$p["product"] = $details["produit_adherent_id"];
$p["achat"] = $details["date_achat"];
$p["product_activation"] = $details["produit_adherent_activation"];
$p["product_expiration"] = max($details["date_expiration"], $details["date_fin_utilisation"], $details["date_prolongee"]);
$p["product_name"] = $details["product_name"];
echo json_encode($p);
?>
