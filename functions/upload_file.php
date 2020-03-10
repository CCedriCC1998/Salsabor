<?php
require_once 'db_connect.php';
$db = PDOFactory::getConnection();

$location = $_POST["location"];

// Since this file is called regardless of the updated file, the main key is variable
$file_key = array_keys($_FILES)[0];

// Source file
$source_file = $_FILES[$file_key]["tmp_name"];

// We update the database as well
if(isset($_POST["user_id"])){
	$user_id = $_POST["user_id"];
	// File destination
	$new_file = $location.$user_id.".".pathinfo($_FILES[$file_key]["name"], PATHINFO_EXTENSION);
	$query = "UPDATE users SET $file_key = '$new_file' WHERE user_id = $user_id";
}

if(isset($_POST["invoice_id"])){
	$invoice_id = $_POST["invoice_id"];
	$invoice_name = $db->query("SELECT invoice_token FROM invoices WHERE invoice_id = $invoice_id")->fetch(PDO::FETCH_COLUMN);
	// File destination
	$new_file = $location.$invoice_name.".".pathinfo($_FILES[$file_key]["name"], PATHINFO_EXTENSION);
	$query = "UPDATE invoices SET $file_key = '$new_file' WHERE invoice_id = $invoice_id";
}

move_uploaded_file($source_file, $new_file);

$update = $db->query($query);

echo json_encode($file_key);
?>
