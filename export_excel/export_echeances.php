<?php
$period_start = $_GET['debut'];
$period_end = $_GET['fin'];
//requete pour exporter le tableau des transactions sur la période sélectionnés
require_once '../functions/db_connect.php';
$db = PDOFactory::getConnection();

$echeances = $db->query("SELECT DISTINCT pe.produits_echeances_id,pe.reference_achat,t.payeur_transaction,pe.payeur_echeance,pe.date_echeance,pe.montant,pe.methode_paiement,pe.echeance_effectuee,pe.date_paiement,pe.statut_banque,pe.date_encaissement,pe.lock_montant
				,p.product_name,p.product_id,pc.category_id,pc.category_name,pc.category_TVA
          FROM produits_echeances pe
          JOIN transactions t ON pe.reference_achat = t.id_transaction
          LEFT JOIN users u ON t.transaction_handler = u.user_id
          LEFT JOIN locations l ON u.user_location = l.location_id
          LEFT JOIN produits_adherents pa ON pa.id_transaction_foreign = t.id_transaction
          LEFT JOIN produits p ON p.product_id = pa.id_produit_foreign
          LEFT JOIN product_categories pc ON pc.category_id = p.product_category
          WHERE (date_echeance BETWEEN '$period_start' AND '$period_end' AND NOT p.product_id = 16
          AND (methode_paiement !='Carte Bancaire' AND methode_paiement !='CB' OR methode_paiement IS NULL))");

$data = $echeances->fetchAll();

require '../functions/export_csv.php';
CSV::export($data,'Table echeances du '.$period_start.' au '.$period_end);

?>
