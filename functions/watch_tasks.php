<?php
session_start();
require_once "db_connect.php";
$db = PDOFactory::getConnection();

$pendingTasks = $db->query("SELECT * FROM tasks WHERE (task_recipient IS NULL OR task_recipient = 0 OR task_recipient = '$_SESSION[user_id]') AND task_state = 0")->rowCount();
echo $pendingTasks;
?>
