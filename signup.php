<?php
require_once "functions/db_connect.php";
require_once "functions/tools.php";
$db = PDOFactory::getConnection();
if(isset($_SESSION["username"])){
	header("Location: dashboard");
} else {
	if(isset($_POST["login"])){
		$fullname = $_POST["user_prenom"]." ".$_POST["user_nom"];
		$user_id = solveAdherentToId(htmlspecialchars($fullname));
		$username = htmlspecialchars($_POST["user_login"]);
		$password = htmlspecialchars($_POST["user_pwd"]);

		try{
			$uploadCredentials = $db->prepare("UPDATE users SET login = ?, password = ? WHERE user_id = ?");
			$uploadCredentials->bindParam(1, $username, PDO::PARAM_STR);
			$uploadCredentials->bindParam(2, $password, PDO::PARAM_STR);
			$uploadCredentials->bindParam(3, $user_id, PDO::PARAM_INT);
			$uploadCredentials->execute();
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
		<title>Inscription | Salsabor</title>
		<?php include "styles.php";?>
	</head>
	<body>
		<?php include "nav.php";?>
		<div class="portal-main">
			<div class="main layer">
				<div class="col-lg-4 col-lg-offset-4 login-space">
					<legend><span class="glyphicon glyphicon-log-in"></span> Bienvenue !</legend>
					<form action="" method="post">
						<div class="form-group form-group-lg">
							<input type="text" placeholder="Prénom" class="form-control form-control-portal" name="user_prenom">
						</div>
						<div class="form-group form-group-lg">
							<input type="text" placeholder="Nom" class="form-control form-control-portal" name="user_nom">
						</div>
						<div class="form-group form-group-lg">
							<input type="text" placeholder="Login" class="form-control form-control-portal" name="user_login">
						</div>
						<div class="form-group form-group-lg">
							<input type="password" placeholder="Mot de passe" class="form-control form-control-portal" name="user_pwd">
						</div>
						<input type="submit" class="btn btn-primary btn-block" name="login" value="Création">
					</form>
					<p class="login-help">Vous avez déjà des identifiants ? Identifiez-vous par <a href="portal">ici</a></p>
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
