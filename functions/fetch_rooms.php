<?php
include "db_connect.php";
session_start();
$db = PDOFactory::getConnection();

$is_admin = $db->query("SELECT COUNT(*) FROM assoc_user_tags aut
				JOIN tags_user tu ON aut.tag_id_foreign = tu.rank_id
				WHERE rank_name = 'Super Admin' AND aut.user_id_foreign = $_SESSION[user_id]")->fetch(PDO::FETCH_COLUMN);

$query = "SELECT * FROM locations l
			LEFT JOIN rooms r ON r.room_location = l.location_id
			LEFT JOIN readers re ON r.room_reader = re.reader_id
			LEFT JOIN colors co ON r.room_color = co.color_id";
if(isset($_SESSION["location"]) && $is_admin != 1)
	$query .= " WHERE location_id = $_SESSION[location]";
$query .= " ORDER BY location_id, room_name ASC";

$load = $db->query($query);

$now = date("Y-m-d H:i:s");
$later = date("Y-m-d H:i:s", strtotime($now.'+60MINUTES'));
$rooms = array();
while($room = $load->fetch(PDO::FETCH_ASSOC)){
	$r = array();
	$r["location_id"] = $room["location_id"];
	$r["location_name"] = $room["location_name"];
	$r["location_address"] = $room["location_address"];
	$r["location_telephone"] = $room["location_telephone"];
	$r["room_id"] = $room["room_id"];
	$r["room_color"] = $room["color_value"];

	// Look up its availability
	if($r["room_id"] != null){
		$r["room_location"] = $room["room_location"];
		$r["room_name"] = $room["room_name"];
		$r["reader_token"] = $room["reader_token"];
		$availability = $db->query("SELECT *, COUNT(*) AS count FROM sessions WHERE session_room = $r[room_id] AND ((session_start >= '$now' AND session_start <= '$later')
	OR (session_start <= '$now' AND session_end >= '$now'))")->fetch(PDO::FETCH_ASSOC);
		if($availability["count"] != 0){
			if($availability["session_start"] < $now){
				$r["availability"] = 0;
				$r["current_session"] = $availability["session_name"];
				$r["current_end"] = $availability["session_end"];
			} else {
				$r["availability"] = 0.5;
				$r["next_session"] = $availability["session_name"];
				$r["next_start"] = $availability["session_start"];
			}
		} else {
			$r["availability"] = 1;
		}
		//$r["availability"] = rand(0,1);
	}
	array_push($rooms, $r);
}

echo json_encode($rooms);
?>
