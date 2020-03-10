<?php
session_start();
if(!isset($_SESSION["username"])){
	header('location: portal');
}
require_once 'functions/db_connect.php';
$db = PDOFactory::getConnection();

$data = $_GET["id"];

// Détails du forfait
$querySalle = $db->prepare("SELECT * FROM rooms WHERE room_id=?");
$querySalle->bindParam(1, $data);
$querySalle->execute();
$salle = $querySalle->fetch(PDO::FETCH_ASSOC);
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Détails de la salle <?php echo $salle["salle_name"];?> | Salsabor</title>
		<?php include "styles.php";?>
	</head>
	<body>
		<?php include "nav.php";?>
		<div class="container-fluid">
			<div class="row">
				<?php include "side-menu.php";?>
				<div class="col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
					<legend><span class="glyphicon glyphicon-pushpin"></span> Salle <?php echo $salle["salle_name"];?></legend>
				</div>
			</div>
		</div>
		<?php include "scripts.php";?>
	</body>
</html>
