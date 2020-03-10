<?php
require_once "db_connect.php";
$db = PDOFactory::getConnection();

$id_produit = $_POST["product_id"];

try{
	$db->beginTransaction();
	$lowestOrder = $db->query("SELECT * FROM panier ORDER BY panier_order DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
	$order = $lowestOrder["panier_order"]+1;
	$new = $db->prepare('INSERT INTO panier(panier_element, panier_order) VALUES(:id_produit, :order)');
	$new->bindParam(':id_produit', $id_produit);
	$new->bindParam(':order', $order);
	$new->execute();
	$db->commit();
	echo "Produit ajouté au panier";
} catch(PDOException $e){
	$db->rollBack();
}
?>
