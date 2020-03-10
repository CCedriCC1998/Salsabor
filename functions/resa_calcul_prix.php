<?php
require_once "../functions/db_connect.php";
$db = PDOFactory::getConnection();
$prestation = $_POST['prestation'];
$heure_debut = $_POST['heure_debut'];
$heure_fin = $_POST['heure_fin'];
$lieu = $_POST['lieu'];
$cumulatedPrice = 0;

/** Conversion de la date **/
$date = date_create($_POST['date_debut'])->format('N');
if($date <= 5){
	$plage_resa = 1;
}
else if($date == 6){
	$plage_resa = 2;
}
else $plage_resa = 3;

	$duration = (strtotime($heure_fin) - strtotime($heure_debut))/1800;
	for($i = 0; $i < $duration; $i++){
		$heure_debut_decalee = date("H:i:s", strtotime($heure_debut) + ($i * 30 * 60));
		$findHours = $db->prepare('SELECT * FROM tarifs_reservations JOIN plages_reservations ON (plage_resa=plages_reservations.plages_resa_id) WHERE type_prestation=? AND plages_resa_jour=? AND plages_resa_debut<=? AND plages_resa_fin>? AND lieu_resa=?');
		$findHours->bindValue(1, $prestation);
		$findHours->bindValue(2, $plage_resa);
		$findHours->bindValue(3, $heure_debut_decalee);
		$findHours->bindValue(4, $heure_debut_decalee);
		$findHours->bindValue(5, $lieu);
		$findHours->execute();
		$res = $findHours->fetch(PDO::FETCH_ASSOC);
		$cumulatedPrice += 0.5 * $res['prix_resa'];
	}
   echo $cumulatedPrice;
?>