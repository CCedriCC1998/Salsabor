<?php
include "db_connect.php";
include "tools.php";

$db = PDOFactory::getConnection();
$table = $_POST["table"];
$column = $_POST["column"];
$value = $_POST["value"];
$target_id = $_POST["target_id"];

if($column == "room_reader"){
	if($value != ""){
		$resolved_value = $db->query("SELECT reader_id FROM readers WHERE reader_token = '$value'")->fetch(PDO::FETCH_COLUMN);
		if($resolved_value == null){
			$new = htmlspecialchars($value);
			$new_reader = array(
				"reader_token" => $value
			);
			$value = addEntry("readers", $new_reader);
		} else {
			$value = $resolved_value;
		}
	} else {
		$value = -1;
	}
}

updateColumn($db, $table, $column, $value, $target_id);
?>
