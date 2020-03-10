<?php
session_start();
if(!isset($_SESSION["username"])){
	header('location: portal');
}
require_once 'functions/db_connect.php';
include "functions/add_entry.php";
$db = PDOFactory::getConnection();

$connaissances = $db->query("SELECT * FROM sources_connaissance");

// Locations
$locations = $db->query("SELECT * FROM locations ORDER BY location_name ASC");
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Inscription d'un adhérent | Salsabor</title>
		<?php include "styles.php";?>
		<?php include "scripts.php";?>
		<script src="assets/js/tags.js"></script>
		<script src="assets/js/fileinput.min.js"></script>
		<?php include "inserts/sub_modal_product.php";?>
	</head>
	<body>
		<?php include "nav.php";?>
		<div class="container-fluid">
			<div class="row">
				<?php include "side-menu.php";?>
				<div class="col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
					<legend><span class="glyphicon glyphicon-pencil"></span> Inscription</legend>
					<form amethod="post" class="form-horizontal" role="form" id="user-form">
						<p class="sub-legend">Informations personnelles</p>
						<div class="form-group">
							<label for="user_prenom" class="col-sm-3 control-label">Prénom</label>
							<div class="col-sm-9">
								<input type="text" name="user_prenom" id="user_prenom" class="form-control mandatory" placeholder="Prénom">
							</div>
						</div>
						<div class="form-group">
							<label for="user_nom" class="col-sm-3 control-label">Nom</label>
							<div class="col-sm-9">
								<input type="text" name="user_nom" id="user_nom" class="form-control mandatory" placeholder="Nom de famille">
							</div>
						</div>
						<div class="form-group">
							<label for="mail" class="col-sm-3 control-label">Adresse mail</label>
							<div class="col-sm-9">
								<input type="email" name="mail" id="mail" placeholder="Adresse mail" class="form-control">
							</div>
						</div>
						<div class="form-group">
							<label for="rue" class="col-sm-3 control-label">Adresse postale</label>
							<div class="col-sm-9">
								<input type="text" name="rue" id="rue" placeholder="Adresse" class="form-control">
							</div>
						</div>
						<div class="form-group">
							<label for="code_postal" class="col-sm-3 control-label">Code postal</label>
							<div class="col-sm-9">
								<input type="number" name="code_postal" id="code_postal" placeholder="Code Postal" class="form-control">
							</div>
						</div>
						<div class="form-group">
							<label for="ville" class="col-sm-3 control-label">Ville</label>
							<div class="col-sm-9">
								<input type="text" name="ville" id="ville" placeholder="Ville" class="form-control">
							</div>
						</div>
						<div class="form-group">
							<label for="telephone" class="col-sm-3 control-label">Téléphone principal</label>
							<div class="col-sm-9">
								<input type="tel" name="telephone" id="telephone" placeholder="Numéro de téléphone" class="form-control">
							</div>
						</div>
						<div class="form-group">
							<label for="website" class="col-sm-3 control-label">Site Web</label>
							<div class="col-sm-9">
								<input type="url" name="website" placeholder="Adresse de site web" class="form-control">
							</div>
						</div>
						<div class="form-group">
							<label for="organisation" class="col-sm-3 control-label">Organisation</label>
							<div class="col-sm-9">
								<input type="text" name="organisation" placeholder="Organisation" class="form-control">
							</div>
						</div>
						<div class="form-group">
							<label for="tel_secondaire" class="col-sm-3 control-label">Téléphone secondaire</label>
							<div class="col-sm-9">
								<input type="tel" name="tel_secondaire" id="tel_secondaire" placeholder="Numéro de téléphone secondaire" class="form-control">
							</div>
						</div>
						<div class="form-group">
							<label for="date_naissance" class="col-sm-3 control-label">Date de naissance</label>
							<div class="col-sm-9">
								<input type="text" name="date_naissance" id="birthdate" class="form-control">
							</div>
						</div>
						<p class="sub-legend">Informations Salsabor</p>
						<div class="form-group">
							<label for="statuts" class="col-sm-3 control-label">&Eacute;tiquettes</label>
							<div class="col-sm-9 user_tags">
								<h4 class="tags_container">
									<span class="label label-default label-clickable label-add trigger-sub" id="label-add" data-subtype='user-tags' data-targettype='user' title="Ajouter une étiquette">+</span>
								</h4>
							</div>
						</div>
						<div class="form-group">
							<label for="date_inscription" class="col-sm-3 control-label">Date d'inscription</label>
							<div class="col-sm-9">
								<input type="text" name="date_inscription" id="date-inscription" class="form-control">
								<p class="help-block">Par défaut, aujourd'hui</p>
							</div>
						</div>
						<div class="form-group">
							<label for="user_rfid" class="col-sm-3 control-label">Code carte</label>
							<div class="col-sm-9">
								<div class="input-group">
									<input type="text" name="user_rfid" class="form-control" placeholder="Scannez une nouvelle puce pour récupérer le code RFID">
									<span role="buttton" class="input-group-btn"><a class="btn btn-info" role="button" name="fetch-rfid">Lancer la détection</a></span>
								</div>
							</div>
						</div>
						<div class="form-group">
							<label for="user_location" class="control-label col-sm-3">Région d'activité <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" title="Personnalise les salles, plannings, membres accessibles en fonction de leurs régions. La région est ignorée pour les utilisateurs non-staff."></span></label>
							<div class="col-sm-9">
								<select name="user_location" class="form-control">
									<option value="">Pas de région</option>
									<?php while($location = $locations->fetch(PDO::FETCH_ASSOC)){ ?>
									<option value="<?php echo $location["location_id"];?>"><?php echo $location["location_name"];?></option>
									<?php } ?>
								</select>
							</div>
						</div>
						<div class="form-group">
							<label for="source_connaissance" class="col-sm-3 control-label">D'où connaissez-vous Salsabor ?</label>
							<div class="col-sm-9">
								<select name="source_connaissance" class="form-control">
									<?php while($sources = $connaissances->fetch(PDO::FETCH_ASSOC)){ ?>
									<option value="<?php echo $sources["source_id"];?>"><?php echo $sources["source"];?></option>
									<?php } ?>
								</select>
								<p class="help-block">Sélectionnez la source la plus influente</p>
							</div>
						</div>
						<div class="form-group">
							<label for="commentaires" class="col-sm-3 control-label">Commentaires</label>
							<div class="col-sm-9">
								<textarea rows="5" class="form-control" name="commentaires"></textarea>
							</div>
						</div>
					</form>
					<div class="col-xs-6">
						<button class="btn btn-primary submit-button btn-block" id="user-add" disabled>Inscrire</button>
					</div>
					<div class="col-xs-6">
						<button class="btn btn-primary submit-button btn-block" id="user-add-sell" disabled>Inscrire et acheter</button>
					</div>
				</div>
			</div>
		</div>
		<script>
			$(document).ready(function(){
				initial_tags = createTagsArray();
				$("#birthdate").datetimepicker({
					format: "DD/MM/YYYY",
					locale: "fr",
				});
				$("#date-inscription").datetimepicker({
					format: "DD/MM/YYYY",
					locale: "fr",
					defaultDate: moment()
				});
			}).on('click', '.submit-button', function(){
				var data = $("#user-form").serialize();
				console.log(data);
				$.when(addEntry("users", data)).done(function(user_id){
					console.log(user_id);
					var current_tags = createTagsArray();
					$.when(updateTargetTags(initial_tags, current_tags, user_id, "user")).done(function(data){
						showNotification("Utilisateur créé avec succès", "success");
						if($(this).attr("id") == "user-add-sell")
							window.top.location = "catalogue.php?user="+user_id;
						else
							window.top.location = "user/"+user_id;
					})
				})
			})
			var listening = false;
			var wait;
			$("[name='fetch-rfid']").click(function(){
				if(!listening){
					wait = setInterval(function(){fetchRFID()}, 2000);
					$("[name='fetch-rfid']").html("Détection en cours...");
					listening = true;
				} else {
					clearInterval(wait);
					$("[name='fetch-rfid']").html("Lancer la détection");
					listening = false;
				}
			});
			function fetchRFID(){
				$.post('functions/fetch_rfid.php').done(function(data){
					if(data != ""){
						$("[name='user_rfid']").val(data);
						clearInterval(wait);
						$("[name='fetch-rfid']").html("Lancer la détection");
						listening = false;
					} else {
						console.log("Aucun RFID détecté");
					}
				});
			}
		</script>
	</body>
</html>
