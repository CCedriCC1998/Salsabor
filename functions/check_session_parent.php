<?php
require_once "db_connect.php";
require_once "cours.php";

$db = PDOFactory::getConnection();
$session_group_id = $_GET["session_group_id"];

checkParent($db, $session_group_id);

function checkParent($db, $session_group_id){
	try{
		$db->beginTransaction();
		$findParent = $db->prepare('SELECT COUNT(*) FROM sessions WHERE session_group=?');
		$findParent->bindParam(1, $session_group_id, PDO::PARAM_INT);
		$findParent->execute();
		if($findParent->fetchColumn() == 0){
			$deleteAll = $db->prepare('DELETE FROM session_groups WHERE session_group_id=?');
			$deleteAll->bindParam(1, $session_group_id, PDO::PARAM_INT);
			$deleteAll->execute();
		}
		$db->commit();
	} catch(PDOException $e){
		$db->rollBack();
		var_dump($e->getMessage());
	}
}

?>
