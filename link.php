<?php
require_once "functions/db_connect.php";
$db = PDOFactory::getConnection();
if(isset($_SESSION["username"])){
	header("Location: dashboard");
} else {
	if(isset($_POST["login"])){
		$user_id = $_POST["user_id"];
		$username = stripslashes($_POST["user_login"]);
		$password = stripslashes($_POST["user_pwd"]);

		try{
			$uploadCredentials = $db->query("UPDATE users SET login = '$username', password = '$password' WHERE user_id = '$user_id'");
			header("Location: portal");
		} catch(PDOException $e){
			$e->getMessage();
		}
	}
}
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Connexion</title>
		<?php include "styles.php";?>
	</head>
	<body>
		<?php include "nav.php";?>
		<div class="portal-main">
			<div class="main layer">
				<div class="col-lg-4 col-lg-offset-4">
					<legend><span class="glyphicon glyphicon-edit"></span> Générer vos identifiants</legend>
					<form action="" method="post">
						<div class="form-group form-group-lg">
							<input type="text" placeholder="Votre ID" class="form-control form-control-portal" name="user_id">
						</div>
						<div class="form-group form-group-lg">
							<input type="text" placeholder="Login" class="form-control form-control-portal" name="user_login">
						</div>
						<div class="form-group form-group-lg">
							<input type="password" placeholder="Mot de passe" class="form-control form-control-portal" name="user_pwd">
						</div>
						<input type="submit" class="btn btn-primary btn-block" name="login" value="Mettre à jour">
					</form>
					<p class="no-account">Vous avez déjà vos identifiants ? Par <a href="portal">ici</a></p>
				</div>
			</div>
		</div>
		<?php include "scripts.php";?>
		<script>
			$(function(){
				// Get a random picture from the library
				var rand = Math.floor(Math.random() * 6);
				console.log(rand);
				$(".portal-main").css("background-image", "url(assets/images/Portal_"+rand+".jpg)");
			})
		</script>
	</body>
</html>
