<?php
include "db_connect.php";
$db = PDOFactory::getConnection();

$user_id = $_GET["user_id"];
$filter_flag = $_GET["filter_flag"];

if($filter_flag == "invoice"){
	$invoice_id = $_GET["filters"][0];
} else {
	if(isset($_GET["filters"][0]))
		$start_date = $_GET["filters"][0];
	if(isset($_GET["filters"][1]))
		$end_date = $_GET["filters"][1];
}

// Fetch sessions
$query = "SELECT session_id, session_name, session_group, session_start, session_end, rate_value, rate_ratio, rate_title, invoice_token FROM sessions s
			LEFT JOIN teacher_rates tr ON s.teacher_rate = tr.rate_id
			LEFT JOIN invoices i ON s.invoice_id = i.invoice_id
			WHERE session_teacher = $user_id";
if($filter_flag == "invoice"){
	$query .= " AND s.invoice_id = $invoice_id";
} else {
	if(isset($_GET["filters"][0]))
		$query .= " AND session_start > '$start_date'";
	if(isset($_GET["filters"][1]))
		$query .= " AND session_end < '$end_date'";
}
$query .= " ORDER BY rate_title ASC, session_start ASC";
$load = $db->query($query);

$sessions = array();
while($session = $load->fetch()){
	// Participants
	$participations = $db->query("SELECT COUNT(passage_id) FROM participations WHERE session_id = $session[session_id]")->fetch(PDO::FETCH_COLUMN);

	// Computing price
	switch($session["rate_ratio"]){
		case 'heure':
			$price = $session["rate_value"] * 1;
			break;

		case 'personne':
			$price = $session["rate_value"] * $participations;
			break;

		case 'prestation':
			$price = $session["rate_value"];
			break;

		default:
			$price = NULL;
			break;
	}

	$invoice = ($session["invoice_token"])?$session["invoice_token"]:"Aucune";

	// Array
	$s = array(
		"id" => $session["session_id"],
		"name" => $session["session_name"],
		"group" => $session["session_group"],
		"start" => $session["session_start"],
		"end" => $session["session_end"],
		"participants" => $participations,
		"invoice" => $invoice,
		"rate" => $session["rate_value"],
		"ratio" => $session["rate_ratio"],
		"rate_title" => $session["rate_title"],
		"price" => $price,
		"type" => "Cours"
	);
	array_push($sessions, $s);
}

// Fetch prestations
$query = "SELECT pu.prestation_id, prestation_address, prestation_start, prestation_end, pu.price, pu.invoice_id, i.invoice_token FROM prestation_users pu
		JOIN prestations p ON pu.prestation_id = p.prestation_id
		LEFT JOIN invoices i ON pu.invoice_id = i.invoice_id
		WHERE user_id = $user_id";
if($filter_flag == "invoice"){
	$query .= " AND pu.invoice_id = $invoice_id";
} else {
	if(isset($_GET["filters"][0]))
		$query .= " AND prestation_start > '$start_date'";
	if(isset($_GET["filters"][1]))
		$query .= " AND prestation_end < '$end_date'";
}
$query .= " ORDER BY prestation_start ASC";
$load = $db->query($query);

$prestations = array();
while($prestation = $load->fetch()){
	$invoice = ($prestation["invoice_token"])?$prestation["invoice_token"]:"Aucune";

	// Array
	$p = array(
		"id" => $prestation["prestation_id"],
		"start" => $prestation["prestation_start"],
		"end" => $prestation["prestation_end"],
		"invoice" => $invoice,
		"address" => $prestation["prestation_address"],
		"price" => $prestation["price"],
		"type" => "Prestation"
	);
	array_push($sessions, $p);
}
echo json_encode($sessions);
?>
