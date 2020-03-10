<?php
include "db_connect.php";
$db = PDOFactory::getConnection();

$maturity_id = $_GET["maturity_id"];

$load = $db->query("SELECT * FROM produits_echeances pe
						JOIN transactions t ON pe.reference_achat = t.id_transaction
						WHERE produits_echeances_id = '$maturity_id'")->fetch(PDO::FETCH_ASSOC);

$m = array();
$m["id"] = $load["produits_echeances_id"];
$m["transaction_id"] = $load["reference_achat"];
$m["transaction_date"] = $load["date_achat"];
$m["payer"] = $load["payeur_echeance"];
$m["date"] = $load["date_echeance"];
$m["price"] = $load["montant"];
$m["method"] = $load["methode_paiement"];
$m["reception_status"] = $load["echeance_effectuee"];
$m["date_reception"] = $load["date_paiement"];
$m["bank_status"] = $load["statut_banque"];
$m["date_bank"] = $load["date_encaissement"];
$m["lock_montant"] = $load["lock_montant"];
echo json_encode($m);
?>
