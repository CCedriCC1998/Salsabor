<?php
$period_start = $_GET['debut'];
$period_end = $_GET['fin'];
$user_nom = $_GET['nom'];

require_once '../functions/db_connect.php';
$db = PDOFactory::getConnection();

$participation = $db->query("SELECT pr.passage_id,pr.user_rfid,u.user_prenom,u.user_nom,u.user_id,pr.passage_date,
                             s.session_name,s.session_teacher,s.session_start,s.session_end,
                             r.room_name,pr.room_token,pr.produit_adherent_id
                      FROM participations pr
                      LEFT JOIN sessions s ON pr.session_id = s.session_id
                      LEFT JOIN rooms r ON s.session_room = r.room_id
                      LEFT JOIN users u ON u.user_id = pr.user_id
                      WHERE s.session_start BETWEEN '$period_start' AND '$period_end' AND
                      u.user_nom LIKE '$user_nom'
                      ORDER BY s.session_start DESC");

$data = $participation->fetchAll();

require '../functions/export_csv.php';
CSV::export($data,'Participations '.$user_nom.'');

?>
