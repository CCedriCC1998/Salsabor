<?php
require_once "db_connect.php";
include "tools.php";

$data = explode('*', $_GET["carte"]);
$tag_rfid = $data[0];
$reader_token = $data[1];

prepareParticipationBeta($tag_rfid, $reader_token);

function prepareParticipationBeta($user_tag, $reader_token){
	$db = PDOFactory::getConnection();
	$today = date("Y-m-d H:i:s");
	if($user_tag != "" || $user_tag != "INIT" || $user_tag != "TEST"){
		if($reader_token == "192.168.0.3"){
			$status = "1";
			$new = $db->query("INSERT INTO participations(user_rfid, room_token, passage_date, status)
					VALUES('$user_tag', '$reader_token', '$today', '$status')");
			echo "$";
		} else {
			// If the tag is not for associating, we search a product that could be used for this session.
			// First, we get the name of the session and the ID of the user.
			// For the session, we have to find it based on the time of the record and the position.
			$values = array(
				"passage_date" => date("d/m/Y H:i:s"),
				"room_token" => $reader_token,
				"user_rfid" => $user_tag
			);
			addParticipationBeta($values);
		}
	}
	echo "$$$";
}
