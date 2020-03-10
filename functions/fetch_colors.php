<?php
require_once "../functions/db_connect.php";
$db = PDOFactory::getConnection();

$colors = $db->query("SELECT * FROM colors");

$colors_list = array();
while($color = $colors->fetch(PDO::FETCH_ASSOC)){
	$c = array();
	$c["color_id"] = $color["color_id"];
	$c["color_value"] = $color["color_value"];
	array_push($colors_list, $c);
}

echo json_encode($colors_list);
?>
