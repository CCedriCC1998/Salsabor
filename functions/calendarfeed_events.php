<?php
require_once "db_connect.php";
// Feeding events to the calendar
$db = PDOFactory::getConnection();
$fetch_start = $_GET["fetch_start"];
$fetch_end = $_GET["fetch_end"];
//$filters = $_GET["filters"];

try{
	// Fetching events
	$feed = $db->prepare("SELECT * FROM events WHERE event_start > '$fetch_start' AND event_end < '$fetch_end'");
	$feed->execute();
	$events = array();

	while($event = $feed->fetch(PDO::FETCH_ASSOC)){
		$e = array(
			"id" => $event["event_id"],
			"title" => $event["event_name"],
			"address" => $event["event_address"],
			"start" => $event["event_start"],
			"end" => $event["event_end"],
			"color" => "CE7F77",
			"type" => "event",
			"allDay" => false
		);
		array_push($events, $e);
	}
	echo json_encode($events);
} catch(PDOException $e){
	echo $e->getMessage();
}
?>
