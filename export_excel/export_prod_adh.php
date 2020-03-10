<?php
$period_start = $_GET['debut'];
$period_end = $_GET['fin'];
//requete pour exporter le tableau des produits adherents
require_once '../functions/db_connect.php';
$db = PDOFactory::getConnection();

$productBuy2 = $db->query("SELECT pa.id_produit_adherent,pa.id_transaction_foreign,pa.id_user_foreign,pa.id_produit_foreign,pa.date_activation,pa.date_expiration,pa.volume_cours,
		                              pa.prix_achat,pa.date_fin_utilisation,pa.actif,pa.AREP,pa.date_prolongee,pa.lock_status,pa.lock_dates
                            FROM transactions t
                            JOIN produits_adherents pa ON pa.id_transaction_foreign = t.id_transaction
                            WHERE t.date_achat BETWEEN '$period_start' AND '$period_end'
                            ORDER BY t.date_achat DESC");

$data = $productBuy2->fetchAll();

require '../functions/export_csv.php';
CSV::export($data,'Table produits adherents');

?>
