<?php
require_once "db_connect.php";
function addTarifResa(){
	$type_prestation = $_POST['type_prestation'];
	$prix_resa = $_POST['prix_resa'];
	$db = PDOFactory::getConnection();
	try{
		$db->beginTransaction();
		/** On parcourt tous les jours **/
		for($k = 1; $k <= 3; $k++){
			/** On vérifie l'état des checkbox des jours **/
			if(isset($_POST['jour-'.$k])){
				/** Pour la semaine, on vérifie aussi l'état des checkboxes des heures **/
				if(isset($_POST['jour-1'])){
					for($i = 1; $i <= 3; $i++){
						if(isset($_POST['plage-'.$i])){
							$plage_resa = $_POST['plage-'.$i];
							/** Une fois qu'on a déterminé la plage où insérer, on vérifie les salles **/
							for($k = 1; $k <= 3; $k++){
								if(isset($_POST['salle-'.$k])){
									$lieu_resa = $_POST['salle-'.$k];
									/** On peut ensuite insérer les requêtes dans la base en fonction des cas **/
									$insert = $db->prepare('INSERT INTO tarifs_reservations(type_prestation, plage_resa, lieu_resa, prix_resa)
									VALUES(:type_prestation, :plage_resa, :lieu_resa, :prix_resa)');
									$insert->bindParam(':type_prestation', $type_prestation);
									$insert->bindParam(':plage_resa', $plage_resa);
									$insert->bindParam(':lieu_resa', $lieu_resa);
									$insert->bindParam(':prix_resa', $prix_resa);
									$insert->execute();
								}
							}
						}
					}
				} else {
					if($k == 2) $plage_resa = 4;
					else $plage_resa = 5;
					/** Une fois qu'on a déterminé la plage où insérer, on vérifie les salles **/
					for($k = 1; $k <= 3; $k++){
						if(isset($_POST['salle-'.$k])){
							$lieu_resa = $_POST['salle-'.$k];
							/** On peut ensuite insérer les requêtes dans la base en fonction des cas **/
							$insert = $db->prepare('INSERT INTO tarifs_reservations(type_prestation, plage_resa, lieu_resa, prix_resa)
							VALUES(:type_prestation, :plage_resa, :lieu_resa, :prix_resa)');
							$insert->bindParam(':type_prestation', $type_prestation);
							$insert->bindParam(':plage_resa', $plage_resa);
							$insert->bindParam(':lieu_resa', $lieu_resa);
							$insert->bindParam(':prix_resa', $prix_resa);
							$insert->execute();
						}
					}
				}
			}
		}
		$db->commit();
	}catch(PDOException $e){
		$db->rollBack();
		var_dump($e->getMessage());
	}
}
