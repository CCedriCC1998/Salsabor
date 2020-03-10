<?php
session_start();
if(!isset($_SESSION["username"])){
	header('location: portal');
}
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Statistiques | Salsabor</title>
		<?php include "styles.php";?>
	</head>
	<body>
		<?php include "nav.php";?>
		<div class="container-fluid">
			<div class="row">
				<?php include "side-menu.php";?>
				<div class="col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
					<legend><span class="glyphicon glyphicon-stats"></span> Statistiques</legend>
				</div>
			</div>
		</div>
		<?php include "scripts.php";?>
	</body>
</html>
