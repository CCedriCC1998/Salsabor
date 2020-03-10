<?php
require_once "/opt/lampp/htdocs/Salsabor/functions/db_connect.php";
include "/opt/lampp/htdocs/Salsabor/functions/tools.php";

/*require_once "../db_connect.php";
require_once "../tools.php";*/
$db = PDOFactory::getConnection();

/** This file will watch for tasks that are not yet done and will expire in 24 hours. It's gonna be executed every 15 minutes, forever.
If it detects one, it will create a notification to remind users that this task is approaching its deadline.

cron line : * / 15 * * * * php -f /opt/lampp/htdocs/Salsabor/functions/schedule/task_reminder.php
**/

$now = date("Y-m-d H:i:s");
$limit = date("Y-m-d H:i:s", strtotime($now.'+1DAY'));

$watch = $db->query("SELECT * FROM tasks WHERE task_deadline <= '$limit' AND task_state = 0");

while($task = $watch->fetch(PDO::FETCH_ASSOC)){
	$token = "TAS-";
	if($task["task_deadline"] <= $now){
		$token .= "L";
	} else {
		$token .= "NE";
	}
	$target = $task["task_id"];

	postNotification($token, $target, null, $now);
}
?>
