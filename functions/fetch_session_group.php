<?php
require_once "db_connect.php";

$db = PDOFactory::getConnection();
if(isset($_GET["group_id"])){
	$group_id = $_GET["group_id"];
	fetchGroupDetails($db, $group_id);
}

function fetchGroupDetails($db, $group_id){
	$stmt = $db->prepare("SELECT * FROM session_groups WHERE session_group_id = ?");
	$stmt->bindParam(1, $group_id, PDO::PARAM_INT);
	$stmt->execute();

	if(isset($_GET["group_id"]))
		echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
	else
		return $stmt->fetch(PDO::FETCH_ASSOC);
}

?>
