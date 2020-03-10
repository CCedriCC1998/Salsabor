<?php
require_once "db_connect.php";
$db = PDOFactory::getConnection();

$date = new DateTime('now');
$today = $date->format("Y-m-d");
$nombreEcheancesDues = $db->query("SELECT * FROM produits_echeances WHERE date_paiement IS NULL AND date_echeance <= '$today'")->rowCount();
echo $nombreEcheancesDues;
?>
