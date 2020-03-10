<?php
require_once "../functions/db_connect.php";
$db = PDOFactory::getConnection();

$date = $_POST["date_debut"];

$search = $db->prepare('SELECT * FROM holidays WHERE holiday_date=?');
$search->bindParam(1, $date);
$search->execute();
echo $search->rowCount();
?>
