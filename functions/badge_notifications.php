<?php
session_start();
require_once "db_connect.php";
$db = PDOFactory::getConnection();

$unread = $db->query("SELECT * FROM team_notifications WHERE (notification_recipient IS NULL OR notification_recipient = 0 OR notification_recipient = '$_SESSION[user_id]') AND notification_state = 1")->rowCount();
echo $unread;
?>
