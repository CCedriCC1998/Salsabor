<?php
session_start();
require_once "db_connect.php";
$db = PDOFactory::getConnection();

$check_date = $_GET["check_date"];

$value = $db->query("SELECT holiday_id FROM holidays WHERE holiday_date = '$check_date' AND holiday_location = $_SESSION[location]");

$holiday_id = $value->fetch(PDO::FETCH_COLUMN);

echo ($holiday_id!=null)?$holiday_id:-1;

?>
