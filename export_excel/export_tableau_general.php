<?php
//requete pour exporter le tableau des produits achetés sur la période avec la somme totale,le nombre de produits vendus et la moyenne.
require_once '../functions/db_connect.php';
$db = PDOFactory::getConnection();

$productBuy2 = $db->query("SELECT p.product_id, p.product_name, SUM(pa.prix_achat) as Somme_Totale,COUNT(pa.id_produit_adherent) as Nombre_de_Produits_Vendus,AVG(pa.prix_achat) as Moyenne_du_prix_produit
                          FROM produits p
                          JOIN produits_adherents pa ON p.product_id = pa.id_produit_foreign
                          WHERE pa.id_transaction_foreign IN (SELECT t.id_transaction FROM transactions t WHERE t.date_achat BETWEEN '2020-01-01' AND '2020-01-31')
                          GROUP BY p.product_id
                          ORDER BY p.product_id");

$data = $productBuy2->fetchAll();

require '../functions/export_csv.php';
CSV::export($data,'récapitulatif des ventes');

?>
