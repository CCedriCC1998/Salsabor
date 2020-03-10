<?php
require_once "db_connect.php";
include "tools.php";
$db = PDOFactory::getConnection();

$room_location = $_POST["room_location"];
$room_name = htmlspecialchars($_POST["room_name"]);

try{
	$db->beginTransaction();
	$stmt = $db->prepare("INSERT INTO rooms(room_location, room_name) VALUES(?, ?)");
	$stmt->bindParam(1, $room_location, PDO::PARAM_INT);
	$stmt->bindParam(2, $room_name, PDO::PARAM_STR);
	$stmt->execute();
	echo $db->lastInsertId();
	logAction($db, "Ajout", "rooms-".$db->lastInsertId());
	$db->commit();
} catch(PDOException $e){
	$db->rollBack();
	echo $e->getMessage();
}
?>
