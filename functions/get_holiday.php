<?php
require_once "db_connect.php";
$db = PDOFactory::getConnection();

// Tarifs
$queryHolidays = $db->query("SELECT * FROM holidays ORDER BY holiday_date ASC");
$result = array();
while($holiday = $queryHolidays->fetch(PDO::FETCH_ASSOC)){
	$h = array();
	$h["id"] = $holiday["holiday_id"];
	$h["date"] = $holiday["holiday_date"];
	array_push($result, $h);
}
echo json_encode($result);
?>
