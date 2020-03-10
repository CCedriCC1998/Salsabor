<?php
require_once "db_connect.php";
$db = PDOFactory::getConnection();

$prof_id = $_POST["prof_id"];

// Liste des cours
$stmt = $db->prepare("SELECT * FROM sessions WHERE session_teacher=?");
$stmt->bindParam(1, $prof_id, PDO::PARAM_INT);
$stmt->execute();
$result = array();
while($coursProf = $stmt->fetch(PDO::FETCH_ASSOC)){
	$h = array();
	$h["day"] = $coursProf["session_start"];
	$h["cours_nom"] = $coursProf["session_name"];
	array_push($result, $h);
}
echo json_encode($result);
?>
