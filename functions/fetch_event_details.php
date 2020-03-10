<?php
include "db_connect.php";
$db = PDOFactory::getConnection();

$event_id = $_GET['event_id'];
$event_details = $db->query("SELECT *, CONCAT(u.user_prenom, ' ', u.user_nom) AS handler FROM events e
							LEFT JOIN users u ON e.event_handler = u.user_id
							WHERE event_id='$event_id'")->fetch(PDO::FETCH_ASSOC);

$e = array(
	"id" => $event_details["event_id"],
	"handler" => ($event_details["handler"]!=NULL)?$event_details["handler"]:"Pas d'organisateur",
	"address" => ($event_details["event_address"]!=NULL)?$event_details["event_address"]:"Pas d'adresse enregistrée",
	"description" => $event_details["event_description"]
);

echo json_encode($e);
?>
