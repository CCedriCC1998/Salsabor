<?php
require_once "db_connect.php";
include "tools.php";
$db = PDOFactory::getConnection();

/** This code will set the status of the record to 1 or 3 again, depending on whether there are products for the user, and delete the participation. Once it's done, we'll "Compute" the product to refresh its data.**/

$participation_id = $_POST["participation_id"];

if(isset($_POST["session_id"])){
	$session_id = $_POST["session_id"];
}
if(isset($_POST["user_id"])){
	$user_id = $_POST["user_id"];
}
if(!isset($_POST["user_id"]) || !isset($_POST["session_id"])){
	$record_detais = $db->query("SELECT user_id, session_id, produit_adherent_id FROM participations WHERE passage_id = '$participation_id'")->fetch(PDO::FETCH_ASSOC);
	$user_id = $record_detais["user_id"];
	$session_id = $record_detais["session_id"];
	$product_id = $record_detais["produit_adherent_id"];
}

$s = array();
if(!isset($product_id) || $product_id == null || $product_id == '0'){
	$status = '3';
	$s["product_id"] = 'NULL';
} else {
	$status = '0';
	$s["product_id"] = $product_id;
}

// Update the record as handled with the correct session and status
$db->query("UPDATE participations SET status = '$status' WHERE passage_id = '$participation_id'");
logAction($db, "Invalidation", "participations-".$participation_id);

$s["status"] = $status;
echo json_encode($s);
?>
