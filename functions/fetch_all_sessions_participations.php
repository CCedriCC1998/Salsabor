<?php
include "db_connect.php";
$db = PDOFactory::getConnection();

$session_group_id = $_GET["session_group_id"];

$feed = $db->prepare("SELECT s.session_id, session_start, COUNT(passage_id) AS crowd FROM sessions s
					LEFT JOIN participations pr ON pr.session_id = s.session_id
					WHERE session_group = ?
					GROUP BY s.session_id");
$feed->bindParam(1, $session_group_id, PDO::PARAM_INT);
$feed->execute();

$stats = array();

while($row = $feed->fetch(PDO::FETCH_ASSOC)){
	$date = new DateTime($row["session_start"]);
	$date = $date->format("Y-m-d");
	$s = array(
		"date" => $date,
		"participations" => $row["crowd"]
	);
	array_push($stats, $s);
}
echo json_encode($stats);

?>
