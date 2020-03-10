<?php
require_once "functions/db_connect.php";
include "functions/tools.php";
$db = PDOFactory::getConnection();
if(isset($_SESSION["username"])){
	header("Location: dashboard");
} else {
	if(isset($_POST["login"])){
		$username = stripslashes($_POST["user_login"]);
		$password = stripslashes($_POST["user_pwd"]);

		$checkCredentials = $db->prepare("SELECT * FROM users WHERE login=? OR mail=? AND password=?");
		$checkCredentials->bindParam(1, $username);
		$checkCredentials->bindParam(2, $username);
		$checkCredentials->bindParam(3, $password);
		$checkCredentials->execute();

		if($checkCredentials->rowCount() == 1){
			$credentials = $checkCredentials->fetch(PDO::FETCH_ASSOC);
			session_start();
			$_SESSION["user_id"] = $credentials["user_id"];
			$_SESSION["username"] = $credentials["user_prenom"]." ".$credentials["user_nom"];
			$_SESSION["photo"] = $credentials["photo"];
			$_SESSION["location"] = $credentials["user_location"];
			logAction($db, "Connexion", "users-".$credentials["user_id"]);
			header("Location: dashboard");
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
					<legend><span class="glyphicon glyphicon-log-in"></span> Connexion</legend>
					<form action="" method="post">
						<div class="form-group form-group-lg">
							<input type="text" placeholder="Login" class="form-control form-control-portal" name="user_login">
						</div>
						<div class="form-group form-group-lg">
							<input type="password" placeholder="Mot de passe" class="form-control form-control-portal" name="user_pwd">
						</div>
						<input type="submit" class="btn btn-primary btn-block" name="login" value="Connexion">
					</form>
					<!--<p class="no-account">Vous êtes staff sans compte ? Par <a href="link">ici</a></p>-->
					<p class="no-account">Pas d'identifiants ? C'est par <a href="signup">ici</a></p>
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
