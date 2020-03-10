<?php
require_once "db_connect.php";
require_once "tools.php";
$db = PDOFactory::getConnection();

$id = solveAdherentToId($_POST["identite"]);
echo $id;
?>
