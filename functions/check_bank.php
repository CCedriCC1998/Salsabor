<?php
require_once "db_connect.php";
$db = PDOFactory::getConnection();

/** Marks a maturity as paiement banked **/

$id = $_POST["maturity_id"];
$date_reception = date_create("now")->format("Y-m-d");

$update = $db->query("UPDATE produits_echeances SET statut_banque = 1, date_encaissement = '$date_reception' WHERE produits_echeances_id='$id'");

echo $date_reception;

?>
