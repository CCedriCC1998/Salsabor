<?php
session_start();
if(!isset($_SESSION["username"])){
	header('location: portal');
}
require_once 'functions/db_connect.php';
$db = PDOFactory::getConnection();
require_once "functions/cours.php";

$event_id = $_GET["id"];
$event = $db->query("SELECT * FROM events e
					LEFT JOIN users u ON e.event_handler = u.user_id
					WHERE event_id = '$event_id'")->fetch(PDO::FETCH_ASSOC);

$user_labels = $db->query("SELECT * FROM tags_user");
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title><?php echo $event["event_name"];?> | Salsabor</title>
		<base href="../">
		<?php include "styles.php";?>
		<?php include "scripts.php";?>
		<script src="assets/js/tasks-js.php"></script>
		<script src="assets/js/tags.js"></script>
		<script src="assets/js/sessions.js"></script>
	</head>
	<body>
		<?php include "nav.php";?>
		<div class="container-fluid">
			<div class="row">
				<?php include "side-menu.php";?>
				<div class="col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
					<legend>
						<span class="glyphicon glyphicon-calendar"></span> <?php echo $event["event_name"];?>
						<div class="btn-toolbar float-right">
							<button class="btn btn-success btn-edit" id="submit-button"><span class="glyphicon glyphicon-ok"></span> Enregistrer les modifications</button>
							<button class="btn btn-danger btn-delete"><span class="glyphicon glyphicon-trash"></span> Supprimer</button>
						</div>
					</legend>
					<p class="sub-legend">Détails</p>
					<form name="event_details" id="event_details" role="form" class="form-horizontal">
						<div class="form-group">
							<label for="event_name" class="col-lg-3 control-label">Titre de l'événement</label>
							<div class="col-lg-9">
								<input type="text" class="form-control" name="event_name" placeholder="Titre de l'événement" value="<?php echo $event["event_name"];?>">
							</div>
						</div>
						<div class="form-group">
							<label for="" class="col-lg-3 control-label">Gérant principal <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" title="Vous pouvez régler les noms qui vous seront suggérés avec le sélecteur 'Suggérer parmi...'"></span></label>
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
									<input type="text" class="form-control filtered-complete" id="complete-teacher" name="event_handler" value="<?php echo $event["user_prenom"]." ".$event["user_nom"];?>">
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
								<input type="text" class="form-control" name="event_address" value="<?php echo $event["event_address"];?>">
							</div>
						</div>
						<div class="form-group">
							<label for="" class="col-lg-3 control-label">Description</label>
							<div class="col-lg-9">
								<textarea name="event_description" class="form-control" id="" cols="30" rows="10"><?php echo $event["event_description"];?></textarea>
							</div>
						</div>
					</form>
					<p class="sub-legend">Tâches à faire</p>
					<div class="tasks-container container-fluid"></div>
					<div class="sub-container container-fluid">
						<div class="panel-heading panel-add-record container-fluid">
							<div class="col-sm-1"><div class="notif-pp empty-pp"></div></div>
							<div class="col-sm-11 new-task-text">Ajouter une nouvelle tâche...</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php include "inserts/edit_modal.php";?>
		<style>
			.main{
				overflow: visible;
			}
		</style>
		<script>
			$(document).ready(function(){
				$("#datepicker-start").datetimepicker({
					format: "DD/MM/YYYY HH:mm:00",
					defaultDate: "<?php echo date_create($event['event_start'])->format("m/d/Y H:i");?>",
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
					defaultDate: "<?php echo date_create($event['event_end'])->format("m/d/Y H:i");?>",
					locale: "fr",
					sideBySide: true,
					stepping: 15
				});

				fetchTasks("EVT", <?php echo $event_id;?>, 0, null, 0);
			}).on('click', '.panel-add-record', function(){
				var emptyTask = "<div class='panel task-line task-new panel-new-task'>";
				emptyTask += "<div class='panel-heading container-fluid'>";
				emptyTask += "<div class='col-lg-1'>";
				emptyTask += "<div class='notif-pp'>";
				emptyTask += "<image src='' alt=''>";
				emptyTask += "</div>";
				emptyTask += "</div>";
				emptyTask += "<div class='col-sm-11'>";
				emptyTask += "<div class='row'>";
				emptyTask += "<p class='task-title col-sm-10'>";
				emptyTask += "<input class='form-control task-title-input' type='text' placeholder='Titre de la tâche'>";
				emptyTask += "</p>"
				emptyTask += "<div class='container-fluid'>";
				emptyTask += "<input class='form-control' id='task-target-input' type='hidden' value='[EVT-<?php echo $event_id;?>]'>";
				emptyTask += "<textarea class='form-control task-description-input'></textarea>";
				emptyTask += "<button class='btn btn-primary post-task' id='post-task-button'>Valider</button>";
				emptyTask += "</div>";
				emptyTask += "</div>";
				emptyTask += "</div>";
				emptyTask += "</div>";
				$(".tasks-container").append(emptyTask);
				// When validating a new task, we delete the new template one and reload the correct one. Easy!
			}).on('click', '.btn-edit', function(){
				var values = $("#event_details").serialize(), table = "events", event_id = <?php echo $event_id;?>;
				//console.log(values);
				$.when(updateEntry(table, values, event_id)).done(function(){
					showNotification("Modifications enregistrées", "success");
				});
			}).on('click', '.btn-delete', function(){
				var event_id = <?php echo $event_id;?>;
				$.when(deleteEntry("events", event_id), deleteTasksByTarget("EVT", event_id)).done(function(){
					window.top.location = "planning";
				});
			})
		</script>
	</body>
</html>
