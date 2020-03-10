<?php
require_once "/opt/lampp/htdocs/Salsabor/functions/db_connect.php";
require_once "/opt/lampp/htdocs/Salsabor/functions/tools.php";
/*require_once "../db_connect.php";
require_once "../tools.php";*/
$db = PDOFactory::getConnection();

/* This file has to open sessions
cron : * / 5 10-23 * * * /opt/lampp/bin/php /opt/lampp/htdocs/Salsabor/functions/schedule/handle_sessions.php
(will be executed every 5 minutes between 10am and 11pm everyday)
*/

$now = date("Y-m-d H:i:00");
/*$now = date("2016-11-08 18:40:00");*/
$compare_start = date("Y-m-d H:i:s", strtotime($now.'+15MINUTES'));
$compare_close = date("Y-m-d H:i:s", strtotime($now.'-45MINUTES'));

/*echo "Heure de test : ".$now." | Ouverture des cours commençant à ".$compare_start." | Fermeture des cours ayant commencé à ".$compare_close;*/

try{
	$db->beginTransaction();
	// Leaves the sesssions open but doesn't accept records anymore for sessions that will end in the next 30 minutes.
	$partial_close = $db->query("UPDATE sessions SET session_opened = 2 WHERE session_start = '$compare_close' AND session_opened = 1");

	// Opens to records session that will begin in the next 90 minutes.
	$sessions = $db->query("SELECT session_id, session_opened FROM sessions WHERE session_start = '$compare_start'");
	while($session = $sessions->fetch(PDO::FETCH_ASSOC)){
		$session_id = $session["session_id"];
		if($session["session_opened"] == 0){
			$open = $db->query("UPDATE sessions SET session_opened = 1 WHERE session_id='$session_id'");
			$token = "SES";
			postNotification($token, $session_id, null, $now);
		}
	}

	$db->commit();
} catch(PDOException $e){
	$db->rollBack();
	echo $e->getMessage();
}
?>
