<?php
require_once "db_connect.php";

if(isset($_POST["tag"]) && isset($_POST["target"]) && isset($_POST["type"])){
	if(is_numeric($_POST["tag"])){
		$tag = intval($_POST["tag"]);
	} else {
		$tag = $_POST["tag"];
	}
	$target = $_POST["target"];
	$type = $_POST["type"];

	associateTag($tag, $target, $type);
}

function associateTag($tag, $target, $type){
	$db = PDOFactory::getConnection();

	if(isset($target)){
		if(!is_numeric($tag)){
			if($type == "product"){
				$tag_type = "session";
			} else {
				$tag_type = $type;
			}
			$tag = $db->query("SELECT rank_id FROM tags_".$tag_type." WHERE rank_name='$tag'")->fetch(PDO::FETCH_COLUMN);
		}

		$query = "INSERT IGNORE INTO assoc_".$type."_tags(".$type."_id_foreign, tag_id_foreign) VALUES($target, $tag)";
		$attach = $db->query($query);

		echo $db->lastInsertId();
		return $db->lastInsertId();
	}
}
?>
