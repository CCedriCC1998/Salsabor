<?php
include "db_connect.php";
$db = PDOFactory::getConnection();

$transaction = $_POST["purchase_id"];

$load = $db->query("SELECT * FROM produits_echeances pe
						JOIN transactions t ON pe.reference_achat = t.id_transaction
						WHERE reference_achat = '$transaction'
						ORDER BY date_echeance IS NULL DESC, date_echeance DESC");

$maturities = array();
while($details = $load->fetch(PDO::FETCH_ASSOC)){
	$m = array(
		"id" => $details["produits_echeances_id"],
		"payer" => ($details["payeur_echeance"]!=NULL)?$details["payeur_echeance"]:"Pas de payeur",
		"date" => $details["date_echeance"],
		"price" => $details["montant"],
		"method" => $details["methode_paiement"],
		"reception_status" => $details["echeance_effectuee"],
		"date_reception" => $details["date_paiement"],
		"bank_status" => $details["statut_banque"],
		"date_bank" => $details["date_encaissement"],
		"lock_montant" => $details["lock_montant"],
		"transaction_id" => $transaction,
		"transaction_user" => $details["payeur_transaction"]
	);
	array_push($maturities, $m);
}
echo json_encode($maturities);
?>
