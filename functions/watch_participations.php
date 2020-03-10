<?php
session_start();
require_once "db_connect.php";
$db = PDOFactory::getConnection();

$nombreParticipations = $db->query("SELECT passage_id FROM participations pr
								LEFT JOIN sessions s ON pr.session_id = s.session_id
								LEFT JOIN rooms r ON s.session_room = r.room_id
								LEFT JOIN locations l ON r.room_location = l.location_id
								WHERE (pr.status != 2 OR (pr.status = 2 AND (produit_adherent_id IS NULL OR produit_adherent_id = '' OR produit_adherent_id = 0)))
								AND location_id = $_SESSION[location]
								AND pr.archived = 0")->rowCount();
echo $nombreParticipations;
?>
