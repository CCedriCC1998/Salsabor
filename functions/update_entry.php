<?php
// This is the generic script to edit an entry into whatever table. With this, you only have to call it once you're set and avoid a billion scripts for every little thing.
require_once "db_connect.php";
require_once "tools.php";
$db = PDOFactory::getConnection();

// Array of values (user serialize in php to have the correct format)
parse_str($_POST["values"], $values);

// The table and entry of it we'll update
$table_name = htmlspecialchars($_POST["table"]);
$entry_id = htmlspecialchars($_POST["target_id"]);

// We get the name of the primary key
$primary_key = $db->query("SHOW INDEX FROM $table_name WHERE Key_name = 'PRIMARY'")->fetch(PDO::FETCH_ASSOC);

// Construction of the query
$query = "UPDATE $table_name SET ";
foreach($values as $column => $value){
	// Have to solve users to their ID if needed here.
	$id_solving_tokens = array("session_teacher", "event_handler", "booking_holder", "booking_handler", "task_recipient", "transaction_handler", "prestation_handler");
	if(in_array($column, $id_solving_tokens)){
		$value = solveAdherentToId($value);
		$handler_id = $value;
	}
	// Have to solve the reader ID of the room
	if($column == "room_reader"){
		$resolved_value = $db->query("SELECT reader_id FROM readers WHERE reader_token = '$value'")->fetch(PDO::FETCH_COLUMN);
		if($resolved_value == null){
			$new = htmlspecialchars($value);
			$reader_details = array(
				"reader_token" => $new
			);
			require_once "add_entry.php";
			$value = addEntry("readers", $reader_details);
		} else {
			$value = $resolved_value;
		}
	}
	// In the database, all dates contain one of these 3 words. We can then test against them to find dates and format them correctly.
	if(preg_match("/(start|end|date)/i", $column)){
		if($value != null){
			if(preg_match('/\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}:\d{2}/',$value)){
				$value_date = DateTime::createFromFormat("d/m/Y H:i:s", $value);
				$value = $value_date->format("Y-m-d H:i:s");
			}else{
				$value_date = DateTime::createFromFormat("d/m/Y", $value);
				$value = $value_date->format("Y-m-d");
			}
		} else {
			$value = NULL;
		}
	} else {
		$value = htmlspecialchars($value);
	}
	// If the table is sessions or prestations, we have to solve the invoice too
	if(($table_name == "sessions") && !isset($values["invoice_id"])){
		if(preg_match("/start/i", $column)){
			if($value != null){
				// Get the month of the date
				$period = date("Y-m-01", strtotime($value));
				$db->query("UPDATE $table_name SET invoice_id = (SELECT invoice_id FROM invoices WHERE invoice_seller_id = $handler_id AND invoice_period = '$period') WHERE $primary_key[Column_name] = '$entry_id'");
			}
		}
	}
	if($value != NULL)
		$query .= "$column = ".$db->quote($value);
	else
		$query .= "$column = NULL";
	if($column !== end(array_keys($values))){
		$query .= ", ";
	}
}
$query .= " WHERE $primary_key[Column_name] = '$entry_id'";

// Execution
try{
	$db->beginTransaction();
	$update = $db->query($query);
	echo $query;
	logAction($db, "Modification", $table_name."-".$entry_id);
	$db->commit();
} catch(PDOException $e){
	$db->rollBack();
	echo $e->getMessage();
}
?>
