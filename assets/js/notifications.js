moment.locale('fr');
$(document).on('click', '.trigger-nav', function(e){
	e.stopPropagation();
	if($(".sub-modal-notification").is(":visible")){
		$(".sub-modal-notification").hide(0);
	} else {
		fetchNotifications(50, null, "smn-body");
		$(".sub-modal-notification").css({left: 64+"%", top:55+"px"});
		$(".sub-modal-notification").show(0);
	}
}).on('click', '.smn-close', function(e){
	e.stopPropagation();
	$(".sub-modal-notification").hide(0);
}).on('click', '.toggle-read', function(e){
	e.stopImmediatePropagation();
	var notification_id = document.getElementById($(this).attr("id")).dataset.notification;

	if($("#notification-"+notification_id).hasClass("notif-new")){
		var value = "0";
	} else {
		var value = "1";
	}

	$.when(updateColumn("team_notifications", "notification_state", value, notification_id)).done(function(){
		$("#notification-"+notification_id).removeClass("notif-old");
		$("#notification-"+notification_id).removeClass("notif-new");
		if(value == 1){
			$("#notification-"+notification_id).addClass("notif-new");
			var span = $("#notification-"+notification_id).find("span.glyphicon-button");
			span.replaceWith("<span class='glyphicon glyphicon-ok col-sm-1 glyphicon-button toggle-read' title='Marquer comme lue'></span>");
			$(".badge-notifications").html(parseInt($("#badge-notifications").html())+1);
		} else {
			$("#notification-"+notification_id).addClass("notif-old");
			var span = $("#notification-"+notification_id).find("span.glyphicon-button");
			span.replaceWith("<span class='glyphicon glyphicon-remove col-sm-1 glyphicon-button toggle-read' title='Marquer comme non lue'></span>");
			$(".badge-notifications").html(parseInt($("#badge-notifications").html())-1);
			if(top.location.pathname === "/Salsabor/dashboard"){
				$("#notification-"+notification_id).fadeOut('normal', function(){
					$(this).remove();
				});
			}
		}
	})
}).on('click', '.notification-line', function(){
	var notification_id = $(this).data().notification;
	if($("#notification-"+notification_id).hasClass("notif-new")){
		var notification_id = $(this).data().notification;
		updateColumn("team_notifications", "notification_state", 0, notification_id);
	}
	window.location = $(this).data().redirect;
}).on('click', '.read-all', function(e){
	e.stopPropagation();
	$.post("functions/read_all.php");
})

function fetchNotifications(limit, filter, destination){
	/*console.log(limit);*/
	$.get("functions/fetch_notifications.php", {filter : filter, limit : limit}).done(function(data){
		if($("."+destination).is(":visible")){
			displayNotifications(data, limit, filter, destination);
		}
	});
}

function displayNotifications(data, limit, filter, destination, half){
	var notifications = JSON.parse(data);
	$("."+destination).empty();
	if(destination == "dashboard-notifications-container"){
		if(notifications.length == 0){
			$(".dashboard-notifications-container").empty();
			$(".dashboard-notifications-container").css("background-image", "url(assets/images/logotype-white.png)");
			$(".dashboard-notifications-container").css("opacity", "0.2");
		} else {
			$(".dashboard-notifications-container").css("background-image", "");
			$(".dashboard-notifications-container").css("opacity", "1.0");
		}
	}
	if(top.location.pathname === "/Salsabor/dashboard"){
		var half = true;
	} else {
		var half = false;
	}
	for(var i = 0; i < notifications.length; i++){
		// Status handling
		var contents = renderNotification(notifications[i], half);
		$("."+destination).append(contents);
	}
	setTimeout(fetchNotifications, 10000, limit, destination);
}

function renderNotification(notification, half){
	var contents = "", notifClass = "", notif_link = "", notif_image = "", notif_icon = "", notif_message = "";
	if(notification.status == '1'){
		notifClass += "notif-new";
	} else {
		notifClass += "notif-old";
	}
	console.log(half);
	if(half){
		var image_width = "col-xs-2 col-sm-2";
		var contents_width = "col-xs-10";
	} else {
		var image_width = "col-xs-2 col-sm-1";
		var contents_width = "col-xs-10 col-sm-11";
	}
	contents += "<div id='notification-"+notification.id+"' data-notification='"+notification.id+"' class='notification-line "+notifClass+" container-fluid'";

	// Token handling
	switch(notification.type){
		case "PRD":
			notif_link = "user/"+notification.user_id+"/abonnements";
			notif_image = notification.photo;
			switch(notification.subtype){
				case "NE":
					notif_message = "Le produit <strong>"+notification.product_name+"</strong> de "+notification.user+" arrivera à expiration le <strong>"+moment(notification.product_validity).format("DD/MM/YYYY")+"</strong>.";
					break;

				case "NH":
					notif_message = "Le produit <strong>"+notification.product_name+"</strong> de "+notification.user+" n'a plus que <strong>"+notification.remaining_hours+" heures restantes</strong>.";
					break;

				case "E":
					notif_message = "Le produit <strong>"+notification.product_name+"</strong> de "+notification.user+" a expiré le <strong>"+moment(notification.product_usage).format("DD/MM/YYYY")+"</strong>.";
					break;
			}
			notif_icon = "glyphicon-credit-card";
			break;

		case "MAT":
			notif_link = "user/"+notification.user_id+"/achats#purchase-"+notification.transaction;
			notif_image = notification.photo;
			switch(notification.subtype){
				case "NE":
					notif_message = "L'échéance de <strong>"+notification.payer+"</strong> pour "+notification.maturity_value+" € de la transaction "+notification.transaction+" arrive à sa date limite, fixée au <strong>"+moment(notification.maturity_date).format("DD/MM/YYYY")+"</strong>.";
					break;

				case "E":
					notif_message = "L'échéance de <strong>"+notification.payer+"</strong> pour "+notification.maturity_value+" € de la transaction "+notification.transaction+" prévue pour le  "+moment(notification.maturity_date).format("DD/MM/YYYY")+" a expiré.";
					break;

				case "L":
					notif_message = "L'échéance de <strong>"+notification.payer+"</strong> pour "+notification.maturity_value+" € de la transaction "+notification.transaction+" prévue pour le  "+moment(notification.maturity_date).format("DD/MM/YYYY")+" <strong>est en retard</strong>.";
					break;
			}
			notif_icon = "glyphicon-repeat";
			break;

		case "MAI":
			notif_link = "user/"+notification.user_id;
			notif_image = notification.photo;
			notif_message = "<strong>"+notification.user+"</strong> n'a pas d'adresse mail enregistrée.";
			notif_icon = "glyphicon-envelope";
			break;

		case "SES":
			if(notification.cours_status == 1){
				notif_link = "passages#ph-session-"+notification.session_id;
			} else {
				notif_link = "cours/"+notification.session_id;
			}
			notif_image = notification.photo;
			notif_message = "Le cours de <strong>"+notification.cours_name+"</strong> tenu par "+notification.user+" et commençant à "+moment(notification.session_start).format("HH:mm")+" en "+notification.salle+" est désormais <strong>ouvert aux participations</strong>.";
			notif_icon = "glyphicon-map-marker";
			break;

		case "TAS":
			notif_link = notification.link;
			notif_image = notification.photo;
			switch(notification.subtype){
				case "A":
					notif_message = "La tâche <strong>"+notification.title+"</strong> vous a été assignée.";
					break;

				case "NE":
					notif_message = "La tâche <strong>"+notification.title+"</strong> arrive bientôt à sa date limite, fixée au <strong>"+moment(notification.deadline).format("ll [à] HH:mm")+"</strong>";
					break;

				case "L":
					notif_message = "La tâche <strong>"+notification.title+"</strong> a dépassé sa date limite du <strong>"+moment(notification.deadline).format("ll [à] HH:mm")+"</strong>";
					break;

				case "CMT":
					notif_message = "Un commentaire a été ajouté à votre tâche <strong>"+notification.title+"</strong>";
					break;
			}
			notif_icon = "glyphicon-list-alt";
			break;

		case "PRO":
			notif_link = "forfait/"+notification.product_id;
			notif_image = "assets/images/sticker_promo.png";
			switch(notification.subtype){
				case "S":
					notif_message = "La promotion du produit <strong>"+notification.product_name+"</strong> commence aujourd'hui et durera jusqu'au "+moment(notification.date_desactivation).format("ll");
					break;

				case "E":
					notif_message = "La promotion du produit <strong>"+notification.product_name+"</strong> s'est achevée aujourd'hui."
					break;
			}
			notif_icon = "glyphicon-euro";
			break;

		default:
			break;
	}
	contents += "data-redirect='"+notif_link+"'>";

	contents += "<div class='"+image_width+"'>";
	contents += "<div class='notif-pp'>";
	contents += "<img src='"+notif_image+"' alt='Notification'>";
	contents += "</div>";
	contents += "</div>";
	contents += "<div class='"+contents_width+"'>";
	contents += "<div class='row'>";
	contents += "<p class='col-xs-11'>"+notif_message+"</p>";
	if(notification.status == 1){
		contents += "<span class='glyphicon glyphicon-ok col-xs-1 glyphicon-button toggle-read' id='toggle-notification-"+notification.id+"' data-notification='"+notification.id+"' title='Marquer comme lue'></span>";
	} else {
		contents += "<span class='glyphicon glyphicon-remove col-xs-1 glyphicon-button toggle-read' id='toggle-notification-"+notification.id+"' data-notification='"+notification.id+"' title='Marquer comme non lue'></span>";
	}
	contents += "<p class='notif-hour col-xs-10'><span class='glyphicon "+notif_icon+"'></span> ";
	contents += ""+moment(notification.date).fromNow()+"</p>";
	contents += "</div>";
	contents += "</div>";
	contents += "</div>";
	return contents;
}

function badgeNotifications(){
	$.get("functions/badge_notifications.php").done(function(data){
		if(data == 0){
			$(".badge-notifications").hide();
		} else {
			$(".badge-notifications").show();
			$(".badge-notifications").html(data);
		}
		setTimeout(badgeNotifications, 10000);
	})
}
