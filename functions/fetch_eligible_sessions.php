<?php
include "db_connect.php";
$db = PDOFactory::getConnection();

$participation_id = $_GET["participation_id"];
// This script will fetch the sessions that WERE available for the participation

$participation = $db->query("SELECT * FROM participations WHERE passage_id = '$participation_id'")->fetch(PDO::FETCH_ASSOC);

$compare_start = date("Y-m-d H:i:s", strtotime($participation["passage_date"].'-90MINUTES'));
$compare_end = date("Y-m-d H:i:s", strtotime($participation["passage_date"].'+90MINUTES'));
$sessions = $db->query("SELECT * FROM sessions s
						JOIN rooms r ON s.session_room = r.room_id
						JOIN users u ON s.session_teacher = u.user_id
						WHERE session_start BETWEEN '$compare_start' AND '$compare_end'");

$session_list = array();
while($details = $sessions->fetch(PDO::FETCH_ASSOC)){
	$s = array();
	$s["id"] = $details["session_id"];
	$s["title"] = $details["session_name"];
	$s["start"] = $details["session_start"];
	$s["end"] = $details["session_end"];
	$s["duration"] = $details["session_duration"];
	$s["room"] = $details["room_name"];
	$s["teacher"] = $details["user_prenom"]." ".$details["user_nom"];
	array_push($session_list, $s);
}

echo json_encode($session_list);
?>
