<?php
require_once "db_connect.php";
require_once "tools.php";
$db = PDOFactory::getConnection();

$user_id = $_POST["user_id"];
$session_id = $_POST["session_id"];
$values = array();

$user_details = $db->query("SELECT user_rfid FROM users WHERE user_id = $user_id")->fetch(PDO::FETCH_ASSOC);

$values["user_id"] = $user_id;
$values["user_rfid"] = $user_details["user_rfid"];

$reader_token = $db->query("SELECT reader_token FROM sessions s
							JOIN rooms r ON s.session_room = r.room_id
							LEFT JOIN readers re ON r.room_reader = re.reader_id
							WHERE session_id = '$session_id'")->fetch(PDO::FETCH_COLUMN);

$values["passage_date"] = date("d/m/Y H:i:s");
$values["room_token"] = $reader_token;
$values["session_id"] = $session_id;

addParticipationBeta($values);
?>
