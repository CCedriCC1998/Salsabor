<?php
require_once "db_connect.php";
include "librairies/fpdf/fpdf.php";
include "librairies/fpdi/fpdi.php";
require_once "tools.php";

function vente(){
	$db = PDOFactory::getConnection();

	// Get payer's ID
	$payer_id = solveAdherentToId($_POST["payeur"]);

	// Generate unique transaction reference
	$transaction = generateReference();

	// Purchase date
	$date_achat = $_POST["date_achat"];

	// Handler's ID
	$handler_id = solveAdherentToId($_POST["handler"]);

	// Get number of maturities
	$echeances = $_POST["echeances"];

	// Total price
	$prix_restant = $_POST["prix_total"];

	// User details
	$user_details = $db->query("SELECT *, CONCAT (user_prenom, ' ', user_nom) AS user_identity FROM users WHERE user_id = $payer_id")->fetch(PDO::FETCH_ASSOC);

	try{
		$db->beginTransaction();
		// Creating transaction
		$new_transaction = $db->prepare("INSERT INTO transactions(id_transaction, payeur_transaction, date_achat, transaction_handler, prix_total) VALUES(:transaction, :payeur, :date_achat, :handler, :prix_total)");
		$new_transaction->bindParam(':transaction', $transaction);
		$new_transaction->bindParam(':payeur', $payer_id);
		$new_transaction->bindParam(':date_achat', $date_achat);
		$new_transaction->bindParam(':handler', $handler_id);
		$new_transaction->bindParam(':prix_total', $prix_restant);
		$new_transaction->execute();

		// Adding products
		$l = 0;
		for($l; $l < $_POST["nombre_produits"]; $l++){
			// Find product from ID
			$queryProduit = $db->prepare("SELECT * FROM produits WHERE product_id=?");
			$nomProduit = $_POST["product_id-".$l];
			$queryProduit->bindParam(1, $nomProduit);
			$queryProduit->execute();
			$produit = $queryProduit->fetch(PDO::FETCH_ASSOC);

			// If the product has an activation date
			if($_POST["activation-".$l] != "0"){
				$actif = 1;
				$date_expiration = date("Y-m-d 00:00:00",strtotime($_POST["activation-".$l].'+'.$produit["product_validity"].'DAYS'));
				$queryHoliday = $db->prepare("SELECT * FROM holidays WHERE holiday_date >= ? AND holiday_date <= ?");
				$queryHoliday->bindParam(1, $_POST["activation-".$l]);
				$queryHoliday->bindParam(2, $date_expiration);
				$queryHoliday->execute();

				$j = 0;

				for($i = 0; $i <= $queryHoliday->rowCount(); $i++){
					$exp_date = date("Y-m-d 00:00:00",strtotime($date_expiration.'+'.$i.'DAYS'));
					$checkHoliday = $db->prepare("SELECT * FROM holidays WHERE holiday_date=?");
					$checkHoliday->bindParam(1, $exp_date);
					$checkHoliday->execute();
					if($checkHoliday->rowCount() != 0){
						$j++;
					}
					$totalOffset = $i + $j;
					$new_exp_date = date("Y-m-d 00:00:00",strtotime($date_expiration.'+'.$totalOffset.'DAYS'));
				}
			} else {
				$actif = 0;
			}

			// Getting user of product
			$beneficiaire = $_POST["beneficiaire-".$l];

			$new = $db->prepare("INSERT INTO produits_adherents(id_transaction_foreign, id_user_foreign, id_produit_foreign, date_activation, date_expiration, volume_cours, prix_achat, actif, arep)
		VALUES(:transaction, :adherent, :produit, :date_activation, :date_expiration, :product_size, :prix_achat, :actif, :arep)");
			$new->bindParam(':transaction', $transaction);
			$new->bindParam(':adherent', $beneficiaire);
			$new->bindParam(':produit', $produit["product_id"]);
			$new->bindParam(':date_activation', $_POST["activation-".$l]);
			$new->bindParam(':date_expiration', $new_exp_date);
			$new->bindParam(':product_size', $produit["product_size"]);
			$new->bindParam(':prix_achat', $_POST["prix-produit-".$l]);
			$new->bindParam(':actif', $actif);
			$new->bindParam(':arep', $produit["autorisation_report"]);
			$new->execute();
		}

		// Maturities
		for($k = 1; $k <= $echeances; $k++){
			if($_POST["statut-echeance-".$k] == '1')
				$date_paiement = $date_achat;

			if(strstr($_POST["moyen-paiement-".$k], "Carte bancaire") !== false){
				$bank_date = $date_paiement;
				$bank_status = 1;
			} else{
				$bank_date = NULL;
				$bank_status = 0;
			}

			$new_echeance = $db->prepare("INSERT INTO produits_echeances(reference_achat, date_echeance, montant, payeur_echeance, methode_paiement, echeance_effectuee, date_paiement, statut_banque, date_encaissement)
			VALUES(:transaction, :date_echeance, :prix, :payeur, :methode, :echeance_effectuee, :date_paiement, :statut_banque, :date_encaissement)");
			$new_echeance->bindParam(':transaction', $transaction);
			$new_echeance->bindParam(':date_echeance', $_POST["date-echeance-".$k]);
			$new_echeance->bindParam(':prix', $_POST["montant-echeance-".$k]);
			$new_echeance->bindParam(':payeur', $_POST["titulaire-paiement-".$k]);
			$new_echeance->bindParam(':methode', $_POST["moyen-paiement-".$k]);
			$new_echeance->bindParam(':echeance_effectuee', $_POST["statut-echeance-".$k]);
			$new_echeance->bindParam(':date_paiement', $date_paiement);
			$new_echeance->bindParam(':statut_banque', $bank_status);
			$new_echeance->bindParam(':date_encaissement', $bank_date);
			$new_echeance->execute();
		}

		$activateUser = $db->query("UPDATE users SET actif = '1', date_last='$date_achat' WHERE user_id='$payer_id'");

		logAction($db, "Transaction", "transactions-".$transaction);
		$db->commit();
		header("Location: end_transaction.php?transaction=$transaction");
	}catch(PDOException $e){
		$db->rollBack();
		var_dump($e->getMessage());
	}
}

/** INVITATION **/
function invitation(){
	$db = PDOFactory::getConnection();

	$user_id = solveAdherentToId($_POST["identite_nom"]);

	$transaction = generateReference();

	$date_achat = date_create("now")->format('Y-m-d H:i:s');

	$actif = 0;
	$product_size = 0;
	$prix_achat = 0;
	$echeances = 0;
	$montant_echeance = 0;
	$arep = 0;

	try{
		$db->beginTransaction();

		if($_POST["id-cours"] != ''){
			$new = $db->prepare("INSERT INTO produits_adherents(id_transaction, id_user_foreign, id_produit, date_achat, volume_cours, prix_achat, actif, arep)
		VALUES(:transaction, :adherent, :product_id, :date_achat, :product_size, :prix_achat, :actif, :arep)");
			$new->bindParam(':transaction', $transaction);
			$new->bindParam(':adherent', $user_id);
			$new->bindParam(':product_id', $_POST["produit"]);
			$new->bindParam(':date_achat', $date_achat);
			$new->bindParam(':product_size', $product_size);
			$new->bindParam(':prix_achat', $prix_achat);
			$new->bindParam(':actif', $actif);
			$new->bindParam(':arep', $arep);
			$new->execute();

			/*$passage = $db->prepare("INSERT INTO cours_participants(session_id_foreign, eleve_id_foreign, produit_adherent_id) VALUES(:cours, :eleve, :transaction)");
			$passage->bindParam(':cours', $_POST["id-cours"]);
			$passage->bindParam(':eleve', $adherent["user_id"]);
			$passage->bindParam(':transaction', $transaction);
			$passage->execute();*/
		} else {
			$actif = 1;

			$new = $db->prepare("INSERT INTO produits_adherents(id_transaction, id_user_foreign, id_produit, date_achat, date_activation, date_expiration, volume_cours, prix_achat, actif, arep)
		VALUES(:transaction, :adherent, :product_id, :date_achat, :date_activation, :date_expiration, :product_size, :prix_achat, :actif, :arep)");
			$new->bindParam(':transaction', $transaction);
			$new->bindParam(':adherent', $user_id);
			$new->bindParam(':product_id', $_POST["produit"]);
			$new->bindParam(':date_achat', $date_achat);
			$new->bindParam(':date_activation', $_POST["date_activation"]);
			$new->bindParam(':date_expiration', $_POST["date_expiration"]);
			$new->bindParam(':product_size', $product_size);
			$new->bindParam(':prix_achat', $prix_achat);
			$new->bindParam(':actif', $actif);
			$new->bindParam(':arep', $arep);
			$new->execute();
		}

		$db->commit();

		header('Location: dashboard.php');
	}catch(PDOException $e){
		$db->rollBack();
		var_dump($e->getMessage());
	}
}
?>
