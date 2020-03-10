<?php
session_start();
if(!isset($_SESSION["username"])){
	header('location: portal');
}
require_once 'functions/db_connect.php';
$db = PDOFactory::getConnection();
require_once "functions/cours.php";

$user_labels = $db->query("SELECT * FROM tags_user");
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Ajouter un événement | Salsabor</title>
		<base href="../">
		<?php include "styles.php";?>
		<?php include "scripts.php";?>
		<script src="assets/js/check_calendar.js"></script>
		<script src="assets/js/sessions.js"></script>
	</head>
	<body>
		<?php include "nav.php";?>
		<div class="container-fluid">
			<div class="row">
				<?php include "side-menu.php";?>
				<div class="col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
					<legend><span class="glyphicon glyphicon-plus"></span> Ajouter un événement
						<button class="btn btn-primary btn-add">Ajouter</button>
					</legend>
					<form method="post" role="form" class="form-horizontal" id="event-add-form">
						<div class="form-group">
							<label for="event_name" class="col-lg-3 control-label">Titre de l'événement</label>
							<div class="col-lg-9">
								<input type="text" class="form-control mandatory" name="event_name" placeholder="Titre de l'événement">
							</div>
						</div>
						<div class="form-group">
							<label for="event_handler" class="col-lg-3 control-label">Gérant principal <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" title="Vous pouvez régler les noms qui vous seront suggérés avec le sélecteur 'Suggérer parmi...'"></span></label>
							<div class="col-lg-9">
								<div class="input-group">
									<div class="input-group-btn">
										<button type="button" class="btn btn-default dropdown-toggle suggestion-text" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Suggérer parmi... <span class="caret"></span></button>
										<ul class="dropdown-menu dropdown-custom">
											<?php while($user_label = $user_labels->fetch(PDO::FETCH_ASSOC)){ ?>
											<li class="completion-option"><a><?php echo $user_label["rank_name"];?></a></li>
											<?php } ?>
											<li class="completion-option"><a>Ne pas suggérer</a></li>
										</ul>
									</div>
									<input type="text" class="form-control filtered-complete" id="complete-teacher" name="event_handler">
								</div>
							</div>
						</div>
						<div class="form-group">
							<label for="event_start" class="col-lg-3 control-label">Début</label>
							<div class="col-lg-9">
								<input type="text" class="form-control" name="event_start" id="datepicker-start">
							</div>
						</div>
						<div class="form-group">
							<label for="event_end" class="col-lg-3 control-label">Fin</label>
							<div class="col-lg-9">
								<input type="text" class="form-control" name="event_end" id="datepicker-end">
							</div>
						</div>
						<div class="form-group">
							<label for="" class="col-lg-3 control-label">Adresse</label>
							<div class="col-lg-9">
								<input type="text" class="form-control" name="event_address">
							</div>
						</div>
						<div class="form-group">
							<label for="" class="col-lg-3 control-label">Description</label>
							<div class="col-lg-9">
								<textarea name="event_description" class="form-control" id="" cols="30" rows="10"></textarea>
							</div>
						</div>
						<div class="align-right">
							<p id="error_message"></p>
						</div>
					</form>
				</div>
			</div>
		</div>
		<style>
			.main{
				overflow: visible;
			}
		</style>
		<script>
			$(document).ready(function(){
				var start = sessionStorage.getItem('start');
				var default_start, default_end;
				if(start != null){
					var format_start = new Date(start).toISOString();
					var end = sessionStorage.getItem('end');
					var format_end = new Date(end).toISOString();
					var default_start = moment(format_start).format('YYYY-MM-DD HH:mm:ss');
					var default_end = moment(format_end).format('YYYY-MM-DD HH:mm:ss');
				} else {
					var format_start = new Date().toISOString();
					var default_start = moment(format_start).startOf('hour').add(1, 'h').format('YYYY-MM-DD HH:mm:ss');
					var default_end = moment(format_start).startOf('hour').add(2, 'h').format('YYYY-MM-DD HH:mm:ss');
				}
				var start_day = moment(format_start).format('YYYY-MM-DD');
				$("#datepicker-start").datetimepicker({
					format: "DD/MM/YYYY HH:mm:00",
					defaultDate: default_start,
					locale: "fr",
					sideBySide: true,
					stepping: 15
				}).on('dp.change', function(e){
					var delta = e.date.diff(e.oldDate, 'minutes');
					var end_value = moment($("#datepicker-end").val(), "DD/MM/YYYY HH:mm:ss");
					var new_end_value = end_value.add(delta, 'minutes');
					$("#datepicker-end").val(new_end_value.format("DD/MM/YYYY HH:mm:ss"));
				})
				$("#datepicker-end").datetimepicker({
					format: "DD/MM/YYYY HH:mm:00",
					defaultDate: default_end,
					locale: "fr",
					sideBySide: true,
					stepping: 15
				});

				sessionStorage.removeItem('end');
				sessionStorage.removeItem('start');
			}).on('click', '.btn-add', function(){
				var table = "events", values = $("#event-add-form").serialize();
				$.when(addEntry(table, values)).done(function(data){
					window.location.href = "event/"+data;
				})
			})
		</script>
	</body>
</html>
