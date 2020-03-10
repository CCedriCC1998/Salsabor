<?php
require_once "db_connect.php";
$db = PDOFactory::getConnection();

$update_id = $_POST["update_id"];

try{
	$db->beginTransaction();
	// Updating teacher price
	$update = $db->prepare('UPDATE tarifs_professeurs SET tarif_prestation=:tarif WHERE tarif_professeur_id=:update_id');
	$update->bindParam(':tarif', $_POST["tarif"]);
	$update->bindParam(':update_id', $update_id);
	$update->execute();

	// Updating all unpaid invoices
	$queryTarif = $db->query("SELECT * FROM tarifs_professeurs WHERE tarif_professeur_id='$update_id'")->fetch(PDO::FETCH_ASSOC);
	$queryCours = $db->prepare("SELECT * FROM sessions WHERE session_teacher=? AND session_paid=0");
	$queryCours->bindParam(1, $queryTarif["prof_id_foreign"]);
	$queryCours->bindParam(2, $queryTarif["type_prestation"]);
	$queryCours->execute();

	$test = 0;
	while($cours = $queryCours->fetch(PDO::FETCH_ASSOC)){
		/* Per head */
		if($queryTarif["ratio_multiplicatif"] == 'personne'){
			// Checking for users who didn't use any invitation [OBSOLETE]
			/*$queryParticipants = $db->query("SELECT * FROM cours_participants JOIN produits_adherents ON produit_adherent_id=produits_adherents.id_transaction JOIN produits ON id_produit=produits.product_id WHERE session_id_foreign='$cours[session_id]' AND product_name != 'Invitation'")->rowCount();*/
			$value = $queryParticipants * $_POST["tarif"];
		} else if($queryTarif["ratio_multiplicatif"] == "heure"){
			// Per hour
			$value = $cours["session_duration"] * $_POST["tarif"];
		} else {
			// Per event
			$value = $_POST["tarif"];
		}
		// Application du nouveau prix
		$db->query("UPDATE cours SET session_price='$value' WHERE session_id='$cours[session_id]'");
		$test++;
	}

	$db->commit();
	echo "Tarif mis à jour. ".$test." cours affectés par la modification";
} catch (PDOExecption $e) {
	$db->rollBack();
	$message = var_dump($e->getMessage());
	$data = array('type' => 'error', 'message' => ' '.$message);
	header('HTTP/1.1 400 Bad Request');
	header('Content-Type: application/json; charset=UTF-8');
	echo json_encode($data);
}
?>
