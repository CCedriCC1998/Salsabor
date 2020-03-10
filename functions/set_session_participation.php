<?php
include "db_connect.php";
$db = PDOFactory::getConnection();

$participation_id = $_POST["participation_id"];
$session_id = $_POST["session_id"];

$assign = $db->query("UPDATE participations SET session_id = '$session_id' WHERE passage_id = '$participation_id'");

$load = $db->query("SELECT session_name, session_start, session_end FROM sessions WHERE session_id = '$session_id'")->fetch(PDO::FETCH_ASSOC);

$s = array();
$s["cours_name"] = $load["session_name"];
$s["session_start"] = $load["session_start"];
$s["session_end"] = $load["session_end"];

echo json_encode($s);
?>
