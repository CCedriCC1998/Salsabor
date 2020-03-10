<?php
require_once "db_connect.php";
require_once "tools.php";
$db = PDOFactory::getConnection();

$prestation_id = $_POST["prestation_id"];
parse_str($_POST["prestataires"], $prestataires);

print_r($prestataires);

$users = array();

echo sizeof($prestataires) / 3;

for($i = 1, $j = 1; $j <= sizeof($prestataires) / 3; $i++, $j++){
	if(isset($prestataires["user_id_".$i])){
		$user_id = solveAdherentToId($prestataires["user_id_".$i]);

		$invoice_id = $prestataires["invoice_id_".$i];
		if($invoice_id == 0){
			// If there's no invoice, the app has to find it by itself.
			$prestation_start = $db->query("SELECT prestation_start FROM prestations WHERE prestation_id = $prestation_id")->fetch(PDO::FETCH_COLUMN);
			$period = date("Y-m-01", strtotime($prestation_start));
			$invoice_id = $db->query("SELECT invoice_id FROM invoices WHERE invoice_seller_id = $user_id AND invoice_period = '$period'")->fetch(PDO::FETCH_COLUMN);
			if($invoice_id == NULL) $invoice_id = "NULL";
		}

		$price = $prestataires["price_".$i];
		if($price == "") $price = "NULL";

		array_push($users, $user_id);

		// Now that we have all the info, we insert or delete
		$db->query("INSERT INTO prestation_users(prestation_id, user_id, invoice_id, price)
					VALUES($prestation_id, $user_id, $invoice_id, '$price')
					ON DUPLICATE KEY UPDATE invoice_id = $invoice_id, price = '$price'");
	} else {
		$j--;
	}
}

// Now that we inserted or updated all users, we can deleted existing records of users no longer participants.
$db->query("DELETE FROM prestation_users WHERE prestation_id = $prestation_id AND user_id NOT IN (".implode(", ", $users).")");
?>
