<?php
session_start();
if(!isset($_SESSION["username"])){
	header('location: portal');
}
require_once 'functions/db_connect.php';
$db = PDOFactory::getConnection();

$user_labels = $db->query("SELECT * FROM tags_user");
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Ajouter une prestation | Salsabor</title>
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
					<legend><span class="glyphicon glyphicon-cd"></span> Ajouter une prestation
						<button class="btn btn-primary btn-add">Ajouter</button>
					</legend>
					<form name="prestation_users" id="prestation_users" role="form" class="form-horizontal">
						<div class="form-group">
							<div class="col-lg-9 col-lg-offset-3">
								<button class="btn btn-primary add-participant btn-block" type="button">Ajouter un participant</button>
							</div>
						</div>
					</form>
					<form method="post" role="form" class="form-horizontal" id="prestation-add-form">
						<div class="form-group">
							<label for="prestation_start" class="col-lg-3 control-label">Début</label>
							<div class="col-lg-9">
								<input type="text" class="form-control" name="prestation_start" id="datepicker-start">
							</div>
						</div>
						<div class="form-group">
							<label for="prestation_end" class="col-lg-3 control-label">Fin</label>
							<div class="col-lg-9">
								<input type="text" class="form-control" name="prestation_end" id="datepicker-end">
							</div>
						</div>
						<div class="form-group">
							<label for="prestation_address" class="col-lg-3 control-label">Adresse</label>
							<div class="col-lg-9">
								<textarea name="prestation_address" id="" cols="30" rows="10" class="form-control"></textarea>
							</div>
						</div>
						<div class="form-group">
							<label for="prestation_description" class="col-lg-3 control-label">Détails supplémentaires</label>
							<div class="col-lg-9">
								<textarea name="prestation_description" id="" cols="30" rows="10" class="form-control"></textarea>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		</div>
	<style>
		.main{
			overflow: visible;
		}
		.prestation-users{
			padding-left: 0;
		}

		.add-participant{
			margin-bottom: 10px;
		}
	</style>
	<script>
		$(document).ready(function(){
			$("#datepicker-start").datetimepicker({
				format: "DD/MM/YYYY HH:mm:00",
				defaultDate: moment(),
				locale: "fr",
				sideBySide: true,
				stepping: 15
			})
			$("#datepicker-end").datetimepicker({
				format: "DD/MM/YYYY HH:mm:00",
				defaultDate: moment(),
				locale: "fr",
				sideBySide: true,
				stepping: 15
			});

		}).on('click', '.btn-add', function(){
			var prestataires = $("#prestation_users").serialize();
			var table = "prestations", values = $("#prestation-add-form").serialize();
			console.log(values);
			$.when(addEntry(table, values)).done(function(data){
				var prestation_id = data;
				$.post("functions/update_prestataires.php", {prestation_id : prestation_id, prestataires : prestataires}).done(function(data){
					console.log(data);
					showNotification("Prestation ajoutée", "success");
					window.location.href = "prestation/"+prestation_id;
				});
			})
		}).on('click', '.add-participant', function(){
			var rank = $(".participant").last().data('rank');
			if(rank) rank++;
			else rank = 1;
			var render = "<div class='form-group participant' id='participant-"+rank+"' data-rank='"+rank+"'>";
			render += "<label for='presentation_handler' class='col-lg-3 control-label'>Participant</label>";
			render += "<div class='col-lg-9 prestation-users'>";
			render += "<div class='col-lg-4'>";
			render += "<input type='text' class='form-control name-input complete-teacher' data-filter='Professeur' name='user_id_"+rank+"' data-rank='"+rank+"'>";
			render += "</div>";
			render += "<div class='col-lg-3'>";
			render += "<select name='invoice_id_"+rank+"' id='invoice-select-"+rank+"' class='form-control'></select>";
			render += "</div>";
			render += "<div class='col-lg-3'>";
			render += "<input type='number' class='form-control' name='price_"+rank+"'>";
			render += "</div>";
			render += "<button class='btn btn-danger delete-participant col-lg-2' type='button' id='delete_"+rank+"' data-target='"+rank+"'>Supprimer</button>";
			render += "</div>";
			render += "</div>";
			$(".add-participant").parent().parent().before(render);
		}).on('keyup change', '.complete-teacher', function(){
			var rank = $(this).data('rank');
			var to_match = $(this).val();
			$("#invoice-select-"+rank+" option").remove();
			fillInvoiceSelect($("#invoice-select-"+rank), to_match, null);
		})
	</script>
	</body>
</html>
