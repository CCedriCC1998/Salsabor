<?php
require_once "db_connect.php";
$db = PDOFactory::getConnection();
// Résout l'adhérent à partir de son ID
$queryAdherent = $db->prepare('SELECT * FROM users WHERE user_id=?');
$queryAdherent->bindValue(1, $_POST["id"]);
$queryAdherent->execute();
$adherent = $queryAdherent->fetch(PDO::FETCH_ASSOC);
echo json_encode($adherent);
?>
