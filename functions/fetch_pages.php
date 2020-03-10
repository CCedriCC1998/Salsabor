<?php
include "db_connect.php";
$db = PDOFactory::getConnection();

$pages = $db->query("SELECT * FROM app_menus am
					JOIN app_pages ap ON am.menu_id = ap.page_menu
					ORDER BY ap.page_menu, ap.page_order ASC");

$page_list = array();

while($details = $pages->fetch(PDO::FETCH_ASSOC)){
	$p = array();
	$p["id"] = $details["page_id"];
	$p["page_name"] = $details["page_name"];
	$p["page_glyph"] = $details["page_glyph"];
	$p["page_menu"] = $details["page_menu"];
	$p["menu_id"] = $details["menu_name"];
	$p["menu_glyph"] = $details["menu_glyph"];
	$p["labels"] = array();
	$labels = $db->query("SELECT * FROM assoc_page_tags apt
						JOIN tags_user tu ON apt.tag_id_foreign = tu.rank_id
						WHERE page_id_foreign = '$p[id]'
						ORDER BY tag_color DESC");
	while($label = $labels->fetch(PDO::FETCH_ASSOC)){
		$l = array();
		$l["entry_id"] = $label["entry_id"];
		$l["tag_color"] = $label["tag_color"];
		$l["rank_name"] = $label["rank_name"];
		array_push($p["labels"], $l);
	}
	array_push($page_list, $p);
}
echo json_encode($page_list);
?>
