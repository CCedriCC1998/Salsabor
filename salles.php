<?php
session_start();
if(!isset($_SESSION["username"])){
	header('location: portal');
}
require_once 'functions/db_connect.php';
$db = PDOFactory::getConnection();
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Salles | Salsabor</title>
		<?php include "styles.php";?>
		<?php include "scripts.php";?>
		<script src="assets/js/circle-progress.js"></script>
	</head>
	<body>
		<?php include "nav.php";?>
		<div class="container-fluid">
			<div class="row">
				<?php include "side-menu.php";?>
				<div class="col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
					<legend><span class="glyphicon glyphicon-pushpin"></span> Salles
					</legend>
					<div id="rooms-list" class="container-fluid">
					</div>
				</div>
			</div>
		</div>
		<?php include "inserts/sub_modal_product.php";?>
		<?php include "inserts/edit_modal.php";?>
		<?php include "inserts/delete_modal.php";?>
		<script>
			$(document).ready(function(){
				$.get("functions/fetch_rooms.php").done(function(data){
					var rooms = JSON.parse(data);
					var contents = "", previousLocation = -1;
					for(var i = 0; i < rooms.length; i++){
						if(rooms[i].location_id != previousLocation){
							if(i != 0){
								contents += constructNewPanel(previousLocation);
								// Close the row
								contents += "</div>";
							}
							contents += "<div class='row row-location' id='row-location-"+rooms[i].location_id+"'>";
							contents += "<p class='sub-legend col-xs-11 modal-editable-"+rooms[i].location_id+"' id='location-name-"+rooms[i].location_id+"' data-field='location_name' data-name='Nom' data-placeholder='false'>"+rooms[i].location_name+"</p>";
							contents += "<span class='col-xs-1 glyphicon glyphicon-pencil glyphicon-button glyphicon-button-big glyphicon-button-alt' data-toggle='modal' data-target='#edit-modal' data-entry='"+rooms[i].location_id+"' data-table='locations' title='Modifier la location "+rooms[i].location_name+"'></span>";

							// Address
							if(rooms[i].location_address == null || rooms[i].location_address == ""){
								var address = "-";
								var is_placeholder = true;
							} else {
								var address = rooms[i].location_address;
								var is_placeholder = false;
							}
							contents += "<div class='col-xs-8 col-sm-5'>";
							contents += "<span class='glyphicon glyphicon-home glyphicon-description'></span>";
							contents += "<p class='modal-editable-"+rooms[i].location_id+"' id='location-address-"+rooms[i].location_id+"' data-field='location_address' data-name='Adresse' data-placeholder='false'>"+address+"</p>";
							contents += "</div>";


							// Phone number
							if(rooms[i].location_telephone == null || rooms[i].location_telephone == ""){
								var phone_number = "-";
								var is_placeholder = true;
							} else {
								var phone_number = rooms[i].location_telephone;
								var is_placeholder = false;
							}
							contents += "<div class='col-xs-4 col-sm-7'>";
							contents += "<span class='glyphicon glyphicon-earphone glyphicon-description'></span>";
							contents += "<p class='modal-editable-"+rooms[i].location_id+"' id='location-telephone-"+rooms[i].location_id+"' data-field='location_telephone' data-name='Téléphone' data-placeholder='"+is_placeholder+"'>"+phone_number+"</p>";
							contents += "</div>";

						}
						if(rooms[i].room_id != null){
							contents += constructRoomPanel(rooms[i]);
						}
						previousLocation = rooms[i].location_id;
						if(i == rooms.length -1){
							contents += constructNewPanel(rooms[i].location_id);
							// Close the row
							contents += "</div>";
						}
					}
					contents += "<div class='panel-heading panel-add-record container-fluid'>";
					contents += "<div class='col-sm-1'><div class='notif-pp empty-pp'></div></div>";
					contents += "<div class='col-sm-11 new-task-text'>Ajouter un nouveau lieu</div>";
					contents += "</div></div>";
					$("#rooms-list").append(contents);
				})
			}).on('click', '.status-new', function(){
				var parent = $(this).parent();
				var location = document.getElementById($(this).attr("id")).dataset.location;
				parent.before(constructEmptyPanel(location));
				$("#new-name").focus();
			}).on('click', '.create-room', function(){
				var location = document.getElementById($(this).attr("id")).dataset.location;
				var name = $("#new-name").val();
				$.post("functions/add_room.php", {room_location : location, room_name : name}).done(function(data){
					var new_room = {room_id : data, room_name : name};
					$(".status-pre-success").parent().replaceWith(constructRoomPanel(new_room));
				})
			}).on('blur', '#new-name', function(){
				if($(this).val() == ""){
					$(".status-pre-success").parent().remove();
				}
			}).on('click', '.panel-add-record', function(){
				$(this).before("<input type='text' class='form-control' id='new-location'>");
				$("#new-location").focus();
			}).on('blur', '#new-location', function(){
				var name = $("#new-location").val();
				if(name != ""){
					$.post("functions/add_location.php", {location_name : name}).done(function(data){
						var new_location = "<p class='sub-legend editable' id='location-name-"+data+"' data-input='text' data-table='locations' data-column='location_name' data-target='"+data+"' data-value='value'>"+name+"</p>";
						new_location += "<p class='editable' id='location-address-"+data+"' data-input='text' data-table='locations' data-column='location_address' data-target='"+data+"' data-value='no-value'>Ajouter une adresse</p>";
						new_location += "<div class='row'>";
						new_location += constructNewPanel(data);
						new_location += "</div>";
						$("#new-location").replaceWith(new_location);
					})
				} else {
					$("#new-location").remove();
				}
			}).on('click', '.color-cube', function(e){
				// Assign a color to a room
				e.stopPropagation();
				var cube = $(this);
				var target = document.getElementById(cube.attr("id")).dataset.target;
				var color_id = document.getElementById(cube.attr("id")).dataset.color;
				var value = /([a-z0-9]+)/i.exec(cube.css("backgroundColor"));
				var table = "rooms";
				$.when(updateColumn(table, "room_color", color_id, target)).done(function(data){
					$("#room-color-cube-"+target).css("background-color", "#"+value[0]);
					$(".color-cube").empty();
					cube.append("<span class='glyphicon glyphicon-ok color-selected'></span>");
				})
			})

			function constructRoomPanel(room){
				var contents = "";
				contents += "<div class='col-xs-12 col-md-6 col-lg-6' id='room-"+room.room_id+"'>";
				if(room.availability == 0){
					var availability_class = "status-over";
					var status = room.current_session+" (jusqu'à "+moment(room.current_end).format("HH:mm")+")";
					var trash_class = "glyphicon-button-disabled not-allowed";
					var trash_title = "Vous ne pouvez pas supprimer une salle occupée";
				} else if(room.availability == 0.5){
					var availability_class = "status-partial-success";
					var status = room.next_session+" (à partir de "+moment(room.next_start).format("HH:mm")+")";
					var trash_class = "glyphicon-button-disabled not-allowed";
					var trash_title = "Vous ne pouvez pas supprimer une salle occupée";
				} else {
					var availability_class = "status-success";
					var status = "Disponible";
					var trash_class = "glyphicon-button";
					var trash_title = "Supprimer la salle "+room.room_name;
				}
				contents += "<div class='panel panel-item panel-room "+availability_class+"'>";
				contents += "<div class='panel-body row'>";
				contents += "<div class='panel-title container-fluid'>";
				contents += "<div class='col-xs-1 room-rectangle trigger-sub' id='room-color-cube-"+room.room_id+"' data-subtype='room-color' data-target='"+room.room_id+"' style='background-color:#"+room.room_color+"' title='Couleur de la salle. Cliquez pour changer la couleur'></div>";
				contents += "<p class='col-xs-9 modal-editable-room-"+room.room_id+"' id='room-name-"+room.room_id+"' data-field='room_name' data-name='Nom' data-placeholder='false'>"+room.room_name+"</p>";
				contents += "<span class='glyphicon glyphicon-pencil col-xs-1 glyphicon-button glyphicon-button-alt' data-entry='room-"+room.room_id+"' data-toggle='modal' data-target='#edit-modal' data-table='rooms' title='Modifier "+room.room_name+"'></span>";
				contents += "<p class='col-xs-1'><span class='glyphicon glyphicon-trash "+trash_class+" glyphicon-button-alt' id='delete-"+room.room_id+"' data-toggle='modal' data-target='#delete-modal' data-entry='"+room.room_id+"' data-table='rooms' data-delete='#room-"+room.room_id+"' title='"+trash_title+"'></span></p>";
				contents += "</div>"; // panel-title
				contents += "<div class='container-fluid'>";
				contents += "<span class='glyphicon glyphicon-star col-xs-2'></span> ";
				contents += "<p class='col-xs-10 purchase-sub no-padding'>"+status+"</p>";
				contents += "</div>"; // container-fluid
				if(room.reader_token == null){
					var reader = "-";
					var is_placeholder = true;
				} else {
					var reader = room.reader_token;
					var is_placeholder = false;
				}
				contents += "<div class='container-fluid'>";
				contents += "<span class='glyphicon glyphicon-hdd col-xs-2'></span> <p class='col-xs-10 no-padding modal-editable-room-"+room.room_id+"' id='room-reader-"+room.room_id+"' data-field='room_reader' data-name='Lecteur' data-placeholder='"+is_placeholder+"'>"+reader+"</p>";
				contents += "</div>"; //container-fluid
				contents += "</div>"; //panel-body
				contents += "</div>"; //panel
				contents += "</div>"; //col-xs-12 col-md-6 col-lg-4
				return contents;
			}

			function constructNewPanel(location){
				var contents = "";
				contents += "<div class='col-xs-12 col-md-6 col-lg-4'>";
				contents += "<div class='panel panel-item panel-room status-new' id='new-"+location+"' data-location='"+location+"'>";
				contents += "<div class='panel-body'>";
				// Panel-title
				contents += "<div class='panel-title'>";
				contents += "<p class='col-xs-12'>Ajouter une salle à ce lieu</p>";
				contents += "</div>";
				contents += "</div>";
				contents += "</div>";
				contents += "</div>";
				return contents;
			}

			function constructEmptyPanel(location){
				var contents = "";
				contents += "<div class='col-xs-12 col-md-6 col-lg-4'>";
				contents += "<div class='panel panel-item panel-room status-pre-success'>";
				contents += "<div class='panel-body'>";
				contents += "<p class='panel-title'><input type='text' class='form-control' id='new-name'></p>";
				contents += "<p><span class='glyphicon glyphicon-hdd'></span> - </p>";
				contents += "<button class='btn btn-success btn-block create-room' id='create-"+location+"' data-location='"+location+"'>Ajouter</button>";
				contents += "</div>";
				contents += "</div>";
				contents += "</div>";
				return contents;
			}
		</script>
	</body>
</html>
