<?php
session_start();
include "db_connect.php";
$db = PDOFactory::getConnection();

$limit = $_GET["limit"];
$filter = $_GET["filter"];

// We construct the query
$query = "SELECT * FROM team_notifications WHERE (notification_recipient IS NULL OR notification_recipient = 0";
if(isset($_SESSION["user_id"])){
	$query .= " OR notification_recipient = $_SESSION[user_id]";
}
$query.= ")";
if($filter != null){
	$query .= " AND";
	if($filter == "seen"){
		$query .= " notification_state = 0";
	} else if($filter == "new"){
		$query .= " notification_state = 1";
	}
}
$query .= " ORDER BY notification_id DESC";
if($limit != 0){
	$query .= " LIMIT $limit";
}
$load = $db->query($query);

$notificationsList = array();

while($details = $load->fetch(PDO::FETCH_ASSOC)){
	$n = array();
	$n["id"] = $details["notification_id"];
	$n["token"] = $details["notification_token"];
	$n["target"] = $details["notification_target"];
	// Additional details depending of the token
	$n["type"] = substr($n["token"], 0, 3);
	$n["subtype"] = substr($n["token"], 4);
	switch($n["type"]){
		case "PRD": // We have to get the details of the product then
			$sub_query = $db->query("SELECT * FROM produits_adherents pa
									JOIN produits p ON pa.id_produit_foreign = p.product_id
									JOIN users u ON pa.id_user_foreign = u.user_id WHERE id_produit_adherent = '$n[target]'")->fetch(PDO::FETCH_ASSOC);
			$n["product_name"] = $sub_query["product_name"];
			$n["product_validity"] = max($sub_query["date_expiration"], $sub_query["date_prolongee"]);
			if(isset($sub_query["date_fin_utilisation"]) && $sub_query["date_fin_utilisation"] != "0000-00-00 00:00:00"){
				$n["product_usage"] = $sub_query["date_fin_utilisation"];
			} else {
				$n["product_usage"] = $n["product_validity"];
			}
			$n["user"] = $sub_query["user_prenom"]." ".$sub_query["user_nom"];
			$n["remaining_hours"] = $sub_query["volume_cours"];
			$n["user_id"] = $sub_query["user_id"];
			$n["photo"] = $sub_query["photo"];
			break;

		case "MAT": // We have to get the details of the maturity
			/*$sub_query = $db->query("SELECT * FROM produits_echeances pe
			JOIN transactions t ON pe.reference_achat = t.id_transaction WHERE produits_echeances_id = '$n[target]'")->fetch(PDO::FETCH_ASSOC);*/
			$sub_query = $db->query("SELECT * FROM produits_echeances pe
									LEFT JOIN transactions t ON pe.reference_achat = t.id_transaction
									LEFT JOIN users u ON t.payeur_transaction = u.user_id
			WHERE produits_echeances_id = '$n[target]'")->fetch(PDO::FETCH_ASSOC);
			$n["payer"] = $sub_query["payeur_echeance"];
			$n["user_id"] = $sub_query["payeur_transaction"];
			$n["maturity_date"] = $sub_query["date_echeance"];
			$n["maturity_value"] = $sub_query["montant"];
			$n["transaction"] = $sub_query["reference_achat"];
			$n["photo"] = $sub_query["photo"];
			break;

		case "MAI": // Here, we only need the user name for the mail address.
			$sub_query = $db->query("SELECT user_prenom, user_nom, photo FROM users u WHERE user_id = '$n[target]'")->fetch(PDO::FETCH_ASSOC);
			$n["user"] = $sub_query["user_prenom"]." ".$sub_query["user_nom"];
			$n["user_id"] = $n["target"];
			$n["photo"] = $sub_query["photo"];
			break;

		case "SES": // Notification when a session has been opened by the system
			$sub_query = $db->query("SELECT * FROM sessions s
									JOIN rooms r ON s.session_room = r.room_id
									JOIN users u ON s.session_teacher = u.user_id
									WHERE session_id='$n[target]'")->fetch(PDO::FETCH_ASSOC);
			$n["session_id"] = $sub_query["session_id"];
			$n["cours_name"] = $sub_query["session_name"];
			$n["salle"] = $sub_query["room_name"];
			$n["session_start"] = $sub_query["session_start"];
			$n["user"] = $sub_query["user_prenom"]." ".$sub_query["user_nom"];
			$n["photo"] = $sub_query["photo"];
			$n["cours_status"] = $sub_query["session_opened"];
			break;

		case "TAS": // Notifications for tasks
			$sub_query = $db->query("SELECT * FROM tasks t WHERE task_id = '$n[target]'")->fetch(PDO::FETCH_ASSOC);
			$n["title"] = $sub_query["task_title"];
			$n["deadline"] = $sub_query["task_deadline"];
			$n["task_type"] = $sub_query["task_token"];
			$n["sub_target"] = $sub_query["task_target"];
			switch($n["task_type"]){
				case "USR": // Here, we only need the user name for the mail address.
					$sub_sub_query = $db->query("SELECT CONCAT(user_prenom, ' ', user_nom) AS user, user_id, photo FROM users u WHERE user_id = '$n[sub_target]'")->fetch(PDO::FETCH_ASSOC);
					$n["user_id"] = $n["target"];
					$n["link"] = "user/".$sub_sub_query["user_id"];
					$n["photo"] = $sub_sub_query["photo"];
					$n["user"] = $sub_sub_query["user"];
					break;

				case "PRD":
					$sub_sub_query = $db->query("SELECT user_id, CONCAT(user_prenom, ' ', user_nom) AS user, photo FROM users u WHERE user_id = (SELECT id_user_foreign FROM produits_adherents WHERE id_produit_adherent ='$n[sub_target]')")->fetch(PDO::FETCH_ASSOC);
					$n["user_id"] = $sub_sub_query["user_id"];
					$n["link"] = "user/".$n["user_id"]."/abonnements";
					$n["photo"] = $sub_sub_query["photo"];
					break;

				case "TRA":
					$sub_sub_query = $db->query("SELECT user_id, CONCAT(user_prenom, ' ', user_nom) AS user, photo FROM users u WHERE user_id = (SELECT payeur_transaction FROM transactions WHERE id_transaction = '$n[sub_target]')")->fetch(PDO::FETCH_ASSOC);
					$n["user_id"] = $sub_sub_query["user_id"];
					$n["link"] = "user/".$n["user_id"]."/achats#purchase-".$n["sub_target"];
					$n["photo"] = $sub_sub_query["photo"];
					break;

				case "CMT":
					$sub_sub_query = $db->query("SELECT CONCAT(user_prenom, ' ', user_nom) AS user, user_id, photo FROM users u WHERE user_id = '$n[sub_target]'")->fetch(PDO::FETCH_ASSOC);
					$n["link"] = "user/".$n["user_id"]."/taches";
					break;

				default:
					break;
			}
			// Handling the title's tokens.
			$pattern = "/(![a-z0-9]+!)/i";
			preg_match_all($pattern, $n["title"], $matches, PREG_SET_ORDER);
			foreach($matches as $val){
				switch($val[0]){
					case "!MAIL!":
						$n["title"] = preg_replace("/!MAIL!/", $n["mail"], $n["title"]);
						break;

					case "!USER!":
						$n["title"] = preg_replace("/!USER!/", "<strong>".$n["user"]."</strong>", $n["title"]);
						break;

					case "!PRD!":
						$n["title"] = preg_replace("/!PRD!/", "<strong>".$n["sub_target"]."</strong>", $n["title"]);
						break;

					case "!TRA!":
						$n["title"] = preg_replace("/!TRA!/", "<strong>".$n["sub_target"]."</strong>", $n["title"]);
						break;

					default:
						break;
				}
			}
			break;

		case "PRO": // Promotions
			$sub_query = $db->query("SELECT * FROM produits p
									WHERE product_id = '$n[target]'")->fetch(PDO::FETCH_ASSOC);
			$n["product_id"] = $sub_query["product_id"];
			$n["product_name"] = $sub_query["product_name"];
			$n["date_activation"] = $sub_query["date_activation"];
			$n["date_desactivation"] = $sub_query["date_desactivation"];
	}
	$n["date"] = $details["notification_date"];
	$n["status"] = $details["notification_state"];
	array_push($notificationsList, $n);
}
echo json_encode($notificationsList);
?>
