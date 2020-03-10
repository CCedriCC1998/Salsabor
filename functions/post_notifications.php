<?php
include "db_connect.php";
include "tools.php";

$db = PDOFactory::getConnection();
$token = $_POST["token"];
$target = $_POST["target"];
if(is_string($_POST["recipient"])){
	$recipient = solveAdherentToId($_POST["recipient"]);
}
$today = date("Y-m-d H:i:s");

postNotification($token, $target, $recipient, $today);
?>
