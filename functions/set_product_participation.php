<?php
include "db_connect.php";
$db = PDOFactory::getConnection();

$participation_id = $_POST["participation_id"];
$product_id = $_POST["product_id"];

$r = array();

if($product_id == -1){
	$status = '3';
	$assign = $db->query("UPDATE participations SET produit_adherent_id = NULL, status = '$status' WHERE passage_id = '$participation_id'");
	$r["product_name"] = "-";
} else {
	$load = $db->query("SELECT product_name, pa.actif AS produit_adherent_actif FROM produits p
					JOIN produits_adherents pa ON pa.id_produit_foreign = p.product_id WHERE id_produit_adherent = '$product_id'")->fetch(PDO::FETCH_ASSOC);
	if($load["produit_adherent_actif"] == '2'){
		$status = '3';
	} else {
		$status = '0';
	}
	$assign = $db->query("UPDATE participations SET produit_adherent_id = '$product_id', status = '$status' WHERE passage_id = '$participation_id'");
	$r["product_name"] = $load["product_name"];
}

$r["status"] = $status;

echo json_encode($r);
?>
