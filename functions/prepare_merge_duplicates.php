<?php
require_once 'db_connect.php';
$db = PDOFactory::getConnection();

// Oh yeah baby here we go: https://www.youtube.com/watch?v=AxU_NFrleDY

$definitive_id = $_GET["basis_id"];

// The definitive ID will be the one of the compiled profile. From there, we can get the info of the "oldest" profile, and retrieve all the duplicate profiles.

$merged_info = array();

$first_profile = $db->query("SELECT user_id, user_prenom, user_nom, user_rfid, date_naissance, rue, code_postal, ville, mail, website, organisation, telephone, photo FROM users WHERE user_id = $definitive_id")->fetch(PDO::FETCH_ASSOC);

// We get the keys we'll use to browse through all the arrays
$keys = array_keys($first_profile);

// We get all the profiles
$all_profiles = $db->query("SELECT user_id, user_prenom, user_nom, user_rfid, date_naissance, rue, code_postal, ville, mail, website, organisation, telephone, photo FROM users WHERE CONCAT(user_prenom, ' ', user_nom) = '$first_profile[user_prenom] $first_profile[user_nom]'")->fetchAll();

$merged_info["user_id"] = $definitive_id;
// We start at 3, to ignore the user ID, first and last names.
for($j = 3; $j < sizeof($keys); $j++){
	// We name the current key
	$current_key = $keys[$j];
	// We create an array that will contain all the values from a column
	$compared_values = array();
	for($i = 0; $i < sizeof($all_profiles); $i++){
		if($all_profiles[$i][$current_key])
			array_push($compared_values, $all_profiles[$i][$current_key]);
	}
	// Filter and duplicate detection
	$uniqued = array_unique($compared_values);
	// Whatever happens, we put the result in the final array.
	$merged_info[$current_key] = $uniqued;
}

// We send back the final array merged_info for handling by the client.
echo json_encode(array_filter($merged_info));
