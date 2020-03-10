<?php
include "db_connect.php";
$db = PDOFactory::getConnection();

$user_id = $_GET["user_id"];

// This file has to get everything related to a user when creating a task, so we can link it to a maturity, a product, a transaction or whatever.

$loadProducts = $db->query("SELECT CONCAT('PRD-',id_produit_adherent) AS target, product_name
					FROM produits_adherents pa
					JOIN produits p ON pa.id_produit_foreign = p.product_id
					WHERE id_user_foreign = $user_id");

$loadTransactions = $db->query("SELECT id_transaction, CONCAT('TRA-',id_transaction) AS target
								FROM transactions
								WHERE payeur_transaction = $user_id");

$target_list = array();

while($details = $loadProducts->fetch(PDO::FETCH_ASSOC)){
	$t = array();
	$t["id"] = $details["target"];
	$t["name"] = $details["product_name"]." [".$details["target"]."]";
	array_push($target_list, $t);
}
while($details = $loadTransactions->fetch(PDO::FETCH_ASSOC)){
	$t = array();
	$t["id"] = $details["target"];
	$t["name"] = "Transaction ".$details["id_transaction"]." [".$details["target"]."]";
	array_push($target_list, $t);
}
echo json_encode($target_list);
?>
