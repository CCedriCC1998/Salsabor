<?php
require_once "db_connect.php";
require_once "tools.php";
include "post_task.php";
include "attach_tag.php";

/**
This code will:
- Compute the amount of remaining hours on a product based on the sessions taken with it.
- Deactivate the product if the remaining hours are equal or less than 0
- Activate the product if it has recieved records while it was still pending

Yes. This code does everything to ensure the information can be tracked and stay as accurate as possible.
**/

if(isset($_POST["product_id"])){
	$product_id = $_POST["product_id"];
	computeProduct($product_id);
}

function computeProduct($product_id){
	$db = PDOFactory::getConnection();
	$product_details = $db->query("SELECT product_id, product_name, product_size, counts_holidays, pa.date_activation AS produit_adherent_activation, product_validity, pa.actif AS produit_adherent_actif, date_achat, date_expiration, date_prolongee, date_fin_utilisation, lock_status, lock_dates, id_transaction, date_achat, id_produit_adherent, CONCAT(user_prenom, ' ', user_nom) AS user FROM produits_adherents pa
						JOIN produits p
							ON pa.id_produit_foreign = p.product_id
						LEFT JOIN transactions t
							ON pa.id_transaction_foreign = t.id_transaction
						LEFT JOIN users u
							ON pa.id_user_foreign = u.user_id
						WHERE id_produit_adherent = '$product_id'")->fetch(PDO::FETCH_ASSOC);

	$query_tags = $db->query("SELECT rank_name FROM assoc_product_tags apt
												JOIN tags_session ts
												ON apt.tag_id_foreign = ts.rank_id
												WHERE product_id_foreign = $product_details[product_id]");

	$product_tags = array();
	while($tag = $query_tags->fetch()){
		array_push($product_tags, $tag["rank_name"]);
	}

	$master_settings = $db->query("SELECT * FROM master_settings WHERE user_id = 0")->fetch(PDO::FETCH_ASSOC);

	$today = date("Y-m-d H:i:s");
	$hour_limit = $master_settings["hours_before_exp"];
	$expiration_limit = date("Y-m-d", strtotime($today.'+'.$master_settings["days_before_exp"].'DAYS'));

	$remaining_hours = $product_details["product_size"];
	$old_status = $product_details["produit_adherent_actif"];
	$old_activation_date = $product_details["produit_adherent_activation"];
	$lock_dates = ($product_details["lock_dates"]==1)?true:false;
	$lock_status = ($product_details["lock_status"]==1)?true:false;

	// All the computed values will be added to an array and sent to the client to refresh the displayed data.
	$computed_values = array();

	/**
	In all cases, this microprogram has to compute the following info :
	- activation date
	- usage date (if existant)
	- expiration date (if product is active/activated)
	- remaining hours
	- status
	**/

	$new_usage_date = NULL; // Usage date changes only if the product can have sessions and is not unlimited and has reached its full usage. By default, it's NULL.
	if($product_details["product_size"] != null){ // If the product can have sessions.
		$sessions = $db->query("SELECT session_duration, session_start, session_end FROM participations pr
							JOIN sessions s ON pr.session_id = s.session_id
							WHERE produit_adherent_id = '$product_id' AND status = 2
							ORDER BY session_start ASC");
		if($sessions->rowCount() == 0){ // If there's no participations recorded
			$new_status = 0;
			$new_activation_date = NULL;
			$new_expiration_date = NULL;
			$new_size = $product_details["product_size"]; //Full size
		} else {
			$new_status = 1;
			$session_iterator = 0;
			while($session = $sessions->fetch(PDO::FETCH_ASSOC)){
				if($session_iterator == 0) // Activation date is the start date of the earliest registered session
					$new_activation_date = date_create($session["session_start"])->format("Y-m-d H:i:s");

				// We decrease the volume of remaining hours as we go to compute the usage date.
				$remaining_hours -= floatval($session["session_duration"]);
				$session_iterator++;

				// Now that we got the activation date, we compute the usage date
				if($remaining_hours <= 0 && $product_details["product_size"] != 0 && $new_status != 2){ // The product has a negative amount of remaining hours: it's fully spent.
					$new_usage_date = $session["session_end"];
					$new_status = 2;
				}
			}

			$new_size = $remaining_hours;
		}
	} else { // The product cannot have participations
		$new_activation_date = $old_activation_date;
		if($new_activation_date != null) $new_status = 1;
		else $new_status = 0;
		$new_size = $product_details["product_size"];
	}
	/** Up to this point, the program knows the new activation date and therefore status(and possibly usage date) of the product. Remains the expiration date only.
		**/
	// We compute the date of expiration
	if($new_activation_date){
		$computed_expiration_date = date_create(computeExpirationDate($db, $new_activation_date, $product_details["product_validity"], $product_details["counts_holidays"]))->format("Y-m-d H:i:s");

		// We check against the potential extended date
		$new_expiration_date = max($computed_expiration_date, $product_details["date_prolongee"]);

		// The computed expiration date can be used to adjust the status
		if($new_expiration_date < date("Y-m-d H:i:s")) $new_status = 2;
	}
	else{
		$new_expiration_date = NULL;
	}


	$compute_query = "UPDATE produits_adherents SET";
	if(!$lock_dates){
		if($new_activation_date) $compute_query .= " date_activation = '$new_activation_date',";
		else $compute_query .= " date_activation = NULL,";

		if($new_expiration_date) $compute_query .= " date_expiration = '$new_expiration_date',";
		else $compute_query .= " date_expiration = NULL,";

		if($new_usage_date) $compute_query .= " date_fin_utilisation = '$new_usage_date',";
		else $compute_query .= " date_fin_utilisation = NULL,";
	}
	if(!$lock_status){
		$compute_query .= " actif = $new_status,";
	} else {
		$new_status = $product_details["produit_adherent_actif"];
	}

	if($new_size) $compute_query .= " volume_cours = $new_size";
	else $compute_query .= " volume_cours = NULL";

	$compute_query .= " WHERE id_produit_adherent = $product_id";

	try{
		$db->query($compute_query);
	}catch(PDOException $e){
		echo $e->getMessage();
	}

	// We fill the array of computed values
	$computed_values = array(
		"id" => $product_details["id_produit_adherent"],
		"product_name" => $product_details["product_name"],
		"user" => $product_details["user"],
		"transaction_id" => $product_details["id_transaction"],
		"date_achat" => $product_details["date_achat"],
		"activation" => $new_activation_date,
		"expiration" => $new_expiration_date,
		"usage_date" => $new_usage_date,
		"display_expiration" => max($new_expiration_date, $new_usage_date),
		"product_size" => $product_details["product_size"],
		"remaining_hours" => $remaining_hours,
		"status" => $new_status,
		"lock_status" => $lock_status,
		"lock_dates" => $lock_dates
	);

	// Once everything is computed, time for notifications
	if($old_status != '2' && $new_status == 2){ // If the product has expired because of this computing.
		$token = "PRD-E";
		postNotification($token, $product_id, null, $today);
		$new_task_id = createTask("Produit expiré", "Le produit a expiré le ".$new_expiration_date.". Veuillez contacter l'utilisateur pour un éventuel renouvellement.", "[PRD-".$product_id."]", null);
		if($new_task_id != null){
			$tag = $db->query("SELECT rank_id FROM tags_user WHERE missing_info_default = 1")->fetch(PDO::FETCH_COLUMN);
			associateTag(intval($tag), $new_task_id, "task");
		}
	} else if($remaining_hours > 0 && $remaining_hours <= $hour_limit){ // If the remaining hours are less than 5.
		$token = "PRD-NH";
		postNotification($token, $product_id, null, $today);
	}

	if(isset($_POST["product_id"])){
		echo json_encode($computed_values);
	}
}
?>
