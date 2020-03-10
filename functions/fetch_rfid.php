<?php
require_once "../functions/db_connect.php";
$db = PDOFactory::getConnection();

$time = date("Y-m-d H:i:s", strtotime("-10 seconds"));

$query = "SELECT user_rfid FROM participations WHERE status = 1 AND passage_date >= '$time'";

$rfid = $db->query($query);

echo $rfid->fetch(PDO::FETCH_COLUMN);
?>
