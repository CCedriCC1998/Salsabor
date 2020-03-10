<?php
include "db_connect.php";
$db = PDOFactory::getConnection();

$booking_id = $_GET["booking_id"];
$booking_details = $db->query("SELECT *, CONCAT(user_prenom, ' ', user_nom) AS holder FROM reservations b
								JOIN users u ON b.booking_holder = u.user_id
								JOIN rooms r ON b.booking_room = r.room_id
								WHERE booking_id = '$booking_id'")->fetch(PDO::FETCH_ASSOC);

$b = array(
	"id" => $booking_details["booking_id"],
	"holder" => $booking_details["holder"],
	"room" => $booking_details["room_name"],
);
echo json_encode($b);
?>
