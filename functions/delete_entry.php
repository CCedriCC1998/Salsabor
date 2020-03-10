<?php
require_once "db_connect.php";
include "tools.php";
$db = PDOFactory::getConnection();

/** Deletes an entry in the database. Once again, this script is generic and can be used for whatever in the whole database. **/

$table = $_POST["table"];
$entry_id = $_POST["entry_id"];

$primary_key = $db->query("SHOW INDEX FROM $table WHERE Key_name = 'PRIMARY'")->fetch(PDO::FETCH_ASSOC);

$stmt = $db->prepare("DELETE FROM $table WHERE $primary_key[Column_name] = ?");
$stmt->bindParam(1, $entry_id, PDO::PARAM_INT);
$stmt->execute();
/*logAction($db, "Suppression", $entry_id);*/
?>
