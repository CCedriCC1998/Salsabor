<?php
require_once "db_connect.php";
$db = PDOFactory::getConnection();

$id = $_POST["id"];

$feed = $db->prepare("SELECT * FROM produits WHERE product_id = ?");
$feed->bindParam(1, $id, PDO::PARAM_INT);
$feed->execute();
echo json_encode($feed->fetch(PDO::FETCH_ASSOC));
?>
