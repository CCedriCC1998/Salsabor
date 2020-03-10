$(document).ready(function(){
	// Init by display all the active sessions
	/* The goal here is to fetch all the active sessions when the page is loaded, then to wait 15 minutes before going to see if new sessions were activated. Thus, every 15 minutes we have to only get the newly activated sessions, which means the sessions that will begin in less than 90 minutes away from the time we're checking. As the sessions could have been added in a deorganised manner, we will construct an array of currently displayed sessions by ID to cross check what can be ignored by subsequent fetches.
	The same goes for the participations. We have to fetch the participations of only the sessions that are not collapsed. To do that, we create an array that will contain the non collapsed sessions, and every so often we'll refresh everything at once.
	*/
	var fetched = [];
	window.openedSessions = [];
	moment.locale('fr');
	if(top.location.pathname === '/Salsabor/participations'){
		$.when(fetchActiveSessions(fetched)).done(function(data){
			$.when(displaySessions(data, fetched)).done(function(){
				refreshTick();
			});
		})
	}
}).on('show.bs.modal', '#add-participation-modal', function(e){
	var session_id = $(e.relatedTarget).data('session'), modal = $(this);
	var previous_value = "";
	var compare;
	modal.find(".name-input").on('keyup change blur', function(){
		// A few seconds after the field is changed, fetch results are display to show if the entry is valid
		var name = $(".name-input").val();
		if(name != previous_value){ // Condition for blur
			if(name.length > 3){
				if(compare){
					clearTimeout(compare);
				}
				modal.find(".load-result").text("Recherche en cours...");
				modal.find(".user-loading-results").empty();
				modal.find(".user-loading-results").trigger('loading');
				compare = setTimeout(function(){
					$.get("functions/quick_search_users.php", {user : name, session_id : session_id}).done(function(data){
						var user_details = JSON.parse(data);
						if(user_details){
							var construct = "";
							for(var i = 0; i < user_details.length; i++){
								construct += "<div class='col-xs-4 user-result selectable ur-selectable' id='result-"+user_details[i].user_id+"' data-user='"+user_details[i].user_id+"'>";
								construct += "<img src='"+user_details[i].photo+"' alt='"+user_details[i].fullname+"' class='load-photo small-user-pp'>";
								construct += "<p class='load-identity'>"+user_details[i].fullname+"</p>";
								construct += "</div>";
							}
							modal.find(".load-result").text(user_details.length+" résultat(s) :");
							modal.find(".user-loading-results").append(construct);
						} else {
							modal.find(".load-result").text("Aucun résultat");
						}
						modal.find(".user-loading-results").trigger('loaded');
					})
				}, 1500);
			} else {
				modal.find(".load-result").text("");
				modal.find(".user-loading-results").empty();
			}
			previous_value = name;
		}
	})
	modal.find(".add-participation").on('click', function(){
		// Adding the participation
		var user_id = $(".user-result.selected").data('user');
		addParticipation(session_id, user_id);
		$(".name-input").val("");
		modal.find(".load-result").text("");
		modal.find(".user-loading-results").empty();
		$("#add-participation-modal").modal('hide');
	})
}).on('hide.bs.modal', '#add-participation-modal', function(e){
	$(this).find(".add-participation").off('click');
	$(this).find(".name-input").off();
}).on('click', '.panel-heading-container', function(){
	if(top.location.pathname === '/Salsabor/regularisation/participations/user/0' || top.location.pathname == '/Salsabor/regularisation/participations/user/1'){
		var id = document.getElementById($(this).attr("id")).dataset.user;
		$("#body-"+id).collapse("toggle");
	} else {
		var id = document.getElementById($(this).attr("id")).dataset.session;
		$("#body-session-"+id).collapse("toggle");
	}
}).on('shown.bs.collapse', ".panel-body:not(.panel-task-body)", function(){
	var session_id = document.getElementById($(this).attr("id")).dataset.session;
	displayParticipations(session_id);
}).on('hidden.bs.collapse', ".panel-body:not(.panel-task-body)", function(){
	var session_id = document.getElementById($(this).attr("id")).dataset.session;
}).on('click', '.set-participation-product', function(){
	var participation_id = document.getElementById($(this).attr("id")).dataset.participation;
	console.log(participation_id);
	if($(this).attr("id") == "btn-product-null-record"){
		var product_target = "-1";
	} else {
		var product_target = document.getElementById("product-selected").dataset.argument;
	}
	changeProductRecord(participation_id, product_target);
}).on('click', '.report-participation', function(){
	var participation_id = document.getElementById($(this).attr("id")).dataset.participation;
	var session_target = document.getElementById("product-selected").dataset.session;
	changeSessionRecord(participation_id, session_target);
}).on('click', function(e){
	//if(top.location.pathname !== "/Salsabor/planning"){
	if($(".sub-modal:hidden") && !$(".sub-modal").hasClass("sub-modal-session")){
		$(".sub-modal").hide();
	}
	//}
}).on('click', '.validate-session', function(e){
	e.stopPropagation();
	var session_id = document.getElementById($(this).attr("id")).dataset.session;
	var participation_ids = $("#body-session-"+session_id).find("li:not(.panel-add-record)").each(function(){
		if($(this).hasClass("status-pre-success") || $(this).hasClass("status-over")){
			validateParticipation(document.getElementById($(this).attr("id")).dataset.participation);
		}
	});
}).on('click', '.close-session', function(e){
	/** Close a session will make it disappear from the records page by changing its state to 0.
(0 : closed, 1 : opened and available for automatic records, 2 : opened but closed to automatic records)**/
	e.stopPropagation();
	var session_id = document.getElementById($(this).attr("id")).dataset.session;
	if($("#session-"+session_id+">ul>li.status-pre-success").length + $("#session-"+session_id+">ul>li.status-over").length != 0){
		alert("Des participations ne sont pas validées. Vous ne pouvez pas fermer ce cours avant d'avoir traité toutes les participations");
	} else {
		$.when(updateColumn("sessions", "session_opened", 0, session_id)).done(function(){
			$("#session-"+session_id).remove();
			// We remove the recently closed session from the list to be refreshed.
			switch(window.openedSessions.length){
				case 0:
					break;

				case 1: // jQuery.grep() cannot empty an array
					window.openedSessions.length = 0;
					break;

				default:
					window.openedSessions = jQuery.grep(window.openedSessions, function(arr){
						return arr !== parseInt(session_id);
					})
			}
			logAction("sessions", "Fermeture", session_id);
		})
	}
})

function fetchActiveSessions(fetched){
	return $.get("functions/fetch_active_sessions.php", {fetched : fetched});
}
function fetchEligibleSessions(participation_id){
	return $.get("functions/fetch_eligible_sessions.php", {participation_id : participation_id});
}

/** Two functions to display the active sessions : one for the page, one for the modal to report**/
function displaySessions(data, fetched){
	var active_sessions = JSON.parse(data);
	var as_display = "";
	$(".active-sessions-container").append(as_display);
	for(var i = 0; i < active_sessions.length; i++){
		var session_start = moment(active_sessions[i].start);
		/*if(session_start > moment().format("DD/MM/YYYY HH:mm")){
				var relative_time = session_start.toNow();
			} else {
				var relative_time = session_start.fromNow();
			}*/
		as_display += "<div class='panel panel-session' id='session-"+active_sessions[i].id+"'>";
		// Panel heading
		as_display += "<div class='panel-heading-container' id='ph-session-"+active_sessions[i].id+"' data-session='"+active_sessions[i].id+"' data-trigger='"+active_sessions[i].id+"'>";
		as_display += "<div class='panel-heading'>";
		// Container fluid for session name and hour
		as_display += "<div class='container-fluid'>";
		as_display += "<p class='session-id col-xs-5'>"+active_sessions[i].title+"</p>";
		as_display += "<p class='session-date col-xs-4'><span class='glyphicon glyphicon-time'></span> Le "+session_start.format("DD/MM")+" de "+session_start.format("HH:mm")+" à "+moment(active_sessions[i].end).format("HH:mm")+"</p>";
		as_display += "<a href='cours/"+active_sessions[i].id+"' class='link-glyphicon session-option'><span class='glyphicon glyphicon-share-alt col-xs-1 glyphicon-button-alt glyphicon-button-big' title='Aller à la page du cours'></span></a>";
		as_display += "<span class='glyphicon glyphicon-ban-circle col-xs-1 session-option close-session glyphicon-button-alt glyphicon-button-big' id='close-session-"+active_sessions[i].id+"' data-session='"+active_sessions[i].id+"' title='Fermer le cours'></span>";
		as_display += "<span class='glyphicon glyphicon-ok-sign col-xs-1 session-option validate-session glyphicon-button-alt glyphicon-button-big' id='validate-session-"+active_sessions[i].id+"' data-session='"+active_sessions[i].id+"' title='Valider tous les passages'></span></p>";
		as_display += "</div>";
		// Tags
		as_display += "<div class='container-fluid'>";
		as_display += "<h5 class='col-xs-12'>";
		for(var j = 0; j < active_sessions[i].labels.length; j++){
			console.log(active_sessions[i].labels[j].entry_id);
			var label;
			if(active_sessions[i].labels[j].is_mandatory == 1){
				label = "<span class='glyphicon glyphicon-star'></span> "+active_sessions[i].labels[j].rank_name;
			} else {
				label = active_sessions[i].labels[j].rank_name;
			}
			as_display += "<span class='label label-salsabor label-display-only' id='task-tag-"+active_sessions[i].labels[j].entry_id+"' data-target='"+active_sessions[i].labels[j].entry_id+"' data-targettype='task' style='background-color:"+active_sessions[i].labels[j].tag_color+"'>"+label+"</span>";
		}
		as_display += "</h5>";
		as_display += "</div>";
		// Container fluid for session level, teacher...
		as_display += "<div class='container-fluid'>";
		as_display += "<p class='col-xs-2 col-sm-2 col-lg-1'><span class='glyphicon glyphicon-user'></span> <span class='user-total-count' id='user-total-count-"+active_sessions[i].id+"'></span></p>";
		as_display += "<p class='col-xs-2 col-sm-2 col-lg-1'><span class='glyphicon glyphicon-ok'></span> <span class='user-ok-count' id='user-ok-count-"+active_sessions[i].id+"'></span></p>";
		as_display += "<p class='col-xs-2 col-sm-2 col-lg-1'><span class='glyphicon glyphicon-warning-sign'></span> <span class='user-warning-count' id='user-warning-count-"+active_sessions[i].id+"'></span></p>";
		as_display += "<p class='col-xs-6 col-lg-3'><span class='glyphicon glyphicon-pushpin'></span> "+active_sessions[i].room+"</p>";
		as_display += "<p class='col-xs-6 col-lg-3'><span class='glyphicon glyphicon-blackboard'></span> "+active_sessions[i].teacher+"</p>";
		as_display += "</div>";

		as_display += "</div>";
		as_display += "</div>";
		// Panel body
		as_display += "<div class='panel-body collapse' id='body-session-"+active_sessions[i].id+"' data-session='"+active_sessions[i].id+"'>";
		as_display += "</div></div>";
		fetched.push(active_sessions[i].id);
		window.openedSessions.push(parseInt(active_sessions[i].id));
	}
	$(".active-sessions-container").append(as_display);
	var opened = $(".panel-session").length;
	switch(opened){
		case 0:
			$(".active-sessions-title").html("<span></span> Aucun cours n'est ouvert");
			break;

		case 1:
			$(".active-sessions-title").html("<span></span> cours est actuellement ouvert");
			$(".active-sessions-title>span").html(opened);
			break;

		default:
			$(".active-sessions-title").html("<span></span> cours sont actuellements ouverts");
			$(".active-sessions-title>span").html(opened);
			break;
	}
	/*console.log(fetched);*/
	/*setTimeout(displaySessions, 5000, fetched);*/
	setTimeout(fetchActiveSessions, 60000, fetched);
}
function displayTargetSessions(data){
	var sessions_list = JSON.parse(data);
	var body = "<ul class='purchase-inside-list'>";
	if(sessions_list.length == 0){
		body += "Aucun cours n'est ouvert";
	} else {
		for(var i = 0; i < sessions_list.length; i++){
			body += "<li class='sub-modal-product item-available' data-session='"+sessions_list[i].id+"'>";
			body += "<p class='smp-title'>"+sessions_list[i].title+"</p>";
			body += "<div class='row'>";
			body += "<p class='col-xs-6'><span class='glyphicon glyphicon-time'></span> "+moment(sessions_list[i].start).format("HH:mm")+" - "+moment(sessions_list[i].end).format("HH:mm")+"</p>";
			body += "<p class='col-xs-6'><span class='glyphicon glyphicon-pushpin'></span> "+sessions_list[i].room+"</p>";
			body += "<p class='col-xs-6'><span class='glyphicon glyphicon-blackboard'></span> "+sessions_list[i].teacher+"</p>";
			body += "</div>";
			body += "</li>";
		}
	}
	body += "</ul>";
	return body;
}

/** To have up-to-date info on every non collapsed session, this function ensures the info is refreshed every so often. Of course, when something big such as a deletion is done, displayParticipations can be called independently as it won't affect the global tick. **/
function refreshTick(){
	var openedSessions = window.openedSessions;
	console.log(openedSessions);
	for(var i = 0; i < openedSessions.length; i++){
		displayParticipations(openedSessions[i]);
	}
	// The tick is set to every 10 seconds.
	setTimeout(refreshTick, 10000);
}

function displayParticipations(session_id){
	$.get("functions/fetch_participations_session.php", {session_id : session_id}).done(function(data){
		var records_list = JSON.parse(data);
		$("#body-session-"+session_id).empty();
		var contents = "<div class='row session-list-container' id='session-"+session_id+"'>";
		contents += "<ul class='container-fluid records-inside-list records-product-list'>";
		var users = 0, ok = 0, warning = 0;
		for(var i = 0; i <= records_list.length; i++){
			if(i == records_list.length){
				contents += "<li class='panel-item panel-record panel-add-record container-fluid col-xs-6 col-md-4 col-lg-3' id='add-record-"+session_id+"' data-toggle='modal' data-target='#add-participation-modal' data-session='"+session_id+"'>";
				contents += "<div class='small-user-pp empty-pp'></div>";
				contents += "<p class='col-lg-12 panel-item-title bf'>Ajouter un passage manuellement</p>";
				contents += "</li>";
			} else {
				var record_status;
				switch(records_list[i].status){
					case '0':
						record_status = "status-pre-success";
						break;

					case '2':
						if(records_list[i].product_name == "-"){
							record_status = "status-partial-success";
						} else {
							record_status = "status-success";
						}
						ok++;
						break;

					case '3':
						record_status = "status-over";
						warning++;
						break;
				}
				users++;
				contents += "<li class='panel-item panel-record "+record_status+" container-fluid col-xs-6 col-md-4 col-lg-3' id='participation-"+records_list[i].id+"' data-participation='"+records_list[i].id+"'>";
				if(records_list[i].count > 0){
					contents += "<a href='user/"+records_list[i].user_id+"/taches' target='_blank'><span class='glyphicon glyphicon-list-alt float-right' title='"+records_list[i].count+" tâche(s) restantes à faire'></span></a>";
				}
				contents += "<div class='small-user-pp'><img src='"+records_list[i].photo+"' alt='"+records_list[i].user+"'></div>";
				contents += "<p class='col-lg-12 panel-item-title bf'><a href='user/"+records_list[i].user_id+"'>"+records_list[i].user+"</a></p>";
				contents += "<p class='col-xs-6 participation-details'><span class='glyphicon glyphicon-time'></span> "+moment(records_list[i].date).format("HH:mm:ss")+"</p>";
				contents += "<p class='col-xs-6 participation-details'><span class='glyphicon glyphicon-qrcode'></span> "+records_list[i].card+"</p>";
				// Indicating the product will soon expire
				if(moment(records_list[i].product_expiration).isBefore(moment('now').add(records_list[i].days_before_exp, 'days'))){
					console.log("days");
					contents += "<p class='col-lg-12 participation-details srd-product product-soon' title='Expiration prochaine : "+moment(records_list[i].product_expiration).format("DD/MM/YYYY")+"'><span class='glyphicon glyphicon-credit-card'></span> "+records_list[i].product_name+"</p>";
				} else if(parseFloat(records_list[i].product_hours) <= records_list[i].hours_before_exp){
					contents += "<p class='col-lg-12 participation-details srd-product product-soon' title='Expiration prochaine : "+records_list[i].product_hours+" heures restantes'><span class='glyphicon glyphicon-credit-card'></span> "+records_list[i].product_name+"</p>";
				} else {
					contents += "<p class='col-lg-12 participation-details srd-product'><span class='glyphicon glyphicon-credit-card'></span> "+records_list[i].product_name+"</p>";
				}
				// Different button depending on the status of the record
				if(records_list[i].status == '2'){
					contents += "<p class='col-xs-3 col-lg-3 panel-item-options' id='option-validate'><span class='glyphicon glyphicon-remove glyphicon-button' onclick='unvalidateParticipation("+records_list[i].id+")' title='Annuler la validation'></span></p>";
				} else {
					contents += "<p class='col-xs-3 col-lg-3 panel-item-options' id='option-validate'><span class='glyphicon glyphicon-ok glyphicon-button' onclick='validateParticipation("+records_list[i].id+")' title='Valider le passage'></span></p>";
				}
				contents += "<p class='col-xs-3 col-lg-3 panel-item-options'><span class='glyphicon glyphicon-credit-card glyphicon-button trigger-sub' id='change-product-"+records_list[i].id+"' data-subtype='set-participation-product' data-participation='"+records_list[i].id+"' title='Changer le produit'></span></p>";
				contents += "<p class='col-xs-3 col-lg-3 panel-item-options'><span class='glyphicon glyphicon-eye-open glyphicon-button trigger-sub' id='change-session-"+records_list[i].id+"' data-subtype='change-participation' data-argument='"+records_list[i].id+"' title='Changer le cours'></span></p>";
				contents += "<p class='col-xs-3 col-lg-3 panel-item-options'><span class='glyphicon glyphicon-trash glyphicon-button' id='delete-record-"+records_list[i].id+"' data-toggle='modal' data-target='#delete-modal' data-entry='"+records_list[i].id+"' data-table='participations' data-delete='#participation-"+records_list[i].id+"' title='Supprimer le passage'></span></p>";
				contents += "</li>";
			}
		}
		contents += "</ul>";
		contents += "</div>";
		$("#body-session-"+session_id).append(contents);
		$("#user-total-count-"+session_id).text(users);
		$("#user-ok-count-"+session_id).text(ok);
		$("#user-warning-count-"+session_id).text(warning);
	})
}

function displayIrregularParticipations(participation_id, age_action){
	// age_action : 0 means take all participations after, 1 means before
	console.log("ID de départ : "+participation_id);
	$.get("functions/fetch_irregular_participations.php", {participation_id : participation_id, age_action : age_action}).done(function(data){
		var records_list = JSON.parse(data);
		var users = 0, ok = 0, warning = 0;
		var contents = "";
		for(var i = 0; i < records_list.length; i++){
			var record_status;
			switch(records_list[i].status){
				case '0':
					if(records_list[i].product_name == "-"){
						record_status = "status-over";
					} else {
						record_status = "status-pre-success";
					}
					break;

				case '2':
				case '4':
				case '5':
					if(records_list[i].product_name == "-"){
						record_status = "status-partial-success";
					} else {
						record_status = "status-success";
					}
					ok++;
					break;

				case '3':
					record_status = "status-over";
					warning++;
					break;
			}
			users++;
			if(i == records_list.length-1){
				contents += "<li class='panel-item panel-record irregular-record "+record_status+" container-fluid col-lg-12 waypoint-mark' id='participation-"+records_list[i].id+"' data-participation='"+records_list[i].id+"'>";
			} else {
				contents += "<li class='panel-item panel-record irregular-record "+record_status+" container-fluid col-lg-12' id='participation-"+records_list[i].id+"' data-participation='"+records_list[i].id+"'>";
			}
			// Profile picture
			if(records_list[i].photo != null){
				var photo = records_list[i].photo;
			} else {
				var photo = "assets/images/logotype-white.png";
			}
			contents += "<div class='notif-pp col-sm-2'><img src='"+photo+"' alt='"+records_list[i].user+"'></div>";
			// Details
			contents += "<div class='row irregular-record-actions'>";

			// User
			if(records_list[i].user != " "){
				var user_message = "<a href='user/"+records_list[i].user_id+"'>"+records_list[i].user+"</a>";
			} else {
				if(records_list[i].card != null){
					var user_message = "Code inconnu - Pas d'utilisateur";
				} else {
					var user_message = "Pas d'utilisateur associé";
				}
			}
			contents += "<p class='panel-item-title col-sm-6 bf'>"+user_message+" ("+records_list[i].id+")</p>";

			// Action buttons
			// Different button depending on the status of the record
			//contents += "<div class='col-xs-12 col-sm-3 col-lg-4'>";
			if(records_list[i].status == '2'){
				contents += "<p class='col-xs-2 col-sm-1 panel-item-options' id='option-validate'><span class='glyphicon glyphicon-remove glyphicon-button' onclick='unvalidateParticipation("+records_list[i].id+")' title='Annuler la validation'></span></p>";
			} else {
				contents += "<p class='col-xs-2 col-sm-1 panel-item-options' id='option-validate'><span class='glyphicon glyphicon-ok glyphicon-button' onclick='validateParticipation("+records_list[i].id+")' title='Valider le passage'></span></p>";
			}
			contents += "<p class='col-xs-2 col-sm-1 panel-item-options'><span class='glyphicon glyphicon-credit-card glyphicon-button trigger-sub' id='change-product-"+records_list[i].id+"' data-subtype='set-participation-product' data-participation='"+records_list[i].id+"' title='Changer le produit'></span></p>";
			contents += "<p class='col-xs-2 col-sm-1 panel-item-options'><span class='glyphicon glyphicon-eye-open glyphicon-button trigger-sub' id='change-session-"+records_list[i].id+"' data-subtype='change-participation' data-argument='"+records_list[i].id+"' title='Changer le cours'></span></p>";
			// Archive
			if(records_list[i].archived == '0'){
				contents += "<p class='col-xs-2 col-sm-1 panel-item-options'><span class='glyphicon glyphicon-folder-close glyphicon-button' id='archive-participation-"+records_list[i].id+"' title='Archiver la participation' data-toggle='modal' data-target='#archive-modal' data-entry='"+records_list[i].id+"' data-table='participations'></span></p>";
			} else {
				contents += "<p class='col-xs-2 col-sm-1 panel-item-options'><span class='glyphicon glyphicon-folder-open glyphicon-button dearchive-data' id='dearchive-participation-"+records_list[i].id+"' title='Désarchiver la participation' data-entry='"+records_list[i].id+"' data-table='participations'></span></p>";
			}
			contents += "<p class='col-xs-2 col-sm-1 panel-item-options'><span class='glyphicon glyphicon-trash glyphicon-button' id='delete-record-"+records_list[i].id+"' data-toggle='modal' data-target='#delete-modal' data-entry='"+records_list[i].id+"' data-table='participations' data-delete='#participation-"+records_list[i].id+"' title='Supprimer le passage'></span></p>";
			//contents += "</div>";
			contents += "</div>";

			contents += "<div class='row irregular-record-details'>";

			//Membership code
			if(records_list[i].card != null){
				var card_message = records_list[i].card;
			} else {
				var card_message = "Passage ajouté manuellement";
			}
			contents += "<p class='col-xs-4 participation-details'><span class='glyphicon glyphicon-qrcode'></span> "+card_message+"</p>";

			// Record hour
			contents += "<p class='col-xs-4 participation-details'><span class='glyphicon glyphicon-time'></span> "+moment(records_list[i].date).format("DD/MM/YYYY HH:mm:ss")+"</p>";

			// Reader
			contents += "<p class='col-xs-4 participation-details'><span class='glyphicon glyphicon-pushpin'></span> "+records_list[i].room+"</p>";

			// Session
			if(records_list[i].cours_name != null){
				contents += "<p class='col-xs-6 participation-details srd-session'><span class='glyphicon glyphicon-eye-open'></span> "+records_list[i].cours_name+" ("+moment(records_list[i].session_start).format("DD/MM/YYYY HH:mm")+" - "+moment(records_list[i].session_end).format("HH:mm")+")</p>";
			} else {
				contents += "<p class='col-xs-6 participation-details srd-session'><span class='glyphicon glyphicon-eye-open'></span> Pas de cours</p>";
			}

			// Indicating the product will soon expire
			if(moment(records_list[i].product_expiration).isBefore(moment('now').add(records_list[i].days_before_exp, 'days'))){
				console.log("days");
				contents += "<p class='col-sm-6 participation-details srd-product product-soon' title='Expiration prochaine : "+moment(records_list[i].product_expiration).format("DD/MM/YYYY")+"'><span class='glyphicon glyphicon-credit-card'></span> "+records_list[i].product_name+"</p>";
			} else if(parseFloat(records_list[i].product_hours) <= records_list[i].hours_before_exp){
				contents += "<p class='col-sm-6 participation-details srd-product product-soon' title='Expiration prochaine : "+records_list[i].product_hours+" heures restantes'><span class='glyphicon glyphicon-credit-card'></span> "+records_list[i].product_name+"</p>";
			} else {
				contents += "<p class='col-sm-6 participation-details srd-product'><span class='glyphicon glyphicon-credit-card'></span> "+records_list[i].product_name+"</p>";
			}

			if(records_list[i].duplicates != false){
				var duplicate_message = "Doublon du passage "+records_list[i].duplicates;
			} else {
				var duplicate_message = "Passage unique; n'est pas un doublon";
			}
			contents += "<p class='participation-details'>"+duplicate_message+"</p>";
			contents += "</div>";
			contents += "</li>";
		}
		$(".irregulars-list").append(contents);
		$(".waypoint-mark").waypoint({
			handler: function(direction){
				if(direction === "down"){
					console.log("Waypoint reached");
					var participation_id = document.getElementById(this.element.id).dataset.participation;
					$("#"+this.element.id).removeClass("waypoint-mark");
					this.destroy();
					console.log($(".waypoint-mark").length);
					var age_limit = moment().subtract(2, 'months');
					if(/old/.exec(top.location.pathname) !== null)
						displayIrregularParticipations(participation_id, 1);
					else
						displayIrregularParticipations(participation_id, 0);
				}
			},
			context: 'irregular-sessions-container',
			offset: '95%'
		})
		console.log($(".waypoint-mark").length);
	})
}

function displayIrregularUsers(archive){
	$.get("functions/fetch_irregular_users.php", {archive : archive}).done(function(data){
		var user_list = JSON.parse(data);
		console.log(user_list);
		var contents = "";
		for(var i = 0; i < user_list.length; i++){
			if(user_list[i].user != ' '){
				contents += "<div class='panel panel-item panel-purchase'>";
				contents += "<a class='panel-heading-container' id='ph-user-"+user_list[i].user_id+"' data-user='"+user_list[i].user_id+"' data-trigger='"+user_list[i].user_id+"'>";
				contents += "<div class='panel-heading container-fluid'>";
				contents += "<p class='irregular-user'><span class='col-xs-11'>"+user_list[i].user+" (<span class='irregular-user-count' id='count-"+user_list[i].user_id+"'>"+user_list[i].count+"</span>)</span> <span class='glyphicon glyphicon-share-alt glyphicon-button glyphicon-button-alt col-xs-1' id='glyph-user-"+user_list[i].user_id+"' data-user='"+user_list[i].user_id+"' title='Aller aux participations de l&apos;utilisateur'></span></p>";
				contents += "</div>";
				contents += "</a>";
				contents += "<div class='panel-collapse collapse' id='body-"+user_list[i].user_id+"' data-user='"+user_list[i].user_id+"'>";
				contents += "<div class='panel-body row irregular-sessions-container'><ul class='irregulars-list' id='list-"+user_list[i].user_id+"'></ul></div>";
				contents += "</div>";
				contents += "</div>";
			}
		}
		$(".nav-tabs").after(contents);
	})
}

function displayIrregularUserParticipations(user_id, archive){
	$.get("functions/fetch_irregular_user_participations.php", {user_id : user_id, archive : archive}).done(function(data){
		$("#list-"+user_id).empty();
		var records_list = JSON.parse(data);
		var contents = "";
		for(var i = 0; i < records_list.length; i++){
			var record_status;
			switch(records_list[i].status){
				case '0':
					if(records_list[i].product_name == "-"){
						record_status = "status-over";
					} else {
						record_status = "status-pre-success";
					}
					break;

				case '2':
				case '4':
				case '5':
					if(records_list[i].product_name == "-"){
						record_status = "status-partial-success";
					} else {
						record_status = "status-success";
					}
					break;

				case '3':
					record_status = "status-over";
					break;
			}
			contents += "<li class='panel-item panel-record irregular-record "+record_status+" container-fluid col-lg-12' id='participation-"+records_list[i].id+"' data-participation='"+records_list[i].id+"'>";
			// Details
			contents += "<div class='row irregular-record-actions'>";

			contents += "<p class='panel-item-title personal-participation-title col-lg-8 bf'>";
			// Session
			if(records_list[i].cours_name != null){
				contents += "<span class='glyphicon glyphicon-eye-open'></span> "+records_list[i].cours_name+" ("+moment(records_list[i].session_start).format("DD/MM/YYYY HH:mm")+" - "+moment(records_list[i].session_end).format("HH:mm")+")";
			} else {
				contents += "<span class='glyphicon glyphicon-eye-open'></span> Pas de cours associé";
			}
			contents += " - Passage n°"+records_list[i].id+"</p>";

			// Action buttons
			// Different button depending on the status of the record
			if(records_list[i].status == '2'){
				contents += "<p class='col-xs-3 col-lg-1 panel-item-options' id='option-validate'><span class='glyphicon glyphicon-remove glyphicon-button' onclick='unvalidateParticipation("+records_list[i].id+")' title='Annuler la validation'></span></p>";
			} else {
				contents += "<p class='col-xs-3 col-lg-1 panel-item-options' id='option-validate'><span class='glyphicon glyphicon-ok glyphicon-button' onclick='validateParticipation("+records_list[i].id+")' title='Valider le passage'></span></p>";
			}
			contents += "<p class='col-xs-3 col-lg-1 panel-item-options'><span class='glyphicon glyphicon-credit-card glyphicon-button trigger-sub' id='change-product-"+records_list[i].id+"' data-subtype='set-participation-product' data-participation='"+records_list[i].id+"' title='Changer le produit'></span></p>";
			contents += "<p class='col-xs-3 col-lg-1 panel-item-options'><span class='glyphicon glyphicon-eye-open glyphicon-button trigger-sub' id='change-session-"+records_list[i].id+"' data-subtype='change-participation' data-argument='"+records_list[i].id+"' title='Changer le cours'></span></p>";
			contents += "<p class='col-xs-3 col-lg-1 panel-item-options'><span class='glyphicon glyphicon-trash glyphicon-button' id='delete-record-"+records_list[i].id+"' data-toggle='modal' data-target='#delete-modal' data-entry='"+records_list[i].id+"' data-table='participations' data-delete='#participation-"+records_list[i].id+"' title='Supprimer le passage'></span></p>";
			contents += "</div>";

			contents += "<div class='row irregular-record-details'>";

			// Record hour
			contents += "<p class='col-xs-6 col-lg-4 participation-details'><span class='glyphicon glyphicon-time'></span> "+moment(records_list[i].date).format("DD/MM/YYYY HH:mm:ss")+"</p>";

			// Reader
			if(records_list[i].room != null)
				contents += "<p class='col-xs-6 col-lg-4 participation-details'><span class='glyphicon glyphicon-pushpin'></span> "+records_list[i].room+"</p>";
			else
				contents += "<p class='col-xs-6 col-lg-4 participation-details'><span class='glyphicon glyphicon-pushpin'></span> -</p>";

			// Indicating the product will soon expire
			if(moment(records_list[i].product_expiration).isBefore(moment('now').add(records_list[i].days_before_exp, 'days'))){
				console.log("days");
				contents += "<p class='col-lg-4 participation-details srd-product product-soon' title='Expiration prochaine : "+moment(records_list[i].product_expiration).format("DD/MM/YYYY")+"'><span class='glyphicon glyphicon-credit-card'></span> "+records_list[i].product_name+"</p>";
			} else if(parseFloat(records_list[i].product_hours) <= records_list[i].hours_before_exp){
				contents += "<p class='col-lg-4 participation-details srd-product product-soon' title='Expiration prochaine : "+records_list[i].product_hours+" heures restantes'><span class='glyphicon glyphicon-credit-card'></span> "+records_list[i].product_name+"</p>";
			} else {
				contents += "<p class='col-lg-4 participation-details srd-product'><span class='glyphicon glyphicon-credit-card'></span> "+records_list[i].product_name+"</p>";
			}

			contents += "</div>";
			contents += "</li>";
		}
		$("#list-"+user_id).append(contents);
	})
}

function displayUserParticipations(data){
	var records_list = JSON.parse(data);
	$(".participations-list").empty();
	var users = 0, ok = 0, warning = 0, pending = 0;
	var contents = "";
	for(var i = 0; i < records_list.length; i++){
		var record_status;
		switch(records_list[i].status){
			case '0':
				if(records_list[i].product_name == "-"){
					record_status = "status-over";
					warning++;
				} else {
					record_status = "status-pre-success";
					pending++;
				}
				break;

			case '2':
				if(records_list[i].product_name == "-"){
					record_status = "status-partial-success";
					warning++;
				} else {
					record_status = "status-success";
					ok++;
				}
				break;

			case '3':
				record_status = "status-over";
				warning++;
				break;
		}
		users++;
		contents += "<li class='panel-item panel-record irregular-record "+record_status+" container-fluid col-lg-12' id='participation-"+records_list[i].id+"' data-participation='"+records_list[i].id+"'>";
		// Details
		contents += "<div class='row irregular-record-actions'>";

		contents += "<p class='panel-item-title personal-participation-title col-xs-8 bf'>";
		// Session
		if(records_list[i].cours_name != null){
			contents += "<span class='glyphicon glyphicon-eye-open'></span> "+records_list[i].cours_name+" ("+moment(records_list[i].session_start).format("DD/MM/YYYY HH:mm")+" - "+moment(records_list[i].session_end).format("HH:mm")+")";
		} else {
			contents += "<span class='glyphicon glyphicon-eye-open'></span> Pas de cours associé";
		}
		contents += " - Passage n°"+records_list[i].id+"</p>";

		// Action buttons
		// Different button depending on the status of the record
		if(records_list[i].status == '2'){
			contents += "<p class='col-xs-1 panel-item-options' id='option-validate'><span class='glyphicon glyphicon-remove glyphicon-button' onclick='unvalidateParticipation("+records_list[i].id+")' title='Annuler la validation'></span></p>";
		} else {
			contents += "<p class='col-xs-1 panel-item-options' id='option-validate'><span class='glyphicon glyphicon-ok glyphicon-button' onclick='validateParticipation("+records_list[i].id+")' title='Valider le passage'></span></p>";
		}
		contents += "<p class='col-xs-1 panel-item-options'><span class='glyphicon glyphicon-credit-card glyphicon-button trigger-sub' id='change-product-"+records_list[i].id+"' data-subtype='set-participation-product' data-participation='"+records_list[i].id+"' title='Changer le produit'></span></p>";
		contents += "<p class='col-xs-1 panel-item-options'><span class='glyphicon glyphicon-eye-open glyphicon-button trigger-sub' id='change-session-"+records_list[i].id+"' data-subtype='change-participation' data-argument='"+records_list[i].id+"' title='Changer le cours'></span></p>";
		contents += "<p class='col-xs-1 panel-item-options'><span class='glyphicon glyphicon-trash glyphicon-button' id='delete-record-"+records_list[i].id+"' data-toggle='modal' data-target='#delete-modal' data-entry='"+records_list[i].id+"' data-table='participations' data-delete='#participation-"+records_list[i].id+"' title='Supprimer le passage'></span></p>";
		contents += "</div>";

		contents += "<div class='row irregular-record-details'>";

		// Record hour
		contents += "<p class='col-xs-4 participation-details'><span class='glyphicon glyphicon-time'></span> "+moment(records_list[i].date).format("DD/MM/YYYY HH:mm:ss")+"</p>";

		// Reader
		contents += "<p class='col-xs-4 participation-details'><span class='glyphicon glyphicon-pushpin'></span> "+records_list[i].room+"</p>";

		// Indicating the product will soon expire
		if(moment(records_list[i].product_expiration).isBefore(moment('now').add(records_list[i].days_before_exp, 'days'))){
			console.log("days");
			contents += "<p class='col-xs-4 participation-details srd-product product-soon' title='Expiration prochaine : "+moment(records_list[i].product_expiration).format("DD/MM/YYYY")+"'><span class='glyphicon glyphicon-credit-card'></span> "+records_list[i].product_name+"</p>";
		} else if(parseFloat(records_list[i].product_hours) <= records_list[i].hours_before_exp){
			contents += "<p class='col-xs-4 participation-details srd-product product-soon' title='Expiration prochaine : "+records_list[i].product_hours+" heures restantes'><span class='glyphicon glyphicon-credit-card'></span> "+records_list[i].product_name+" (acheté le "+moment(records_list[i].date_achat).format("DD/MM/YYYY")+")</p>";
		} else {
			contents += "<p class='col-xs-4 participation-details srd-product'><span class='glyphicon glyphicon-credit-card'></span> "+records_list[i].product_name+" (acheté le "+moment(records_list[i].date_achat).format("DD/MM/YYYY")+")</p>";
		}

		contents += "</div>";
		contents += "</li>";
	}
	$(".participations-list").append(contents);
	$("#total-count").text(users);
	$("#valid-count").text(ok);
	$("#pending-count").text(pending);
	$("#over-count").text(warning);
}

function validateParticipation(participation_id){
	$.post("functions/validate_participation.php", {participation_id : participation_id}).done(function(product_id){
		var re = /historique/i;
		$("#participation-"+participation_id).removeClass("status-pre-success");
		$("#participation-"+participation_id).removeClass("status-over");
		if(product_id == ""){
			$("#participation-"+participation_id).addClass("status-partial-success");
		} else {
			$("#participation-"+participation_id).addClass("status-success");
			computeRemainingHours(product_id, false);
			if(top.location.pathname === '/Salsabor/regularisation/participations/user'){
				var count = parseInt($("#participation-"+participation_id).closest($(".panel")).find("span.irregular-user-count").text());
				count--;
				setTimeout(function(){
					$("#participation-"+participation_id).remove();
				}, 2000);
				$("#participation-"+participation_id).closest($(".panel")).find("span.irregular-user-count").text(count);
				if(count == 0){
					$("#participation-"+participation_id).closest($(".panel")).remove();
				}
			} else if(top.location.pathname === '/Salsabor/regularisation/participations'){
				$(".sub-legend>span").text(parseInt($(".sub-legend>span").text())-1);
				$("#participation-"+participation_id).remove();
			}
		}
		if(re.exec(top.location.pathname) != null){
			$("#valid-count").text($(".status-success").length);
			var partial = $(".status-partial-success").length;
			var over = $(".status-over").length;
			$("#over-count").text(partial + over);
		}
		$("#participation-"+participation_id).find($("p#option-validate")).html("<span class='glyphicon glyphicon-remove glyphicon-button' onclick='unvalidateParticipation("+participation_id+")' title='Annuler la validation'></span>")
	})
}

function unvalidateParticipation(participation_id){
	$.post("functions/unvalidate_participation.php", {participation_id : participation_id}).done(function(result){
		var data = JSON.parse(result);
		var status = data.status, product_id = data.product_id;
		$("#participation-"+participation_id).removeClass("status-success");
		$("#participation-"+participation_id).removeClass("status-partial-success");
		var re = /historique/i;
		if(status == 0){
			$("#participation-"+participation_id).addClass("status-pre-success");
			computeRemainingHours(product_id, false);
		} else {
			$("#participation-"+participation_id).addClass("status-over");
		}
		if(re.exec(top.location.pathname) != null){
			$("#valid-count").text($(".status-success").length);
			var partial = $(".status-partial-success").length;
			var over = $(".status-over").length;
			$("#over-count").text(partial + over);
		}
		$("#participation-"+participation_id).find($("p#option-validate")).html("<span class='glyphicon glyphicon-ok glyphicon-button' onclick='validateParticipation("+participation_id+")' title='Valider le passage'></span>");
	})
}

function deleteParticipation(participation_id){
	if($("#participation-"+participation_id).hasClass("status-success")){
		unvalidateParticipation(participation_id);
	}
	$.post("functions/delete_participation.php", {participation_id : participation_id}).done(function(){
		$(".sub-modal").hide();
		var re = /historique/i;
		if(re.exec(top.location.pathname) != null){
			$(".irregulars-target-container").empty();
			$("#participation-"+participation_id).remove();
			$("#total-count").text($(".product-participation").length);
			$("#valid-count").text($(".status-success").length);
			$("#over-count").text($(".status-over").length);
		} else {
			if(top.location.pathname === '/Salsabor/regularisation/participations/user'){
				var count = parseInt($("#participation-"+participation_id).closest($(".panel")).find("span.irregular-user-count").text());
				count--;
				setTimeout(function(){
					$("#participation-"+participation_id).remove();
				}, 2000);
				$("#participation-"+participation_id).closest($(".panel")).find("span.irregular-user-count").text(count);
				if(count == 0){
					$("#participation-"+participation_id).closest($(".panel")).remove();
				}
			} else if(top.location.pathname === '/Salsabor/regularisation/participations/all'){
				$(".sub-legend>span").text(parseInt($(".sub-legend>span").text())-1);
				$("#participation-"+participation_id).remove();
			}
		}
	})
}

/** This function will change the product the record will use when it's validated. If the record was valid before, then it'll be unvalidated to allow computing of the previous product, switched and then validated again for computing. **/
function changeProductRecord(participation_id, target_product_id){
	if(target_product_id == null){
		console.log("No product has been indicated. Aborting...");
	} else {
		var wasValid = false;
		if($("#participation-"+participation_id).hasClass("status-success") || $("#participation-"+participation_id).hasClass("status-partial-success")){
			$.when(unvalidateParticipation(participation_id)).done(function(){
				wasValid = true;
			});
		}
		$.post("functions/set_product_participation.php", {participation_id : participation_id, product_id : target_product_id}).done(function(response){
			var data = JSON.parse(response);
			var product_name = data.product_name, status = data.status;
			console.log(status);
			$(".sub-modal").hide();
			$("#participation-"+participation_id).find($(".srd-product")).html("<span class='glyphicon glyphicon-credit-card'></span> "+product_name);
			$("#participation-"+participation_id).removeClass("status-over");
			$("#participation-"+participation_id).addClass("status-pre-success");
			if(wasValid){
				if($("#participation-"+participation_id).hasClass("product-participation")){
					$("#participation-"+participation_id).remove();
				}
				validateParticipation(participation_id);
			}
		})
	}
}

/** Similarly to the function above, this one will also fiddle with the validation if need be. Its main goal is changing the session attached to a record if a user just validated in the wrong place. It happens. More often that not. **/
function changeSessionRecord(participation_id, target_session_id){
	if(target_session_id == null){
		console.log("No session has been indicated. Aborting...");
	} else {
		$.post("functions/set_session_participation.php", {participation_id : participation_id, session_id : target_session_id}).done(function(session){
			var session_data = JSON.parse(session);
			console.log(top.location.pathname);
			if(top.location.pathname === '/Salsabor/regularisation/participations'){
				$("#participation-"+participation_id).find($("p.srd-session")).html("<span class='glyphicon glyphicon-eye-open'></span> "+session_data.cours_name+" ("+moment(session_data.session_start).format("DD/MM/YYYY HH:mm")+" - "+moment(session_data.session_end).format("HH:mm")+")");
			} else if(top.location.pathname === '/Salsabor/participations') {
				$("#participation-"+participation_id).remove();
			} else {
				$("#participation-"+participation_id).find($(".panel-item-title")).html("<span class='glyphicon glyphicon-eye-open'></span> "+session_data.cours_name+" ("+moment(session_data.session_start).format("DD/MM/YYYY HH:mm")+" - "+moment(session_data.session_end).format("HH:mm")+") - Passage n°"+participation_id);
			}
		})
	}

}

function addParticipation(target_session_id, user_id){
	$.post("functions/add_participation.php", {user_id : user_id, session_id : target_session_id}).done(function(data){
		displayParticipations(target_session_id);
		showNotification("Participation ajoutée (doublons ignorés)", "success");
	})
}

function renderParticipationWide(participation, is_waypoint){
	var participation_status, waypoint_class = "", contents = "", photo = "assets/images/logotype-white.png";
	switch(participation.status){
		case '0':
			if(participation.product_name == "-")
				participation_status = "status-over";
			else
				participation_status = "status-pre-success";
			break;

		case '2':
			if(participation.product_name == "-")
				participation_status = "status-partial-success";
			else
				participation_status = "status-succes";
			break;

		case '3':
			participation_status = "status-over";
			break;
	}
	if(is_waypoint)
		waypoint_class = "waypoint-mark";

	contents += "<li class='panel-item panel-record irregular-record "+participation_status+" container-fluid col-lg-12 "+waypoint_class+"' id='participation-"+participation.id+"' data-participation='"+participation.id+"'>";

	// Profile picture
	if(participation.photo != null)
		photo = participation.photo;

	contents += "<div clas='notif-pp col-sm-2'><img src='"+photo+"' alt='"+participation.user+"'></div>";

	// Details
	contents += "<div class='row irregular-record-actions'>";
	// User
	var user_message = "";
	if(participation.user != " "){
		user_message = "<a href='user/"+participation.user_id+"'>"+participation.user+"</a>";
	} else {
		if(participation.card != null)
			user_message = "Code inconnu - Pas d'utilisateur";
		else
			user_message = "Pas d'utilisateur associé";
	}
	contents += "<p class='panel-item-title col-sm-7 bf'>"+user_message+" ("+participation.id+")</p>";

	// Actions


	contents += "</li>";
}
