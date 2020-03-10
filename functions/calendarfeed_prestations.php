<?php
require_once "db_connect.php";
// Feeding prestations to the calendar
$db = PDOFactory::getConnection();
$fetch_start = $_GET["fetch_start"];
$fetch_end = $_GET["fetch_end"];
//$filters = $_GET["filters"];

try{
	// Fetching prestations
	$feed = $db->prepare("SELECT * FROM prestations WHERE prestation_start > '$fetch_start' AND prestation_end < '$fetch_end'");
	$feed->execute();
	$prestations = array();

	while($prestation = $feed->fetch()){
		$p = array(
			"id" => $prestation["prestation_id"],
			"address" => $prestation["prestation_address"],
			"start" => $prestation["prestation_start"],
			"end" => $prestation["prestation_end"],
			"color" => "FF993D",
			"type" => "prestation",
			"allDay" => false
		);
		array_push($prestations, $p);
	}
	echo json_encode($prestations);
} catch(PDOException $e){
	echo $e->getMessage();
}
?>
