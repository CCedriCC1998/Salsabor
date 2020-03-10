<?php
require_once "db_connect.php";
$db = PDOFactory::getConnection();

try{
	$db->beginTransaction();
	$update = $db->prepare("UPDATE tarifs_reservations SET prix_resa=:tarif WHERE tarif_resa_id=:update_id");
	$update->bindParam(':tarif', $_POST["tarif"]);
	$update->bindParam(':update_id', $_POST["update_id"]);
	$update->execute();
	$db->commit();
} catch (PDOExecption $e) {
	$db->rollBack();
	$message = var_dump($e->getMessage());
	$data = array('type' => 'error', 'message' => ' '.$message);
	header('HTTP/1.1 400 Bad Request');
	header('Content-Type: application/json; charset=UTF-8');
	echo json_encode($data);
}
?>