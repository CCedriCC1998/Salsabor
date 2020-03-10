<?php
require_once 'db_connect.php';
$db = PDOFactory::getConnection();

$stmt = $db->query("SELECT user_id, CONCAT(users.user_prenom, ' ', users.user_nom) AS identity, telephone, mail, CONCAT(rue, ' - ', code_postal, ' ', ville) AS address, user_rfid FROM users
								JOIN (SELECT user_prenom, user_nom
										FROM users
										GROUP BY user_nom, user_prenom
										HAVING COUNT(user_id) > 1) dup
								ON users.user_nom = dup.user_nom AND users.user_prenom = dup.user_prenom
								ORDER BY users.user_nom ASC, users.user_prenom ASC, users.user_id ASC");

$duplicates = array();
while($duplicate = $stmt->fetch(PDO::FETCH_ASSOC)){
	$d = array(
		"id" => $duplicate["user_id"],
		"identity" => $duplicate["identity"],
		"telephone" => $duplicate["telephone"],
		"mail" => $duplicate["mail"],
		"address" => $duplicate["address"],
		"rfid" => $duplicate["user_rfid"]
	);
	array_push($duplicates, $d);
}
echo json_encode($duplicates);

?>
