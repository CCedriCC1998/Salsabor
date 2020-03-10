<?php
include "db_connect.php";
include "tools.php";
$db = PDOFactory::getConnection();

$user_name = $_GET["user_name"];

$user_id = solveAdherentToId($user_name) or NULL;

if($user_id != NULL){
	$stmt = $db->query("SELECT * FROM teacher_rates WHERE user_id_foreign = $user_id");

	$rates = array();
	while($rate = $stmt->fetch(PDO::FETCH_ASSOC)){
		$r = array(
			"value" => $rate["rate_id"],
			"text" => $rate["rate_title"]." (".$rate["rate_value"]."€/".$rate["rate_ratio"].")"
		);
		array_push($rates, $r);
	}
	echo json_encode($rates);
} else {
	echo $user_id;
}

?>
