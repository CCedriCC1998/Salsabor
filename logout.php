<?php
session_start();
require_once "functions/db_connect.php";
include "functions/tools.php";
$db = PDOFactory::getConnection();
if(session_destroy()){
	logAction($db, "Déconnexion", "users-".$_SESSION["user_id"]);
	header("Location: dashboard");
}
?>
