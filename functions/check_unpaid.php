<?php
require_once "../functions/db_connect.php";
$db = PDOFactory::getConnection();
$search = $db->prepare("SELECT * FROM produits_echeances JOIN transactions ON reference_achat=transactions.id_transaction WHERE echeance_effectuee=2 AND payeur_transaction=?");
$search->bindParam(1, $_POST['search_id']);
$search->execute();
$count = $search->rowCount();
echo $count;
?>
