<?php
session_start();
require_once "db_connect.php";
require_once "tools.php";
$db = PDOFactory::getConnection();

$accesWeb = 1;

try{
	$db->beginTransaction();
	$new = $db->prepare('INSERT INTO users(user_prenom, user_nom, user_rfid, date_naissance,
										date_inscription, rue, code_postal, ville,
										mail, telephone, acces_web, actif)
									VALUES(:prenom, :nom, :rfid, :date_naissance,
									:date_inscription, :rue, :code_postal, :ville,
									:mail, :telephone, :acces_web, :actif)');
	$new->bindParam(':prenom', $_POST['identite_prenom']);
	$new->bindParam(':nom', $_POST['identite_nom']);
	$new->bindParam(':rfid', $_POST["rfid"]);
	$new->bindParam(':date_naissance', $_POST['date_naissance']);
	$new->bindParam(':date_inscription', date_create('now')->format('Y-m-d'));
	$new->bindParam(':rue', $_POST['rue']);
	$new->bindParam(':code_postal', $_POST['code_postal']);
	$new->bindParam(':ville', $_POST['ville']);
	$new->bindParam(':mail', $_POST['mail']);
	$new->bindParam(':telephone', $_POST['telephone']);
	$new->bindParam(':acces_web', $accesWeb);
	$new->bindParam(':actif', $accesWeb);
	$new->execute();
	if(isset($_POST["rfid"])){
		$delete = $db->prepare("DELETE FROM participations WHERE user_rfid = ? AND status=1");
		$delete->bindParam(1, $_POST["rfid"]);
		$delete->execute();
	}
	$res = array(
		"success" => "Inscription réalisée",
		"id" => $db->lastInsertId()
	);
	logAction($db, "Ajout", $db->lastInsertId());
	$db->commit();
	echo json_encode($res);
} catch(PDOException $e){
	$db->rollBack();
	echo $e->getMessage();
}
?>
