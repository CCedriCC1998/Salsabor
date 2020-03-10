<?php
include "db_connect.php";
$db = PDOFactory::getConnection();

$prestation_id = $_GET['prestation_id'];
$prestation = $db->query("SELECT *, CONCAT(u.user_prenom, ' ', u.user_nom) AS handler FROM prestations p
							LEFT JOIN users u ON p.prestation_handler = u.user_id
							WHERE prestation_id='$prestation_id'")->fetch();

$p = array(
	"id" => $prestation["prestation_id"],
	"handler" => ($prestation["handler"]!=NULL)?$prestation["handler"]:"Pas d'organisateur",
	"address" => ($prestation["prestation_address"]!=NULL)?$prestation["prestation_address"]:"Pas d'adresse enregistrée",
	"description" => $prestation["prestation_description"]
);

echo json_encode($p);
?>
