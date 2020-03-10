<?php
$period_start = $_GET['debut'];
$period_end = $_GET['fin'];
//requete pour exporter le tableau des transactions par type de produit sur la période sélectionnés
require_once '../functions/db_connect.php';
$db = PDOFactory::getConnection();

$productBuy2 = $db->query("SELECT *
FROM users
WHERE user_id IN (SELECT payeur_transaction
                  FROM transactions
                  WHERE date_achat BETWEEN '$period_start' AND '$period_end')");

$data = $productBuy2->fetchAll();

require '../functions/export_csv.php';
CSV::export($data,'Table users selon achat effectués');

?>
