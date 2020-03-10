<?php
require_once "db_connect.php";
$db = PDOFactory::getConnection();

/** Forcefully deactivates a product **/
$product_id = $_POST["product_id"];

$db->query("UPDATE produits_adherents SET actif = 2 WHERE id_produit_adherent = '$product_id'");
?>
