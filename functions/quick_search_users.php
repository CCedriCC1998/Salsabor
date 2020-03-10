<?php
include "db_connect.php";
$db = PDOFactory::getConnection();

$name = $_GET["user"];

$query = "SELECT * FROM (
	SELECT user_id, user_rfid, photo, CONCAT(user_prenom, ' ', user_nom) as fullname FROM users) base
	WHERE fullname LIKE ?";
if(isset($_GET["session_id"]))
	$query .= " AND user_id NOT IN (SELECT user_id FROM participations WHERE session_id = $_GET[session_id])";
$query .= " ORDER BY fullname ASC";

$stmt = $db->prepare($query);
$stmt->bindParam(1, htmlspecialchars('%'.$name.'%'), PDO::PARAM_STR);
$stmt->execute();

$users = array();
while($user = $stmt->fetch(PDO::FETCH_ASSOC)){
	array_push($users, $user);
}

echo json_encode($users);
