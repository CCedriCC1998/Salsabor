<?php
require_once "db_connect.php";
include "tools.php";
$db = PDOFactory::getConnection();

$participation_id = $_POST["participation_id"];

$record_detais = $db->query("SELECT user_id, produit_adherent_id FROM participations WHERE passage_id = '$participation_id'")->fetch(PDO::FETCH_ASSOC);
$user_id = $record_detais["user_id"];
$product_id = $record_detais["produit_adherent_id"];

if(isset($_POST["product_id"])){
	$product_id = $_POST["product_id"];
}
if(isset($_POST["user_id"])){
	$user_id = $_POST["user_id"];
}

$today = date_create('now')->format('Y-m-d H:i:s');
$db->query("UPDATE users SET actif = '1', date_last='$today' WHERE user_id='$user_id'");
$db->query("UPDATE participations SET status = 2 WHERE passage_id = '$participation_id'");
logAction($db, "Validation", "participations-".$participation_id);

echo $product_id;
?>
