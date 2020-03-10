<?php
// Correcting file that will delete all duplicates for tasks and notifications
require_once "db_connect.php";
$db = PDOFactory::getConnection();

$all_tasks = $db->query("SELECT task_id, task_token, task_target, task_creation_date FROM tasks ORDER BY task_creation_date ASC");

while($task = $all_tasks->fetch(PDO::FETCH_ASSOC)){
	$id = $task["task_id"];
	$token = $task["task_token"];
	$target = $task["task_target"];
	$date = $task["task_creation_date"];

	$duplicates = $db->query("DELETE FROM tasks WHERE task_token = '$token' AND task_target = '$target' AND task_id != $id");
}

$unique = $db->query("ALTER TABLE tasks ADD UNIQUE(task_token, task_target)");

$all_notifications = $db->query("SELECT notification_id, notification_token, notification_target, notification_date FROM team_notifications ORDER BY notification_date ASC");

while($notification = $all_notifications->fetch(PDO::FETCH_ASSOC)){
	$id = $notification["notification_id"];
	$token = substr($notification["notification_token"], 0, 3);
	$target = $notification["notification_target"];
	$date = $notification["notification_date"];

	$duplicates = $db->query("DELETE FROM team_notifications WHERE notification_token LIKE '%$token%' AND notification_target = $target AND notification_id != $id");
}

?>
