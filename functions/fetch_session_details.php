<?php
include "db_connect.php";
$db = PDOFactory::getConnection();

$session_id = $_GET['session_id'];
$session_details = $db->query("SELECT s.session_id, session_name, session_start, session_end, room_name, color_value, COUNT(passage_id) AS participations_count, CONCAT(u.user_prenom, ' ', u.user_nom) AS teacher FROM sessions s
							LEFT JOIN users u ON s.session_teacher = u.user_id
							JOIN rooms r ON s.session_room = r.room_id
							JOIN colors co ON r.room_color = co.color_id
							JOIN participations pr ON s.session_id = pr.session_id
							WHERE s.session_id='$session_id'")->fetch(PDO::FETCH_ASSOC);

$tags_session = $db->query("SELECT rank_name, is_mandatory, tag_color FROM assoc_session_tags us
							JOIN tags_session ts ON us.tag_id_foreign = ts.rank_id
							WHERE session_id_foreign = '$session_id'
							ORDER BY tag_color DESC")->fetchAll(PDO::FETCH_ASSOC);

$s = array(
	"id" => $session_details["session_id"],
	"title" => $session_details["session_name"],
	"start" => $session_details["session_start"],
	"end" => $session_details["session_end"],
	"teacher" => $session_details["teacher"],
	"room" => $session_details["room_name"],
	"color" => "#".$session_details["color_value"],
	"participations_count" => $session_details["participations_count"],
	"tags" => $tags_session
);

echo json_encode($s);
?>
