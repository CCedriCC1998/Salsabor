<?php
require_once "db_connect.php";
$db = PDOFactory::getConnection();

/** Forcefully deactivates a product **/

$product_id = $_POST["product_id"];
if(isset($_POST["value"])){
	$deactivate = $db->query("UPDATE produits_adherents
						SET actif='$_POST[value]'
						WHERE id_produit_adherent = '$product_id'");
	echo $_POST["value"];
} else {
	/** Check if the product has already been activated before **/
	$details = $db->query("SELECT pa.date_activation AS produit_adherent_activation, pa.actif AS produit_adherent_actif, date_expiration, date_fin_utilisation, volume_cours, product_size, lock_dates, lock_status FROM produits_adherents pa
						JOIN produits p ON pa.id_produit_foreign = p.product_id
						WHERE id_produit_adherent = '$product_id'")->fetch(PDO::FETCH_ASSOC);

	if($details["volume_cours"] < $details["product_size"]){
		if(($details["date_fin_utilisation"] != NULL && $details["date_fin_utilisation"] != "0000-00-00 00:00:00")){
			$date_fin_utilisation = $details["date_fin_utilisation"];
		} else {
			$date_fin_utilisation = date_create("now")->format("Y-m-d");
		}
		$deactivate = $db->query("UPDATE produits_adherents
						SET actif='2', date_fin_utilisation = '$date_fin_utilisation'
						WHERE id_produit_adherent = '$product_id'");
		echo json_encode($date_fin_utilisation);
	} else {
		$deactivate = $db->query("UPDATE produits_adherents
						SET actif='0', date_activation = NULL, date_expiration = NULL, date_fin_utilisation = NULL
						WHERE id_produit_adherent = '$product_id'");
		echo 0;
	}
}
?>
