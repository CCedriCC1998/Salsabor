<?php
require_once "../Salsabor/functions/db_connect.php";
include "../Salsabor/functions/tools.php";
$db = PDOFactory::getConnection();

$data = explode('*', $_GET["carte"]);
$tag_rfid = $data[0];
$reader_token = $data[1];

prepareParticipationBeta($tag_rfid, $reader_token);

function prepareParticipationBeta($user_tag, $reader_token){
	$today = date("Y-m-d H:i:s");
	if($reader_token == "192.168.0.3"){
		$status = "1";
		$new = $db->query("INSERT INTO participations(user_rfid, room_token, passage_date, status)
					VALUES('$user_tag', '$reader_token', '$today', '$status')");
		echo "$";
	} else {
		// If the tag is not for associating, we search a product that could be used for this session.
		// First, we get the name of the session and the ID of the user.
		// For the session, we have to find it based on the time of the record and the position.
		$values = array();
		$values["passage_date"] = date("d/m/Y H:i:s");
		$values["room_token"] = $reader_token;
		$values["user_rfid"] = $user_tag;
		addParticipationBeta($values);
	}
}
