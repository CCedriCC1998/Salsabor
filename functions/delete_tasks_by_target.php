<?php
// When an entity is deleted, the tasks associated to it must be deleted as well. As this level of complexity cannot be handled by MySQL "ON DELETE CASCADE" mechanism, this file serves the purpose of cleaning after something is deleted.
include "db_connect.php";
$db = PDOFactory::getConnection();

$token = $_POST["token"];
$target_id = $_POST["target_id"];

$delete = $db->query("DELETE FROM tasks WHERE task_token = '$token' AND task_target = '$target_id'");
?>
