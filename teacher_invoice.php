<?php
include "librairies/fpdf/fpdf.php";
include "librairies/fpdi/fpdi.php";
require_once 'functions/db_connect.php';
require_once "functions/tools.php";

$db = PDOFactory::getConnection();

if(isset($_GET["invoice_id"])){
	$invoice_id = $_GET["invoice_id"];
	createInvoice($db, $invoice_id);
}

function createInvoice($db, $invoice_id){
	// Transaction details
	$invoice_details = $db->query("SELECT invoice_token, invoice_seller_id FROM invoices WHERE invoice_id = $invoice_id")->fetch();

	$user_id = $invoice_details["invoice_seller_id"];

	// User details
	$user_details = $db->query("SELECT *, CONCAT (user_prenom, ' ', user_nom) AS user_identity FROM users WHERE user_id = $user_id")->fetch();

	// Begin PDF
	$pdf = new FPDI();
	$pdf->AddPage();
	$pdf->SetSourceFile("librairies/Salsabor-facture-previsionnelle-cours.pdf");
	$template = $pdf->importPage(1);
	$pdf->useTemplate($template, 0, 0, 210);
	$pdf->SetFont('Arial', '', 11);
	$pdf->SetAutoPageBreak(false);

	// PDF : Seller info
	$pdf->setXY(49, 16);
	$invoice_details_line = "Facture prévisionnelle ".$invoice_details["invoice_token"]." pour ".$user_details["user_identity"];
	$invoice_details_line = iconv('UTF-8', 'windows-1252', $invoice_details_line);
	$pdf->MultiCell(0, 7, $invoice_details_line);

	// PDF : Sessions list
	$sessions = $db->query("SELECT session_id, session_name, session_group, session_start, session_end, rate_value, rate_ratio, rate_title, invoice_token FROM sessions s
			LEFT JOIN teacher_rates tr ON s.teacher_rate = tr.rate_id
			LEFT JOIN invoices i ON s.invoice_id = i.invoice_id
			WHERE session_teacher = $user_id
			AND s.invoice_id = $invoice_id
			ORDER BY rate_title ASC, session_start ASC");

	$pdf->setXY(30, 54);
	$l = 1; $current_line = 64;
	$pdf->setXY(10, $current_line);
	$infos = "> Détail des cours";
	$infos = iconv('UTF-8', 'windows-1252', $infos);
	$pdf->Write(0, $infos);

	// PDF : Product price
	$pdf->setXY(180, $current_line);
	$infos = "Prix";
	$infos = iconv('UTF-8', 'windows-1252', $infos);
	$pdf->Write(0, $infos);
	$total_price = 0;

	$first_line = 56;
	$rate_title = "";
	while($session = $sessions->fetch()){
		$pdf->SetFont('Arial', '', 11);

		$participations = $db->query("SELECT COUNT(passage_id) FROM participations WHERE session_id = $session[session_id]")->fetch(PDO::FETCH_COLUMN);

		switch($session["rate_ratio"]){
			case 'heure':
				$price = $session["rate_value"] * 1;
				break;

			case 'personne':
				$price = $session["rate_value"] * $participations;
				break;

			case 'prestation':
				$price = $session["rate_value"];
				break;

			default:
				$price = NULL;
				break;
		}

		// PDF : List of products
		$current_line = $first_line + (18*$l);
		if($current_line > 270){
			$pdf->AddPage();
			$l = 1; $first_line = 2; $current_line = $first_line;
			$current_line = $first_line + (18*$l);
		}

		if($rate_title != $session["rate_title"]){
			$l++;
			$rate_title = $session["rate_title"];
			$pdf->setXY(10, $current_line);
			$pdf->SetFont('Arial', 'B', 24);
			$infos = $session["rate_title"];
			$infos = iconv('UTF-8', 'windows-1252', $infos);
			$pdf->Write(0, $infos);
			$current_line = $first_line + (18*$l);
		}

		// PDF : Product name
		$pdf->setXY(10, $current_line);
		$pdf->SetFont('Arial', 'B', 16);
		$infos = $session["session_name"];
		$infos = iconv('UTF-8', 'windows-1252', $infos);
		$pdf->Write(0, $infos);

		$pdf->SetFont('Arial', '', 11);
		$current_line += 6;
		// PDF : Session details
		$pdf->setXY(20, $current_line);
		$infos = $participations." participants";
		$infos = iconv('UTF-8', 'windows-1252', $infos);
		$pdf->Write(0, $infos);

		$pdf->setXY(60, $current_line);
		$infos = date_create($session["session_start"])->format('d/m/Y H:i')." - ".date_create($session["session_end"])->format('H:i');
		$infos = iconv('UTF-8', 'windows-1252', $infos);
		$pdf->Write(0, $infos);

		$current_line -= 5;
		// PDF : Product price
		$pdf->setXY(180, $current_line);
		$infos = $price." €";
		$infos = iconv('UTF-8', 'windows-1252', $infos);
		$pdf->Write(0, $infos);

		$l++;
		$total_price += $price;
	}

	$current_line = $first_line + (18*$l) + 6;
	$pdf->setXY(10, $current_line);
	$infos = "> Détail des prestations";
	$infos = iconv('UTF-8', 'windows-1252', $infos);
	$pdf->Write(0, $infos);

	// PDF : Product price
	$pdf->setXY(180, $current_line);
	$infos = "Prix";
	$infos = iconv('UTF-8', 'windows-1252', $infos);
	$pdf->Write(0, $infos);

	$l++;

	// Prestations
	$prestations = $db->query("SELECT pu.prestation_id, prestation_start, prestation_end, prestation_address, price FROM prestation_users pu
	JOIN prestations p ON pu.prestation_id = p.prestation_id
	WHERE pu.invoice_id = $invoice_id
	ORDER BY prestation_start ASC");
	while($prestation = $prestations->fetch()){
		$price = $prestation["price"];
		$pdf->SetFont('Arial', '', 11);

		// PDF : List of products
		$current_line = $first_line + (18*$l);
		if($current_line > 275){
			$pdf->AddPage();
			$l = 1; $first_line = 2; $current_line = $first_line;
			$current_line = $first_line + (18*$l);
		}

		// PDF : Product name
		$pdf->setXY(10, $current_line);
		$pdf->SetFont('Arial', 'B', 16);
		$infos = str_replace(array("\r\n", "\r"), " - ", $prestation["prestation_address"]);
		$infos = iconv('UTF-8', 'windows-1252', $infos);
		$pdf->Write(0, $infos);

		$pdf->SetFont('Arial', '', 11);
		$current_line += 6;
		// PDF : Session details

		$pdf->setXY(60, $current_line);
		$infos = date_create($prestation["prestation_start"])->format('d/m/Y H:i')." - ".date_create($prestation["prestation_end"])->format('H:i');
		$infos = iconv('UTF-8', 'windows-1252', $infos);
		$pdf->Write(0, $infos);

		$current_line -= 5;
		// PDF : Product price
		$pdf->setXY(180, $current_line);
		$infos = $price." €";
		$infos = iconv('UTF-8', 'windows-1252', $infos);
		$pdf->Write(0, $infos);

		$l++;
		$total_price += $price;
	}

	// Total
	$pdf->SetFont('Arial', 'B', 20);
	$current_line = $first_line + (18*$l);
	$pdf->setXY(10, $current_line);
	$infos = "TOTAL : ".$total_price." €";
	$infos = iconv('UTF-8', 'windows-1252', $infos);
	$pdf->Write(0, $infos);

	// Output PDF
	$pdf->Output();
}

?>
