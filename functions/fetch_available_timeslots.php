<?php
require_once "db_connect.php";
include "tools.php";
$db = PDOFactory::getConnection();
// This script is called when modifying the dates of a session group. To estimate how many sessions will be added when adjusting the date of end of recurrence or when will be the end of recurrence when adjusting the number of sessions wanted.

$value_to_compute = $_GET["compute"];

if($value_to_compute == "steps"){
	$current_end = $_GET["current_recurrence_end"];
	$new_end = $_GET["new_recurrence_end"]." 00:00:00";

	// Now, we have to get how many steps are not holidays between the two dates
	$valid_slots = 0;
	if($current_end < $new_end){
		while($current_end <= $new_end){
			$current_end = strtotime($current_end.'+7DAYS');
			$current_end = date("Y-m-d H:i:s", $current_end);
			if($current_end <= $new_end && !isHoliday($db, $current_end))
				$valid_slots++;
		}
	} else if($current_end > $new_end){
		while($current_end >= $new_end){
			$current_end = strtotime($current_end.'-7DAYS');
			$current_end = date("Y-m-d H:i:s", $current_end);
			if($current_end >= $new_end && !isHoliday($db, $current_end))
				$valid_slots--;
		}
	} else {
		$valid_slots = 0;
	}
	echo $valid_slots;
}

if($value_to_compute == "date"){
	$delta_steps = $_GET["delta_steps"];
	$current_end = $_GET["current_recurrence_end"];

	if($delta_steps > 0){
		for($i = 1; $i <= $delta_steps; $i++){
			$current_end = strtotime($current_end.'+7DAYS');
			$current_end = date("Y-m-d H:i:s", $current_end);
			if(isHoliday($db, $current_end)){
				// If the date is a holiday, we skip it by adding another week
				$current_end = strtotime($current_end.'+7DAYS');
				$current_end = date("Y-m-d H:i:s", $current_end);
			}
		}
	} else if($delta_steps < 0){
		for($i = -1; $i >= $delta_steps; $i--){
			$current_end = strtotime($current_end.'-7DAYS');
			$current_end = date("Y-m-d H:i:s", $current_end);
			if(isHoliday($db, $current_end)){
				// If the date is a holiday, we skip it by adding another week
				$current_end = strtotime($current_end.'-7DAYS');
				$current_end = date("Y-m-d H:i:s", $current_end);
			}
		}
	}
	echo $current_end;
}

?>
