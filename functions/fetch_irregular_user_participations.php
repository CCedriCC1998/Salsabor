<?php
include "db_connect.php";
$db = PDOFactory::getConnection();

$user_id = $_GET["user_id"];
$archive = $_GET["archive"];

$load = $db->query("SELECT pr.passage_id, pr.passage_date, pr.status, s.session_id, s.session_name, s.session_start, s.session_end, p.product_name, pa.date_expiration, pa.date_fin_utilisation, pa.date_prolongee, p.product_size, pa.volume_cours, r.room_name AS reader_room_name, r2.room_name AS session_room_name, u.user_rfid, pr.room_token FROM participations pr
					LEFT JOIN readers re ON pr.room_token = re.reader_token
					LEFT JOIN rooms r ON re.reader_id = r.room_reader
					LEFT JOIN users u ON pr.user_id = u.user_id
					LEFT JOIN produits_adherents pa ON pr.produit_adherent_id = pa.id_produit_adherent
					LEFT JOIN produits p ON pa.id_produit_foreign = p.product_id
					LEFT JOIN sessions s ON pr.session_id = s.session_id
					LEFT JOIN rooms r2 ON s.session_room = r.room_id
					WHERE (pr.status != 2 OR (pr.status = 2 AND (produit_adherent_id IS NULL OR produit_adherent_id = '' OR produit_adherent_id = 0)))
					AND pr.user_id = '$user_id'
					AND pr.archived = $archive
					GROUP BY pr.passage_id
					ORDER BY pr.passage_id DESC");

$notifications_settings = $db->query("SELECT * FROM master_settings WHERE user_id = '0'")->fetch(PDO::FETCH_ASSOC);

$recordsList = array();
while($details = $load->fetch(PDO::FETCH_ASSOC)){
	// Find possible duplicates
	$lower_limit = date("Y-m-d H:i:s", strtotime($details["passage_date"].'-20MINUTES'));
	$upper_limit = date("Y-m-d H:i:s", strtotime($details["passage_date"].'+20MINUTES'));
	$duplicates = $db->query("SELECT passage_id FROM participations
							WHERE user_rfid = '$details[user_rfid]'
							AND room_token = '$details[room_token]'
							AND CASE WHEN session_id IS NOT NULL
								THEN session_id = '$details[session_id]'
							END
							AND passage_date BETWEEN '$lower_limit' AND '$upper_limit'
							AND passage_id != '$details[passage_id]'
							ORDER BY room_token DESC")->fetch(PDO::FETCH_COLUMN);

	$r = array();
	$r["duplicates"] = $duplicates;
	$r["id"] = $details["passage_id"];
	$r["date"] = $details["passage_date"];
	$r["status"] = $details["status"];
	$r["room"] = ($details["reader_room_name"] == null)?$details["session_room_name"]:$details["reader_room_name"];
	$r["session_id"] = $details["session_id"];
	$r["cours_name"] = $details["session_name"];
	$r["session_start"] = $details["session_start"];
	$r["session_end"] = $details["session_end"];
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
	array_push($recordsList, $r);
}

echo json_encode($recordsList);
?>
