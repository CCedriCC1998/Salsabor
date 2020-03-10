<?php
session_start();
if(!isset($_SESSION["username"])){
	header('location: portal');
}
require_once 'functions/db_connect.php';
$db = PDOFactory::getConnection();
$user_id = $_GET['id'];

// User details
$details = $db->query("SELECT * FROM users u
						LEFT JOIN locations l ON u.user_location = l.location_id
						WHERE user_id='$user_id'")->fetch(PDO::FETCH_ASSOC);

$labels = $db->query("SELECT * FROM assoc_user_tags ur
						JOIN tags_user tu ON ur.tag_id_foreign = tu.rank_id
						WHERE user_id_foreign = '$user_id'
						ORDER BY tag_color DESC");

$details["count"] = $db->query("SELECT * FROM tasks
					WHERE ((task_token LIKE '%USR%' AND task_target = '$user_id')
					OR (task_token LIKE '%PRD%' AND task_target IN (SELECT id_produit_adherent FROM produits_adherents WHERE id_user_foreign = '$user_id'))
					OR (task_token LIKE '%TRA%' AND task_target IN (SELECT id_transaction FROM transactions WHERE payeur_transaction = '$user_id')))
						AND task_state = 0")->rowCount();

$is_teacher = $db->query("SELECT * FROM assoc_user_tags ur
								JOIN tags_user tu ON tu.rank_id = ur.tag_id_foreign
								WHERE rank_name = 'Professeur' AND user_id_foreign = '$user_id'")->rowCount();

// Locations
$locations = $db->query("SELECT * FROM locations ORDER BY location_name ASC");

// Possible late maturities
$queryEcheances = $db->query("SELECT * FROM produits_echeances JOIN transactions ON reference_achat=transactions.id_transaction WHERE echeance_effectuee=2 AND payeur_transaction=$user_id")->rowCount();
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Editer - <?php echo $details["user_prenom"]." ".$details["user_nom"];?> | Salsabor</title>
		<base href="../">
		<?php include "styles.php";?>
		<link href="assets/css/croppie.css" rel="stylesheet" type="text/css">
		<link rel="stylesheet" href="assets/css/fileinput.min.css">
		<?php include "scripts.php";?>
		<script src="assets/js/tags.js"></script>
		<script src="assets/js/croppie.min.js"></script>
		<script src="assets/js/fileinput.min.js"></script>
	</head>
	<body>
		<?php include "nav.php";?>
		<div class="container-fluid">
			<div class="row">
				<?php include "side-menu.php";?>
				<div class="col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
					<?php include "inserts/user_banner.php";?>
					<?php if($queryEcheances != 0){ ?>
					<div class="alert alert-danger"><strong>Attention !</strong> Cet adhérent a des échéances en retard.</div>
					<?php } ?>
					<ul class="nav nav-tabs">
						<li role="presentation" class="active visible-xs-block"><a href="user/<?php echo $user_id;?>">Infos perso</a></li>
						<li role="presentation" class="active hidden-xs"><a href="user/<?php echo $user_id;?>">Informations personnelles</a></li>
						<?php if($is_teacher == 1){ ?>
						<!--<li role="presentation"><a>Cours donnés</a></li>-->
						<li role="presentation"><a href="user/<?php echo $user_id;?>/tarifs">Tarifs</a></li>
						<li role="presentation"><a href="user/<?php echo $user_id;?>/facturation">Facturation</a></li>
						<!--<li role="presentation"><a>Statistiques</a></li>-->
						<?php } ?>
						<li role="presentation"><a href="user/<?php echo $user_id;?>/abonnements">Abonnements</a></li>
						<li role="presentation"><a href="user/<?php echo $user_id;?>/historique">Participations</a></li>
						<li role="presentation"><a href="user/<?php echo $user_id;?>/achats">Achats</a></li>
						<li role="presentation"><a href="user/<?php echo $user_id;?>/reservations">Réservations</a></li>
						<li role="presentation"><a href="user/<?php echo $user_id;?>/taches">Tâches</a></li>
					</ul>
					<p class="sub-legend">Informations personnelles</p>
					<form method="post" class="form-horizontal" role="form" id="user-details-form" enctype="multipart/form-data">
						<div class="form-group">
							<label for="user_prenom" class="col-sm-3 control-label">Prénom</label>
							<div class="col-sm-9">
								<input type="text" name="user_prenom" id="user_prenom" placeholder="Prénom" class="form-control" value="<?php echo $details["user_prenom"];?>">
							</div>
						</div>
						<div class="form-group">
							<label for="user_nom" class="col-sm-3 control-label">Nom</label>
							<div class="col-sm-9">
								<input type="text" name="user_nom" id="user_nom" placeholder="Nom" class="form-control modal-updatable-<?php echo $user_id;?>" value="<?php echo $details["user_nom"];?>">
							</div>
						</div>
						<div class="form-group">
							<label for="mail" class="col-sm-3 control-label">Adresse mail</label>
							<div class="col-sm-9">
								<input type="email" name="mail" id="mail" placeholder="Adresse mail" class="form-control modal-updatable-<?php echo $user_id;?>" value="<?php echo $details["mail"];?>">
							</div>
						</div>
						<div class="form-group">
							<label for="avatar" class="col-sm-3 control-label">Photo de profil <span class="glyphicon glyphicon-floppy-saved" data-toggle="tooltip" title="Enregistrement automatique."></span></label>
							<div class="col-sm-9">
								<div class="pp-input btn btn-primary">
									<span>Choisissez une image</span>
									<input type="file" id="upload" accept="image/jpeg, image/x-png">
								</div>
							</div>
							<!--<p class="help-block">Formats JPEG ou PNG et de taille inférieurs à 1 Mo.</p>-->
							<div class="crop-step">
								<div id="upload-demo"></div>
								<input type="hidden" id="imagebase64">
								<span class="btn btn-primary btn-block upload-result">Mettre à jour</span>
							</div>
						</div>
						<div class="form-group">
							<label for="rue" class="col-sm-3 control-label">Adresse postale</label>
							<div class="col-sm-9">
								<input type="text" name="rue" id="rue" placeholder="Adresse" class="form-control" value="<?php echo $details["rue"];?>">
							</div>
						</div>
						<div class="form-group">
							<label for="code_postal" class="col-sm-3 control-label">Code postal</label>
							<div class="col-sm-9">
								<input type="number" name="code_postal" id="code_postal" placeholder="Code Postal" class="form-control" value="<?php echo $details["code_postal"];?>">
							</div>
						</div>
						<div class="form-group">
							<label for="ville" class="col-sm-3 control-label">Ville</label>
							<div class="col-sm-9">
								<input type="text" name="ville" id="ville" placeholder="Ville" class="form-control" value="<?php echo $details["ville"];?>">
							</div>
						</div>
						<div class="form-group">
							<label for="telephone" class="col-sm-3 control-label">Téléphone principal</label>
							<div class="col-sm-9">
								<input type="tel" name="telephone" id="telephone" placeholder="Numéro de téléphone secondaire" class="form-control modal-updatable-<?php echo $user_id;?>" value="<?php echo $details["telephone"];?>">
							</div>
						</div>
						<div class="form-group">
							<label for="tel_secondaire" class="col-sm-3 control-label">Téléphone secondaire</label>
							<div class="col-sm-9">
								<input type="tel" name="tel_secondaire" id="tel_secondaire" placeholder="Numéro de téléphone secondaire" class="form-control" value="<?php echo $details["tel_secondaire"];?>">
							</div>
						</div>
						<div class="form-group">
							<label for="website" class="col-sm-3 control-label">Site Web</label>
							<div class="col-sm-9">
								<input type="url" name="website" placeholder="Adresse de site web" class="form-control" value="<?php echo $details["website"];?>">
							</div>
						</div>
						<div class="form-group">
							<label for="organisation" class="col-sm-3 control-label">Organisation</label>
							<div class="col-sm-9">
								<input type="text" name="organisation" placeholder="Organisation" class="form-control" value="<?php echo $details["organisation"];?>">
							</div>
						</div>
						<div class="form-group">
							<label for="date_naissance" class="col-sm-3 control-label">Date de naissance</label>
							<div class="col-sm-9">
								<input type="text" name="date_naissance" id="birthdate" class="form-control" placeholder="Date de naissance">
							</div>
						</div>
						<p class="sub-legend">Informations Salsabor</p>
						<div class="form-group">
							<label for="statuts" class="col-sm-3 control-label">&Eacute;tiquettes</label>
							<div class="col-sm-9 user_tags">
								<h4 class="tags_container">
									<?php while($label = $labels->fetch(PDO::FETCH_ASSOC)){ ?>
									<span class="label label-salsabor" title="<?php echo $label["rank_name"];?>" id="user-tag-<?php echo $label["entry_id"];?>" style="background-color:<?php echo $label["tag_color"];?>"><?php echo $label["rank_name"];?></span>
									<?php } ?>
									<span class="label label-default label-clickable label-add trigger-sub" id="label-add" data-subtype='user-tags' data-targettype='user' title="Ajouter une étiquette">+</span>
								</h4>
							</div>
						</div>
						<div class="form-group">
							<label for="date_inscription" class="control-label col-sm-3">Date d'inscription</label>
							<div class="col-sm-9">
								<input type="text" name="date_inscription" id="register_date" class="form-control" placeholder="Date d'inscription">
							</div>
						</div>
						<div class="form-group">
							<label for="user_location" class="control-label col-sm-3">Région d'activité <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" title="Personnalise les salles, plannings et résultats de recherche accessibles en fonction de leurs régions. Correspond à la région principale fréquentée pour les utilisateurs non-staff."></span></label>
							<div class="col-sm-9">
								<select name="user_location" id="user-location" class="form-control">
									<option value="">Aucune région</option>
									<?php while($location = $locations->fetch(PDO::FETCH_ASSOC)){
	if($details["user_location"] == $location["location_id"]){ ?>
									<option selected value="<?php echo $location["location_id"];?>"><?php echo $location["location_name"];?></option>
									<?php } else { ?>
									<option value="<?php echo $location["location_id"];?>"><?php echo $location["location_name"];?></option>
									<?php }
} ?>
								</select>
							</div>
						</div>
						<div class="form-group">
							<label for="user_rfid" class="col-sm-3 control-label">Code carte</label>
							<div class="col-sm-9">
								<div class="input-group">
									<input type="text" name="user_rfid" id="user-rfid" class="form-control" placeholder="Scannez une nouvelle puce pour récupérer le code RFID" value="<?php echo $details["user_rfid"];?>">
									<span role="buttton" class="input-group-btn"><a class="btn btn-info" role="button" name="fetch-rfid">Lancer la détection</a></span>
								</div>
							</div>
						</div>
						<div class="form-group">
							<label for="certificat" class="col-sm-3 control-label">Certificat Médical <span class="glyphicon glyphicon-floppy-saved" data-toggle="tooltip" title="Le document est enregistré automatiquement quand l&apos;upload est terminé."></span></label>
							<div class="col-sm-9">
								<div class="row">
									<div class="col-sm-6">
										<input type="file" class="file-loading" id="certificat-input" name="certificat">
									</div>
									<?php if($details["certificat"] != null){ ?>
									<div class="col-sm-6">
										<a href="Salsabor/<?php echo $details["certificat"];?>" target="_blank" class="btn btn-primary btn-block">Visualiser</a>
									</div>
									<?php } ?>
								</div>
							</div>
						</div>
						<div class="form-group">
							<label for="rib" class="col-sm-3 control-label">RIB <span class="glyphicon glyphicon-floppy-saved" data-toggle="tooltip" title="Le document est enregistré automatiquement quand l&apos;upload est terminé."></span></label>
							<div class="col-sm-9">
								<div class="row">
									<div class="col-sm-6">
										<input type="file" class="file-loading" id="rib-input" name="rib">
									</div>
									<?php if($details["rib"] != null){ ?>
									<div class="col-sm-6">
										<a href="Salsabor/<?php echo $details["rib"];?>" target="_blank" class="btn btn-primary btn-block">Visualiser</a>
									</div>
									<?php } ?>
								</div>
							</div>
						</div>
						<div class="form-group">
							<label for="commentaires" class="col-sm-3 control-label">Commentaires</label>
							<div class="col-sm-9">
								<textarea rows="5" class="form-control" name="commentaires"><?php echo $details["commentaires"];?></textarea>
							</div>
						</div>
					</form>
					<button class="btn btn-primary btn-block save-settings" id="update-user">Enregistrer les modifications</button>
				</div>
			</div>
		</div>
		<?php include "inserts/sub_modal_product.php";?>
		<?php include "inserts/edit_modal.php";?>
		<style>
			.profile-picture{
				float: left;
				display: none;
			}
			.pp-input{
				cursor: pointer;
				position: relative;
			}
			.pp-input > input{
				position: absolute;
				top: 0;
				left: 0;
				opacity: 0;
				cursor: pointer;
				width: 100%;
				height: 100%;
			}
			.crop-step{
				display: none;
			}
			.user-pp{
				margin-bottom: 10px;
			}
		</style>
		<script>
			$(document).ready(function(){
				initial_tags = createTagsArray();
				console.log(initial_tags);
				var user_id = /([0-9]+)/.exec(top.location.pathname)[0];
				$.get("functions/fetch_user_details.php", {user_id : user_id}).done(function(data){
					var user_dates = JSON.parse(data);
					$("#birthdate").datetimepicker({
						format: "DD/MM/YYYY",
						defaultDate: user_dates.date_naissance,
						locale: "fr"
					});
					$("#register_date").datetimepicker({
						format: "DD/MM/YYYY",
						defaultDate: user_dates.date_inscription,
						locale: "fr"
					});
				});

				$("#certificat-input").fileinput({
					autoReplace: true,
					browseClass: "btn btn-info btn-block",
					browseLabel: 'Choisissez un fichier',
					dropZoneEnabled: false,
					maxFileCount: 1,
					uploadLabel: 'Envoyer',
					removeLabel: 'Supprimer',
					showCaption: false,
					uploadExtraData:{
						user_id: /([0-9]+)/.exec(top.location.pathname)[0],
						location: "../user_data/certificats_medicaux/"
					},
					uploadUrl: "functions/upload_file.php"
				})

				$("#rib-input").fileinput({
					autoReplace: true,
					browseClass: "btn btn-info btn-block",
					browseLabel: 'Choisissez un fichier',
					dropZoneEnabled: false,
					maxFileCount: 1,
					uploadLabel: 'Envoyer',
					removeLabel: 'Supprimer',
					showCaption: false,
					uploadExtraData:{
						user_id: /([0-9]+)/.exec(top.location.pathname)[0],
						location: "../user_data/ribs/"
					},
					uploadUrl: "functions/upload_file.php"
				})

				// Croppie
				var $uploadCrop;

				function readFile(input) {
					if (input.files && input.files[0]) {
						var reader = new FileReader();
						reader.onload = function (e) {
							$uploadCrop.croppie('bind', {
								url: e.target.result
							});
							$('.upload-demo').addClass('ready');
							$(".crop-step").show();
						}
						reader.readAsDataURL(input.files[0]);
					}
				}

				$uploadCrop = $('#upload-demo').croppie({
					viewport: {
						width: 200,
						height: 200,
						type: 'circle'
					},
					boundary: {
						width: 300,
						height: 300
					}
				});

				$('#upload').on('change', function () { readFile(this); });
				$('.upload-result').on('click', function (ev) {
					$uploadCrop.croppie('result', {
						type: 'canvas',
						size: 'original'
					}).then(function (resp) {
						$('#imagebase64').val(resp);
						$('#form').submit();
					});
				});

				//
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

				window.initial_form = $("#user-details-form").serialize();
				$(window).on('beforeunload', function(){
					var current_form = $("#user-details-form").serialize();
					console.log(window.initial_form, current_form);
					if(current_form !== window.initial_form)
						return "Vous avez des modifications non enregistrées, êtes-vous sûr de vouloir quitter la page ?";
				})
			}).on('click', '.upload-result', function(){
				var picture_value = $("#imagebase64").val();
				var user_id = /([0-9]+)/.exec(top.location.pathname);
				$.post("functions/update_picture.php", {picture_value : picture_value, user_id : user_id[0]}).done(function(data){
					console.log(data);
					var d = new Date();
					$(".banner-profile-picture").attr("src", data+"?"+d.getTime());
					$(".crop-step").hide();
				})
			}).on('click', '#update-user', function(){
				var user_id = /([0-9]+)/.exec(top.location.pathname);
				var values = $("#user-details-form").serialize(), table = "users", entry_id = user_id[0], current_tags = createTagsArray();
				console.log(values);
				$.when(updateEntry(table, values, entry_id), updateTargetTags(initial_tags, current_tags, entry_id, "user")).done(function(data){
					console.log(data);
					var rfid = $("#user-rfid").val();
					if(rfid != ""){
						$.post("functions/delete_association_record.php", {rfid : rfid});
					}
					showNotification("Modifications enregistrées", "success");
					if(user_id[0] == "<?php echo $_SESSION["user_id"];?>"){
						console.log("updating saved session"+ user_id);
						$.get("functions/update_user_session.php");
					}
					$("#refresh-rfid").text($("#user-rfid").val());
					var updated_adress = $("#rue").val()+" - "+$("#code_postal").val()+" "+$("#ville").val();
					$("#refresh-address").text(updated_adress);
					$("#refresh-region").text($("#user-location>option:selected").text());
					$("#refresh-mail").text($("#mail").val());
					$("#refresh-telephone").text($("#telephone").val());
					$("#refresh-prenom").text($("#user_prenom").val());
					$("#refresh-nom").text($("#user_nom").val());
					initial_tags = current_tags;
					window.initial_form = $("#user-details-form").serialize();
				})
			})
		</script>
	</body>
</html>
