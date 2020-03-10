<?php
/**
 * Created by PhpStorm.
 * User: AngelZatch
 * Date: 19/03/2017
 * Time: 21:10
 */

include "db_connect.php";
$db = PDOFactory::getConnection();

/*$query = $db->query("SELECT product_id, product_name FROM produits");

$products_list = array();
while($product = $query->fetch(PDO::FETCH_ASSOC)){
    $product["product_name"] = utf8_encode($product["product_name"]);
    array_push($products_list, $product);
}
echo json_encode($products_list);*/

$query = $db->query("SELECT product_name FROM produits");

echo json_encode($query->fetchAll(PDO::FETCH_COLUMN));


?>
