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
		<title>Monitoring Participations | Salsabor</title>
		<base href="../">
		<?php include "styles.php";?>
		<?php include "scripts.php";?>
	</head>
	<body>
		<?php include "nav.php";?>
		<div class="container-fluid">
			<div class="row">
				<?php include "side-menu.php";?>
				<div class="col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
					<legend><span class="glyphicon glyphicon-tasks"></span> Monitoring - Participations</legend>
					<p class="sub-legend">Heure de début de surveillance : <?php echo date("d/m/Y H:i:s");?></p>
					<span class="help-block">Lorsqu'un passe RFID est enregistré, les informations s'actualiseront automatiquement</span>
					<div class="row">
						<div class="col-sm-5 col-md-3">
							<div class="thumbnail">
								<img src="assets/images/qr_example.png" alt="..." id="qr_image">
							</div>
						</div>
						<div class="col-sm-5 monitoring-infos">
							<h2 id="rfid_name"></h3>
							<p>Enregistré dans : <span id="rfid_location"></span></p>
							<p>Heure de passage : <span id="record_time"></span></p>
							<p>Type de passage : <span id="passage_type"></span></p>
							<p>Prévu pour le cours : <span id="passage_session"></span></p>
							<p>Produit utilisé : <span id="passage_product"></span></p>
						</div>
					</div>
				</div>
			</div>
		</div>
		<script>
			$(document).ready(function(){
				$("#rfid_name").text("En attente");
				var date = moment().format("YYYY-MM-DD HH:mm:ss");
				displayMonitoring(date);
			})

			function displayMonitoring(date){
				$.when(monitorParticipations(date)).done(function(data){
					if(data !== "false"){
						console.log(data);
						var record_details = JSON.parse(data);
						date = record_details.passage_date;
						if(record_details.status == 5){
							$("#rfid_name").text("Pas d'utilisateur correspondant");
						} else {
							$("#rfid_name").text(record_details.user_prenom+" "+record_details.user_nom+" ("+record_details.user_rfid+")");
						}
						$("#qr_image").attr("src", record_details.photo);
						$("#rfid_location").text(record_details.room_name+" ( "+record_details.room_token+" )")
						$("#record_time").text(moment(record_details.passage_date).format("DD/MM/YYYY HH:mm:ss"));
						var record_message = "";
						switch(record_details.status){
							case "0":
								record_message = "Valide";
								break;

							case "1":
								record_message = "Association";
								break;

							case "2":
								record_message = "Validé";
								break;

							case "3":
								record_message = "Pas de forfait correspondant";
								break;

							case "4":
								record_message = "Pas de cours correspondant";
								break;

							case "5":
								record_message = "Pas d'utilisateur correspondant";
								break;
						}
						$("#passage_type").text(record_message);
						$("#passage_session").text(record_details.session_name+"(du "+moment(record_details.session_start).format("DD/MM/YYYY HH:mm:ss")+" au "+moment(record_details.session_end).format("DD/MM/YYYY HH:mm:ss")+")");
						$("#passage_product").text(record_details.product_name);
					} else {
						console.log("En attente");
					}

					setTimeout(displayMonitoring, 1000, date);
				})
			}

			function monitorParticipations(date){
				return $.get("functions/fetch_participation_monitoring.php", {date : date});
			}
		</script>
	</body>
</html>
