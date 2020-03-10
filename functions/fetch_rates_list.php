<?php
include "db_connect.php";
$db = PDOFactory::getConnection();

$stmt = $db->query("SELECT DISTINCT rate_id, rate_title FROM teacher_rates");

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
