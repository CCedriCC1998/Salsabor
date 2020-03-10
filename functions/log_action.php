<?php
session_start();
require_once "db_connect.php";
include "tools.php";
$db = PDOFactory::getConnection();

$table = $_POST["table"];
$action = $_POST["action"];
$target_id = $_POST["target_id"];

logAction($db, $action, $table."-".$target_id);
?>
