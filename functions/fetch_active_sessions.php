<?php
session_start();
include "db_connect.php";
$db = PDOFactory::getConnection();

$compare_start = date("Y-m-d H:i:s");
$compare_end = date("Y-m-d H:i:s", strtotime($compare_start.'+90MINUTES'));
$query = "SELECT * FROM sessions s
			JOIN rooms r ON s.session_room = r.room_id
			JOIN locations l ON r.room_location = l.location_id
			LEFT JOIN users u ON s.session_teacher = u.user_id
			WHERE session_opened != 0";
if(isset($_SESSION["location"])){
	$query .= " AND location_id = $_SESSION[location]";
}
if(isset($_GET["fetched"])){
	$fetched = $_GET["fetched"];
	$query .= " AND session_id NOT IN ('".implode($fetched, "','")."')";
}
$query .= " ORDER BY session_start ASC, session_id ASC";
$load = $db->query($query);

$sessionsList = array();
while($details = $load->fetch(PDO::FETCH_ASSOC)){
	$s = array(
		"id" => $details["session_id"],
		"title" => $details["session_name"],
		"start" => $details["session_start"],
		"end" => $details["session_end"],
		"duration" => $details["session_duration"],
		"room" => $details["room_name"],
		"location" => $details["room_location"],
		"teacher" => $details["user_prenom"]." ".$details["user_nom"],
		"labels" => array()
	);
	// Tags
	$labels = $db->query("SELECT * FROM assoc_session_tags us
						JOIN tags_session ts ON us.tag_id_foreign = ts.rank_id
						WHERE session_id_foreign = '$s[id]'
						ORDER BY tag_color DESC");
	while($label = $labels->fetch(PDO::FETCH_ASSOC)){
		$l = array(
			"entry_id" => $label["entry_id"],
			"tag_color" => $label["tag_color"],
			"rank_name" => $label["rank_name"],
			"is_mandatory" => $label["is_mandatory"]
		);
		array_push($s["labels"], $l);
	}
	array_push($sessionsList, $s);
}

echo json_encode($sessionsList);
?>
