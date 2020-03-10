<?php
include "db_connect.php";
$db = PDOFactory::getConnection();

$location_id = $_GET["location_id"];

$stmt = $db->query("SELECT room_id, room_name, color_value, location_name FROM rooms r
					JOIN locations l ON r.room_location = l.location_id
					JOIN colors c ON r.room_color = c.color_id
					WHERE room_location = $location_id
					ORDER BY room_id ASC");

$rooms = array();
while($room = $stmt->fetch(PDO::FETCH_ASSOC)){
	$r = array(
		"room_id" => $room["room_id"],
		"name" => $room["room_name"],
		"color" => $room["color_value"],
		"location_name" => $room["location_name"],
		"location_id" => $location_id
	);
	array_push($rooms, $r);
}
echo json_encode($rooms);
?>
