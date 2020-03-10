<?php
require_once "db_connect.php";
$db = PDOFactory::getConnection();

/** Deletes a product. If the transaction is empty afterwards, it deletes the transaction as well. So everything's clean and neat. **/

$product_id = $_POST["product_id"];

$transaction_id = $db->query("SELECT id_transaction_foreign FROM produits_adherents WHERE id_produit_adherent = '$product_id'")->fetch(PDO::FETCH_COLUMN);

$deleteProduct = $db->query("DELETE FROM produits_adherents WHERE id_produit_adherent = '$product_id'");

$search = $db->query("SELECT id_produit_adherent FROM produits_adherents WHERE id_transaction_foreign = '$transaction_id'")->rowCount();

if($search == 0){
	$deleteTransaction = $db->query("DELETE FROM transactions WHERE id_transaction = '$transaction_id'");
	echo $transaction_id;
}
?>
