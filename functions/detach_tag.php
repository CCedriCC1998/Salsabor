<?php
require_once "db_connect.php";
$db = PDOFactory::getConnection();

if(isset($_POST["tag"]) && isset($_POST["target"]) && isset($_POST["type"])){
	if(is_numeric($_POST["tag"])){
		$tag = intval($_POST["tag"]);
	} else {
		$tag = $_POST["tag"];
	}
	$target = $_POST["target"];
	$type = $_POST["type"];

	detachTag($db, $tag, $target, $type);
}

function detachTag($db, $tag, $target, $type){
	if(!is_numeric($tag)){
		if($type == "product"){
			$tag_type = "session";
		} else {
			$tag_type = $type;
		}
		$tag = $db->query("SELECT rank_id FROM tags_".$tag_type." WHERE rank_name='$tag'")->fetch(PDO::FETCH_COLUMN);
	}

	$query = "SELECT entry_id FROM assoc_".$type."_tags WHERE ".$type."_id_foreign = $target AND tag_id_foreign = $tag";

	$entry_id = $db->query($query)->fetch(PDO::FETCH_COLUMN);
	/*echo $entry_id;*/

	if(isset($entry_id)){
		$query = "DELETE FROM assoc_".$type."_tags WHERE entry_id = $entry_id";
		$detach = $db->query($query);
		/*echo $query;*/

		echo $entry_id;
	}
}
?>
