<?php
require_once "db_connect.php";
// Feeding sessions to the calendar
$db = PDOFactory::getConnection();
$fetch_start = $_GET["fetch_start"];
$fetch_end = $_GET["fetch_end"];
$filters = $_GET["filters"];
try{
	// Fetching sessions
	$calendar = $db->prepare("SELECT session_id, session_name, room_id, session_start, session_end, color_value FROM sessions s
							JOIN rooms r ON s.session_room = r.room_id
							JOIN colors co ON r.room_color = co.color_id
							WHERE session_start > '$fetch_start' AND session_end < '$fetch_end'
							AND room_id IN (".implode(",", array_map("intval", $filters)).")");
	$calendar->execute();
	$events = array();

	while($row_calendar = $calendar->fetch(PDO::FETCH_ASSOC)){
		$e = array();
		$e['id'] = $row_calendar['session_id'];
		$e["title"] = $row_calendar["session_name"];
		$e['lieu'] = $row_calendar['room_id'];
		$e['start'] = $row_calendar['session_start'];
		$e['end'] = $row_calendar['session_end'];
		$e['color'] = $row_calendar['color_value'];
		$e['type'] = 'cours';
		// Fullcalendar.js parameter
		$e['allDay'] = false;

		array_push($events, $e);
	}

	echo json_encode($events);
	exit();
} catch(PDOException $e) {
	echo $e->getMessage();
}
?>
