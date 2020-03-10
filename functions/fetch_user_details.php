<?php
include "db_connect.php";
$db = PDOFactory::getConnection();

$user_id = $_GET["user_id"];

$stmt = $db->query("SELECT date_naissance, date_inscription FROM users u
					WHERE user_id = $user_id")->fetch();

$user_details = array(
	"date_naissance" => $stmt["date_naissance"],
	"date_inscription" => $stmt["date_inscription"],
);

echo json_encode($user_details);
?>
