<?php
session_start();
require_once 'db_connect.php';
$db = PDOFactory::getConnection();

$data = $_POST['picture_value'];
$user_id = $_POST["user_id"];

list($type, $data) = explode(';', $data);
list(, $data)      = explode(',', $data);
$data = base64_decode($data);

// Target directory to move the picture
$target_dir = "../assets/pictures/";

// Database "fictional location" to retrieve it
$data_base_address = "assets/pictures/";

// Real file
$new_file = $target_dir.$user_id.'.png';

// Fictional file
$fictional_file = $data_base_address.$user_id.'.png';

file_put_contents($new_file, $data);
move_uploaded_file($new_file, $fictional_file);
$update = $db->query("UPDATE users SET photo = '$fictional_file' WHERE user_id = $user_id");

if($_SESSION["user_id"] == $user_id)
	$_SESSION["photo"] = "/Salsabor/".$fictional_file;
echo $fictional_file;
?>
