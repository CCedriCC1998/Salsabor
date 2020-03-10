$(document).on('click', '.label-deletable', function(e){
	e.stopPropagation();
	var id = $(this).attr("id");
	var target = document.getElementById(id).dataset.target;
	var table = "assoc_"+document.getElementById(id).dataset.targettype+"_tags";
	console.log(target, table);
	$.when(deleteEntry(table, target)).done(function(data){
		$("#"+id).remove();
		showNotification("Etiquette détachée", "success");
	});
}).on('click', '.label-addable', function(e){
	e.stopPropagation();
	var tag = document.getElementById($(this).attr("id")).dataset.tag;
	var target_type = document.getElementById($(this).attr("id")).dataset.targettype;
	if(target_type == "task" || target_type == "page"){
		var target = /([0-9]+)/.exec(window.target)[0];
	} else {
		var target = /([0-9]+$)/.exec(document.location.href)[0];
	}
	var tag_text = $(this).text();
	if($(this).hasClass("toggled")){
		$.when(detachTag(tag, target, target_type)).done(function(data){
			console.log(data);
			$("#tag-"+tag).removeClass("toggled");
			$("#tag-"+tag).find("span").remove();
			$("#"+target_type+"-tag-"+data).remove();
			showNotification("Etiquette "+tag_text+" détachée", "success");
		})
	} else {
		var value = /([a-z0-9]+)/i.exec($(this).css("backgroundColor"));
		$.when(attachTag(tag, target, target_type)).done(function(data){
			$("#tag-"+tag).addClass("toggled");
			$("#tag-"+tag).append("<span class='glyphicon glyphicon-ok float-right'></span>");
			if(target_type == "task" || target_type == "page"){
				var insert = "#label-add-"+target;
			} else {
				var insert = ".label-add";
			}
			$(insert).before("<span class='label label-salsabor label-clickable label-deletable' title='Supprimer l&apos;étiquette' id='"+target_type+"-tag-"+data+"' data-target='"+data+"' data-targettype='"+target_type+"' style='background-color:"+value[0]+"'>"+tag_text+"</span>");
			showNotification("Etiquette "+tag_text+" attachée", "success");
		})
	}
}).on('click', '.label-manual', function(e){
	e.stopPropagation();
	var tag = $(this).data('tag'), target_type = $(this).data('targettype');
	if(target_type == "task" || target_type == "page"){
		var target = /([0-9]+)/.exec(window.target)[0];
	}
	var tag_text = $(this).text();
	console.log(tag_text);
	if(target_type == "task" || target_type == "page"){
		var insert = "#label-add-"+target;
	} else {
		var insert = ".label-add";
	}
	if($(this).hasClass("toggled")){
		$("#tag-"+tag).removeClass("toggled");
		$("#tag-"+tag).find("span").remove();
		$(insert).parent(".tags_container").find("span:contains("+tag_text+")").remove();
	} else {
		var value = /([a-z0-9]+)/i.exec($(this).css("backgroundColor"));
		$("#tag-"+tag).addClass("toggled");
		$("#tag-"+tag).append("<span class='glyphicon glyphicon-ok float-right'></span>");
		$(insert).before("<span class='label label-salsabor' title='"+tag_text+"' style='background-color:"+value[0]+"'>"+tag_text+"</span>");
	}
}).on('click', '.label-new-tag', function(){
	var tag_type = document.getElementById($(this).attr("id")).dataset.tagtype;
	var target_type = document.getElementById($(this).attr("id")).dataset.targettype;
	$(this).before("<input class='tag-input form-control' id='input-new-tag' data-tagtype='"+tag_type+"' data-targettype='"+target_type+"' placeholder='Titre de l&apos;étiquette'>");
	$(".tag-input").focus();
}).on('focus', '.tag-input', function(){
	$(this).keyup(function(event){
		if(event.which == 13){
			var tag_type = document.getElementById($(this).attr("id")).dataset.tagtype;
			var target_type = document.getElementById($(this).attr("id")).dataset.targettype;
			var tag_name = $(this).val();
			createTag(tag_name, tag_type, target_type);
		} else if(event.which == 27){
			$(".tag-input").remove();
		}
	})
}).on('click', '.color-cube', function(e){
	// Assign a color to a tag
	e.stopPropagation();
	var cube = $(this);
	var target = document.getElementById(cube.attr("id")).dataset.target;
	var tag_type = document.getElementById($(this).attr("id")).dataset.tagtype;
	var value = /([a-z0-9]+)/i.exec(cube.css("backgroundColor"));
	var table = "tags_"+tag_type;
	$.when(updateColumn(table, "tag_color", value[0], target)).done(function(data){
		$("#tag-"+target).css("background-color", "#"+value[0]);
		$(".color-cube").empty();
		cube.append("<span class='glyphicon glyphicon-ok color-selected'></span>");
	})
}).on('click', '.btn-tag-name', function(){
	var target = $("#edit-tag-name").data().target;
	var value = $("#edit-tag-name").val();
	var table = "tags_"+$("#edit-tag-name").data().tagtype;
	console.log(table, value, target);
	$.when(updateColumn(table, "rank_name", value, target)).done(function(data){
		$("#tag-"+target).text(value);
	})
}).on('click', '.delete-tag', function(){
	$(".sub-modal").hide(0);
	var target = $("#delete-tag").data().target;
	var table = "tags_"+$("#edit-tag-name").data().tagtype;
	$.when(deleteEntry(table, target)).done(function(){
		$("#edit-"+target).remove();
		$("#tag-"+target).remove();
		$("#mid-"+target).remove();
	})
}).on('click', '.mid-button', function(){
	var clicked = $(this);
	var target = document.getElementById($(this).attr("id")).dataset.target;
	if($(this).hasClass("glyphicon-button-disabled")){
		var value = 1;
		$.when(updateColumn("tags_user", "missing_info_default", value, target)).done(function(data){
			$(".glyphicon-button-enabled").each(function(){
				var deactivate = $(this);
				var target = document.getElementById($(this).attr("id")).dataset.target;
				var value = 0;
				$.when(updateColumn("tags_user", "missing_info_default", value, target)).done(function(data){
					deactivate.removeClass("glyphicon-button-enabled");
					deactivate.addClass("glyphicon-button-disabled");
				})
			})
			clicked.removeClass("glyphicon-button-disabled");
			clicked.addClass("glyphicon-button-enabled");
		})
	}
})

function fetchTags(tag_type){
	return $.get("functions/fetch_tags.php", {type : tag_type});
}

function displayTargetTags(data, target_type, tag_type){
	var tags = JSON.parse(data), addable = "", added = "", body = "";
	for(var i = 0; i < tags.length; i++){
		if(target_type == "task" || target_type == "page"){
			var compare = $("#"+target_type+"-"+/([0-9]+)/.exec(window.target)[0]).find(".label-salsabor");
			var label_class = "label-addable";
		} else {
			var compare = $(".label-salsabor");
			var label_class = "label-manual";
		}
		compare.each(function(){
			if(tags[i].rank_name == $(this).text()){
				addable = "toggled";
				added = "<span class='glyphicon glyphicon-ok float-right'></span>";
				return false;
			} else {
				addable = "";
				added = "";
			}
		})
		body += "<h4><span class='label col-xs-12 label-clickable "+label_class+" "+addable+"' id='tag-"+tags[i].rank_id+"' data-tag='"+tags[i].rank_id+"' data-targettype='"+target_type+"' data-tagtype='"+tag_type+"' style='background-color:"+tags[i].color+"'>"+tags[i].rank_name+added+"</span></h4>";
	}
	body += "<h4><span class='label col-xs-12 label-default label-clickable label-new-tag' id='label-new' data-targettype='"+target_type+"' data-tagtype='"+tag_type+"'>Créer une étiquette</span></h4>";
	return body;
}

function createTag(tag_name, tag_type, target_type){
	$.post("functions/create_tag.php", {name : tag_name, type : tag_type}).done(function(data){
		switch(top.location.pathname){
			case "/Salsabor/tags/users":
				var label_content = "<h4>";
				label_content += "<div class='col-sm-12' id='tagline-"+data+"'>";
				label_content += "<span class='label col-xs-4 label-clickable label-restyle' id='tag-"+data+"' data-tag='"+data+"' data-tagtype='user' style='background-color:a80139'>"+tag_name+"</span>";
				label_content += "<p class='col-xs-2'><span class='glyphicon glyphicon-pencil glyphicon-button glyphicon-button-alt trigger-sub' id='edit-"+data+"' data-subtype='edit-tag' data-tagtype='user' data-target='"+data+"' title='Editer l&apos;étiquette'></span></p>";
				label_content += "<p class='col-xs-4'><span class='glyphicon glyphicon-list-alt glyphicon-button glyphicon-button-alt mid-button glyphicon-button-disabled' id='mid-"+data+"' data-target='"+data+"' title='Indiquer l&apos;étiquette comme celle par défaut pour les tâches de type &apos;Informations manquantes&apos;'></span></p>";
				label_content += "<p class='col-xs-1'><span class='glyphicon glyphicon-trash glyphicon-button glyphicon-button-alt' id='delete-tag-"+data+"' data-toggle='modal' data-target='#delete-modal' data-entry='"+data+"' data-table='tags_user' data-delete='#tagline-"+data+"' data-title='Supprimer l&apos;étiquette "+tag_name+"'></span></p>";
				label_content += "</div>";
				label_content += "</h4>";
				$(".tag-input").remove();
				$(".new-label-space").before(label_content);
				break;

			case "/Salsabor/tags/sessions":
				var label_content = "<h4>";
				label_content += "<div class='col-sm-12' id='tag-"+data+"'>";
				label_content += "<span class='label col-xs-4 label-clickable label-restyle' id='tag-"+data+"' data-tag='"+data+"' data-tagtype='session' style='background-color:a80139'>"+tag_name+"</span>";
				label_content += "<p class='col-xs-2'><span class='glyphicon glyphicon-pencil glyphicon-button glyphicon-button-alt trigger-sub' id='edit-"+data+"' data-subtype='edit-tag' data-target='"+data+"' data-tagtype='session' title='Editer l&apos;étiquette'></span></p>";
				label_content += "<p class='col-xs-1'><span class='glyphicon glyphicon-trash glyphicon-button glyphicon-button-alt' id='delete-tag-"+data+"' data-toggle='modal' data-target='#delete-modal' data-entry='"+data+"' data-table='tags_session' data-delete='#tagline-"+data+"' data-title='Supprimer l&apos;étiquette "+tag_name+"'></span></p>";
				label_content += "</div>";
				label_content += "</h4>";
				$(".tag-input").remove();
				$(".new-label-space").before(label_content);
				break;

			default:
				$(".tag-input").replaceWith("<h4><span class='label col-xs-12 label-salsabor label-clickable label-addable' id='tag-"+data+"' data-tag='"+data+"' data-targettype='"+target_type+"' data-tagtype='"+tag_type+"'>"+tag_name+"</span></h4>");
				break;
		}
	})
}

function attachTag(tag, target, target_type){
	return $.post("functions/attach_tag.php", {tag : tag, target : target, type : target_type});
}

function detachTag(tag, target, target_type){
	return $.post("functions/detach_tag.php", {tag : tag, target : target, type : target_type});
}

function createTagsArray(){
	var tags = [];
	$(".label-salsabor").each(function(){
		tags.push($(this).text());
	})
	return tags;
}

function updateTargetTags(initial_tags, current_tags, entry_id, entry_type){
	// Once the update is called on an entity, this function is called to compare the initial_tags array with the update_tags array. It will then attach or detach tags as needed.
	// WARNING : The code below, though very effective, is borderline intended by the developers of jQuery. If something breaks when updating to a newer version of jQuery (> 2.1.4), please see here first.
	var to_be_detached = $(initial_tags).not(current_tags).get();
	var to_be_attached = $(current_tags).not(initial_tags).get();
	for(var i = 0; i < to_be_detached.length; i++){
		detachTag(to_be_detached[i], entry_id, entry_type);
		console.log("detaching tag "+to_be_detached[i]+" from "+entry_type+" "+entry_id);
	}
	for(var i = 0; i < to_be_attached.length; i++){
		attachTag(to_be_attached[i], entry_id, entry_type);
		console.log("attaching tag "+to_be_attached[i]+" to "+entry_type+" "+entry_id);
	}
}
