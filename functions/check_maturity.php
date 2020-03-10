<?php
require_once "db_connect.php";
$db = PDOFactory::getConnection();

/** Marks a maturity as paiement recieved **/

$id = $_POST["maturity_id"];
$date_reception = date_create("now")->format("Y-m-d");

$update = $db->query("UPDATE produits_echeances SET echeance_effectuee = '1', date_paiement = '$date_reception' WHERE produits_echeances_id='$id'");

echo $date_reception;

?>
