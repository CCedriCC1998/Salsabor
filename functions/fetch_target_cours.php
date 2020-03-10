<?php
require_once "db_connect.php";
$db = PDOFactory::getConnection();

$id = $_POST["passage_id"];
$passage = $db->query("SELECT passage_date FROM participations WHERE passage_id=$id")->fetch(PDO::FETCH_ASSOC);
/* Pour trouver les cours potentiels pouvant correspondre à ce passage, on cherche tous les cours ayant commencé au plus tôt 60 minutes avant le passage et qui commenceront au plus tard 60 minutes après */
$start = date("Y-m-d H:i:s", strtotime($passage["passage_date"].'-80MINUTES'));
$end = date("Y-m-d H:i:s", strtotime($passage["passage_date"].'+80MINUTES'));
$queryFeed = $db->prepare("SELECT * FROM sessions s
						JOIN rooms r ON s.session_room = r.room_id
						JOIN users u ON s.session_teacher = u.user_id
						WHERE session_start>='$start' AND session_end <='$end'");
$queryFeed->bindValue(1, $id);
$queryFeed->execute();
$cours = array();
while($feed = $queryFeed->fetch(PDO::FETCH_ASSOC)){
	$f = array();
	$f["id"] = $feed["session_id"];
	$f["nom"] = $feed["session_name"];
	$f["salle"] = $feed["room_name"];
	$f["heure"] = date_create($feed["session_start"])->format("H:i")."-".date_create($feed["session_end"])->format("H:i");
	$f["prof"] = $feed["user_prenom"]." ".$feed["user_nom"];
	array_push($cours, $f);
}
echo json_encode($cours);
?>
