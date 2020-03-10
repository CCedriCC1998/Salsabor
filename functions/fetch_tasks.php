<?php
session_start();
include "db_connect.php";
$db = PDOFactory::getConnection();

$limit = $_GET["limit"]; // Fetching limit
$target_id = $_GET["user_id"]; // Target ID
$attached_id = $_GET["attached_id"]; // Task recipient (the person who has to sovle the task)
$filter = $_GET["filter"]; // Filter (done, pending, all)
$task_token = $_GET["task_token"]; // Token (entity the task concerns)
$creator_id = $_SESSION["user_id"]; // Creator ID (the person who created the task)

// We dynamically construct the query depending on the flags
$query = "SELECT *, CONCAT (u.user_prenom, ' ', u.user_nom) AS recipient, CONCAT (u2.user_prenom, ' ', u2.user_nom) AS creator, u2.user_id AS creator_id FROM tasks t
			LEFT JOIN users u ON t.task_recipient = u.user_id
			LEFT JOIN users u2 ON t.task_creator = u2.user_id
			LEFT JOIN assoc_task_tags at ON t.task_id = at.task_id_foreign";
if($target_id != 0 || $attached_id != 0 || $filter != ""){
	$query .= " WHERE";
}
if($task_token != null){
	$query .= " task_token LIKE '%$task_token%' AND task_target = '$target_id'";
} else {
	if($target_id != 0){
		$query .= " (task_token LIKE '%USR%' AND task_target = '$target_id')
					OR (task_token LIKE '%PRD%' AND task_target IN (SELECT id_produit_adherent FROM produits_adherents WHERE id_user_foreign = '$target_id'))
					OR (task_token LIKE '%TRA%' AND task_target IN (SELECT id_transaction FROM transactions WHERE payeur_transaction = '$target_id'))";
	} else if($attached_id == null){
		$query .= " task_recipient IS NULL AND task_id NOT IN (SELECT task_id_foreign FROM assoc_task_tags) AND";
	}
}
if($target_id != 0 && $attached_id != 0){
	$query .= " AND";
}
if($attached_id != 0){
	$query .= " (task_recipient = $attached_id OR tag_id_foreign IN (SELECT tag_id_foreign FROM assoc_user_tags WHERE user_id_foreign = $attached_id) OR task_creator = $creator_id)";
}
if($attached_id != 0 && $filter != ""){
	$query .= " AND";
}
if($filter == "pending"){
	$query .= " task_state = 0";
} else if($filter == "done"){
	$query .= " task_state = 1";
}
$query .= " ORDER BY task_id DESC";
if($limit != 0){
	$query .= " LIMIT $limit";
}
/*echo $query;*/
$load = $db->query($query);
$task_list = array();

while($details = $load->fetch(PDO::FETCH_ASSOC)){
	$t = array();
	$t["id"] = $details["task_id"];
	$t["token"] = $details["task_token"];
	$t["target"] = $details["task_target"];
	if($details["task_recipient"] != null && $details["task_recipient"] != 0){
		$t["recipient"] = $details["recipient"];
		$t["recipient_id"] = $details["task_recipient"];
	} else {
		$t["recipient"] = "";
	}
	if($details["task_creator"] == null){
		$t["creator"] = "Système";
		$t["creator_id"] = -1;
	} else {
		$t["creator"] = $details["creator"];
		$t["creator_id"] = $details["creator_id"];
	}
	// Additional details depending of the token
	$t["type"] = substr($t["token"], 0, 3);
	switch($t["type"]){
		case "USR": // Here, we only need the user name for the mail address.
			$sub_query = $db->query("SELECT CONCAT(user_prenom, ' ', user_nom) AS user, photo FROM users u WHERE user_id = '$t[target]'")->fetch(PDO::FETCH_ASSOC);
			$t["user_id"] = $t["target"];
			$t["link"] = "user/".$t["user_id"];
			$t["user"] = $sub_query["user"];
			$t["photo"] = $sub_query["photo"];
			$t["target_phrase"] = $t["user"];
			break;

		case "PRD": // Task for products
			$sub_query = $db->query("SELECT user_id, CONCAT(user_prenom, ' ', user_nom) AS user, photo, product_name FROM users u JOIN produits_adherents pa ON pa.id_user_foreign = u.user_id JOIN produits p ON p.product_id = pa.id_produit_foreign WHERE id_produit_adherent ='$t[target]'")->fetch(PDO::FETCH_ASSOC);
			$t["user_id"] = $sub_query["user_id"];
			$t["link"] = "user/".$t["user_id"]."/abonnements";
			$t["user"] = $sub_query["user"];
			$t["photo"] = $sub_query["photo"];
			$t["target_phrase"] = $sub_query["product_name"]." de ".$t["user"];
			break;

		case "TRA": // Task for transactions
			$sub_query = $db->query("SELECT user_id, CONCAT(user_prenom, ' ', user_nom) AS user, photo FROM users u WHERE user_id IN (SELECT payeur_transaction FROM transactions WHERE id_transaction = '$t[target]')")->fetch(PDO::FETCH_ASSOC);
			$t["user_id"] = $sub_query["user_id"];
			$t["link"] = "user/".$t["user_id"]."/achats#purchase-".$t["target"];
			$t["user"] = $sub_query["user"];
			$t["photo"] = $sub_query["photo"];
			$t["target_phrase"] = "Transaction n°".$t["target"]." de ".$t["user"];
			break;

		case "SES": // Task for sessions
			$sub_query = $db->query("SELECT session_name, session_start, session_end, room_name FROM sessions s JOIN rooms r ON s.session_room = r.room_id WHERE session_id = '$t[target]'")->fetch(PDO::FETCH_ASSOC);
			$t["photo"] = "assets/images/sticker_promo.png";
			$t["link"] = "cours/".$t["target"];
			$date_start = date_create($sub_query["session_start"])->format("d/m/Y");
			$hour_start = date_create($sub_query["session_start"])->format("H:i");
			$hour_end = date_create($sub_query["session_end"])->format("H:i");
			$t["target_phrase"] = "Cours de ".$sub_query["session_name"]." du ".$date_start." de ".$hour_start." à ".$hour_end." en ".$sub_query["room_name"];
			break;

		case "EVT": // Task for events
			$sub_query = $db->query("SELECT event_name, event_start, event_end FROM events WHERE event_id = '$t[target]'")->fetch(PDO::FETCH_ASSOC);
			$event_start = date_create($sub_query["event_start"])->format("d/m/Y");
			$event_end = date_create($sub_query["event_end"])->format("d/m/Y");
			$t["photo"] = "assets/images/sticker_promo.png"; // TEMPORARY
			$t["link"] = "event/".$t["target"];
			$t["target_phrase"] = $sub_query["event_name"]." du ".$event_start." au ".$event_end;
			break;

		case "BKN": // Task for bookings
			$sub_query = $db->query("SELECT CONCAT(user_prenom, ' ', user_nom) AS holder, booking_start, booking_end, photo FROM reservations b JOIN users u ON b.booking_holder = u.user_id WHERE booking_id = '$t[target]'")->fetch(PDO::FETCH_ASSOC);
			$date_start = date_create($sub_query["booking_start"])->format("d/m/Y");
			$hour_start = date_create($sub_query["booking_start"])->format("H:i");
			$hour_end = date_create($sub_query["booking_end"])->format("H:i");
			$t["photo"] = $sub_query["photo"];
			$t["link"] = "reservation/".$t["target"];
			$t["target_phrase"] = "Réservation par ".$sub_query["holder"]." du ".$date_start." de ".$hour_start." à ".$hour_end;
			break;

		case "MAT": // Task for maturities
			$sub_query = $db->query("SELECT m.reference_achat, m.date_echeance, u.user_id, u.photo FROM produits_echeances m
									JOIN transactions t ON m.reference_achat = t.id_transaction
									JOIN users u ON t.payeur_transaction = u.user_id
									WHERE produits_echeances_id = '$t[target]'")->fetch(PDO::FETCH_ASSOC);
			$date = date_create($sub_query["date_echeance"])->format("d/m/Y");
			$t["target_phrase"] = "Echéance du ".$date." pour la transaction ".$sub_query["reference_achat"];
			$t["link"] = "user/".$sub_query["user_id"]."/achats#purchase-".$sub_query["reference_achat"];
			$t["photo"] = $sub_query["photo"];
			break;

		default:
			break;
	}
	$t["date"] = $details["task_creation_date"];
	$t["deadline"] = $details["task_deadline"];
	$t["title"] = $details["task_title"];
	if($details["task_description"] != ""){
		$t["description"] = htmlspecialchars_decode($details["task_description"]);
	} else {
		$t["description"] = "Ajouter une description";
	}
	$t["message_count"] = $db->query("SELECT * FROM task_comments WHERE task_id_foreign = '$t[id]'")->rowCount();
	$t["status"] = $details["task_state"];

	$t["connected_id"] = $_SESSION["user_id"];

	// Tags
	$labels = $db->query("SELECT * FROM assoc_task_tags ur
						JOIN tags_user tu ON ur.tag_id_foreign = tu.rank_id
						WHERE task_id_foreign = '$t[id]'");
	$t["labels"] = array();
	while($label = $labels->fetch(PDO::FETCH_ASSOC)){
		$l = array();
		$l["entry_id"] = $label["entry_id"];
		$l["tag_color"] = $label["tag_color"];
		$l["rank_name"] = $label["rank_name"];
		array_push($t["labels"], $l);
	}
	array_push($task_list, $t);
}
echo json_encode($task_list);
?>
