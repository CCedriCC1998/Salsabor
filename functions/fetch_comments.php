<?php
session_start();
include "db_connect.php";
$db = PDOFactory::getConnection();

$task_id = $_GET["task_id"];

$load = $db->query("SELECT * FROM task_comments WHERE task_id_foreign = '$task_id' ORDER BY task_comment_date DESC");

$comment_list = array();

while($details = $load->fetch(PDO::FETCH_ASSOC)){
	$t = array();
	$t["id"] = $details["task_comment_id"];
	$t["comment"] = $details["task_comment"];
	$t["date"] = $details["task_comment_date"];
	$t["author_id"] = $details["task_comment_author"];
	$t["author"] = $db->query("SELECT CONCAT(user_prenom, ' ', user_nom) AS author FROM users u WHERE u.user_id = '$details[task_comment_author]'")->fetch(PDO::FETCH_COLUMN);
	$t["own"] = ($t["author_id"] == $_SESSION["user_id"])?true:false;
	array_push($comment_list, $t);
}
echo json_encode($comment_list);
?>
