<?php
require_once "db_connect.php";
require_once "tools.php";
$db = PDOFactory::getConnection();

$user_credentials = $_GET["user_credentials"];
if(is_numeric($user_credentials)){
	$user_id = $user_credentials;
} else {
	$user_id = solveAdherentToId($user_credentials) or NULL;
}

if(isset($user_id)){
	$stmt = $db->query("SELECT * FROM invoices WHERE invoice_seller_id = $user_id ORDER BY invoice_period DESC");
	$invoices = array();
	// Empty option
	$i = array(
		"value" => 0,
		"token" => "Choisissez une facture"
	);
	array_push($invoices, $i);
	while($invoice = $stmt->fetch()){
		$i = array(
			"value" => $invoice["invoice_id"],
			"token" => $invoice["invoice_token"]
		);
		array_push($invoices, $i);
	}
echo json_encode($invoices);
}
?>
