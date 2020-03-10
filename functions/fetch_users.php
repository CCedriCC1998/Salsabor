<?php
require_once 'db_connect.php';
$db = PDOFactory::getConnection();

$query = $db->query("SELECT user_nom FROM users");

echo json_encode($query->fetchAll(PDO::FETCH_COLUMN));
?>
