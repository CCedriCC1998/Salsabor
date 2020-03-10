<?php
require_once "db_connect.php";
$db = PDOFactory::getConnection();

$taillePanier = $db->query("SELECT * FROM panier")->rowCount();
echo $taillePanier;
?>