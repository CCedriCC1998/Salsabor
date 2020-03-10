<?php
require_once "db_connect.php";
$db = PDOFactory::getConnection();

$read = $db->query("UPDATE team_notifications SET notification_state = '0' WHERE notification_state = '1'");
?>
