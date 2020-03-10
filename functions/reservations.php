<?php
/** WARNING : THIS WHOLE FILE IS DEPRECATED DUE TO SOME DATA TABLE HAVING BEEN DELETED -Jan 2017- **/
require_once "db_connect.php";
include "librairies/fpdf/fpdf.php";
include "librairies/fpdi/fpdi.php";
require_once "tools.php";

function outputBookingInvoice(){
	$db = PDOFactory::getConnection();

	$user = $_POST["identite_nom"];

	$date_debut = $_POST['date_debut']." ".$_POST['heure_debut'];
	$date_fin = $_POST['date_debut']." ".$_POST['heure_fin'];
	$lieu = $_POST['lieu'];

	$unite = (strtotime($_POST['heure_fin']) - strtotime($_POST['heure_debut']))/3600;
	$prix = $_POST['prix_resa'];

	$priorite = $_POST['priorite'];
	$paiement = $_POST['paiement'];

	// Obtention de la personne qui a fait la réservation
	$user_id = solveAdherentToId($_POST["identite_nom"]);
	$adherent = $db->query("SELECT * FROM users WHERE user_id = $user_id")->fetch(PDO::FETCH_ASSOC);
	$salle = getLieu($lieu);

	/**** PDF ****/
	$pdf = new FPDI();
	$pdf->AddPage();
	$pdf->SetSourceFile("librairies/Salsabor-resa-facture.pdf");
	$tplIdx = $pdf->importPage(1);
	$pdf->useTemplate($tplIdx, 0, 0, 210);
	$pdf->SetFont('Arial', '', 10);
	// Phrase de début
	$pdf->setXY(21, 49);
	$infos = $adherent['user_prenom']." ".$adherent['user_nom'];
	$infos = iconv('UTF-8', 'windows-1252', $infos);
	$pdf->Write(0, $infos);
	$pdf->SetFont('Arial', '', 11);
	// Informations
	$pdf->setXY(10, 74);
	$infos = $adherent['user_prenom']." ".$adherent['user_nom']."\n".$adherent['rue']." - ".$adherent['code_postal']." ".$adherent['ville']."\n".$adherent['mail']."\nTél : ".$adherent['telephone'];
	$infos = iconv('UTF-8', 'windows-1252', $infos);
	$pdf->MultiCell(0, 7, $infos);

	// Réservation
	$pdf->setXY(10, 131);
	if($priorite == 0) {
		$textPriorite = 'libre (Attention : une réservation libre peut être supprimée sans préavis au profit d\'un cours)';
	} else $textPriorite = 'payée';

	// Obtention de l'intitulé du type de prestation
	// DEPRECATED
	$nomPresa = $db->query('SELECT prestations_name FROM prestations WHERE prestations_id='.$prestation)->fetch(PDO::FETCH_ASSOC);

	$reservation = $nomPresa['prestations_name']."\nLe ".date_create($date_debut)->format('d/m/Y')." de ".date_create($date_debut)->format('H:i')." à ".date_create($date_fin)->format('H:i')."\nRéservation ".$textPriorite."\n".$salle['room_name'];
	$reservation = iconv('UTF-8', 'windows-1252', $reservation);
	$pdf->MultiCell(0, 7, $reservation);

	if($priorite == 1){
		$pdf->setXY(170, 165);
		$pdf->setFont('Arial', 'B', 18);
		$pdf->SetTextColor(169, 2, 58);
		$linePrix = $prix." € TTC";
		$linePrix = iconv('UTF-8', 'windows-1252', $linePrix);
		$pdf->Write(0, $linePrix);
	}
	// On génère le PDF "facture" une fois que la transaction est terminée
	$pdf->Output();
	/**** /PDF ****/
}
