<?php
require_once "../functions/db_connect.php";
require_once "../functions/tools.php";

$user_id = $_GET["user_id"];

$query = "SELECT * FROM produits_adherents WHERE user_id_foreign = $user_id";

return json_encode($db->query($query)->fetch());
?>
