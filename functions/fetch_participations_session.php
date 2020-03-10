<?php
include "db_connect.php";
$db = PDOFactory::getConnection();

$session_id = $_GET["session_id"];

$session = $db->query("SELECT session_room, session_start
					FROM sessions
					WHERE session_id = '$session_id'")->fetch(PDO::FETCH_ASSOC);

$limit_start = date("Y-m-d H:i:s", strtotime($session["session_start"].'-30MINUTES'));
$limit_end = date("Y-m-d H:i:s", strtotime($session["session_start"].'+30MINUTES'));

$load = $db->query("SELECT * FROM participations pr
					LEFT JOIN readers re ON pr.room_token = re.reader_token
					LEFT JOIN rooms r ON re.reader_id = r.room_reader
					LEFT JOIN users u ON pr.user_id = u.user_id
					LEFT JOIN produits_adherents pa ON pr.produit_adherent_id = pa.id_produit_adherent
					LEFT JOIN produits p ON pa.id_produit_foreign = p.product_id
					LEFT JOIN sessions s ON pr.session_id = s.session_id
					WHERE pr.session_id = '$session_id'
					ORDER BY u.user_nom ASC");

$notifications_settings = $db->query("SELECT * FROM master_settings WHERE user_id = '0'")->fetch(PDO::FETCH_ASSOC);

$recordsList = array();
while($details = $load->fetch(PDO::FETCH_ASSOC)){
	$r = array();
	$r["id"] = $details["passage_id"];
	$r["card"] = $details["user_rfid"];
	$r["user_id"] = $details["user_id"];
	$r["user"] = $details["user_prenom"]." ".$details["user_nom"];
	$r["photo"] = $details["photo"];
	$r["date"] = $details["passage_date"];
	$r["status"] = $details["status"];
	if($details["product_name"] != null){
		$r["product_name"] = $details["product_name"];
		$r["product_expiration"] = max($details["date_expiration"], $details["date_fin_utilisation"], $details["date_prolongee"]);
		if($details["product_size"] == "0"){
			$r["product_hours"] = 9999;
		} else {
			$r["product_hours"] = $details["volume_cours"];
		}
	} else {
		$r["product_name"] = "-";
	}
	$r["days_before_exp"] = $notifications_settings["days_before_exp"];
	$r["hours_before_exp"] = $notifications_settings["hours_before_exp"];
	$r["count"] = $db->query("SELECT * FROM tasks
					WHERE ((task_token LIKE '%USR%' AND task_target = '$r[user_id]')
					OR (task_token LIKE '%PRD%' AND task_target IN (SELECT id_produit_adherent FROM produits_adherents WHERE id_user_foreign = '$r[user_id]'))
					OR (task_token LIKE '%TRA%' AND task_target IN (SELECT id_transaction FROM transactions WHERE payeur_transaction = '$r[user_id]')))
						AND task_state = 0")->rowCount();
	array_push($recordsList, $r);
}

echo json_encode($recordsList);
?>
