<?php
require_once "functions/db_connect.php";
require_once "functions/tools.php";
$db = PDOFactory::getConnection();
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
				<div class="col-lg-4 col-lg-offset-4 error-space">
					<p class="big-legend"><span class="glyphicon glyphicon-hourglass"></span></p>
					<legend> Oups ! :(</legend>
					<p class="sub-legend">On dirait que la page que vous recherchez n'existe pas...</p>
					<a href="../Salsabor/dashboard" class="btn btn-primary btn-block">Revenir en sécurité</a>
					<img src="assets/images/404.gif" alt="" class="error-image">
				</div>
			</div>
		</div>
		<style>
			.error-space{
				text-align: center;
			}

			.error-image{
				margin-top: 20px;
				width: 70%;
			}
		</style>
		<?php include "scripts.php";?>
	</body>
</html>
