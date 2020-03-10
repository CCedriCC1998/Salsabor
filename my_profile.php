<?php
session_start();
if(!isset($_SESSION["username"])){
	header('location: portal');
}
require_once 'functions/db_connect.php';
$db = PDOFactory::getConnection();
$user_id = $_SESSION["user_id"];
$user_details = $db->query("SELECT * FROM users WHERE user_id = '$user_id'")->fetch(PDO::FETCH_ASSOC);
$birthday = date($user_details["date_naissance"]);
if($user_details["user_rfid"] != ""){
	$rfid = $user_details["user_rfid"];
} else {
	$rfid = "Pas de code";
}
if(isset($_POST['imagebase64'])){
	$data = $_POST['imagebase64'];

	list($type, $data) = explode(';', $data);
	list(, $data)      = explode(',', $data);
	$data = base64_decode($data);

	$target_dir = "assets/pictures/";
	$new_file = $target_dir.$user_id.'.png';
	file_put_contents($new_file, $data);
	$target_destination = $new_file;
	move_uploaded_file($new_file, $target_destination);
	$update = $db->query("UPDATE users SET photo = '$new_file' WHERE user_id = $user_id");
	$_SESSION["photo"] = $new_file;
}
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Mon profil | Salsabor</title>
		<base href="../">
		<?php include "styles.php";?>
		<link href="assets/css/croppie.css" rel="stylesheet" type="text/css">
		<?php include "scripts.php";?>
		<script src="assets/js/jquery-1.11.3.min.js"></script>
		<script src="assets/js/croppie.min.js"></script>
	</head>
	<body>
		<?php include "my-nav.php";?>
		<div class="container-fluid">
			<div class="row">
				<?php include "side-menu.php";?>
				<div class="col-sm-offset-3 col-lg-10 col-lg-offset-2 banner-container no-padding">
					<div id="banner">
						<img src="assets/images/my-salsabor.png">
					</div>
					<div class="user-profile-container">
						<div class="user-pp">
							<img src="<?php echo $user_details["photo"];?>" alt="">
						</div>
						<div class="user-profile-name">
							<span><?php echo $user_details["user_prenom"]." ".$user_details["user_nom"];?></span>
						</div>
						<div class="user-profile-code">
							<span><span class="glyphicon glyphicon-qrcode"></span> <?php echo $rfid;?></span>
						</div>
						<button class="btn btn-primary" data-toggle="collapse" data-target="#pp-collapse">Changer votre photo</button>
					</div>
				</div>
				<div class="col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
					<legend><span class="glyphicon glyphicon-user"></span> Mes informations personnelles</legend>
					<div class="collapse" id="pp-collapse">
						<div class="well">
							<form id="form" method="post">
								<div class="pp-input btn btn-primary btn-block">
									<span>Choisissez une image</span>
									<input type="file" id="upload" accept="image/jpeg, image/x-png">
								</div>
								<p class="help-block">Formats JPEG ou PNG et de taille inférieurs à 1 Mo.</p>
								<div class="crop-step">
									<div id="upload-demo"></div>
									<input type="hidden" id="imagebase64" name="imagebase64">
									<a href="my/profile#" class="btn btn-primary btn-block upload-result">Mettre à jour</a>
								</div>
							</form>
						</div>
					</div>
					<p class="sub-legend">Modifiez vos informations personnelles</p>
					<form action="" class="form-horizontal">
						<div class="form-group">
							<label for="" class="col-sm-3 control-label">Prénom</label>
							<div class="col-sm-9">
								<input type="text" class="form-control" placeholder="Prénom" name="user_prenom" value="<?php echo $user_details["user_prenom"];?>">
							</div>
						</div>
						<div class="form-group">
							<label for="" class="col-sm-3 control-label">Nom</label>
							<div class="col-sm-9">
								<input type="text" class="form-control" placeholder="Nom" name="user_nom" value="<?php echo $user_details["user_nom"];?>">
							</div>
						</div>
						<div class="form-group">
							<label for="" class="col-sm-3 control-label">Adresse mail</label>
							<div class="col-sm-9">
								<input type="mail" class="form-control" placeholder="Adresse mail" name="mail" value="<?php echo $user_details["mail"];?>">
							</div>
						</div>
						<div class="form-group">
							<label for="" class="col-sm-3 control-label">Numéro de téléphone</label>
							<div class="col-sm-9">
								<input type="tel" class="form-control" name="telephone" value="<?php echo $user_details["telephone"];?>">
							</div>
						</div>
						<div class="form-group">
							<label for="" class="col-sm-3 control-label">Date de naissance</label>
							<div class="col-sm-9">
								<input type="date" class="form-control" name="date_naissance" value="<?php echo $birthday;?>">
							</div>
						</div>
					</form>
					<button class="btn btn-primary btn-block save-settings">Enregistrer les modifications</button>
				</div>
			</div>
		</div>
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
			$( document ).ready(function() {
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

			});
			$(document).on('click', '.save-settings', function(){
				var values = $(".form-horizontal").serialize(), table = "users", entry_id = <?php echo $user_id;?>;
				$.when(updateEntry(table, values, entry_id)).done(function(){
					$(".save-settings").switchClass("btn-primary", "btn-success");
					$(".save-settings").blur();
					$(".save-settings").text("Modifications enregistrées");
					setTimeout(function(){
						$(".save-settings").switchClass("btn-success", "btn-primary");
						$(".save-settings").text("Valider");
					}, 3000);
					showNotification("Votre profil a été mis à jour", "success");
				})
			})
		</script>
	</body>
</html>

