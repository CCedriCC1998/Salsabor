<?php
$period_start = $_GET['debut'];
$period_end = $_GET['fin'];
//requete pour exporter le tableau des transactions sur la période sélectionnés
require_once '../functions/db_connect.php';
$db = PDOFactory::getConnection();

$productBuy2 = $db->query("SELECT t.id_transaction,t.payeur_transaction,CONCAT(u.user_prenom, ' ', u.user_nom) AS nom_du_payeur,t.date_achat,t.transaction_handler,t.prix_total,t.transaction_commentaires
                            FROM transactions t
                            JOIN users u ON u.user_id = t.payeur_transaction
                            WHERE t.date_achat BETWEEN '$period_start' AND '$period_end'
                            ORDER BY t.date_achat DESC");

$data = $productBuy2->fetchAll();

require '../functions/export_csv.php';
CSV::export($data,'Table transactions');

?>
