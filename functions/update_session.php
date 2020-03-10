<?php
// As the generic update entry cannot be used for a session because of all the differences, there's a separare script to handle the update of one or many sessions at once.
require_once "db_connect.php";
require_once "tools.php";
require_once "cours.php";
$db = PDOFactory::getConnection();

// The main difference comes from the dates; Modifying them for one session is easy, we just have to overwrite. But if we want to update several sessions at once, the correct way is to modify the dates based on the delta between the old date and the new date; this delta will be applied to all dates of all sessions, making it really easier.

// First, we get the sessions that will be modified.
$sessions = $_POST["sessions"];
// Then, these are all the values that have to be applied to the array of sessions
parse_str($_POST["values"], $values);
// Lastly, we receive the ID of the session the modifications come from
$hook = $_POST["hook"];

// == GET THE DELTA ==
// New values from the serialized array
$new_start = DateTime::createFromFormat("d/m/Y H:i:s", $values["session_start"]);
$new_end = DateTime::createFromFormat("d/m/Y H:i:s", $values["session_end"]);

// Old values from the database and the hook
$hook_times = $db->query("SELECT session_start, session_end FROM sessions WHERE session_id = $hook")->fetch(PDO::FETCH_ASSOC);
$hook_start = DateTime::createFromFormat("Y-m-d H:i:s", $hook_times["session_start"]);
$hook_end = DateTime::createFromFormat("Y-m-d H:i:s", $hook_times["session_end"]);

// We calculate the delta
$start_delta = $hook_start->diff($new_start);
$end_delta = $hook_end->diff($new_end);

if($new_start < $hook_start){
	$token_start = "sub";
} else {
	$token_start = "add";
}

if($new_end < $hook_end){
	$token_end = "sub";
} else {
	$token_end = "add";
}

// == QUERY ==
for($i = 0; $i < sizeof($sessions); $i++){
	// We fetch the times from each session
	$session_times = $db->query("SELECT session_start, session_end FROM sessions WHERE session_id = $sessions[$i]")->fetch(PDO::FETCH_ASSOC);
	try{
		$query = "UPDATE sessions SET ";
		foreach($values as $row => $value){
			// If we have to get a name
			if($row == "session_teacher"){
				$value = solveAdherentToId($value);
				$teacher_id = $value;
			}

			// We apply the delta
			if($row == "session_start"){
				$session_start = DateTime::createFromFormat("Y-m-d H:i:s", $session_times["session_start"]);
				if($token_start == "sub"){
					$session_start->sub(new DateInterval("P".$start_delta->format("%d")."DT".$start_delta->format("%h")."H".$start_delta->format("%i")."M"));
				} else {
					$session_start->add(new DateInterval("P".$start_delta->format("%d")."DT".$start_delta->format("%h")."H".$start_delta->format("%i")."M"));
				}
				$value = $session_start->format("Y-m-d H:i:s");
			}

			if($row == "session_end"){
				$session_end = DateTime::createFromFormat("Y-m-d H:i:s", $session_times["session_end"]);
				if($token_end == "sub"){
					$session_end->sub(new DateInterval("P".$end_delta->format("%d")."DT".$end_delta->format("%h")."H".$end_delta->format("%i")."M"));
				} else {
					$session_end->add(new DateInterval("P".$end_delta->format("%d")."DT".$end_delta->format("%h")."H".$end_delta->format("%i")."M"));
				}
				$value = $session_end->format("Y-m-d H:i:s");
			}

			if($row == "invoice_id"){
				$invoice_id = $value;
				if($value == 0) $value = NULL;
			}

			if($value != NULL)
				$query .= "$row = ".$db->quote($value);
			else
				$query .= "$row = NULL";

			if($row !== end(array_keys($values))){
				$query .= ", ";
			}
		}
		$query .= " WHERE session_id = '$sessions[$i]'";

		echo $query."\n";
		$db->beginTransaction();
		$update = $db->query($query);
		$db->commit();
		// Update invoice
		setInvoice($sessions[$i], $invoice_id);
	} catch(PDOException $e){
		$db->rollBack();
		echo $e->getMessage();
	}
}
