<?php
require_once "db_connect.php";
require_once "tools.php";
$db = PDOFactory::getConnection();

/** Forcefully activates a product **/

if(isset($_POST["product_id"])){
	$product_id = $_POST["product_id"];
	$start_date = $_POST["start_date"];
	activateProduct($db, $product_id, $start_date);
}

function activateProduct($db, $product_id, $start_date){
	/** Check if the product has already been activated before **/
	$details = $db->query("SELECT pa.date_activation AS produit_adherent_activation, pa.actif AS produit_adherent_actif, date_expiration, date_fin_utilisation, product_validity, volume_cours, counts_holidays FROM produits_adherents pa
						JOIN produits p ON pa.id_produit_foreign = p.product_id
						WHERE id_produit_adherent = '$product_id'")->fetch(PDO::FETCH_ASSOC);

	if($details["produit_adherent_activation"] != "0000-00-00 00:00:00" && $details["produit_adherent_activation"] != NULL && $details["produit_adherent_actif"] == "0"){
		$date_activation = $details["produit_adherent_activation"];
		$date_expiration = $details["date_expiration"];
	} else {
		if(isset($start_date) || $start_date == "0"){
			$date_activation = $start_date;
		} else {
			$date_activation = date_create("now")->format("Y-m-d");
		}
		if($details["counts_holidays"] == 0){
			$has_holiday = true;
		} else {
			$has_holiday = false;
		}
		$new_exp_date = date_create(computeExpirationDate($db, $date_activation, $details["product_validity"], $has_holiday))->format("Y-m-d H:i:s");
	}

	if($new_exp_date < date_create("now")->format("Y-m-d")){
		$actif = '2';
	} else {
		$actif ='1';
	}
	$activate = $db->query("UPDATE produits_adherents
						SET actif='$actif', date_fin_utilisation = NULL, date_activation = '$date_activation', date_expiration = '$new_exp_date'
						WHERE id_produit_adherent = '$product_id'");

	echo json_encode(array($date_activation, $new_exp_date, $details["volume_cours"]));
}
?>
