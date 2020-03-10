<?php
require_once "../functions/db_connect.php";
$db = PDOFactory::getConnection();
// Vérifie si l'emplacement est libre
$heure_debut = $_POST['heure_debut'];
$heure_fin = $_POST['heure_fin'];
$lieu = $_POST['lieu'];

// Vérification de la récurrence
$recurring = $_POST['recurring'];

/** Dans le cas où il n'y a pas de récurrence (cours et réservations) **/
if($recurring == 'false'){
	/** Conversion de la date **/
	$date_debut = $_POST['date_debut']." ".$heure_debut;
	$date_fin = $_POST['date_debut']." ".$heure_fin;

	$findResa = $db->prepare('SELECT COUNT(*) FROM sessions WHERE session_room=? AND ((session_start<=? AND session_end>?) OR (session_start<? AND session_end>=?))');
	$findResa->bindValue(1, $lieu);
	$findResa->bindValue(2, $date_debut);
	$findResa->bindValue(3, $date_debut);
	$findResa->bindValue(4, $date_fin);
	$findResa->bindValue(5, $date_fin);
	$findResa->execute();
	echo $res = $findResa->fetchColumn();
	} else {
	/** Cas des cours récurrents **/
	$frequence_repetition = $_POST['frequence_repetition'];
	$date_debut = $_POST['date_debut'];
	$date_fin = $_POST['date_debut'];
	$start = $date_debut." ".$heure_debut;
	$end = $date_debut." ".$heure_fin;
	(int)$nombre_repetitions = (strtotime($date_fin) - strtotime($date_debut))/(86400 * $frequence_repetition)+1;
	$res = 0;
	for($i = 1; $i < $nombre_repetitions; $i++){
		$findResa = $db->prepare('SELECT COUNT(*) FROM sessions WHERE session_room=? AND ((session_start<=? AND session_end>?) OR (session_start<? AND session_end>=?))');
		$findResa->bindValue(1, $lieu);
		$findResa->bindValue(2, $start);
		$findResa->bindValue(3, $start);
		$findResa->bindValue(4, $end);
		$findResa->bindValue(5, $end);
		$findResa->execute();
		$res += $findResa->fetchColumn();
		$date_debut = strtotime($start.'+'.$frequence_repetition.'DAYS');
		$date_fin = strtotime($end.'+'.$frequence_repetition.'DAYS');
		$start = date("Y-m-d H:i:s", $date_debut);
		$end = date("Y-m-d H:i:s", $date_fin);
	}
	echo $res;
}
?>
