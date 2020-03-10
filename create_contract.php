<?php
include "librairies/fpdf/fpdf.php";
include "librairies/fpdi/fpdi.php";
require_once 'functions/db_connect.php';
require_once "functions/tools.php";

$db = PDOFactory::getConnection();

if(isset($_GET["transaction"])){
	$transaction_id = $_GET["transaction"];
	createContract($db, $transaction_id);
}

function createContract($db, $transaction_id){
	// Transaction details
	$transaction_details = $db->query("SELECT * FROM transactions t
										LEFT JOIN users u ON t.transaction_handler = u.user_id
										LEFT JOIN locations l ON u.user_location = l.location_id
										WHERE id_transaction = '$transaction_id'")->fetch(PDO::FETCH_ASSOC);

	$user_id = $transaction_details["payeur_transaction"];

	// User details
	$user_details = $db->query("SELECT *, CONCAT (user_prenom, ' ', user_nom) AS user_identity FROM users WHERE user_id = $user_id")->fetch(PDO::FETCH_ASSOC);

	// Begin PDF
	$pdf = new FPDI();
	$pdf->AddPage();
	$pdf->SetSourceFile("librairies/Salsabor-contrat.pdf");
	$template = $pdf->importPage(1);
	$pdf->useTemplate($template, 0, 0, 210);
	$pdf->SetFont('Arial', '', 11);

	// PDF : Seller info
	$pdf->setXY(49, 24);
	$transaction_details_line = "Contrat réalisé par : ".$transaction_details["user_prenom"]." ".$transaction_details["user_nom"]."\n";
	$transaction_details_line .= $transaction_details["location_address"]."\n";
	$transaction_details_line .= $transaction_details["location_telephone"];
	$transaction_details_line = iconv('UTF-8', 'windows-1252', $transaction_details_line);
	$pdf->MultiCell(0, 7, $transaction_details_line);

	// PDF : User info
	$pdf->setXY(130, 24);
	$user_details_line = "Pour : ".$user_details["user_identity"]."\n";
	$user_details_line .= $user_details["rue"]." - ".$user_details["code_postal"]." ".$user_details["ville"]."\n";
	$user_details_line .= $user_details["mail"]." / ".$user_details["telephone"];
	$user_details_line = iconv('UTF-8', 'windows-1252', $user_details_line);
	$pdf->MultiCell(0, 7, $user_details_line);

	$pdf->SetFont('Arial', '', 11);

	$products_list = $db->query("SELECT * FROM produits_adherents pa
								JOIN produits p ON pa.id_produit_foreign = p.product_id
								WHERE id_transaction_foreign = '$transaction_id'");

	$l = 1; $first_line = 86; $current_line = $first_line;
	// PDF : Product name
	$pdf->setXY(10, $current_line);
	$infos = "Nom du produit";
	$infos = iconv('UTF-8', 'windows-1252', $infos);
	$pdf->Write(0, $infos);

	/*// PDF : Product details
	$pdf->setXY(25, $current_line + 5);*/

	// PDF : Taxes
	$pdf->setXY(140, $current_line);
	$infos = "Prix HT";
	$infos = iconv('UTF-8', 'windows-1252', $infos);
	$pdf->Write(0, $infos);

	// PDF : Product price
	$pdf->setXY(180, $current_line);
	$infos = "Prix TTC";
	$infos = iconv('UTF-8', 'windows-1252', $infos);
	$pdf->Write(0, $infos);

	$first_line += 2;

	while($product = $products_list->fetch(PDO::FETCH_ASSOC)){
		// PDF : List of products
		$current_line = $first_line + (7*$l);

		// PDF : Product name
		$pdf->setXY(10, $current_line);
		$infos = $product["product_name"];
		$infos = iconv('UTF-8', 'windows-1252', $infos);
		$pdf->Write(0, $infos);

		/*// PDF : Product details
		$pdf->setXY(25, $current_line + 5);*/

		// PDF : Product price
		$pdf->setXY(140, $current_line);
		$infos = $product["prix_achat"]." €";
		$infos = iconv('UTF-8', 'windows-1252', $infos);
		$pdf->Write(0, $infos);

		$pdf->setXY(180, $current_line);
		$infos = $product["prix_achat"]." €";
		$infos = iconv('UTF-8', 'windows-1252', $infos);
		$pdf->Write(0, $infos);

		$l++;
	}

	// PDF : Total price
	$current_line += 8;
	$pdf->setXY(180, $current_line);
	$infos = "--------------------------------------------------------------------------------------------------------------------------------------------------";
	$infos = iconv('UTF-8', 'windows-1252', $infos);
	$pdf->Write(0, $infos);
	$pdf->setXY(10, $current_line + 6);
	$pdf->SetFont('Arial', 'B', 18);
	$infos = "TOTAL";
	$infos = iconv('UTF-8', 'windows-1252', $infos);
	$pdf->Write(0, $infos);
	$pdf->setXY(165, $current_line + 6);
	$infos = $transaction_details["prix_total"]." €";
	$infos = iconv('UTF-8', 'windows-1252', $infos);
	$pdf->Write(0, $infos);

	$current_line = 140;

	$pdf->SetTextColor(0, 0, 0);
	$pdf->setFont('Arial', '', 10);
	$pdf->Rect(10, $current_line, 35, 7);
	$pdf->setXY(10, $current_line + 3);
	$infos = "Numéro d'écheance";
	$infos = iconv('UTF-8', 'windows-1252', $infos);
	$pdf->Write(0, $infos);
	$pdf->Rect(45, $current_line, 50, 7);
	$pdf->setXY(45, $current_line + 3);
	$infos = "Date d'encaissement";
	$infos = iconv('UTF-8', 'windows-1252', $infos);
	$pdf->Write(0, $infos);
	$pdf->Rect(95, $current_line, 20, 7);
	$pdf->setXY(95, $current_line + 3);
	$infos = "Montant";
	$infos = iconv('UTF-8', 'windows-1252', $infos);
	$pdf->Write(0, $infos);
	$pdf->Rect(115, $current_line, 85, 7);
	$pdf->setXY(115, $current_line + 3);
	$infos = "Méthode de paiement";
	$infos = iconv('UTF-8', 'windows-1252', $infos);
	$pdf->Write(0, $infos);

	$first_line = $current_line + 10;

	// Maturities
	$maturities_list = $db->query("SELECT * FROM produits_echeances WHERE reference_achat = '$transaction_id'");
	$i = 0;
	while($maturity = $maturities_list->fetch(PDO::FETCH_ASSOC)){
		$current_line = $first_line + (8*$i);
		//Echeances - Contenu du tableau
		$pdf->Rect(10, $current_line, 35, 8);
		$pdf->setXY(18, $current_line + 3);
		$infos = "Echéance ".($i + 1);
		$infos = iconv('UTF-8', 'windows-1252', $infos);
		$pdf->Write(0, $infos);
		$pdf->Rect(45, $current_line, 50, 8);
		$pdf->setXY(45, $current_line + 3);
		$infos = date_create($maturity["date_echeance"])->format('d/m/Y');
		$infos = iconv('UTF-8', 'windows-1252', $infos);
		$pdf->Write(0, $infos);
		$pdf->Rect(95, $current_line, 20, 8);
		$pdf->setXY(95, $current_line + 3);
		$infos = $maturity["montant"]." €";
		$infos = iconv('UTF-8', 'windows-1252', $infos);
		$pdf->Write(0, $infos);
		$pdf->Rect(115, $current_line, 85, 8);
		$pdf->setXY(115, $current_line + 3);
		$infos = $maturity["methode_paiement"];
		$infos = iconv('UTF-8', 'windows-1252', $infos);
		$pdf->Write(0, $infos);
		$i++;
	}

	$pdf->setXY(13, 263);
	$infos = iconv('UTF-8', 'windows-1252', date_create($transaction_details["date_achat"])->format("d/m/Y"));
	$pdf->Write(0, $infos);

	// Output PDF
	$pdf->Output();
}
