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
		<title>Logging | Salsabor</title>
		<?php include "styles.php";?>
		<?php include "scripts.php";?>
	</head>
	<body>
		<?php include "nav.php";?>
		<div class="container-fluid">
			<div class="row">
				<?php include "side-menu.php";?>
				<div class="col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
					<legend><span class="glyphicon glyphicon-hdd"></span> Logging</legend>
					<span class="help-block">Le listing est rafraîchi automatiquement.</span>
					<div class="logs-container container-fluid loading-container">
					</div>
				</div>
			</div>
		</div>
		<script>
			$(document).ready(function(){
				fetchLogs($(".logs-container"), null, 0);
			})
		</script>
	</body>
</html>
