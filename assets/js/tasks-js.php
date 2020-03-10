<?php
header("Content-type: application/javascript");
session_start();
?>
	// Oh yeah we cheating boys. Basically we need to get $_SESSION variables for comments, so this is an acceptable method.

	$(document).on('click', '.panel-heading-task', function(){
	var id = document.getElementById($(this).attr("id")).dataset.trigger;
	$("#body-task-"+id).collapse("toggle");
}).on('show.bs.collapse', '.panel-task-body', function(){
	var task_id = document.getElementById($(this).attr("id")).dataset.task;
	fetchComments(task_id);
}).on('click', '.btn-comment', function(){
	var task_id = document.getElementById($(this).attr("id")).dataset.task;
	var comment = $("#comment-form-"+task_id+">textarea").val();
	var comment_author = <?php echo json_encode($_SESSION["user_id"]);?>;
	postComment(comment, comment_author, task_id);
}).on('focus', '#task-target-input', function(e){
	e.stopPropagation();
	var id = $(this).data().user;
	$.get("functions/fetch_targets.php", {user_id : id}).done(function(data){
		console.log(data);
		var targetList = JSON.parse(data);
		var autocompleteList = [];
		for(var i = 0; i < targetList.length; i++){
			autocompleteList.push(targetList[i].name);
		}
		$("#task-target-input").textcomplete([{
			match: /(^|\b)(\w{2,})$/,
			search: function(term, callback){
				callback($.map(autocompleteList, function(item){
					return item.toLowerCase().indexOf(term.toLocaleLowerCase()) === 0 ? item : null;
				}));
			},
			replace: function(item){
				return item;
			}
		}]);
	})
}).on('click', '.post-task', function(){
	var task_title = $(".task-title-input").val();
	var task_description = $(".task-description-input").val();
	var task_token = $("#task-target-input").val();
	var task_creator = <?php echo json_encode($_SESSION["user_id"]);?>;
	if(task_token == ""){
		task_token = "[USR-"+$("#task-target-input").data().user+"]";
	}
	console.log(task_creator);
	postTask(task_title, task_description, task_token, task_creator);
}).on('click', '.delete-task', function(){
	var task_id = document.getElementById($(this).attr("id")).dataset.task;
	var table = "tasks";
	$(".sub-modal").hide(0);
	console.log(task_id, table);
	$.when(deleteEntry(table, task_id)).done(function(){
		$("#task-"+task_id).remove();
	})
}).on('click', '.toggle-task', function(){
	var table_name = "tasks";
	var flag = "task_state";
	var target_id = document.getElementById($(this).attr("id")).dataset.target;

	if($("#task-"+target_id).hasClass("task-new")){
		var value = "1";
	} else {
		var value = "0";
	}

	$.when(updateColumn(table_name, flag, value, target_id)).done(function(){
		$("#task-"+target_id).removeClass("task-new");
		$("#task-"+target_id).removeClass("task-old");
		$("#toggle-task-"+target_id).removeClass("glyphicon-ok");
		$("#toggle-task-"+target_id).removeClass("glyphicon-remove");
		if(value == 1){
			$("#task-"+target_id).addClass("task-old");
			$("#toggle-task-"+target_id).addClass("glyphicon-remove");
			$("#toggle-task-"+target_id).attr("title", "Marquer comme non traitée");
			logAction(table_name, "Validation", target_id);
		} else {
			$("#task-"+target_id).addClass("task-new");
			$("#toggle-task-"+target_id).addClass("glyphicon-ok");
			$("#toggle-task-"+target_id).attr("title", "Marquer comme traitée");
			logAction(table_name, "Invalidation", target_id);
		}
		if(top.location.pathname === "/Salsabor/dashboard"){
			$("#task-"+target_id).fadeOut('normal', function(){
				$(this).remove();
			});
		}
	})
}).on('click', '.glyphicon-button-alt', function(e){
	e.stopPropagation();
}).on('click', '.task-deadline', function(){
	var deadline = moment($(".datepicker").val(), "DD/MM/YYYY HH:mm").format("YYYY-MM-DD HH:mm");
	var task_id = document.getElementById($(this).attr("id")).dataset.task;
	$(".sub-modal").hide(0);
	$.when(updateColumn("tasks", "task_deadline", deadline, task_id)).done(function(){
		// Deadline
		if(deadline != null){
			var deadline_class = displayDeadline(moment(deadline));
			$("#deadline-"+task_id).removeClass("deadline-near");
			$("#deadline-"+task_id).removeClass("deadline-expired");
			$("#deadline-"+task_id).addClass(deadline_class);
			$("#deadline-"+task_id).html("<span class='glyphicon glyphicon-time'></span> "+moment(deadline).format("D MMM [à] H:mm"));
		} else {
			$("#deadline-"+task_id).html("<span class='glyphicon glyphicon-time'></span> Ajouter une date limite");
		}
		logAction("tasks", "Modification", task_id);
	})
}).on('click', '.delete-comment', function(){
	var id = document.getElementById($(this).attr("id")).dataset.target;
	$.when(deleteEntry("task_comments", id)).done(function(data){
		$("#unit-"+id).remove();
	})
})

function fetchTasks(task_token, user_id, attached_id, filter, limit){
	$(".tasks-container").trigger('loading');
	$.get("functions/fetch_tasks.php", {task_token : task_token, user_id : user_id, attached_id : attached_id, limit : limit, filter : filter}).done(function(data){
		if(limit == 0 || $(".sub-modal-notification").is(":visible")){
			displayTasks(data, task_token, user_id, attached_id, limit, filter);
		}
	});
}

function fetchComments(task_id){
	$.get("functions/fetch_comments.php", {task_id : task_id}).done(function(data){
		displayComments(task_id, data);
	})
}

function refreshTask(task){
	// Title
	$("#task-title-"+task.id).html(task.title);

	// Description
	$("#task-description-"+task.id+":not(.editing)").html(task.description);

	// Deadline
	if(task.deadline != null){
		var deadline_class = displayDeadline(moment(task.deadline));
		$("#deadline-"+task.id).removeClass("deadline-near");
		$("#deadline-"+task.id).removeClass("deadline-expired");
		$("#deadline-"+task.id).addClass(deadline_class);
		$("#deadline-"+task.id).html("<span class='glyphicon glyphicon-time'></span> "+moment(task.deadline).format("D MMM [à] H:mm"));
	} else {
		$("#deadline-"+task.id).html("<span class='glyphicon glyphicon-time'></span> Ajouter une date limite");
	}

	// Comments count
	$("#comments-count-"+task.id).html("<span class='glyphicon glyphicon-comment'></span> "+task.message_count);
}

function displayTasks(data, task_token, user_id, attached_id, limit, filter){
	var tasks = JSON.parse(data);
	if(tasks.length == 0){
		if($(".tasks-container").hasClass("dashboard-task-container")){
			$(".tasks-container").empty();
		}
		$(".dashboard-task-container").css("background-image", "url(assets/images/logotype-white.png)");
		$(".dashboard-task-container").css("opacity", "0.2");
	} else {
		$(".tasks-container").css("background-image", "");
		$(".tasks-container").css("opacity", "1.0");
	}
	if(top.location.pathname === "/Salsabor/dashboard"){
		var half = true;
	} else {
		var half = false;
	}
	for(var i = 0; i < tasks.length; i++){
		if($("#task-"+tasks[i].id).length > 0){
			refreshTask(tasks[i]);
		} else {
			if(i == 0){
				$(".tasks-container").empty();
			}

			var contents = renderTask(tasks[i], half);
			$(".tasks-container").append(contents);
		}
	}
	$(".tasks-container").trigger('loaded');
	setTimeout(fetchTasks, 5000, task_token, user_id, attached_id, filter, limit);
}

function displayDeadline(deadline){
	var deadline_class = "";
	if(deadline < moment()){
		deadline_class = "deadline-expired";
	} else if(deadline < moment().add(3, 'days')){
		deadline_class = "deadline-near";
	}
	return deadline_class;
}

function displayComments(task_id, data){
	$("#task-comments-"+task_id).empty();
	var messages = "";
	var message_list = JSON.parse(data);
	for(var i = 0; i < message_list.length; i++){
		/*console.log(message_list[i].own);*/
		messages += "<div class='comment-unit' id='unit-"+message_list[i].id+"'>";
		messages += "<a href='user/"+message_list[i].author_id+"' class='link-alt message-author'>"+message_list[i].author+"</a>";
		messages += "<div class='message-container' id='message-"+message_list[i].id+"'>"+message_list[i].comment+"</div>";
		messages += "<p class='message-details row'><span class='col-xs-5'>"+moment(message_list[i].date).format("[le] ll [à] HH:mm")+"</span>";
		if(message_list[i].own){
			/*messages += "<span class='comment-options col-xs-2 edit-comment' id='edit-"+message_list[i].id+"' data-target='"+message_list[i].id+"'>Editer</span>";*/
			messages += "<span class='comment-options col-xs-2 delete-comment' id='delete-"+message_list[i].id+"' data-target='"+message_list[i].id+"'>Supprimer</span>";
		}
		messages += "</p>";
		messages += "</div>";
	}
	$("#task-comments-"+task_id).append(messages);
	setTimeout(fetchComments, 10000, task_id);
}

function postComment(comment, author, task_id){
	$.post("functions/post_comment.php", {comment : comment, user_id : author, task_id : task_id}).done(function(e){
		console.log(e);
		$("#comment-form-"+task_id+">textarea").val('');
		if(e == 1){
			var creator = $("#creator-"+task_id).text();
			postNotification("TAS-CMT", task_id, creator);
		}
		fetchComments(task_id);
	})
}

function postTask(title, description, token, task_creator_id){
	$.post("functions/post_task.php", {task_title : title, task_description : description, task_token : token, task_creator_id : task_creator_id}).done(function(new_id){
		$(".panel-new-task").remove();
	})
}

function renderTask(task, half){
	// Status handling
	var contents = "", notifClass = "", link = "", linkTitle = "", deadline = moment(task.deadline);
	if(task.status == '0'){
		notifClass = "task-new";
	} else {
		notifClass = "task-old";
	}
	contents += "<div id='task-"+task.id+"' data-task='"+task.id+"' data-state='"+task.status+"' class='panel task-line "+notifClass+"'>";
	contents += "<div class='panel-heading panel-heading-task container-fluid' id='ph-task-"+task.id+"' data-trigger='"+task.id+"'>";

	if(half){
		var image_width = "col-lg-2";
		var contents_width = "col-lg-10";
		var comments_count_width = "col-lg-2";
		var deadline_width = "col-lg-5";
		var recipient_width = "col-lg-5";
	} else {
		var image_width = "col-lg-1";
		var contents_width = "col-lg-11";
		var comments_count_width = "col-lg-2";
		var deadline_width = "col-lg-3";
		var recipient_width = "col-lg-3";
	}
	contents += "<div class='hidden-xs col-sm-2 "+image_width+"'>";
	contents += "<div class='notif-pp'>";
	contents += "<image src='"+task.photo+"'>";
	contents += "</div>";
	contents += "</div>";

	contents += "<div class='col-sm-10 "+contents_width+"'>";
	contents += "<div class='row'>";

	contents += "<div class='visible-xs-block col-xs-2'>";
	contents += "<div class='notif-pp'>";
	contents += "<image src='"+task.photo+"'>";
	contents += "</div>";
	contents += "</div>";

	contents += "<p class='task-title col-xs-7 col-sm-8 modal-editable-"+task.id+"' data-field='task_title' data-name='Intitulé' id='task-title-"+task.id+"'>";

	contents += task.title;

	// Token handling
	switch(task.type){
		case "USR":
			linkTitle += "Aller à l&apos;utilisateur";
			break;

		case "PRD":
			linkTitle += "Aller au produit";
			break;

		case "SES":
			linkTitle += "Aller au cours";

		case "EVT":
			linkTitle += "Aller à l'événement";
			break;

		case "BKN":
			linkTitle += "Aller à la réservation";
			break;

		default:
			break;
	}

	contents += "</p>";

	if("/Salsabor/"+task.link !== top.location.pathname){
		contents += "<a href='"+task.link+"' class='link-glyphicon' target='_blank'><span class='glyphicon glyphicon-share-alt col-xs-1 col-sm-1 glyphicon-button-alt glyphicon-button-big' title='"+linkTitle+"'></span></a>";
	} else {
		contents += "<span class='col-xs-1'></span>";
	}
	contents += "<p class='col-xs-1'><span class='glyphicon glyphicon-pencil glyphicon-button glyphicon-button-alt glyphicon-button-big' id='edit-"+task.id+"' data-toggle='modal' data-target='#edit-modal' data-entry='"+task.id+"' data-table='tasks' title='Modifier la tâche'></span></p>";
	if(task.status == 1){
		contents += "<span class='glyphicon glyphicon-remove col-xs-1 glyphicon-button-alt glyphicon-button-big toggle-task' id='toggle-task-"+task.id+"' data-target='"+task.id+"' title='Marquer comme non traitée'></span>";
	} else {
		contents += "<span class='glyphicon glyphicon-ok col-xs-1 glyphicon-button-alt glyphicon-button-big toggle-task' id='toggle-task-"+task.id+"' data-target='"+task.id+"' title='Marquer comme traitée'></span>";
	}
	contents += "<p class='col-xs-1 panel-item-options'><span class='glyphicon glyphicon-trash glyphicon-button-alt glyphicon-button-big' id='delete-task-"+task.id+"' data-toggle='modal' data-target='#delete-modal' data-entry='"+task.id+"' data-table='tasks' data-delete='#task-"+task.id+"' title='Supprimer une tâche'></span></p>";
	contents += "<p class='task-target col-xs-10 col-sm-12'><span class='glyphicon glyphicon-play'></span> <strong>"+task.target_phrase+"</strong></p>";
	contents += "</div>";
	contents += "</div>";

	contents += "<div class='container-fluid col-xs-12'>";
	contents += "<p class='task-hour col-xs-12 col-sm-12'> créée "+moment(task.date).format("[le] ll [à] HH:mm")+" par <span class='task-creator' id='creator-"+task.id+"'>"+task.creator+"</span></p>";

	// Labels
	contents += "<h4>";
	for(var j = 0; j < task.labels.length; j++){
		contents += "<span class='label label-salsabor label-clickable label-deletable' title='Supprimer l&apos;étiquette' id='task-tag-"+task.labels[j].entry_id+"' data-target='"+task.labels[j].entry_id+"' data-targettype='task' style='background-color:"+task.labels[j].tag_color+"'>"+task.labels[j].rank_name+"</span>";
	}
	contents += "<span class='label label-default label-clickable label-add trigger-sub' id='label-add-"+task.id+"' data-subtype='user-tags' data-targettype='task' title='Ajouter une étiquette'>+";
	if(task.labels.length == 0){
		contents += " Ajouter une étiqutte";
	}
	contents += "</span>";
	contents += "</h4>";

	if(task.description == null){
		var description = "-";
		var is_placeholder = true;
	} else {
		var description = task.description;
		var is_placeholder = false;
	}

	contents += "<div><span class='glyphicon glyphicon-align-left glyphicon-description'></span><p class='modal-editable-"+task.id+"' id='task-description-"+task.id+"' data-field='task_description' data-name='Description' data-input='textarea' data-placeholder='"+is_placeholder+"'>"+description+"</p></div>";
	contents += "<div class='col-xs-2 col-md-2 "+comments_count_width+" comment-span' id='comments-count-"+task.id+"'>";
	contents += "<span class='glyphicon glyphicon-comment'></span> "+task.message_count;
	contents += "</div>";

	var deadline_class = displayDeadline(deadline);
	contents += "<div class='col-xs-5 col-md-5 "+deadline_width+" deadline-span "+deadline_class+" trigger-sub' id='deadline-"+task.id+"' data-subtype='deadline' data-task='"+task.id+"'>";
	if(task.deadline != null){
		contents += "<span class='glyphicon glyphicon-time'></span> "+deadline.format("D MMM [à] HH:mm");
	} else {
		contents += "<span class='glyphicon glyphicon-time'></span> Ajouter une date limite";
	}
	contents += "</div>";

	contents += "<div class='col-xs-5 col-md-5 "+recipient_width+" comment-span'>";
	contents += "<span class='glyphicon glyphicon-user glyphicon-description'></span> ";
	if(task.recipient == ""){
		var recipient = "-";
		var is_placeholder = true;
	} else {
		var recipient = task.recipient;
		var is_placeholder = false;
	}
	contents += "<p class='modal-editable-"+task.id+"' id='task-recipient-"+task.id+"' data-field='task_recipient' data-name='Membre affecté à cette tâche' data-placeholder='"+is_placeholder+"' data-complete='true' data-complete-filter='staff'>"+recipient+"</p>";
	contents += "</div>";

	contents += "</div>";
	contents += "</div>";

	// Commentaires de la notification
	contents += "<div class='panel-body panel-task-body collapse' id='body-task-"+task.id+"' data-task='"+task.id+"'>";
	contents += "<p><span class='glyphicon glyphicon-comment'></span> Commentaires</p>";
	contents += "<div class='comment-unit comment-form' id='comment-form-"+task.id+"'>";
	contents += "<textarea rows='2' class='form-control' placeholder='&Eacute;crire un commentaire...'></textarea>";
	contents += "<div class='input-group'>";
	contents += "<span class='input-group-btn'><button class='btn btn-primary btn-comment' id='comment-task-"+task.id+"' data-task='"+task.id+"'>Envoyer</button></span>";
	contents += "</div>";
	contents += "</div>";
	contents += "<div class='task-comments' id='task-comments-"+task.id+"'></div>";
	contents += "</div>";

	return contents;
}
