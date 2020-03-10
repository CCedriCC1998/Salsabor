<?php
session_start();
require_once "db_connect.php";
$db = PDOFactory::getConnection();

$user_details = $db->query("SELECT user_prenom, user_nom, user_location FROM users WHERE user_id = $_SESSION[user_id]")->fetch(PDO::FETCH_ASSOC);

unset($_SESSION["location"]);
$_SESSION["location"] = $user_details["user_location"];
unset($_SESSION["username"]);
$_SESSION["username"] = $user_details["user_prenom"]." ".$user_details["user_nom"];
?>
