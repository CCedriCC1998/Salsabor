<?php
include "db_connect.php";
$db = PDOFactory::getConnection();

$date = $_GET["date"];

$last_record = $db->query("SELECT * FROM participations pr
							LEFT JOIN readers re ON pr.room_token = re.reader_token
							LEFT JOIN rooms r ON re.reader_id = r.room_reader
							LEFT JOIN users u ON pr.user_id = u.user_id
							LEFT JOIN produits_adherents pa ON pr.produit_adherent_id = pa.id_produit_adherent
							LEFT JOIN produits p ON pa.id_produit_foreign = p.product_id
							LEFT JOIN sessions s ON pr.session_id = s.session_id
							WHERE passage_date > '$date' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

echo json_encode($last_record);
