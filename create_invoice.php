<?php
include "librairies/fpdf/fpdf.php";
include "librairies/fpdi/fpdi.php";
require_once 'functions/db_connect.php';
require_once "functions/tools.php";

$db = PDOFactory::getConnection();

if(isset($_GET["transaction"])){
	$transaction_id = $_GET["transaction"];
	createInvoice($db, $transaction_id);
}

function createInvoice($db, $transaction_id){
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
	$pdf->SetSourceFile("librairies/Salsabor-vente-facture.pdf");
	$template = $pdf->importPage(1);
	$pdf->useTemplate($template, 0, 0, 210);
	$pdf->SetFont('Arial', '', 11);

	// PDF : Seller info
	$pdf->setXY(49, 16);
	$transaction_details_line = "Vente assurée par : ".$transaction_details["user_prenom"]." ".$transaction_details["user_nom"]."\n";
	$transaction_details_line .= $transaction_details["location_address"]."\n";
	$transaction_details_line .= $transaction_details["location_telephone"];
	$transaction_details_line = iconv('UTF-8', 'windows-1252', $transaction_details_line);
	$pdf->MultiCell(0, 7, $transaction_details_line);

	// PDF : User info
	$pdf->setXY(30, 54);
	$user_details_line = $user_details["user_identity"]."\n";
	$user_details_line .= $user_details["rue"]." - ".$user_details["code_postal"]." ".$user_details["ville"]."\n";
	$user_details_line .= $user_details["mail"]."\n";
	$user_details_line .= $user_details["telephone"];
	$user_details_line = iconv('UTF-8', 'windows-1252', $user_details_line);
	$pdf->MultiCell(0, 7, $user_details_line);

	// PDF : Reference
	$pdf->setXY(20, 101);
	$text = "Votre commande du ".date_create($transaction_details["date_achat"])->format("d/m/Y");
	$text = iconv('UTF-8', 'windows-1252', $text);
	$pdf->Write(0, $text);

	$pdf->setXY(110, 101);
	$transaction_line = "Transaction n° ".$transaction_id;
	$transaction_line = iconv('UTF-8', 'windows-1252', $transaction_line);
	$pdf->Write(0, $transaction_line);

	$pdf->SetFont('Arial', '', 11);

	$products_list = $db->query("SELECT * FROM produits_adherents pa
								JOIN produits p ON pa.id_produit_foreign = p.product_id
								WHERE id_transaction_foreign = '$transaction_id'");

	$l = 1; $first_line = 112; $current_line = $first_line;
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
		$current_line = $first_line + (10*$l);

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
	$pdf->setXY(180, $current_line + 12);
	$infos = "--------------------------------------------------------------------------------------------------------------------------------------------------";
	$infos = iconv('UTF-8', 'windows-1252', $infos);
	$pdf->Write(0, $infos);
	$pdf->setXY(10, $current_line + 18);
	$pdf->SetFont('Arial', 'B', 18);
	$infos = "TOTAL";
	$infos = iconv('UTF-8', 'windows-1252', $infos);
	$pdf->Write(0, $infos);
	$pdf->setXY(165, $current_line + 18);
	$infos = $transaction_details["prix_total"]." €";
	$infos = iconv('UTF-8', 'windows-1252', $infos);
	$pdf->Write(0, $infos);

	// Output PDF
	$pdf->Output();
}

?>
