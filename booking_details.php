<?php
session_start();
if(!isset($_SESSION["username"])){
	header('location: portal');
}
require_once 'functions/db_connect.php';
$db = PDOFactory::getConnection();
require_once 'functions/reservations.php';


$booking_id = $_GET['id'];
$booking_query = $db->prepare("SELECT *, CONCAT(u.user_prenom, ' ', u.user_nom) AS holder, CONCAT(u2.user_prenom, ' ', u2.user_nom) AS handler
							FROM reservations b
							JOIN users u ON b.booking_holder = u.user_id
							LEFT JOIN users u2 ON b.booking_handler = u2.user_id
							WHERE booking_id = ?");
$booking_query->bindParam(1, $booking_id);
$booking_query->execute();
$booking_details = $booking_query->fetch(PDO::FETCH_ASSOC);

if($booking_details["last_edit_date"] == NULL)
	$last_edit_date = "";
else
	$last_edit_date = date_create($booking_details['last_edit_date'])->format('d/m/Y H:i:s');

$rooms_query = $db->query("SELECT * FROM rooms");


$labels = $db->query("SELECT * FROM assoc_session_tags us
						JOIN tags_session ts ON us.tag_id_foreign = ts.rank_id
						WHERE session_id_foreign = '$booking_id'
						ORDER BY tag_color DESC");

$user_labels = $db->query("SELECT * FROM tags_user");
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Résevation par <?php echo $booking_details['user_prenom']." ".$booking_details['user_nom'];?> le <?php echo date_create($booking_details['booking_start'])->format('d/m/Y');?> de <?php echo date_create($booking_details['booking_start'])->format('H:i')?> à <?php echo date_create($booking_details['booking_end'])->format('H:i');?> | Salsabor</title>
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
						<span class="glyphicon glyphicon-bookmark"></span> Réservation
						<div class="btn-toolbar float-right">
							<button class="btn btn-success btn-edit" id="submit-button"><span class="glyphicon glyphicon-ok"></span> Enregistrer les modifications</button>
							<button class="btn btn-danger btn-delete"><span class="glyphicon glyphicon-trash"></span> Supprimer</button>
							<input type="hidden" name="id" value="<?php echo $booking_id;?>">
						</div> <!-- btn-toolbar -->
					</legend>
					<p class="sub-legend">Détails</p>
					<form name="booking_details" id="booking_details" role="form" class="form-horizontal">
						<div class="form-group">
							<label for="booking_name" class="col-lg-3 control-label">Réservation pour</label>
							<div class="col-lg-9">
								<input type="text" class="form-control" name="booking_holder" value="<?php echo $booking_details['holder']?>">
							</div>
						</div>
						<div class="form-group">
							<label for="booking_start" class="col-lg-3 control-label">Début</label>
							<div class="col-lg-9">
								<input type="text" class="form-control" name="booking_start" id="datepicker-start">
							</div>
						</div>
						<div class="form-group">
							<label for="booking_end" class="col-lg-3 control-label">Fin</label>
							<div class="col-lg-9">
								<input type="text" class="form-control" name="booking_end" id="datepicker-end">
							</div>
						</div>
						<div class="form-group">
							<label for="" class="col-lg-3 control-label">Salle</label>
							<div class="col-lg-9">
								<select name="booking_room" class="form-control">
									<?php while($rooms_details = $rooms_query->fetch(PDO::FETCH_ASSOC)){
	if($booking_details["booking_room"] == $rooms_details["room_id"]) {?>
									<option selected="selected" value="<?php echo $rooms_details["room_id"];?>"><?php echo $rooms_details["room_name"];?></option>
									<?php } else { ?>
									<option value="<?php echo $rooms_details["room_id"];?>"><?php echo $rooms_details["room_name"];?></option>
									<?php }
} ?>
								</select>
							</div>
						</div>
						<div class="form-group">
							<label for="booking_handler" class="col-lg-3 control-label">Responsable de la réservation</label>
							<div class="col-lg-9">
								<input type="text" class="form-control" name="booking_handler" value="<?php echo $booking_details["handler"];?>" placeholder="Staff qui a réalisé cette réservation">
							</div>
						</div>
						<!--<div class="form-group">
<label for="priorite" class="cbx-label">Réservation payée</label>
<input name="priorite" id="priorite" data-toggle="checkbox-x" data-size="lg" data-three-state="false" value="<?php echo $booking_details['priorite']?>">
<label for="priorite">Une réservation payée ne peut plus être supprimée au profit d'un cours.</label>
</div>
<div class="form-group" id="prix_reservation">
<label for="prix_resa" class="control-label">Prix de la réservation : </label>
<div class="input-group">
<span class="input-group-addon" id="currency-addon">€</span>
<input type="number" step="any" name="prix_resa" id="prix_calcul" class="form-control" value="<?php echo $booking_details['booking_price'];?>" aria-describedby="currency-addon">
</div>
<input type="checkbox" <?php if($booking_details['booking_paid'] == '0') echo "unchecked"; else echo "checked";?> data-toggle="toggle" data-on="Payée" data-off="Due" data-onstyle="success" data-offstyle="danger" style="float:left;" id="paiement">
<input type="hidden" name="paiement" id="paiement-sub" value="<?php echo $booking_details['booking_paid'];?>">
</div>-->
						<div class="form-group">
							<label for="" class="col-lg-3 control-label">Dernière modification</label>
							<div class="col-lg-9">
								<p type="text" class="form-control-static" name="last_edit_date" id="last-edit-date"><?php echo $last_edit_date;?></p>
							</div>
						</div>
						<div class="align-right">
							<p id="error_message"></p>
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
		<script>
			$(document).ready(function(){
				$("#datepicker-start").datetimepicker({
					format: "DD/MM/YYYY HH:mm:00",
					defaultDate: "<?php echo date_create($booking_details['booking_start'])->format("m/d/Y H:i");?>",
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
					defaultDate: "<?php echo date_create($booking_details['booking_end'])->format("m/d/Y H:i");?>",
					locale: "fr",
					sideBySide: true,
					stepping: 15
				});

				fetchTasks("BKN", <?php echo $booking_id;?>, 0, null, 0);
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
				emptyTask += "<input class='form-control' id='task-target-input' type='hidden' value='[BKN-<?php echo $booking_id;?>]'>";
				emptyTask += "<textarea class='form-control task-description-input'></textarea>";
				emptyTask += "<button class='btn btn-primary post-task' id='post-task-button'>Valider</button>";
				emptyTask += "</div>";
				emptyTask += "</div>";
				emptyTask += "</div>";
				emptyTask += "</div>";
				$(".tasks-container").append(emptyTask);
				// When validating a new task, we delete the new template one and reload the correct one. Easy!
			}).on('click', '.btn-edit', function(){
				var values = $("#booking_details").serialize(), table = "reservations", booking_id = <?php echo $booking_id;?>;
				updateEntry(table, values, booking_id).done(function(data){
					console.log(data);
					$("#last-edit-date").text(moment().format("DD/MM/YYYY HH:mm:ss"));
					showNotification("Modifications enregistrées", "success");
				});
			}).on('click', '.btn-delete', function(){
				var booking_id = <?php echo $booking_id;?>;
				$.when(deleteEntry("reservations", booking_id), deleteTasksByTarget("BKN", booking_id)).done(function(){
					window.top.location = "planning";
				})
			})
			if($('#priorite').attr('value') == 0){
				$('#prix_reservation').hide();
			}
			$('#priorite').change(function(){
				$('#prix_reservation').toggle('600');
			})
			$('#paiement').change(function(){
				var state = $('#paiement').prop('checked');
				if(state){
					$('#paiement-sub').val(1);
				} else {
					$('#paiement-sub').val(0);
				}
			});
		</script>
	</body>
</html>
