<?php
session_start();
if(!isset($_SESSION["username"])){
	header('location: portal');
}
require_once 'functions/db_connect.php';
$db = PDOFactory::getConnection();
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Notifications | Salsabor</title>
		<?php include "styles.php";?>
	</head>
	<body>
		<?php include "nav.php";?>
		<div class="container-fluid">
			<div class="row">
				<?php include "side-menu.php";?>
				<div class="col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
					<legend><span class="glyphicon glyphicon-bell"></span> Notifications</legend>
					<ul class="notifications-container container-fluid"></ul>
				</div>
			</div>
		</div>
		<?php include "scripts.php";?>
		<script src="assets/js/jquery.waypoints.min.js"></script>
		<script>
			$(document).ready(function(){
				moment.locale('fr');
				fetchNotifications(50, null, "notifications-container");
			})
		</script>
	</body>
</html>
<script>
</script>
