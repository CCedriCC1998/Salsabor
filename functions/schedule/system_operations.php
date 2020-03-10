<?php
/*require_once "/opt/lampp/htdocs/Salsabor/functions/db_connect.php";
require_once "/opt/lampp/htdocs/Salsabor/functions/compute_product.php";
require_once "/opt/lampp/htdocs/Salsabor/functions/post_task.php";
require_once "/opt/lampp/htdocs/Salsabor/functions/attach_tag.php";*/
require_once "../db_connect.php";
require_once "../compute_product.php";
require_once "../post_task.php";
require_once "../attach_tag.php";

$db = PDOFactory::getConnection();

/** This file just does the daily system_operations:
- Set products as expired
- Show/Hide promotions
- Watch for active/inactive users
- Archive old participations
- Clean obsolete notifications
It's executed once per day, at night because some operations (like computing all active products) might take some time.
cron line : cron : * 1 * * * /opt/lampp/bin/php /opt/lampp/htdocs/Salsabor/functions/schedule/system_operations.php
(will be executed daily at 1am)
**/

$when = new DateTime();
$compare_start = date("Y-m-d");
$activationLimit = date("Y-m-d H:i:s", strtotime($compare_start.'-1YEAR'));

try{
	$db->beginTransaction();

	// Compute all active products. As compute generates notifications, this will also take care of all the notifications for the products.
	$products = $db->query("SELECT id_produit_adherent FROM produits_adherents WHERE actif = 1");
	while($product = $products->fetch(PDO::FETCH_COLUMN)){
		computeProduct($product);
	}

	/*// Activate available promotions
	$toActivate = $db->query("SELECT product_id FROM produits WHERE date_activation <= '$compare_start' AND date_activation != '0000-00-00 00:00:00'");
	while($match = $toActivate->fetch(PDO::FETCH_ASSOC)){
		updateColumn($db, "produits", "actif", 1, $match["product_id"]);
		postNotification("PRO-S", $match["product_id"], null, $compare_start);
	}

	// Or deactivate expired ones
	$toDeactive = $db->query("SELECT product_id FROM produits WHERE date_desactivation <= '$compare_start' AND date_desactivation != '0000-00-00 00:00:00'");
	while($match = $toDeactive->fetch(PDO::FETCH_ASSOC)){
		updateColumn($db, "produits", "actif", 0, $match["product_id"]);
		postNotification("PRO-E", $match["product_id"], null, $compare_start);
	}*/

	$db->commit();
} catch(PDOException $e){
	$db->rollBack();
	echo $e->getMessage();
}

try{
	$db->beginTransaction();
	// We deactivate any user that didn't buy a product or attended a session for more than 12 months.
	$deactivateUser = $db->query("UPDATE users SET actif = 0 WHERE actif = '1' AND date_last < '$activationLimit'");
	$db->commit();
} catch(PDOException $e){
	$db->rollBack();
	echo $e->getMessage();
}

try{
	$db->beginTransaction();
	// Check all membership cards from active users
	$noCards = $db->query("SELECT user_id FROM users u WHERE actif = 1")->fetchAll(PDO::FETCH_COLUMN);
	foreach($noCards as $user){
		$membership_cards = $db->query("SELECT id_produit_adherent, pa.actif, date_achat, date_expiration
							FROM produits_adherents pa
							JOIN transactions t ON pa.id_transaction_foreign = t.id_transaction
							JOIN produits p ON pa.id_produit_foreign = p.product_id
							WHERE id_user_foreign = '$user' AND product_name = 'Adhésion Annuelle' ORDER BY id_produit_adherent ASC")->fetchAll();
		if(sizeof($membership_cards == 0)){ // If there's no membership card, we create a task
			$new_task_id = createTask("Adhésion Annuelle manquante", "Cet utilisateur n'a pas d'adhésion annuelle.", "[USR-".$user."]", null);
			if($new_task_id !== null){
				echo "Nouvelle tâche ajoutée, ID : ".$new_task_id."<br>";
				$tag = $db->query("SELECT rank_id FROM tags_user WHERE missing_info_default = 1")->fetch(PDO::FETCH_COLUMN);
				echo "Ajout du tag ".intval($tag)." à la tâche ".$new_task_id."<br>";
				associateTag(intval($tag), $new_task_id, "task");
			}
		} else {
			// Resetting loop variables
			$active_card = false;
			unset($next_activation_date);
			// Looping on membership cards of the user
			foreach($membership_cards as $card){
				if($card["actif"] == 0 && !$active_card){
					if(isset($next_activation_date))
						$activation_date = $next_activation_date;
					else
						$activation_date = $card["date_achat"];

					activateProduct($db, $card["id_produit_adherent"], $activation_date);
					$active_card = true;
				}
				if($card["actif"] == 1){ // If the card is active, no need to search for other cards to activate
					$active_card = true;
				}
				if($card["actif"] == 2){ // If the card has expired, the next one will have to be activated with this one's expiration date in mind for continued activation.
					$next_activation_date = $card["date_expiration"];
				}
			}
			if(!$active_card){
				$new_task_id = createTask("Adhésion Annuelle manquante", "Cet utilisateur n'a pas d'adhésion annuelle.", "[USR-".$user."]", null);
				$tag = $db->query("SELECT rank_id FROM tags_user WHERE missing_info_default = 1")->fetch(PDO::FETCH_COLUMN);
				associateTag(intval($tag), $new_task_id, "task");
			}
		}
	}
} catch(PDOException $e){
	$db->rollBack();
	echo $e->getMessage();
}

try{
	$age = $db->query("SELECT setting_value FROM settings WHERE setting_code = 'archiv_part'")->fetch(PDO::FETCH_COLUMN);
	$delta = "P".$age."M";
	$when->sub(new dateinterval($delta));
	$when = $when->format("Y-m-d H:i:s");

	$db->query("UPDATE participations pr SET archived = 1
				WHERE (pr.status != 2 OR (pr.status = 2 AND produit_adherent_id IS NULL))
				AND passage_date < '$date'");
}

// We delete "old" notifications about closed sessions
$delete_old_notifications = $db->query("DELETE FROM team_notifications WHERE notification_token = 'SES'");
?>
