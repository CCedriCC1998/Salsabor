<?php
include "db_connect.php";
$db = PDOFactory::getConnection();

$participation_id = $_POST["participation_id"];

$load = $db->query("SELECT produit_adherent_id FROM participations WHERE passage_id = '$participation_id'")->fetch(PDO::FETCH_ASSOC);

$delete = $db->query("DELETE FROM participations WHERE passage_id='$participation_id'");

echo $load["produit_adherent_id"];
?>
