<?php
session_start();
if(!isset($_SESSION["username"])){
	header('location: portal');
}
require_once 'functions/db_connect.php';
$db = PDOFactory::getConnection();
$user_id = $_GET['id'];

// User details
$details = $db->query("SELECT * FROM users u
						LEFT JOIN locations l ON u.user_location = l.location_id
						WHERE user_id='$user_id'")->fetch(PDO::FETCH_ASSOC);

$details["count"] = $db->query("SELECT * FROM tasks
					WHERE ((task_token LIKE '%USR%' AND task_target = '$user_id')
					OR (task_token LIKE '%PRD%' AND task_target IN (SELECT id_produit_adherent FROM produits_adherents WHERE id_user_foreign = '$user_id'))
					OR (task_token LIKE '%TRA%' AND task_target IN (SELECT id_transaction FROM transactions WHERE payeur_transaction = '$user_id')))
						AND task_state = 0")->rowCount();

$is_teacher = $db->query("SELECT * FROM assoc_user_tags ur
								JOIN tags_user tu ON tu.rank_id = ur.tag_id_foreign
								WHERE rank_name = 'Professeur' AND user_id_foreign = '$user_id'")->rowCount();

// Locations
$locations = $db->query("SELECT * FROM locations ORDER BY location_name ASC");

// Possible late maturities
$queryEcheances = $db->query("SELECT * FROM produits_echeances JOIN transactions ON reference_achat=transactions.id_transaction WHERE echeance_effectuee=2 AND payeur_transaction=$user_id")->rowCount();
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Editer - <?php echo $details["user_prenom"]." ".$details["user_nom"];?> | Salsabor</title>
		<base href="../../">
		<?php include "styles.php";?>
		<link href="assets/css/croppie.css" rel="stylesheet" type="text/css">
		<link rel="stylesheet" href="assets/css/fileinput.min.css">
		<?php include "scripts.php";?>
		<script src="assets/js/tags.js"></script>
		<script src="assets/js/croppie.min.js"></script>
		<script src="assets/js/fileinput.min.js"></script>
	</head>
	<body>
		<?php include "nav.php";?>
		<div class="container-fluid">
			<div class="row">
				<?php include "side-menu.php";?>
				<div class="col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
					<?php include "inserts/user_banner.php";?>
					<?php if($queryEcheances != 0){ ?>
					<div class="alert alert-danger"><strong>Attention !</strong> Cet adhérent a des échéances en retard.</div>
					<?php } ?>
					<ul class="nav nav-tabs">
						<li role="presentation" class="visible-xs-block"><a href="user/<?php echo $user_id;?>">Infos perso</a></li>
						<li role="presentation" class="hidden-xs"><a href="user/<?php echo $user_id;?>">Informations personnelles</a></li>
						<?php if($is_teacher == 1){ ?>
						<!--<li role="presentation"><a>Cours donnés</a></li>-->
						<li role="presentation"><a href="user/<?php echo $user_id;?>/tarifs">Tarifs</a></li>
						<li role="presentation" class="active"><a href="user/<?php echo $user_id;?>/facturation">Facturation</a></li>
						<!--<li role="presentation"><a>Statistiques</a></li>-->
						<?php } ?>
						<li role="presentation"><a href="user/<?php echo $user_id;?>/abonnements">Abonnements</a></li>
						<li role="presentation"><a href="user/<?php echo $user_id;?>/historique">Participations</a></li>
						<li role="presentation"><a href="user/<?php echo $user_id;?>/achats">Achats</a></li>
						<li role="presentation"><a href="user/<?php echo $user_id;?>/reservations">Réservations</a></li>
						<li role="presentation"><a href="user/<?php echo $user_id;?>/taches">Tâches</a></li>
					</ul>
					<div class="container-fluid">
						<p class="help-block">Filtrez les cours par l'une des deux façons ci-dessous. Lorsqu'une facture est sélectionnée, il est impossible de filtrer par dates.</p>
						<div class="invoice-filter col-xs-4">
							<div class="form-group filter-group">
								<label for="invoice_id" class="control-label">Choisissez une facture</label>
								<div class="input-group">
									<select name="invoice_id" id="invoice-select" class="form-control filter">
									</select>
									<span class="input-group-btn">
										<button class="btn btn-primary" data-toggle="modal" data-target="#invoice-create-modal">Créer une facture</button>
									</span>
								</div>
							</div>
						</div>
						<div class="dates-filter row col-xs-8">
							<div class="form-group filter-group col-xs-6">
								<label for="session_start" class="control-label">Date de début</label>
								<input type="text" name="session_start" class="form-control date-filter filter" id="datepicker-start">
							</div>
							<div class="form-group filter-group col-xs-6">
								<label for="session_end" class="control-label">Date de fin</label>
								<input type="text" name="session_end" class="form-control date-filter filter" id="datepicker-end">
							</div>
							<!--<div class="form-group filter-group col-xs-4">
<label for="" class="control-label">Intitulé</label>
<input type="text" class="form-control" id="name-filter" placeholder="Tapez pour filtrer les cours affichés">
</div>-->
						</div>
					</div>
					<div class="container-fluid list-container">
						<p class="sub-legend"><span id="filtered-sessions-number"></span> cours affichés <span id="sub-legend-helper"><span id="listing-price"></span> €</span>
						</p>
						<div class="invoice-actions">
							<p><span class="glyphicon glyphicon-file"></span> Facture <span id="invoice-name"></span> <span class="label label-primary label-reception">En attente</span> <span class="label label-primary label-payment">En attente</span></p>
							<p class="help-block">Ces opérations concernent la facture sélectionnée</p>
							<div class="row">
								<div class="col-sm-4">
									<input type="file" class="file-loading" id="upload-invoice" name="invoice_address">
								</div>
								<div class="col-sm-2">
									<a href="" target="_blank" class="btn btn-primary btn-block" id="show-invoice"><span class="glyphicon glyphicon-file"></span> Visualiser la facture</a>
								</div>
								<div class="col-sm-2">
									<a href="" target="_blank" class="btn btn-primary btn-block" id="create-invoice"><span class="glyphicon glyphicon-briefcase"></span> Facture prévisionnelle</a>
								</div>
								<div class="col-sm-2">
									<button class="btn btn-primary btn-block date-modal" id="receive-invoice" data-toggle="modal" data-target="#invoice-date-modal"><span class="glyphicon glyphicon-ok"></span> Accuser réception</button>
								</div>
								<div class="col-sm-2">
									<button class="btn btn-primary btn-block date-modal" id="pay-invoice" data-toggle="modal" data-target="#invoice-date-modal"><span class="glyphicon glyphicon-download"></span> Valider paiement</button>
								</div>
							</div>
						</div>
						<ul class="list loading-container">
						</ul>
					</div>
				</div>
			</div>
		</div>
		<div class="modal fade" id="invoice-create-modal" tabindex="-1" role="dialog">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						<h4 class="modal-title">Créer une facture</h4>
					</div>
					<div class="modal-body container-fluid">
						<form class="form-horizontal" id="modal-add-form">
							<div class="form-group">
								<label for="invoice_token" class="col-lg-4 control-label">Code de la facture</label>
								<div class="col-lg-8"><input type="text" class="form-control" id="invoice-token-input" placeholder="20 caractères maximum"></div>
							</div>
							<div class="form-group">
								<label for="invoice-period" class="col-lg-4 control-label">Période</label>
								<div class="col-lg-8">
									<input type="text" class="form-control" id="invoice-period">
								</div>
							</div>
							<p class="help-block">Les cours contenus dans la période choisie seront automatiquement associés.</p>
						</form>
					</div>
					<div class="modal-footer">
						<button class="btn btn-primary create-invoice">Créer la facture</button>
					</div>
				</div>
			</div>
		</div>
		<div class="modal fade" id="invoice-associate-modal" tabindex="-1" role="dialog">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						<h4 class="modal-title">Associer des cours à une facture</h4>
					</div>
					<div class="modal-body container-fluid">
						<div class="form-group">
							<label class="col-lg-4 control-label">Sélectionnez la facture pour l'association</label>
							<div class="col-lg-8">
								<select id="invoice-assoc-select" class="form-control"></select>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button class="btn btn-primary associate-sessions">Associer les cours</button>
					</div>
				</div>
			</div>
		</div>
		<div class="modal fade" id="invoice-date-modal" tabindex="-1" role="dialog">
			<div class="modal-dialog" role="dialog">
				<div class="modal-content">
					<div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-label="close"><span aria-hidden="true">&times;</span></button>
						<h4 class="modal-title">Indiquer la date de réception</h4>
					</div>
					<div class="modal-body container-fluid"></div>
					<div class="modal-footer">
						<button class="btn btn-primary validate-date">Valider</button>
					</div>
				</div>
			</div>

		</div>
		<?php include "inserts/sub_modal_product.php";?>
		<?php include "inserts/edit_modal.php";?>
		<style>
			.filter-group{
				text-align: center;
			}

			.invoice-filter{
				border-right: 1px solid #888;
				padding-bottom: 15px;
			}

			.filter-group>input{
				text-align: center;
			}

			.profile-picture{
				float: left;
				display: none;
			}
			.pp-input{
				cursor: pointer;
				position: relative;
			}
			.pp-input > input{
				position: absolute;
				top: 0;
				left: 0;
				opacity: 0;
				cursor: pointer;
				width: 100%;
				height: 100%;
			}
			.crop-step{
				display: none;
			}
			.user-pp{
				margin-bottom: 10px;
			}

			.session-row{
				padding-top: 15px;
			}

			.session-row:hover{
				background-color: #AAAAAA;
			}
		</style>
		<script>
			$(document).ready(function(){
				window.user_id = parseInt(/([0-9]+)/.exec(top.location.pathname)[0]);
				$("#datepicker-start").datetimepicker({
					format: "DD/MM/YYYY",
					defaultDate: moment().subtract(1, 'month'),
					locale: "fr",
					sideBySide: true,
					stepping: 15
				}).on('dp.change', function(e){
					fetchGivenSessions(user_id, "dates");
				})
				$("#datepicker-end").datetimepicker({
					format: "DD/MM/YYYY",
					defaultDate: moment(),
					locale: "fr",
					sideBySide: true,
					stepping: 15
				}).on('dp.change', function(e){
					fetchGivenSessions(user_id, "dates");
				})
				$("#invoice-period").datetimepicker({
					format: "MM/YYYY",
					defaultDate: moment(),
					locale: "fr",
				})
				$("#upload-invoice").fileinput({
					autoReplace: true,
					browseClass: "btn btn-info btn-block",
					browseLabel: "Uploader une facture professeur correspondante",
					dropZoneEnabled: false,
					maxFileCount: 1,
					uploadLabel: "Envoyer",
					removeLabel: "Supprimer",
					showCaption: false,
					uploadExtraData: function(){ // Tweak
						var obj = {
							invoice_id: $("#invoice-select").val(),
							location: "../user_data/factures/"
						};
						return obj;
					},
					uploadUrl: "functions/upload_file.php"
				})
				fillInvoiceSelect($("#invoice-select"), user_id, null);
				fetchGivenSessions(user_id, "dates");
			}).on('click', '.create-invoice', function(){
				createInvoice();
			}).on('change', '#invoice-select', function(){
				var invoice_id = $(this).val();
				if($(this).val() != 0){
					fetchGivenSessions(user_id, "invoice");
					// Search if there's an invoice already uploaded
					fetchInvoiceDetails(invoice_id);
				} else {
					fetchGivenSessions(user_id, "dates");
				}
			}).on('show.bs.modal', '#invoice-associate-modal', function(){
				fillInvoiceSelect($("#invoice-assoc-select"), user_id, null);
			}).on('click', '.associate-sessions', function(){
				var invoice = {
					invoice_id : $("#invoice-assoc-select").val()
				};
				$(".session-row").each(function(){
					var current_id = $(this).data("session");
					// Update the entry in the database
					$.when(updateEntry("sessions", $.param(invoice), current_id)).done(function(data){
						console.log(data);
						// Update the display behind the modal
						$("#invoice-session-"+current_id).text($("#invoice-assoc-select option:selected").text());
					});
				})
				$("#invoice-associate-modal").modal('hide');
				showNotification("Cours associés", "success");
			}).on('show.bs.modal', '#invoice-date-modal', function(e){
				var action = $(e.relatedTarget).attr("id"), invoice_id = $(e.relatedTarget).data('invoice'), modal = $(this);

				modal.find(".modal-body").empty();
				modal.find(".validate-date").attr("data-invoice", invoice_id);

				var contents = "<form class='form-horizontal' id='invoice-date-form'>";
				contents += "<div class='form-group'>";
				if(action == "receive-invoice"){
					contents += "<label for='invoice_reception_date' class='col-lg-4 control-label'>Date de réception</label>";
					contents += "<div class='col-lg-8'><input type='text' id='modal-date' class='form-control' name='invoice_reception_date'></div>";
				} else {
					contents += "<label for='invoice_payment_date' class='col-lg-4 control-label'>Date de paiement</label>";
					contents += "<div class='col-lg-8'><input type='text' id='modal-date' class='form-control' name='invoice_payment_date'></div>";
				}
				contents += "</div>";
				contents += "</form>";
				modal.find(".modal-body").append(contents);
				$("#modal-date").datetimepicker({
					format: "DD/MM/YYYY",
					defaultDate: moment(),
					locale: "fr"
				})
				modal.find(".validate-date").on('click', function(){
					var values = modal.find("#invoice-date-form").serialize();
					$.when(updateEntry("invoices", values, invoice_id)).done(function(){
						modal.modal('hide');
						if(action == "receive-invoice"){
							$(".label-reception").html("<span class='glyphicon glyphicon-ok'></span> "+moment(modal.find("modal-date").val()).format("DD/MM/YYYY"));
							$(".label-reception").show();
						} else {
							$(".label-payment").html("<span class='glyphicon glyphicon-download'></span> "+moment(modal.find("modal-date").val()).format("DD/MM/YYYY"));
							$(".label-payment").show();
						}
					})
				})
			}).on('hidden.bs.modal', '#invoice-date-modal', function(){
				$(".validate-date").off('click');
			})
			function createInvoice(){
				var invoice_details = {
					invoice_seller_id : /([0-9]+)/.exec(top.location.pathname)[0],
					invoice_token : $("#invoice-token-input").val(),
					invoice_period : $("#invoice-period").val()
				};
				$.post("functions/add_invoice.php", {values : $.param(invoice_details)}).done(function(data){
					$("#invoice-token-input").val("");
					$("#invoice-create-modal").modal('hide');
					showNotification("Facture créée", "success");
					// Refresh the invoice select
					fillInvoiceSelect($("#invoice-select"), user_id, data);
				})
			}
			function fetchGivenSessions(user_id, filter_flag){
				if(filter_flag == "invoice"){
					$(".dates-filter").animate({
						opacity: '0.5'
					}, 400);
					$(".invoice-filter").animate({
						opacity: '1'
					}, 400);
					$(".invoice-actions").show();
				} else {
					$(".dates-filter").animate({
						opacity: '1'
					}, 400);
					$(".invoice-filter").animate({
						opacity: '0.5'
					}, 400);
					$(".invoice-actions").hide();
				}
				/*$(".list").trigger("loading");*/
				var filters = [];
				if(filter_flag == "invoice"){
					filters.push($("#invoice-select").val());
				} else {
					$(".date-filter").each(function(){
						if($(this).val() != "")
							filters.push(moment($(this).val(), "DD/MM/YYYY").format("YYYY-MM-DD"));
					})
				}
				console.log(filters, user_id);
				$.get("functions/fetch_given_sessions.php", {user_id : user_id, filters : filters, filter_flag : filter_flag}).done(function(data){
					renderGivenSessions(data);
				})
			}
			function fetchInvoiceDetails(invoice_id){
				$.get("functions/fetch_invoice_details.php", {invoice_id : invoice_id}).done(function(data){
					renderInvoiceDetails(JSON.parse(data));
				});
			}
			function renderGivenSessions(data){
				/*$(".list").trigger("loaded");*/
				$(".list").empty();
				var sessions = JSON.parse(data);
				var contents = "", total_price = 0, rate_title = "";
				$("#filtered-sessions-number").text(sessions.length);
				for(var i = 0; i < sessions.length; i++){
					contents += "<div class='row session-row' id='session-"+sessions[i].id+"' data-session='"+sessions[i].id+"'>";
					if(sessions[i].type == "Cours"){
						if(rate_title != sessions[i].rate_title){
							rate_title = sessions[i].rate_title;
							contents += "<p class='bf'><span class='glyphicon glyphicon-tasks'></span> "+rate_title+": "+sessions[i].rate+"€ / "+sessions[i].ratio+"</p>";
						}
						contents += "<p class='col-xs-11 bf'><label class='label label-info'>"+sessions[i].type+"</label> <strong>"+sessions[i].name+"</strong></p>";
						contents += "<a class='link-glyphicon' href='cours/"+sessions[i].id+"' title='Aller au cours'><span class='col-xs-1 glyphicon glyphicon-share-alt glyphicon-button-alt'></span></a>";
						contents += "<p class='col-xs-4 col-md-3'><span class='glyphicon glyphicon-time'></span> "+moment(sessions[i].start).format("lll")+" - "+moment(sessions[i].end).format("HH:mm")+"</p>";
						contents += "<p class='col-xs-2'><span class='glyphicon glyphicon-user'></span> "+sessions[i].participants+"</p>";
						contents += "<p class='col-xs-2'><span class='glyphicon glyphicon-tasks'></span> "+sessions[i].rate+"€ / "+sessions[i].ratio+"</p>";
						contents += "<p class='col-xs-2 col-md-1'><span class='glyphicon glyphicon-eur'></span> <span class='product-price'>"+sessions[i].price+"</span></p>";
						contents += "<p class='col-xs-2'><span class='glyphicon glyphicon-file'></span> <span id='invoice-session-"+sessions[i].id+"'>"+sessions[i].invoice+"</span></p>";
					}
					if(sessions[i].type == "Prestation"){
						contents += "<p class='col-xs-11 bf'><label class='label label-prestation'>"+sessions[i].type+"</label> <strong>"+sessions[i].address+"</strong></p>";
						contents += "<a class='link-glyphicon' href='prestation/"+sessions[i].id+"' title='Aller à la prestation'><span class='col-xs-1 glyphicon glyphicon-share-alt glyphicon-button-alt'></span></a>";
						contents += "<p class='col-xs-8 col-md-7'><span class='glyphicon glyphicon-time'></span> "+moment(sessions[i].start).format("lll")+" - "+moment(sessions[i].end).format("HH:mm")+"</p>";
						contents += "<p class='col-xs-2 col-md-1'><span class='glyphicon glyphicon-eur'></span> <span class='product-price'>"+sessions[i].price+"</span></p>";
						contents += "<p class='col-xs-2'><span class='glyphicon glyphicon-file'></span> <span id='invoice-session-"+sessions[i].id+"'>"+sessions[i].invoice+"</span></p>";
					}
					contents += "</div>";
					total_price += parseFloat(sessions[i].price);
				}
				$("#listing-price").text(total_price);
				$(".list").append(contents);
			}
			function renderInvoiceDetails(invoice){
				// Called to render the invoice action buttons
				if(invoice.invoice_token){
					$("#invoice-name").text(invoice.invoice_token);
				}

				if(invoice.invoice_address){
					if(invoice.invoice_address != null){
						$("#show-invoice").show();
						$("#show-invoice").attr("href", "Salsabor/"+invoice.invoice_address);
					}
					else{
						$("#show-invoice").hide();
					}
				} else{
					$("#show-invoice").hide();
				}

				if(invoice.invoice_reception_date){
					$(".label-reception").html("<span class='glyphicon glyphicon-ok'></span> "+moment(invoice.invoice_reception_date).format("DD/MM/YYYY"));
					$(".label-reception").show();
				} else {
					$(".label-reception").hide();
				}

				if(invoice.invoice_payment_date){
					$(".label-payment").html("<span class='glyphicon glyphicon-download'></span> "+moment(invoice.invoice_payment_date).format("DD/MM/YYYY"));
					$(".label-payment").show();
				} else {
					$(".label-payment").hide();
				}

				$("#receive-invoice").attr("data-invoice", invoice.invoice_id);
				$("#pay-invoice").attr("data-invoice", invoice.invoice_id);

				$("#create-invoice").attr("href", "teacher_invoice.php?invoice_id="+invoice.invoice_id)
			}
		</script>
	</body>
</html>
