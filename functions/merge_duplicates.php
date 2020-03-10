<?php
require_once 'db_connect.php';
$db = PDOFactory::getConnection();

// PART 2 OF THE MERGE : https://www.youtube.com/watch?v=_VnjLiFXXho

$target_id = $_POST["target_id"];
parse_str($_POST["values"], $values);

// We update the main entry first
$update_query = "UPDATE users SET ";
foreach($values as $column => $value){
	if(sizeof($values[$column]) > 1 || $value == "Aucune valeur"){
			$update_query .= "$column = NULL";
	} else {
		if($value != ""){
			$update_query .= "$column = ".$db->quote($value);
		}
	}
	if($column !== end(array_keys($values))){
		$update_query .= ", ";
	}
}
$update_query .= " WHERE user_id = $target_id";
$db->query($update_query);

// We then get the duplicate IDs
$duplicates = $db->query("SELECT user_id FROM users WHERE CONCAT(user_prenom, ' ', user_nom) = (SELECT CONCAT(user_prenom, ' ', user_nom) AS identity FROM users WHERE user_id = $target_id) AND user_id != $target_id")->fetchAll(PDO::FETCH_COLUMN);

// Then we will change the user_id of all attached records to the duplicates
for($i = 0; $i < sizeof($duplicates); $i++){
	// Updating events
	$db->query("UPDATE events SET event_handler = $target_id WHERE event_handler = $duplicates[$i]");

	// Participations
	$db->query("UPDATE participations SET user_id = $target_id WHERE user_id = $duplicates[$i]");

	// Subscriptions
	$db->query("UPDATE produits_adherents SET id_user_foreign = $target_id WHERE id_user_foreign = $duplicates[$i]");

	// Bookings
	$db->query("UPDATE reservations SET booking_holder = $target_id WHERE booking_holder = $duplicates[$i]");

	// Sessions
	$db->query("UPDATE sessions SET session_teacher = $target_id WHERE session_teacher = $duplicates[$i]");
	$db->query("UPDATE session_groups SET group_teacher = $target_id WHERE group_teacher = $duplicates[$i]");

	// Tasks
	$db->query("UPDATE tasks SET task_target = $target_id WHERE task_target = $duplicates[$i] AND task_token = 'USR'");
	$db->query("UPDATE tasks SET task_recipient = $target_id WHERE task_recipient = $duplicates[$i]");
	$db->query("UPDATE task_comments SET task_comment_author = $target_id WHERE task_comment_author = $duplicates[$i]");

	// Rates
	$db->query("UPDATE teacher_rates SET user_id_foreign = $target_id WHERE user_id_foreign = $duplicates[$i]");

	// Notifications
	$db->query("UPDATE team_notifications SET notification_target = $target_id WHERE notification_recipient = $duplicates[$i] AND notification_token = 'MAI'");
	$db->query("UPDATE team_notifications SET notification_recipient = $target_id WHERE notification_recipient = $duplicates[$i]");

	// Transactions
	$db->query("UPDATE transactions SET payeur_transaction = $target_id WHERE payeur_transaction = $duplicates[$i]");
	$db->query("UPDATE transactions SET transaction_handler = $target_id WHERE transaction_handler = $duplicates[$i]");

	// We lastly delete the duplicate
	$delete_duplicate = $db->query("DELETE FROM users WHERE user_id = $duplicates[$i]");
}
