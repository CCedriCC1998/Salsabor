<?php
require_once "db_connect.php";
$db = PDOFactory::getConnection();

$rfid = $_POST["rfid"];

$delete = $db->prepare('DELETE FROM participations WHERE user_rfid = ? AND status=1');
$delete->bindParam(1, $rfid);
$delete->execute();
?>
