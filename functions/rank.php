<?php
require_once "db_connect.php";

/** ADD RANK **/
function addRank(){
	$db = PDOFactory::getConnection();
	$insertRank = $db->prepare('INSERT INTO rank (rank_name) VALUES(?)');
	$insertRank->bindValue(1,$_POST['rank_name'],PDO::PARAM_STR);
	$insertRank->execute();
}

/** DELETE RANK **/
function deleteRank(){
	$db = PDOFactory::getConnection();
	$deleteRank = $db->prepare('DELETE FROM rank WHERE rank_id=?');
	$deleteRank->bindValue(1, $_POST['id'], PDO::PARAM_INT);
	$deleteRank->execute();
}
?>
