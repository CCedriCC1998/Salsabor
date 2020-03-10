<?php
require_once "db_connect.php";
$db = PDOFactory::getConnection();

parse_str($_POST["values"], $values);

$query = "UPDATE master_settings SET ";
foreach($values as $row => $value){
	$query .= "$row = $value";
	if($row !== end(array_keys($values))){
		$query .= ", ";
	}
}
$query .= " WHERE user_id = '0'";
$update = $db->query($query);
?>
