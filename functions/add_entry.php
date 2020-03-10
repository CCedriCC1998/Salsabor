<?php
require_once "db_connect.php";
require_once "tools.php";

if(isset($_POST["table"])){ // AJAX call.
	$table_name = htmlspecialchars($_POST["table"]);
	parse_str($_POST["values"], $values);
	addEntry($table_name, $values);
}

// Type hinting $values because it can come from AJAX or PHP and MUST be an array. This function has to do the minimal amount of work, it doesn't have time to play with the values argument, which has to be parsed or unserialized beforehand.
function addEntry($table_name, Array $values){
	$db = PDOFactory::getConnection();
	// Constructing generic query
	$query = "INSERT INTO $table_name(";
	foreach($values as $column => $value){
		$query .= "$column";
		if($column !== end(array_keys($values))){
			$query .= ", ";
		} else {
			$query .= ")";
		}
	}
	$query .= " VALUES(";
	foreach($values as $column => $value){
		$id_solving_tokens = array("session_teacher", "event_handler", "booking_holder", "booking_handler", "task_recipient", "transaction_handler", "prestation_handler");
		if(in_array($column, $id_solving_tokens)){
			$value = solveAdherentToId($value);
		}
		if(preg_match("/(start|end|date)/i", $column)){
			if($value != null){
				// In the database, all dates contain one of these 3 words. We can then test against them to find dates and format them correctly.
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
		if($value != NULL)
			$query .= "'$value'";
		else
			$query .= "NULL";
		if($column !== end(array_keys($values))){
			$query .= ", ";
		} else {
			$query .= ")";
		}
	}

	try{
		$db->beginTransaction();
		$insert = $db->query($query);
		$new_id = $db->lastInsertId();
		logAction($db, "Ajout", $table_name."-".$new_id);
		$db->commit();
		/*echo $query;*/
		if(isset($_POST["table"])){//AJAX
			echo $new_id;
		}
		return $new_id;
	} catch(PDOException $e){
		$db->rollBack();
		echo $e->getMessage();
	}
}
