<?php
session_start();
include "db_connect.php";
$db = PDOFactory::getConnection();


$start_date = $_GET["filters"][0];
$end_date = $_GET["filters"][1];
$region = $_GET["region"];

$query = "SELECT DISTINCT pe.produits_echeances_id,pe.reference_achat,t.payeur_transaction,pe.payeur_echeance,pe.date_echeance,pe.montant,pe.methode_paiement,pe.echeance_effectuee,pe.date_paiement,pe.statut_banque,pe.date_encaissement,pe.lock_montant
				,p.product_name,p.product_id,pc.category_id,pc.category_name,pc.category_TVA
          FROM produits_echeances pe
          JOIN transactions t ON pe.reference_achat = t.id_transaction
          LEFT JOIN users u ON t.transaction_handler = u.user_id
          LEFT JOIN locations l ON u.user_location = l.location_id
          LEFT JOIN produits_adherents pa ON pa.id_transaction_foreign = t.id_transaction
          LEFT JOIN produits p ON p.product_id = pa.id_produit_foreign
          LEFT JOIN product_categories pc ON pc.category_id = p.product_category
          WHERE (date_echeance BETWEEN '$start_date' AND '$end_date' AND NOT p.product_id = 16
          AND (methode_paiement !='Carte Bancaire' AND methode_paiement !='CB' OR methode_paiement IS NULL))";

          /*if($region == "1"){
          	$query .= " AND (location_id = $_SESSION[location] OR location_id IS NULL)";
          }*/
          $query .= "ORDER BY pc.category_TVA ASC";

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
    "category_TVA" => $details["category_TVA"]
	);
	array_push($maturities, $m);
}
echo json_encode($maturities);


 ?>
