<?php
session_start();
if(!isset($_SESSION["username"])){
	header('location: portal');
}
require_once 'functions/db_connect.php';
$db = PDOFactory::getConnection();

$prestation_id = $_GET["id"];
$prestation = $db->query("SELECT * FROM prestations p WHERE prestation_id = $prestation_id")->fetch();


$prestataires = $db->query("SELECT pu.user_id, CONCAT(u.user_prenom, ' ', u.user_nom) AS identity, pu.invoice_id, i.invoice_token, price FROM prestation_users pu
							JOIN users u ON pu.user_id = u.user_id
							LEFT JOIN invoices i ON pu.invoice_id = i.invoice_id
							WHERE prestation_id = $prestation_id");

$i = 1;
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Prestation du <?php echo date_create($prestation['prestation_start'])->format('d/m/Y');?> de <?php echo date_create($prestation['prestation_start'])->format('H:i')?> à <?php echo date_create($prestation['prestation_end'])->format('H:i');?> | Salsabor</title>
		<base href="../">
		<?php include "styles.php";?>
		<?php include "scripts.php";?>
		<script src="assets/js/check_calendar.js"></script>
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
						<span class="glyphicon glyphicon-cd"></span> Prestation du <?php echo date_create($prestation['prestation_start'])->format('d/m/Y');?> de <?php echo date_create($prestation['prestation_start'])->format('H:i')?> à <?php echo date_create($prestation['prestation_end'])->format('H:i');?>
						<div class="btn-toolbar float-right">
							<button class="btn btn-success btn-edit" id="submit-button"><span class="glyphicon glyphicon-ok"></span> Enregistrer les modifications</button>
							<button class="btn btn-danger btn-delete"><span class="glyphicon glyphicon-trash"></span> Supprimer</button>
							<input type="hidden" name="id" value="<?php echo $prestation_id;?>">
						</div> <!-- btn-toolbar -->
					</legend>
					<p class="sub-legend">Détails</p>
					<form name="prestation_users" id="prestation_users" role="form" class="form-horizontal">
						<?php while($prestataire = $prestataires->fetch()){ ?>
						<div class="form-group participant" id="participant-<?php echo $i;?>" data-rank="<?php echo $i;?>">
							<label for="prestation_handler" class="col-lg-3 control-label">Participant</label>
							<div class="col-lg-9 prestation-users">
								<div class="col-lg-4">
									<input type="text" class="form-control name-input complete-teacher" data-filter="Professeur" name="user_id_<?php echo $i;?>" value="<?php echo $prestataire['identity']?>" data-rank="<?php echo $i;?>">
								</div>
								<div class="col-lg-3">
									<select name="invoice_id_<?php echo $i;?>" id="invoice-select-<?php echo $i;?>" class="form-control">
										<option value="0">Sélectionnez une facture</option>
										<?php $invoices = $db->query("SELECT invoice_id, invoice_token FROM invoices WHERE invoice_seller_id = $prestataire[user_id]");
																		   while($invoice = $invoices->fetch()){ ?>
										<option value="<?php echo $invoice["invoice_id"];?>" <?php if($invoice["invoice_id"] == $prestataire["invoice_id"]) echo "selected";?>><?php echo $invoice["invoice_token"];?></option>
										<?php } ?>
									</select>
								</div>
								<div class="col-lg-3">
									<input type="number" class="form-control" name="price_<?php echo $i;?>" value="<?php echo $prestataire['price']?>">
								</div>
								<button class="btn btn-danger delete-participant col-lg-2" type="button" id="delete_<?php echo $i;?>" data-target="<?php echo $i;?>">Supprimer</button>
							</div>
						</div>
						<?php $i++; } ?>
						<div class="form-group">
							<div class="col-lg-9 col-lg-offset-3">
								<button class="btn btn-primary add-participant btn-block" type="button">Ajouter un participant</button>
							</div>
						</div>
					</form>
					<form name="prestation_details" id="prestation_details" role="form" class="form-horizontal">
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
								<textarea name="prestation_address" id="" cols="30" rows="10" class="form-control"><?php echo $prestation["prestation_address"];?></textarea>
							</div>
						</div>
						<div class="form-group">
							<label for="prestation_description" class="col-lg-3 control-label">Détails supplémentaires</label>
							<div class="col-lg-9">
								<textarea name="prestation_description" id="" cols="30" rows="10" class="form-control"><?php echo $prestation["prestation_description"];?></textarea>
							</div>
						</div>
					</form>
					<p class="sub-legend top-divider">Tâches à faire</p>
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
					defaultDate: "<?php echo date_create($prestation['prestation_start'])->format("m/d/Y H:i");?>",
					locale: "fr",
					sideBySide: true,
					stepping: 15
				})
				$("#datepicker-end").datetimepicker({
					format: "DD/MM/YYYY HH:mm:00",
					defaultDate: "<?php echo date_create($prestation['prestation_end'])->format("m/d/Y H:i");?>",
					locale: "fr",
					sideBySide: true,
					stepping: 15
				});

				fetchTasks("PRS", <?php echo $prestation_id;?>, 0, null, 0);
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
				emptyTask += "<input class='form-control' id='task-target-input' type='hidden' value='[PRS-<?php echo $prestation_id;?>]'>";
				emptyTask += "<textarea class='form-control task-description-input'></textarea>";
				emptyTask += "<button class='btn btn-primary post-task' id='post-task-button'>Valider</button>";
				emptyTask += "</div>";
				emptyTask += "</div>";
				emptyTask += "</div>";
				emptyTask += "</div>";
				$(".tasks-container").append(emptyTask);
				// When validating a new task, we delete the new template one and reload the correct one. Easy!
			}).on('click', '.btn-edit', function(){
				var prestataires = $("#prestation_users").serialize();
				var values = $("#prestation_details").serialize(), table = "prestations", prestation_id = <?php echo $prestation_id;?>;
				updateEntry(table, values, prestation_id).done(function(data){
					console.log(data);
					$.post("functions/update_prestataires.php", {prestation_id : prestation_id, prestataires : prestataires}).done(function(data){
						console.log(data);
						showNotification("Modifications enregistrées", "success");
					});
				});
			}).on('click', '.btn-delete', function(){
				var booking_id = <?php echo $prestation_id;?>;
				$.when(deleteEntry("reservations", booking_id), deleteTasksByTarget("PRS", booking_id)).done(function(){
					window.top.location = "planning";
				})
			}).on('click', '.delete-participant', function(){
				$("#participant-"+$(this).data('target')).remove();
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
