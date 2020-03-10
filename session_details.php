<?php
session_start();
if(!isset($_SESSION["username"])){
	header('location: portal');
}
require_once 'functions/db_connect.php';
$db = PDOFactory::getConnection();
require_once 'functions/cours.php';
/** Récupération des valeurs dans la base de données des champs **/
$id = $_GET['id'];
$cours = $db->query("SELECT * FROM sessions s
							JOIN rooms r ON s.session_room = r.room_id
							JOIN locations l ON r.room_location = l.location_id
							LEFT JOIN users u ON s.session_teacher = u.user_id
							WHERE session_id='$id'")->fetch(PDO::FETCH_ASSOC);
// Array of all the sessions from this parent.
$all = $db->query("SELECT session_id FROM sessions WHERE session_group = $cours[session_group]")->fetchAll(PDO::FETCH_COLUMN);
$count = sizeof($all);
$current = array_search($id, $all);
$all_js = json_encode($all);
$next_js = json_encode(array_slice($all, $current));
// Link to previous and next
if($all[$current] != reset($all)){
	$prev = $all[$current - 1];
}
if($all[$current] != end($all)){
	$next = $all[$current + 1];
}

$on_going = false;
$now = new DateTime();
$session_start = new DateTime($cours["session_start"]);
$session_end = new DateTime($cours["session_end"]);
if($session_start <= $now && $session_end > $now)
	$on_going = true;

$querySalles = $db->query("SELECT room_id, room_name, location_name FROM rooms r
							JOIN locations l ON r.room_location = l.location_id
							WHERE room_location = $_SESSION[location] OR room_location= $cours[location_id]");

$labels = $db->query("SELECT * FROM assoc_session_tags us
						JOIN tags_session ts ON us.tag_id_foreign = ts.rank_id
						WHERE session_id_foreign = '$id'
						ORDER BY tag_color DESC");

$user_labels = $db->query("SELECT * FROM tags_user");

if($cours['user_id']){
    $rates = $db->query("SELECT * FROM teacher_rates WHERE user_id_foreign = $cours[user_id]");
    $invoices = $db->query("SELECT * FROM invoices WHERE invoice_seller_id = $cours[user_id]");
} else {
    $rates = [];
    $invoices = [];
}

?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Cours de <?php echo $cours['session_name'];?> (<?php echo date_create($cours['session_start'])->format('d/m/Y');?> : <?php echo date_create($cours['session_start'])->format('H:i')?> / <?php echo date_create($cours['session_end'])->format('H:i');?>) | Salsabor</title>
		<base href="../">
		<?php include "styles.php";?>
		<link rel="stylesheet" href="assets/css/jquery.shining.min.css">
		<?php include "scripts.php";?>
		<script src="assets/js/products.js"></script>
		<script src="assets/js/participations.js"></script>
		<script src="assets/js/tasks-js.php"></script>
		<script src="assets/js/tags.js"></script>
		<script src="assets/js/sessions.js"></script>
		<script src="assets/js/raphael-min.js"></script>
		<script src="assets/js/morris.min.js"></script>
		<script src="assets/js/jquery.shining.min.js"></script>
	</head>
	<body>
		<?php include "nav.php";?>
		<div class="container-fluid">
			<div class="row">
				<?php include "side-menu.php";?>
				<div class="col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
					<legend>
						<span class="glyphicon glyphicon-eye-open"></span> <span class="session-name"><?php echo $cours['session_name'];?></span>
						<?php if($on_going){ ?>
						<div class="label label-active label-ongoing" title="Le cours se déroule actuellement">
							<span class="label-active-text">En cours</span>
						</div>
						<?php } else if($cours["session_opened"] == 1){ ?>
						<div class="label label-active label-soon" title="Le cours est actuellement ouvert">
							<span class="label-active-text">Ouvert</span>
						</div>
						<?php }?>
						<div class="btn-toolbar float-right">
							<?php if($count == '1'){ ?>
							<input type='submit' name='edit-one' id='edit-one' role='button' class='btn btn-success btn-edit' value='Enregistrer les modifications'>
							<?php } else { ?>
							<a href='#save-options' class='btn btn-primary' role='button' data-toggle='collapse' aria-expanded='false' aria-controls='saveOptions'><span class="glyphicon glyphicon-ok"></span> Enregistrer</a>
							<?php } ?>
							<a href="#delete-options" role="button" class="btn btn-danger" data-toggle="collapse" aria-expanded="false" aria-controls="deleteOptions"><span class="glyphicon glyphicon-trash"></span> Supprimer</a>
							<input type="hidden" name="id" value="<?php echo $id;?>">
						</div>
					</legend>
					<?php if($count != "1"){ ?>
					<div class="collapse" id="save-options">
						<div class="well">
							<span>Enregistrer...</span>
							<button class="btn btn-primary btn-edit" id="edit-one">Ce cours</button>
							<button class="btn btn-primary btn-edit" id="edit-next">Tous les suivants</button>
							<button class="btn btn-primary btn-edit" id="edit-all">Toute la série</button>
						</div>
					</div>
					<?php } ?>
					<div class="collapse" id="delete-options">
						<div class="well">
							<span>Supprimer...</span>
							<button class="btn btn-danger btn-delete" id="delete-one">Ce cours</button>
							<button class="btn btn-danger btn-delete" id="delete-next">Tous les suivants</button>
							<button class="btn btn-danger btn-delete" id="delete-all">Toute la série</button>
						</div>
					</div>
					<div class="container-fluid session-nav">
						<div class="col-xs-4 col-sm-3">
							<?php if(isset($prev)){ ?>
							<a href="cours/<?php echo $prev;?>" class="sub-legend prev-session"><span class="glyphicon glyphicon-arrow-left"></span> Cours précédent</a>
							<?php } else { ?>
							<p class="sub-legend prev-session disabled"><span class="glyphicon glyphicon-arrow-left"></span> - </p>
							<?php } ?>
						</div>
						<div class="col-xs-4 col-sm-6">
							<p id="last-edit"><?php if($cours['last_edit_date'] != '0000-00-00 00:00:00') echo "Dernière modification le ".date_create($cours['last_edit_date'])->format('d/m/Y')." à ".date_create($cours['last_edit_date'])->format('H:i');?></p>
						</div>
						<div class="col-xs-4 col-sm-3">
							<?php if(isset($next)){ ?>
							<a href="cours/<?php echo $next;?>" class="sub-legend next-session float-right">Cours suivant <span class="glyphicon glyphicon-arrow-right"></span></a>
							<?php } else { ?>
							<p class="sub-legend next-session float-right disabled"> - <span class="glyphicon glyphicon-arrow-right"></span></p>
							<?php } ?>
						</div>
					</div>
					<ul class="nav nav-tabs" role="tablist">
						<li role="presentation" class="active"><a href="#details" role="tab" data-toggle="tab">Détails</a></li>
						<li role="presentation"><a href="#stats" role="tab" data-toggle="tab">Rentabilité</a></li>
						<li role="presentation"><a href="#tasks" role="tab" data-toggle="tab">Tâches</a></li>
					</ul>
					<div class="tab-content">
						<div class="tab-pane active" id="details">
							<p class="sub-legend">Détails</p>
							<form name="session_details" id="session_details" role="form" class="form-horizontal">
								<div class="form-group">
									<label for="" class="col-lg-3 control-label">Intitulé du cours</label>
									<div class="col-lg-9">
										<input type="text" class="form-control" name="session_name" id="session_name_input" value="<?php echo $cours['session_name'];?>">
									</div>
								</div>
								<div class="form-group">
									<label for="" class="col-lg-3 control-label">Professeur <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" title="Vous pouvez régler les noms qui vous seront suggérés avec le sélecteur 'Suggérer parmi...'"></span></label>
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
											<input type="text" class="form-control filtered-complete" id="complete-teacher" name="session_teacher" value="<?php echo $cours['user_prenom']." ".$cours['user_nom'];?>">
										</div>
                                        <?php if($cours['user_id'] == null){?> <small class="help-block">Aucun professeur n'a été trouvé pendant la saisie. Vous pouvez le renseigner pour retrouver ses tarifs et factures.</small><?php } ?>
									</div>
								</div>
                                <?php if($cours['user_id'] != null){ ?>
								<div class="form-group">
									<label for="teacher_rate" class="col-lg-3 control-label">Tarif</label>
									<div class="col-lg-9">
										<select name="teacher_rate" class="form-control" id="teacher-rate">
											<?php while($rate = $rates->fetch(PDO::FETCH_ASSOC)){ ?>
											<option <?php if($rate["rate_id"] == $cours["teacher_rate"]) echo "selected='selected'";?> value="<?php echo $rate["rate_id"];?>"><?php echo $rate["rate_title"]." (".$rate["rate_value"]."€/".$rate["rate_ratio"].")";?></option>
											<?php } ?>
										</select>
									</div>
								</div>
								<div class="form-group">
									<label for="invoice_id" class="col-lg-3 control-label">Facture professeur</label>
									<div class="col-lg-9">
										<select name="invoice_id" id="invoice-select" class="form-control">
										<option>Choisissez une facture</option>
										<?php while($invoice = $invoices->fetch()){?>
										<option <?php if($invoice["invoice_id"] == $cours["invoice_id"]) echo "selected='selected'";?> value="<?php echo $invoice["invoice_id"];?>"><?php echo $invoice["invoice_token"];?></option>
										<?php } ?>
										</select>
									</div>
								</div>
                                <?php } ?>
								<div class="form-group">
									<label for="session_start" class="col-lg-3 control-label">Début</label>
									<div class="col-lg-9">
										<input type="text" class="form-control" name="session_start" id="datepicker-start">
									</div>
								</div>
								<div class="form-group">
									<label for="session_end" class="col-lg-3 control-label">Fin</label>
									<div class="col-lg-9">
										<input type="text" class="form-control" name="session_end" id="datepicker-end">
									</div>
								</div>
								<div class="form-group">
									<label for="" class="col-lg-3 control-label">Etiquettes</label>
									<div class="col-lg-9">
										<h4 class="tags_container">
											<?php while($label = $labels->fetch(PDO::FETCH_ASSOC)){
	if($label["is_mandatory"] == 1){
		$label_name = "<span class='glyphicon glyphicon-star'></span> ".$label["rank_name"];
	} else {
		$label_name = $label["rank_name"];
	}?>
											<span class="label label-salsabor label-clickable label-deletable" title="Supprimer l'étiquette" id="session-tag-<?php echo $label["entry_id"];?>" data-target="<?php echo $label["entry_id"];?>" data-targettype='session' style="background-color:<?php echo $label["tag_color"];?>"><?php echo $label_name;?></span>
											<?php } ?>
											<span class="label label-default label-clickable label-add trigger-sub" id="label-add" data-subtype='session-tags' data-targettype='session' title="Ajouter une étiquette">+</span>
										</h4>
									</div>
								</div>
								<div class="form-group">
									<label for="" class="col-lg-3 control-label">Salle</label>
									<div class="col-lg-9">
										<select name="session_room" class="form-control">
											<?php while($salles = $querySalles->fetch(PDO::FETCH_ASSOC)){
	if($cours["session_room"] == $salles["room_id"]) {?>
											<option selected="selected" value="<?php echo $salles["room_id"];?>"><?php echo $salles["room_name"];?></option>
											<?php } else { ?>
											<option value="<?php echo $salles["room_id"];?>"><?php echo $salles["room_name"];?></option>
											<?php }
} ?>
										</select>
									</div>
								</div>
							</form>
							<p class="sub-legend top-divider">Série</p>
							<form name="session_group" id="session_group" role="form" class="form-horizontal">
								<div class="form-group">
									<label for="" class="col-lg-3 control-label">Identifiant <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" title="Série auquel appartient le cours."></span></label>
									<div class="col-lg-9">
										<p type="text" class="form-control-static" name="cours_parent" id="group-input"><?php echo $cours["session_group"];?></p>
									</div>
								</div>
								<span class="col-lg-offset-2 col-lg-10 help-block">Modifiez les champs ci-dessous pour ajouter ou retirer des cours. Si vous prolongez la récurrence (en augmentant le nombre ou la date) de nouveaux cours seront créés. Inversement, si vous réduisez la récurrence, des cours existants seront supprimés. Pensez à vérifier les jours chômés avant de valider vos modifications.</span>
								<div class="form-group">
									<label for="" class="col-lg-3 control-label">Nombre de cours</label>
									<div class="col-lg-9">
										<input type="number" class="form-control" id="steps" name="steps" value="<?php echo $count;?>">
									</div>
								</div>
								<div class="form-group">
									<label for="recurrence_end" class="col-lg-3 control-label">Fin de récurrence</label>
									<div class="col-lg-9">
										<input type="text" class="form-control" name="recurrence_end" id="recurrence_end">
									</div>
								</div>
							</form>
							<div class="container-fluid">
								<button class="btn btn-primary col-xs-12 col-sm-offset-6 col-sm-6" id="group-edit">Valider les modifications d'appartenance</button>
								<!--<button class="btn btn-danger col-xs-6" id="group-split">Dissocier du groupe</button>-->
							</div>
							<p class="sub-legend top-divider">Participations de ce cours</p>
							<div class="panel panel-session" id="session-<?php echo $id;?>">
								<a class="panel-heading-container" id='ph-session-<?php echo $id;?>' data-session='<?php echo $id;?>' data-trigger='<?php echo $id;?>'>
									<div class="panel-heading">
										<div class="container-fluid">
											<p class="col-xs-5 col-md-3">Liste des participants</p>
											<p class="col-xs-2 col-lg-1"><span class="glyphicon glyphicon-user"></span> <span class="user-total-count" id="user-total-count-<?php echo $id;?>"></span></p>
											<p class="col-xs-2 col-lg-1"><span class="glyphicon glyphicon-ok"></span> <span class="user-ok-count" id="user-ok-count-<?php echo $id;?>"></span></p>
											<p class="col-xs-2 col-lg-1"><span class="glyphicon glyphicon-warning-sign"></span> <span class="user-warning-count" id="user-warning-count-<?php echo $id;?>"></span></p>
											<span class="glyphicon glyphicon-ok-sign col-xs-1 col-md-1 col-md-offset-5 glyphicon-button-alt glyphicon-button-big validate-session" id="validate-session-<?php echo $id;?>" data-session="<?php echo $id;?>" title="Valider tous les passages"></span>
										</div>
									</div>
								</a>
								<div class="panel-body collapse" id="body-session-<?php echo $id;?>" data-session="<?php echo $id;?>"></div>
							</div>
						</div>
						<div class="tab-pane" id="stats">
							<p class="sub-legend top-divider">Participations de la série</p>
							<span class="help-block">Nombre de participants à chaque cours (Série : <?php echo $cours["session_group"];?>)</span>
							<div class="chart" id="session-chart" style="height:250px"></div>
						</div>
						<div class="tab-pane" id="tasks">
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
			</div>
		</div>
		<?php include "inserts/sub_modal_product.php";?>
		<?php include "inserts/edit_modal.php";?>
		<?php include "inserts/delete_modal.php";?>
		<?php include "inserts/add_participation_modal.php";?>
		<style>
			#session-chart svg{
				width: 100%;
			}
		</style>
		<script>
			$("a[href='#stats']").on('shown.bs.tab', function(e){
				stats_group.redraw();
			})
			$(document).ready(function(){
				setInterval(function () {
					$('.label-active-text').shineText({
						speed: 30
					});
				}, 4000);
				$("#datepicker-start").datetimepicker({
					format: "DD/MM/YYYY HH:mm:00",
					defaultDate: "<?php echo date_create($cours['session_start'])->format("m/d/Y H:i");?>",
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
					defaultDate: "<?php echo date_create($cours['session_end'])->format("m/d/Y H:i");?>",
					locale: "fr",
					sideBySide: true,
					stepping: 15
				});
				window.openedSessions = [<?php echo $id;?>];
				initial_tags = createTagsArray();
				refreshTick();

				fetchTasks("SES", <?php echo $id;?>, 0, null, 0);

				var session_group_id = <?php echo $cours["session_group"];?>;
				window.initial_steps = $("#steps").val();

				window.initial_form = $("#session_details").serialize();
				$.get("functions/fetch_session_group.php", {group_id : session_group_id}).done(function(data){
					var group_details = JSON.parse(data);
					$("#recurrence_end").datetimepicker({
						format : "DD/MM/YYYY",
						locale: 'fr',
						defaultDate: group_details.parent_end_date
					}).on('dp.change', function(e){
						if(!$("#steps").is(":focus")){
							var end_date = moment($(this).val(), "DD/MM/YYYY");
							var starting_date = moment(group_details.parent_start_date);
							$.get("functions/fetch_available_timeslots.php", {compute : "steps", current_recurrence_end : group_details.parent_end_date, new_recurrence_end : moment(end_date).format("YYYY-MM-DD")}).done(function(computed_delta_steps){
								window.delta_steps = parseInt(computed_delta_steps);
								new_steps = parseInt(initial_steps) + window.delta_steps;
								$("#steps").val(new_steps);
								changeGroupButtonMessage(window.delta_steps);
							})
						}
					})
					$("#steps").keyup(function(){
						var steps = $(this).val();
						window.delta_steps = steps - initial_steps;
						$.get("functions/fetch_available_timeslots.php", {compute: "date", current_recurrence_end : group_details.parent_end_date, delta_steps : window.delta_steps}).done(function(computed_end_date){
							$("#recurrence_end").val(moment(computed_end_date, "YYYY-MM-DD HH:mm:ss").format("DD/MM/YYYY"));
							changeGroupButtonMessage(delta_steps);
						});
					})
				})

				var stats_data = $.getJSON("functions/fetch_all_sessions_participations.php", {session_group_id : session_group_id}, function(data){
					var line_options = {
						// ID of the element in which to draw the chart.
						element: 'session-chart',
						// Chart data records -- each entry in this array corresponds to a point on
						// the chart.
						data: data,
						// The name of the data record attribute that contains x-values.
						xkey: 'date',
						// A list of names of data record attributes that contain y-values.
						ykeys: ['participations'],
						// Labels for the ykeys -- will be displayed when you hover over the
						// chart.
						labels: ['Participants'],
						lineColors: ['#A80139']
					};
					stats_group = Morris.Line(line_options);
				});


				// Redirect to week of the session if going back to planning
				$("a[href=planning]").attr("href", "planning?default-date=<?php echo date_create($cours['session_start'])->format("Y-m-d");?>");
			}).on('click', '.btn-edit', function(){
				var id = $(this).attr("id");
				var form = $("#session_details"), entry_id = <?php echo $id;?>;
				switch(id){
					case "edit-one":
						var sessions = [entry_id];
						break;

					case "edit-next":
						var sessions = <?php echo $next_js;?>;
						break;

					case "edit-all":
						var sessions = <?php echo $all_js;?>;
						break;
				}
				var definitive_tags = createTagsArray();
                console.log(initial_tags, definitive_tags);
				$.post("functions/update_session.php", {sessions : sessions, values : form.serialize(), hook : entry_id}).done(function(data){
					// Attach & detach tags to other sessions
					for(var i = 0; i < sessions.length; i++){
						var copy_initial_tags = initial_tags;
						var copy_def_tags = definitive_tags;
						/* For each session, we have the tags when the page loaded in initial_tags. We'll now do something for each tag that exists NOW (from definitive_tags). 2 actions can be taken for the differences between the two arrays :
								-> The tag is not in the initial array but in the definitive one : it has to be attached to the sessions.
								-> The tag was in the initial array but not in the definitive one : it has to be detached from the sessions.
							*/
						/*console.log(copy_initial_tags);
							console.log(copy_def_tags);*/
						updateTargetTags(initial_tags, definitive_tags, sessions[i], "session");
					}
					// We replace the original tags by the new ones after modifying.
					initial_tags = definitive_tags;
					// Close the well
					$(".in").collapse('hide');
					// Update the name of the session in the legend
					$(".session-name").text($("#session_name_input").val());
					// Update the last edition date
					$("#last-edit").text("Dernière modification le "+moment().format("DD/MM/YYYY [à] H:mm"));
					window.top.location = "planning?default-date="+moment($("#datepicker-start").val(), "DD/MM/YYYY HH:mm:ss").format("YYYY-MM-DD");
					window.initial_form = $("#session_details").serialize();
				})
			}).on('click', '.btn-delete', function(){
				var id = $(this).attr("id"), entry_id = <?php echo $id;?>;
				var session_group_id = <?php echo $cours['session_group']?>;
				switch(id){
					case "delete-one":
						var sessions = [entry_id];
						break;

					case "delete-next":
						var sessions = <?php echo $next_js;?>;
						break;

					case "delete-all":
						var sessions = <?php echo $all_js;?>;
						break;
				}
				for(var i = 0; i < sessions.length; i++){
					if(i < sessions.length - 1){
						deleteEntry("sessions", sessions[i]);
						deleteTasksByTarget("SES", sessions[i]);
					} else {
						console.log("checking parent");
						$.when(deleteEntry("sessions", sessions[i]), deleteTasksByTarget("SES", sessions[i])).done(function(data){
							$.get("functions/check_session_parent.php", {session_group_id : session_group_id}).done(function(){
								window.top.location = "planning?default-date="+moment($("#datepicker-start").val(), "DD/MM/YYYY HH:mm:ss").format("YYYY-MM-DD");
							})
						})
					}
				}
			}).on('click', '#group-edit', function(){
				var group_id = $("#group-input").text();
				$("#group-edit").text("Modifications en cours, veuilez patienter...");
				$.post("functions/edit_group.php", {group_id : group_id, delta_steps : delta_steps}).done(function(data){
					console.log(data);
					$("#group-edit").text("Valider les modifications d'appartenance");
					if(delta_steps > 0){
						if($(".next-session").hasClass("disabled")){
							$(".next-session").replaceWith("<a href='cours/"+data+"' class='sub-legend next-session float-right'> Cours suivant <span class='glyphicon glyphicon-arrow-right'></span></a>");
						}
					}
					if(delta_steps < 0){
						var current_session_id = <?php echo $id;?>;
						if(data == current_session_id){
							$(".next-session").replaceWith("<p class='sub-legend next-session float-right disabled'> - <span class='glyphicon glyphicon-arrow-right'></span></p>");
						} else {
							if(moment($("#datepicker-end").val()).format("YYYY-MM-DD") > moment($("#recurrence_end").val()).format("YYYY-MM-DD")){
								console.log("cours/"+data);
								// If the displayed sesssion has been deleted too, we redirect the user to the last session of the group
								window.top.location = "cours/"+data;
							}
						}
					}
				});
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
				emptyTask += "<input class='form-control' id='task-target-input' type='hidden' value='[SES-<?php echo $id;?>]'>";
				emptyTask += "<textarea class='form-control task-description-input'></textarea>";
				emptyTask += "<button class='btn btn-primary post-task' id='post-task-button'>Valider</button>";
				emptyTask += "</div>";
				emptyTask += "</div>";
				emptyTask += "</div>";
				emptyTask += "</div>";
				$(".tasks-container").append(emptyTask);
				// When validating a new task, we delete the new template one and reload the correct one. Easy!
			})

			$(window).on('beforeunload', function(){
				var current_form = $("#session_details").serialize();
				console.log(window.initial_form, current_form);
				if(current_form !== window.initial_form)
					return "Vous avez des modifications non enregistrées, êtes-vous sûr de vouloir quitter la page ?";
			})

			$('#paiement').change(function(){
				var state = $('#paiement').prop('checked');
				if(state){
					$('#paiement-sub').val(1);
				} else {
					$('#paiement-sub').val(0);
				}
			});

			$("#complete-teacher").on('keyup change', function(){
				var to_match = $(this).val();
				$("#teacher-rate option").remove();
				$.get("functions/fetch_user_rates.php", {user_name : to_match}).done(function(data){
					if(data){
						var options = JSON.parse(data);
						console.log(options);
						for(var i = 0; i < options.length; i++){
							console.log(options[i]);
							$("#teacher-rate").append(
								$("<option></option>").text(options[i].text).val(options[i].value)
							);
						}
					}
				})
				fillInvoiceSelect($("#invoice-select"), to_match);
			})

			function changeGroupButtonMessage(delta_steps){
				if(delta_steps < -1)
					$("#group-edit").text("Valider les modifications d'appartenance ("+-delta_steps+" cours retirés)");
				if(delta_steps == -1)
					$("#group-edit").text("Valider les modifications d'appartenance ("+-delta_steps+" cours retiré)");
				if(delta_steps == 0)
					$("#group-edit").text("Valider les modifications d'appartenance");
				if(delta_steps == 1)
					$("#group-edit").text("Valider les modifications d'appartenance ("+delta_steps+" cours ajouté)");
				if(delta_steps > 1)
					$("#group-edit").text("Valider les modifications d'appartenance ("+delta_steps+" cours ajoutés)");
			}
		</script>
	</body>
</html>
