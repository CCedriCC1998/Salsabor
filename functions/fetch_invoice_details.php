<?php
session_start();
include "db_connect.php";
$db = PDOFactory::getConnection();

$invoice_id = $_GET["invoice_id"];

$invoice_details = $db->query("SELECT invoice_id, invoice_address, invoice_reception_date, invoice_payment_date, invoice_token FROM invoices WHERE invoice_id = $invoice_id")->fetch();

echo json_encode($invoice_details);
?>
