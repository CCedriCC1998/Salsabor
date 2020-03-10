<?php
require_once "db_connect.php";
if(!isset($_SESSION))
	session_start();

function addParticipationBeta($values){
	$db = PDOFactory::getConnection();
	if(!isset($values["user_id"])){
		// We try to find the user from the details
		$user_id = $db->query("SELECT user_id FROM users WHERE user_rfid = '$values[user_rfid]'")->fetch(PDO::FETCH_COLUMN);
	} else {
		$user_id = $values["user_id"];
	}

	if(!isset($values["session_id"])){
		// We try to find the session
		$session_id = $db->query("SELECT session_id FROM sessions s
								JOIN rooms r ON s.session_room = r.room_id
								JOIN readers re ON r.room_reader = re.reader_id
								WHERE session_opened = '1' AND reader_token = '$values[room_token]'")->fetch(PDO::FETCH_COLUMN);

		if($session_id != "" || $session_id != NULL)
			$values["session_id"] = $session_id;
	} else {
		$session_id = $values["session_id"];
	}

	// We create the array of values the system will find
	//$duplicate_test = $db->query("SELECT COUNT(passage_id) FROM participations WHERE (user_rfid = '$values[user_rfid]' OR user_id = $values[user_id]) AND session_id = $values[session_id]")->fetch(PDO::FETCH_COLUMN);

	/*if($duplicate_test == 0){*/
	if($user_id != "" || $user_id != NULL){
		$values["user_id"] = $user_id;
		if($session_id != "" || $session_id != NULL){
			$product_id = getCorrectProductFromTags($session_id, $user_id) or NULL; // As of Dec. 21 2016, needs testing
			if($product_id != "") $status = 0; // Product found.
			else $status = 3; // No product available
			$values["produit_adherent_id"] = $product_id;
		} else {
			$status = 4; // No session has been found
		}
	} else {
		$status = 5; // No user ID has been matched
	}

	$values["status"] = $status;

	include "add_entry.php";
	addEntry("participations", $values);
	/*}*/

	echo "$";
}

function computeExpirationDate($db, $date_activation, $validity, $has_holidays){
	if($validity != 365)
		$validity--;
	$date_expiration = date("Y-m-d 23:59:59", strtotime($date_activation.'+'.$validity.'DAYS'));
	if($has_holidays || $has_holidays == 1){
		$queryHoliday = $db->prepare("SELECT * FROM holidays WHERE holiday_date >= ? AND holiday_date <= ?");
		$queryHoliday->bindParam(1, $date_activation);
		$queryHoliday->bindParam(2, $date_expiration);
		$queryHoliday->execute();

		$j = 0;

		for($i = 0; $i < $queryHoliday->rowCount(); $i++){
			$exp_date = date("Y-m-d 23:59:59",strtotime($date_expiration.'+'.$i.'DAYS'));
			$checkHoliday = $db->prepare("SELECT * FROM holidays WHERE holiday_date=?");
			$checkHoliday->bindParam(1, $exp_date);
			$checkHoliday->execute();
			if($checkHoliday->rowCount() != 0){
				$j++;
			}
			$totalOffset = $i + $j;
			$new_exp_date = date("Y-m-d 23:59:59",strtotime($date_expiration.'+'.$totalOffset.'DAYS'));
		}
	}
	if(!isset($new_exp_date)){
		$new_exp_date = $date_expiration;
	}
	return $new_exp_date;
}

function generateReference() {
	$length = 10;
	$characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$chars_length = strlen($characters);
	$reference = '';
	for ($i = 0; $i < $length; $i++) {
		$reference .= $characters[rand(0, $chars_length - 1)];
	}
	return $reference;
}

function getCorrectProductFromTags($session_id, $user_id){
	$db = PDOFactory::getConnection();
	/** When a participation is recorded, this function will be called to find the correct product of the user based on the tags of the session the user is attending to **/
	$tags_session = $db->query("SELECT tag_id_foreign, is_mandatory FROM assoc_session_tags ast
								JOIN tags_session ts ON ts.rank_id = ast.tag_id_foreign
								WHERE session_id_foreign = $session_id
								ORDER BY is_mandatory DESC")->fetchAll(PDO::FETCH_ASSOC);

	// First, we'll list the mandatory tags of the session
	$mandatory_tags = []; $supplementary_tags = [];
	foreach($tags_session as $tag){
		if($tag["is_mandatory"] == 1){
			array_push($mandatory_tags, $tag["tag_id_foreign"]);
		} else {
			array_push($supplementary_tags, $tag["tag_id_foreign"]);
		}
	}
	/*	echo "<br>-- MANDATORY TAGS OF SESSION $session_id --</br>";
	print_r($mandatory_tags);

	echo "<br>-- SUPPLEMENTARY TAGS OF SESSION $session_id --<br>";
	print_r($supplementary_tags);*/

	// Then, we'll get all the products that have mandatory tags compatible with the session.
	$compatible_subscriptions = [];
	$i = 0;
	foreach($mandatory_tags as $tag){
		$query = "SELECT id_produit_foreign FROM produits_adherents pa
				LEFT JOIN produits p ON pa.id_produit_foreign = p.product_id
				LEFT JOIN assoc_product_tags apt ON p.product_id = apt.product_id_foreign
				LEFT JOIN transactions t ON pa.id_transaction_foreign = t.id_transaction
				WHERE tag_id_foreign = $tag
				AND id_user_foreign = $user_id
				AND p.product_size IS NOT NULL
				AND pa.actif != 2
				ORDER BY date_achat DESC";
		// If a subscription has mandatory tags that are NOT in the list of the session, they are not added to the list of compatible subscriptions.
		$pre_compatible_fetch = $db->query($query);
		while($pre_compatible_subs = $pre_compatible_fetch->fetch(PDO::FETCH_COLUMN)){
			$comparison = "SELECT tag_id_foreign FROM assoc_product_tags apt
							JOIN tags_session ts ON apt.tag_id_foreign = ts.rank_id
							WHERE product_id_foreign = $pre_compatible_subs
							AND is_mandatory = 1";
			$mandatory_tags_session = $db->query($comparison)->fetchAll(PDO::FETCH_COLUMN);
			/*echo "<br>-- MANDATORY TAGS OF SUBSCRIPTION $pre_compatible_subs --<br>";
			print_r($mandatory_tags_session);
			echo "<br>-- INTERSECT MANDATORY TAGS OF SUBSCRIPTION AND MANDATORY TAGS OF SESSION --<br>";*/
			$intersect = array_diff($mandatory_tags_session, $mandatory_tags);
			/*print_r($intersect);*/
			if(sizeof($intersect) > 0){
				/*echo "INTERSECT IS NOT NULL. SUBSCRIPTION HAS EXCLUSIVE MANDATORY TAGS AND WILL BE IGNORED.<br>";*/
			} else {
				/*echo "INTERSECT IS NULL. SUBSCRIPTION DOESN'T HAS EXCLUSIVE MANDATORY TAGS AND WILL THEREFORE BE ADDED TO THE LIST OF COMPATIBLE SUBSCRIPTIONS<br>";*/
				array_push($compatible_subscriptions, $pre_compatible_subs);
			}
		}
	}
	$compatible_subscriptions = array_unique($compatible_subscriptions);
	/*echo "<br>-- COMPATIBLE SUBSCRIPTIONS --<br>";
	print_r($compatible_subscriptions);
	echo "-- /COMPATIBLE SUBSCRIPTIONS --<br>";*/

	// Step two : take the subscription with the highest number of fitting tags.
	if(sizeof($compatible_subscriptions) == 1){ // If there's only one product that can fit.
		$query = "SELECT id_produit_adherent FROM produits_adherents pa
				WHERE id_produit_foreign = $compatible_subscriptions[0]
				AND pa.actif != 2
				AND id_user_foreign = $user_id";
		$product_id = $db->query($query)->fetch(PDO::FETCH_COLUMN);
		/*echo "<br>-- PRODUCT --<br>";*/
		return $product_id;
	} else if(sizeof($compatible_subscriptions) > 1){ // If there are more than 1 product fitting, we test non-mandatory tags
		$supplementary_array = [];
		foreach($supplementary_tags as $tag){
			$query = "SELECT id_produit_adherent FROM produits_adherents pa
				LEFT JOIN produits p ON pa.id_produit_foreign = p.product_id
				LEFT JOIN assoc_product_tags apt ON p.product_id = apt.product_id_foreign
				WHERE tag_id_foreign = $tag
				AND pa.actif != 2
				AND product_id_foreign IN (".implode(",", $compatible_subscriptions).")
				AND id_user_foreign = $user_id
				ORDER BY pa.actif DESC, id_produit_adherent DESC";
			$compatible_supplementary = $db->query($query)->fetchAll(PDO::FETCH_COLUMN);
			array_push($supplementary_array, $compatible_supplementary);
		}
		/*print_r($supplementary_array);*/

		if(sizeof($supplementary_array) > 0){
			// Merge all arrays and count values
			$eligible_products = array_count_values(call_user_func_array("array_merge", $supplementary_array));
			/*print_r($eligible_products);*/
			arsort($eligible_products, SORT_NUMERIC);
			/*print_r($eligible_products);*/
			$product_id = array_keys($eligible_products)[0]; // Product that fits
			/*echo "<br>-- PRODUCT --<br>";*/
			return $product_id;
		} else {
			/*echo "<br>-- PRODUCT --<br>";*/
			return $supplementary_array[0];
		}
	}
	/* echo NOTHING */
	return null;
}

function getLieu($id){
	$db = PDOFactory::getConnection();
	$stmt = $db->prepare('SELECT * FROM rooms WHERE room_id=?');
	$stmt->bindParam(1, $id, PDO::PARAM_INT);
	$stmt->execute();
	$res = $stmt->fetch(PDO::FETCH_ASSOC);
	return $res;
}

function isHoliday($db, $target_date){
	// We format the date to the MySQL format
	$target_date = DateTime::createFromFormat("Y-m-d H:i:s", $target_date);
	$target_date = $target_date->format("Y-m-d");
	$holiday_count = $db->query("SELECT * FROM holidays WHERE holiday_date = '$target_date'")->rowCount();
	if($holiday_count > 0)
		return true;
	else
		return false;
}

function logAction($db, $action, $target){
	if(isset($_SESSION["user_id"])){
		$insert = $db->prepare("INSERT INTO logging(user_id, action, action_target) VALUES(?, ?, ?)");
		$insert->bindParam(1, $_SESSION["user_id"]);
		$insert->bindParam(2, $action);
		$insert->bindParam(3, $target);
		$insert->execute();
	}
}

function postNotification($token, $target, $recipient, $date){
	$db = PDOFactory::getConnection();
	// To ensure there aren't two notifications about different states of the same target, we deleted every notification regarding the target before inserting the new one.
	$type_token = substr($token, 0, 3);
	$delete_previous_states = $db->query("DELETE FROM team_notifications WHERE notification_token LIKE '%$type_token%' AND notification_target = $target");
	$notification = $db->query("INSERT IGNORE INTO team_notifications(notification_token, notification_target, notification_recipient, notification_date, notification_state)
								VALUES('$token', '$target', '$recipient', '$date', '1')");
}

function solveAdherentToId($name){
	$db = PDOFactory::getConnection();
	$stmt = $db->prepare("SELECT * FROM (
							SELECT user_id, CONCAT(user_prenom, ' ', user_nom) as fullname FROM users) base
						WHERE fullname = ?");
	$stmt->bindParam(1, htmlspecialchars($name), PDO::PARAM_STR);
	$stmt->execute();
	$res = $stmt->fetch(PDO::FETCH_ASSOC);
	if($res["user_id"] != null)
		return $res["user_id"];
	else
		return null;
}

function updateColumn($db, $table, $column, $value, $target_id){
	$now = date("Y-m-d H:i:s");
	$value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5);
	if($column == "task_recipient"){
		if($value != ""){
			$value = solveAdherentToId($value);
		} else {
			$value = null;
		}
	}
	try{
		$primary_key = $db->query("SHOW INDEX FROM $table WHERE Key_name = 'PRIMARY'")->fetch(PDO::FETCH_ASSOC);

		if($value == -1){
			$query = "UPDATE $table SET $column = NULL";
		} else {
			$query = "UPDATE $table SET $column = '$value'";
		}

		if($table == "tasks"){
			$query .= ", task_last_update = '$now'";
		}

		$query .= " WHERE $primary_key[Column_name] = '$target_id'";

		$update = $db->query($query);
		//echo $query;
	} catch(PDOException $e){
		echo $e->getMessage();
	}
}
?>
