<?php
session_start();
include "db_connect.php";
$db = PDOFactory::getConnection();

$archive = $_GET["archive"];

$load = $db->query("SELECT u.user_id, user_prenom, user_nom, COUNT(pr.passage_id) AS count
					FROM participations pr
					LEFT JOIN readers re ON pr.room_token = re.reader_token
					LEFT JOIN users u ON pr.user_id = u.user_id
					LEFT JOIN sessions s ON pr.session_id = s.session_id
					LEFT JOIN rooms r ON s.session_room = r.room_id
					LEFT JOIN locations l ON r.room_location = l.location_id
					WHERE (pr.status != 2 OR (pr.status = 2 AND (produit_adherent_id IS NULL OR produit_adherent_id = '' OR produit_adherent_id = 0)))
					AND location_id = $_SESSION[location]
					AND pr.archived = $archive
					GROUP BY user_id
					ORDER BY user_nom ASC");

$user_list = array();
while($details = $load->fetch()){
	$u = array();
	$u["user_id"] = $details["user_id"];
	$u["user"] = $details["user_prenom"]." ".$details["user_nom"];
	$u["count"] = $details["count"];
	array_push($user_list, $u);
}

echo json_encode($user_list);
?>
