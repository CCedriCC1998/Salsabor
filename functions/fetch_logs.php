<?php
session_start();
require_once "db_connect.php";
$db = PDOFactory::getConnection();

// This function fetches logs. It can be all the logs, or only some.

$query = "SELECT entry_id, u.user_id, CONCAT(user_prenom, ' ', user_nom) AS identity, photo, action, action_target, action_time
			FROM logging l
			LEFT JOIN users u ON l.user_id = u.user_id";

if($_GET["target"] != null)
	$query .= " WHERE action_target = ''";

$query .= " WHERE entry_id > $_GET[last_id]";

$query .= " ORDER BY entry_id ASC";

$stmt = $db->query($query);

$logs = array();
while($log = $stmt->fetch()){
	// Fetch target details
	preg_match('/([a-z\_]+)-([a-z0-9]+)/i', $log["action_target"], $tokens);
	$target_table = $tokens[1];
	$target_id = $tokens[2];

	$l = array(
		"id" => $log["entry_id"],
		"user_id" => $log["user_id"],
		"user_name" => $log["identity"],
		"user_photo" => $log["photo"],
		"action" => $log["action"],
		"target" => $log["action_target"],
		"target_type" => $target_table,
		"timestamp" => $log["action_time"]
	);

	switch($target_table){
		case 'users':
			$l["target_name"] = $db->query("SELECT CONCAT(user_prenom, ' ', user_nom) AS target_identity FROM users WHERE user_id = $target_id")->fetch(PDO::FETCH_COLUMN);
			$l["url"] = "user/".$target_id;
			break;

		case 'produits':
			$l["target_name"] = $db->query("SELECT product_name FROM produits WHERE product_id = $target_id")->fetch(PDO::FETCH_COLUMN);
			$l["url"] = "forfait/".$target_id;
			break;

		case 'product_categories':
			$l["target_name"] = $db->query("SELECT category_name FROM product_categories WHERE category_id = $target_id")->fetch(PDO::FETCH_COLUMN);
			$l["url"] = "categories-produits";
			break;

		case 'locations':
			$l["target_name"] = $db->query("SELECT location_name FROM locations WHERE location_id = $target_id")->fetch(PDO::FETCH_COLUMN);
			$l["url"] = "salles";
			break;

		case 'rooms':
			$l["target_name"] = $db->query("SELECT room_name FROM rooms WHERE room_id = $target_id")->fetch(PDO::FETCH_COLUMN);
			$l["url"] = "salles";
			break;

		case 'sessions':
			$l["target_name"] = $db->query("SELECT session_name FROM sessions WHERE session_id = $target_id")->fetch(PDO::FETCH_COLUMN);
			$l["url"] = "cours/".$target_id;
			break;

		case 'session_groups':
			$l["target_name"] = $db->query("SELECT parent_intitule FROM session_groups WHERE session_group_id = $target_id")->fetch(PDO::FETCH_COLUMN);
			break;

		case 'participations':
			$details = $db->query("SELECT CONCAT(user_prenom, ' ', user_nom) AS identity, session_name, s.session_id FROM participations p
				LEFT JOIN users u ON p.user_id = u.user_id
				LEFT JOIN sessions s ON p.session_id = s.session_id
				WHERE passage_id = $target_id")->fetch();
			$l["url"] = "cours/".$details["session_id"]."#session-".$details["session_id"];
			$l["target_name"] = " de ".$details["identity"]." au cours de ".$details["session_name"];
			break;

		case 'produits_echeances':
			$details = $db->query("SELECT m.reference_achat, m.date_echeance, u.user_id
				FROM produits_echeances m
				JOIN transactions t ON m.reference_achat = t.id_transaction
				JOIN users u ON t.payeur_transaction = u.user_id
				WHERE produits_echeances_id = $target_id")->fetch();
			$l["url"] = "user/".$details["user_id"]."/achats#purchase-".$details["reference_achat"];
			$l["target_name"] = " du ".date_create($details["date_echeance"])->format("d/m/Y")." pour la transaction ".$details["reference_achat"];
			break;

		case 'tasks':
			$details = $db->query("SELECT task_title, task_token, task_target FROM tasks WHERE task_id = $target_id")->fetch();
			switch($details["task_token"]){
				case "USR":
					$l["url"] = "user/".$details["task_target"]."/taches#task-".$target_id;
					break;

				case "PRD": // Task for products
					$user_id = $db->query("SELECT user_id FROM users u
						JOIN produits_adherents pa ON pa.id_user_foreign = u.user_id
						WHERE id_produit_adherent ='$details[task_target]'")->fetch(PDO::FETCH_COLUMN);
					$l["url"] = "user/".$user_id."/taches#task-".$target_id;
					break;

				case "TRA": // Task for transactions
					$user_id = $db->query("SELECT user_id FROM users u WHERE user_id IN (SELECT payeur_transaction FROM transactions WHERE id_transaction = '$details[task_target]')")->fetch(PDO::FETCH_COLUMN);
					$l["url"] = "user/".$user_id."/taches#task-".$target_id;
					break;

				case "SES": // Task for sessions
					$l["url"] = "cours/".$details["task_target"];
					break;

				case "EVT": // Task for events
					$l["url"] = "event/".$details["task_target"];
					break;

				case "BKN": // Task for bookings
					$l["url"] = "reservation/".$details["task_target"];
					break;

				case "MAT": // Task for maturities
					$user_id = $db->query("SELECT u.user_id FROM produits_echeances m
									JOIN transactions t ON m.reference_achat = t.id_transaction
									JOIN users u ON t.payeur_transaction = u.user_id
									WHERE produits_echeances_id = '$details[task_target]'")->fetch(PDO::FETCH_COLUMN);
					$l["url"] = "user/".$user_id."/taches#task-".$target_id;
					break;

				default:
					break;
			}
			$l["target_name"] = $details["task_title"];
			break;

		case 'tags_user':
		case 'tags_session':
			$l["target_name"] = $db->query("SELECT rank_name FROM $target_table WHERE rank_id = $target_id")->fetch(PDO::FETCH_COLUMN);
			break;

		case 'task_comments':
			$details = $db->query("SELECT task_title, task_token, task_target FROM task_comments tc JOIN tasks t ON tc.task_id_foreign = t.task_id WHERE task_comment_id = $target_id")->fetch();
			switch($details["task_token"]){
				case "USR":
					$l["url"] = "user/".$details["task_target"]."/taches#task-".$target_id;
					break;

				case "PRD": // Task for products
					$user_id = $db->query("SELECT user_id FROM users u
						JOIN produits_adherents pa ON pa.id_user_foreign = u.user_id
						WHERE id_produit_adherent ='$details[task_target]'")->fetch(PDO::FETCH_COLUMN);
					$l["url"] = "user/".$user_id."/taches#task-".$target_id;
					break;

				case "TRA": // Task for transactions
					$user_id = $db->query("SELECT user_id FROM users u WHERE user_id IN (SELECT payeur_transaction FROM transactions WHERE id_transaction = '$details[task_target]')")->fetch(PDO::FETCH_COLUMN);
					$l["url"] = "user/".$user_id."/taches#task-".$target_id;
					break;

				case "SES": // Task for sessions
					$l["url"] = "cours/".$details["task_target"];
					break;

				case "EVT": // Task for events
					$l["url"] = "event/".$details["task_target"];
					break;

				case "BKN": // Task for bookings
					$l["url"] = "reservation/".$details["task_target"];
					break;

				case "MAT": // Task for maturities
					$sub_query = $db->query("SELECT u.user_id FROM produits_echeances m
									JOIN transactions t ON m.reference_achat = t.id_transaction
									JOIN users u ON t.payeur_transaction = u.user_id
									WHERE produits_echeances_id = '$details[task_target]'")->fetch(PDO::FETCH_COLUMN);
					$l["url"] = "user/".$sub_query["user_id"]."/taches#task-".$target_id;
					break;

				default:
					break;
			}
			$l["target_name"] = $details["task_title"];
			break;

		case 'transactions':
			$user_id = $db->query("SELECT user_id FROM users u WHERE user_id IN (SELECT payeur_transaction FROM transactions WHERE id_transaction = '$target_id')")->fetch(PDO::FETCH_COLUMN);
			$l["url"] = "user/".$user_id."/achats#purchase-".$target_id;
			$l["target_name"] = $target_id;
			break;

		default:
			$l["target_name"] = null;
	}

	array_push($logs, $l);
}
echo json_encode($logs);

?>
