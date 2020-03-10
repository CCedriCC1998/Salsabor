<?php
session_start();
include "db_connect.php";
$db = PDOFactory::getConnection();

// This file is called when loading all the "yet to be banked" maturities

//date permet de déterminer la date du jour puis déterminer la date limite pour l'échéance
$date = new DateTime('now');
$year = $date->format('Y');
$month = $date->format('m');
$day = $date->format('d');
if($day >= 1 && $day <= 8){
	$maturityDay = 10;
} else if($day >= 9 && $day <= 18){
	$maturityDay = 20;
} else if($day >= 19 && $day <= 28){
	$maturityDay = 30;
}else{
	$maturityDay = 10;
	$month++;
	if($month > 12){
		$year++;
		$month = 1;
	}
}
$time = new DateTime($year.'-'.$month.'-'.$maturityDay);
$maturityTime = $time->format('Y-m-d');

$today = $date->format("Y-m-d");

$region = $_GET["region"];

$query = "SELECT * FROM produits_echeances pe
						JOIN transactions t ON pe.reference_achat = t.id_transaction
						LEFT JOIN users u ON t.transaction_handler = u.user_id
						LEFT JOIN locations l ON u.user_location = l.location_id
						WHERE ((date_echeance <= '$maturityTime' AND date_echeance > '$today' AND (methode_paiement !='Carte Bancaire' OR methode_paiement IS NULL))
						OR (date_echeance <= '$today' AND date_encaissement IS NULL))";

if($region == "1"){
	$query .= " AND (location_id = $_SESSION[location] OR location_id IS NULL)";
}
$query .= "ORDER BY date_echeance DESC";

$load = $db->query($query);

$maturities = array();
while($details = $load->fetch(PDO::FETCH_ASSOC)){
	$m = array(
		"id" => $details["produits_echeances_id"],
		"transaction_id" => $details["reference_achat"],
		"transaction_user" => $details["payeur_transaction"],
		"payer" => $details["payeur_echeance"],
		"date" => $details["date_echeance"],
		"price" => $details["montant"],
		"method" => ($details["methode_paiement"]!=NULL)?$details["methode_paiement"]:"En attente",
		"reception_status" => $details["echeance_effectuee"],
		"date_reception" => $details["date_paiement"],
		"bank_status" => $details["statut_banque"],
		"date_bank" => $details["date_encaissement"],
		"lock_montant" => $details["lock_montant"],
		"category_TVA" => "-"
	);
	array_push($maturities, $m);
}
echo json_encode($maturities);
?>
