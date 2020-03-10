/*
Le fichier tools.js contient toutes les fonctions javascript qui peuvent être utilisés par plusieurs fichiers,
qu'elles soient les fonctions de notification, ou des fonctions plus utilitaires.
Dès que le document est prêt, tous les modaux et les fonctions qui doivent tourner de façon constantes sont lancées ici.
*/

$(document).ready(function(){
	jQuery.expr[':'].regex = function(elem, index, match) {
		var matchParams = match[3].split(','),
			validLabels = /^(data|css):/,
			attr = {
				method: matchParams[0].match(validLabels) ?
				matchParams[0].split(':')[0] : 'attr',
				property: matchParams.shift().replace(validLabels,'')
			},
			regexFlags = 'ig',
			regex = new RegExp(matchParams.join('').replace(/^s+|s+$/g,''), regexFlags);
		return regex.test(jQuery(elem)[attr.method](attr.property));
	}
	$('[data-toggle="tooltip"]').tooltip();
	if(top.location.pathname !== "/Salsabor/my/profile" && top.location.pathname !== "/Salsabor/notifications/settings"){
		$.cssHooks.backgroundColor = {
			get: function(elem) {
				if (elem.currentStyle)
					var bg = elem.currentStyle["backgroundColor"];
				else if (window.getComputedStyle)
					var bg = document.defaultView.getComputedStyle(elem, null).getPropertyValue("background-color");
				if (bg.search("rgb") == -1)
					return bg;
				else {
					bg = bg.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
					function hex(x) {
						return ("0" + parseInt(x).toString(16)).slice(-2);
					}
					return "#" + hex(bg[1]) + hex(bg[2]) + hex(bg[3]);
				}
			}
		}
	}

	var firstCount = 0; // Pour éviter la notification dès le rafraîchissement de la page.
	window.numberProduits = 1; // Articles dans le panier
	notifCoursParticipants(firstCount);
	notifEcheancesDues(firstCount);
	notifPanier();
	setInterval(notifCoursParticipants, 30000);
	setInterval(notifEcheancesDues, 30000);
	badgeNotifications();
	badgeTasks();
	moment.locale("fra");

	// Construit le tableau d'inputs obligatoires par formulaire
	var mandatories = [];
	$(".mandatory").each(function(){
		$(this).prev("label").append(" <span class='span-mandatory' title='Ce champ est obligatoire'>*</span>");
		var inputName = $(this).attr('name');
		mandatories.push(inputName);
		$(this).parent().addClass('has-feedback');
		$(this).parent().append("<span class='glyphicon form-control-feedback'></span>");
		if($(this).html() != '' || $(this).val() != ''){
			$(this).parent().addClass('has-success');
			$(this).next("span").addClass('glyphicon-ok');
		}
	}).on('focus keyup change blur', function(){
		if($(this).html() != '' || $(this).val() != ''){
			$(this).parent().removeClass('has-error');
			$(this).parent().addClass('has-success');
			$(this).next("span").removeClass('glyphicon-remove');
			$(this).next("span").addClass('glyphicon-ok');
		} else {
			$(this).parent().removeClass('has-success');
			$(this).parent().addClass('has-error');
			$(this).next("span").removeClass('glyphicon-ok');
			$(this).next("span").addClass('glyphicon-remove');
		}
		var j = 0;
		for(var i = 0; i < mandatories.length; i++){
			if($("[name="+mandatories[i]+"]").val() != '' || $("[name="+mandatories[i]+"]").html() != ''){
				j++; // Incrémente le compteur d'input remplis et vide les éventuels messages d'erreurs indiquant que le champ est obligatoire
				$(this).next().children('p').empty();
			} else {
				// Affiche un message indiquant que le champ est obligatoire
				$(this).next().children('p').html("Ce champ est requis");
			}
		}
		// Si tous les inputs sont remplis, alors on autorise la soumission du formulaire
		if(j == mandatories.length){
			$("#submit-button").prop('disabled', false);
			$(".submit-button").prop('disabled', false);
		} else {
			$("#submit-button").prop('disabled', true);
			$(".submit-button").prop('disabled', false);
		}
	});

	// Filtre dynamique
	var $rows = $('#filter-enabled tr');
	$('#search').keyup(function(){
		var val = $.trim($(this).val()).replace(/ +/g, ' ').toLowerCase();
		$rows.show().filter(function(){
			var text = $(this).text().replace(/\s+/g, ' ').toLowerCase();
			return !~text.indexOf(val);
		}).hide();
	});

	// Vérification de l'existence d'un utilisateur dans la base
	$(".has-check").on('blur keyup focus',function(){
		var field = $(this);
		var identite = $(this).val();
		var token = $(this).attr('name').substr(12);
		$.post("functions/check_adherent.php", {identite : identite}).done(function(data){
			console.log(data);
			if(data == 0){
				if($(":regex(id,^unknown-user)").length == 0){
					var addOptions = "<div id='unknown-user"+token+"'>";
					addOptions += "<p>Aucun résultat. Voulez vous inscrire cet adhérent ?</p>";
					addOptions += "<a href='#user-details"+token+"' role='button' class='btn btn-info btn-block' value='create-user' id='create-user"+token+"' data-toggle='collapse' aria-expanded='false' aria-controls='userDetails'>Ouvrir le formulaire de création</a>";
					addOptions += "<div id='user-details"+token+"' class='collapse'>";
					addOptions += "<div class='well'>";
					addOptions += "<div class='row'>";
					addOptions += "<div class='col-lg-6'>";
					addOptions += "<div class='form-group'>";
					addOptions += "<label class='control-label'>Prénom</label><input type='text' name='identite_prenom' id='identite_prenom' class='form-control input-lg' placeholder='Prénom'>";
					addOptions += "</div>"; /*form-group*/
					addOptions += "</div>"; /*col-lg-6*/
					addOptions += "<div class='col-lg-6'>";
					addOptions += "<div class='form-group'>";
					addOptions += "<label class='control-label'>Nom</label><input type='text' name='identite_nom' id='identite_nom' class='form-control input-lg' placeholder='Nom'>";
					addOptions += "</div>"; /*form-group*/
					addOptions += "</div>"; /*col-lg-6*/
					addOptions += "</div>"; /*row*/
					addOptions += "<div class='row'>";
					addOptions += "<div class='col-lg-6'>";
					addOptions += "<div class='form-group'>";
					addOptions += "<label class='control-label'>Adresse postale</label><input type='text' name='rue' id='rue' placeholder='Adresse' class='form-control input-lg'>";
					addOptions += "</div>"; /*form-group*/
					addOptions += "</div>" /*col-lg-6*/
					addOptions += "<div class='col-lg-3'>";
					addOptions += "<div class='form-group'>";
					addOptions += "<label class='control-label'>Code postal</label><input type='number' name='code_postal' id='code_postal' placeholder='Code Postal' class='form-control input-lg'>";
					addOptions += "</div>"; /*form-group*/
					addOptions += "</div>"; /*col-lg-3*/
					addOptions += "<div class='col-lg-3'>";
					addOptions += "<div class='form-group'>";
					addOptions += "<label class='control-label'>Ville</label><input type='text' name='ville' id='ville' placeholder='Ville' class='form-control input-lg'>";
					addOptions += "</div>"; /*form-group*/
					addOptions += "</div>"; /*col-lg-6*/
					addOptions += "</div>"; /*row*/
					addOptions += "<div class='row'>";
					addOptions += "<div class='col-lg-6'>";
					addOptions += "<div class='form-group'>";
					addOptions += "<label for='text' class='control-label'>Adresse mail</label><input type='email' name='mail' id='mail' placeholder='Adresse mail' class='form-control input-lg'>";
					addOptions += "</div>"; /*form-group*/
					addOptions += "</div>"; /*col-lg-6*/
					addOptions += "<div class='col-lg-6'>";
					addOptions += "<div class='form-group'>";
					addOptions += "<label for='telephone' class='control-label'>Numéro de téléphone</label><input type='tel' name='telephone' id='telephone' placeholder='Numéro de téléphone' class='form-control input-lg'>";
					addOptions += "</div>"; /*form-group*/
					addOptions += "</div>"; /*col-lg-6*/
					addOptions += "</div>"; /*row*/
					addOptions += "<p class='help-block'><span class='glyphicon glyphicon-warning-sign'></span> Par défaut, la date d'inscription est celle du jour. Si vous voulez la modifier, n'oubliez pas de vous rendre sur le profil de l'utilisateur après la transaction</p>";
					addOptions += "<a class='btn btn-primary btn-block' onClick='addAdherent()'>Inscrire l'adhérent</a>";
					addOptions += "</div>"; /*well*/
					addOptions += "</div>"; /*collapse*/
					addOptions += "</div>"; /*unknown-user*/
					field.after(addOptions);
				}
			} else {
				$(":regex(id,^unknown-user)").remove();
				$(".has-name-completion:not(.completed)").val(identite);
			}
		})
	})

	$('.separate-scroll').on('DOMMouseScroll mousewheel', function(ev) {
		var $this = $(this),
			scrollTop = this.scrollTop,
			scrollHeight = this.scrollHeight,
			height = $this.height(),
			delta = (ev.type == 'DOMMouseScroll' ?
					 ev.originalEvent.detail * -40 :
					 ev.originalEvent.wheelDelta),
			up = delta > 0;

		var prevent = function() {
			ev.stopPropagation();
			ev.preventDefault();
			ev.returnValue = false;
			return false;
		}

		if (!up && -delta > scrollHeight - height - scrollTop) {
			// Scrolling down, but this will take us past the bottom.
			$this.scrollTop(scrollHeight);
			return prevent();
		} else if (up && delta > scrollTop) {
			// Scrolling up, but this will take us past the top.
			$this.scrollTop(0);
			return prevent();
		}
	});
}).delegate('*[data-toggle="lightbox"]', 'click', function(event) {
	event.preventDefault();
	return $(this).ekkoLightbox({
		onNavigate: false
	});
}).on('click', '.submit-relay', function(){
	$(".submit-relay-target").click();
}).on('focus', '.name-input', function(){
	var filter = $(this).data('filter');
	console.log(filter);
	provideAutoComplete($(this), filter);
}).on('click', '.sub-modal-close', function(){
	$(".sub-modal").toggle();
}).on('click', '.trigger-sub', function(e){
	e.stopPropagation();
	$(".sub-modal").hide(0);
	$(".sub-modal-body").empty();
	var target = document.getElementById($(this).attr("id"));
	var tpos = $(this).position(), type = target.dataset.subtype, toffset = $(this).offset();
	/*console.log(product_id, type);*/

	console.log(document.getElementById($(this).attr("id")));
	var title, body = "", footer = "";
	switch(type){
		case 'AREP':
			var product_id = target.dataset.argument;
			title = "Prolonger";
			body += "<input type='text' class='form-control datepicker'/>";
			footer += "<button class='btn btn-success extend-product' data-argument='"+product_id+"' id='btn-sm-extend'>Prolonger</button>";
			if(moment(target.dataset.arep).isValid()){
				footer += "<button class='btn btn-danger float-right btn-arep' data-argument='"+product_id+"' id='btn-sm-unextend'>Annuler AREP</button>";
				var options = {
					format: "DD/MM/YYYY",
					inline: true,
					locale: "fr",
					defaultDate: moment(target.dataset.arep)
				};
			} else {
				var options = {
					format: "DD/MM/YYYY",
					inline: true,
					locale: "fr"
				};
			}
			$(".sub-modal").css({
				top : tpos.top+136+'px',
				left: toffset.left+'px'});
			$(".sub-modal-body").html(body);
			break;

		case 'activate':
			var product_id = target.dataset.argument;
			title = "Activer";
			body += "<input type='text' class='form-control datepicker'/>";
			footer += "<button class='btn btn-success activate-product' data-argument='"+product_id+"' id='btn-sm-activate'>Activer</button>";
			$(".sub-modal").css({top : tpos.top+51+'px'});
			$(".sub-modal-body").html(body);
			var options = {
				format: "DD/MM/YYYY",
				inline: true,
				locale: "fr"
			};
			break;

		case 'deadline':
			var task_id = target.dataset.task;
			title = "Date limite";
			body += "<input type='text' class='form-control datepicker'/>";
			footer += "<button class='btn btn-success task-deadline' data-task='"+task_id+"' id='btn-set-deadline'>Définir</button>";
			$(".sub-modal").css({top : toffset.top+25+'px', left : toffset.left+15+'px'});
			$(".sub-modal-body").html(body);
			var options = {
				format: "DD/MM/YYYY HH:mm",
				inline: true,
				locale: "fr",
				stepping: 15
			};
			break;

		case 'set-participation-product':
			title = "Changer le produit à utiliser";
			var participation_id = target.dataset.participation;
			var token = {};
			token["participation_id"] = participation_id;
			$.when(fetchProducts($.param(token))).done(function(data){
				var construct = displayEligibleProducts(data);
				$(".sub-modal-body").html(construct);
			})
			footer += "<button class='btn btn-success set-participation-product' id='btn-set-participation-product' data-participation='"+participation_id+"'>Reporter</button>";
			footer += " <button class='btn btn-default btn-modal set-participation-product' id='btn-product-null-record' data-participation='"+participation_id+"'><span class='glyphicon glyphicon-link'></span> Retirer</button>";
			$(".sub-modal").css({top : toffset.top+'px'});
			if(toffset.left > 1000){
				$(".sub-modal").css({left : toffset.left-350+'px'});
			} else {
				$(".sub-modal").css({left : toffset.left+20+'px'});
			}
			break;

		case 'change-participation':
			title = "Changer le cours associé";
			var participation_id = target.dataset.argument;
			$.when(fetchEligibleSessions(participation_id)).done(function(data){
				console.log(data);
				var construct = displayTargetSessions(data);
				$(".sub-modal-body").html(construct);
			})
			footer += "<button class='btn btn-success report-participation' id='btn-session-changer-record' data-participation='"+participation_id+"'>Changer</button>";
			$(".sub-modal").css({top : toffset.top+'px'});
			if(toffset.left > 1000){
				$(".sub-modal").css({left : toffset.left-350+'px'});
			} else {
				$(".sub-modal").css({left : toffset.left+20+'px'});
			}
			break;

		case 'delete':
			title = "Supprimer une participation";
			var participation_id = target.dataset.argument;
			body += "Êtes-vous sûr de vouloir supprimer cette participation ?";
			$(".sub-modal-body").html(body);
			footer += "<button class='btn btn-danger delete-participation col-lg-6' id='btn-product-delete' data-session='"+participation_id+"'><span class='glyphicon glyphicon-trash'></span> Supprimer</button><button class='btn btn-default col-lg-6'>Annuler</button>";
			if(toffset.left > 1000){
				$(".sub-modal").css({left : toffset.left-350+'px'});
			} else {
				$(".sub-modal").css({left : toffset.left+20+'px'});
			}
			break;

		case 'delete-product':
			title = "Supprimer un produit";
			var product_id = target.dataset.product;
			body += "ATTENTION : Si ce produit est seul dans une transaction, la transaction sera supprimée avec ce produit. Une fois validée, cette opération destructrice est irréversible. Êtes-vous sûr de vouloir supprimer ce produit ?";
			footer += "<button class='btn btn-danger delete-product col-lg-6' id='btn-product-delete' data-product='"+product_id+"' data-dismiss='modal'><span class='glyphicon glyphicon-trash'></span> Supprimer</button><button class='btn btn-default col-lg-6'>Annuler</button>";
			$(".sub-modal").css({top : tpos.top+51+'px'});
			$(".sub-modal-body").html(body);
			break;

		case 'unlink':
			title = "Délier une participation";
			var participation_id = target.dataset.argument;
			body += "Êtes vous sûr de vouloir délier cette participation ? Vous la retrouverez dans les passages non régularisés";
			$(".sub-modal-body").html(body);
			footer += "<button class='btn btn-default unlink-session col-lg-6' id='btn-product-unlink' data-session='"+participation_id+"'><span class='glyphicon glyphicon-link'></span> Délier</button> <button class='btn btn-default col-lg-6'>Annuler</button>";
			if(toffset.left > 1000){
				$(".sub-modal").css({left : toffset.left-350+'px'});
			} else {
				$(".sub-modal").css({left : toffset.left+20+'px'});
			}
			break;

		case 'receive-maturity':
			var maturity_id = target.dataset.maturity;
			var method = $("#maturity-"+maturity_id+"-method>span").first().text();
			title = "Réception de l'échéance";
			body += "<input type='text' class='form-control datepicker reception-date'/>";
			body += "<label class='control-label'>Méthode de paiement</label>";
			body += "<input type='text' class='form-control reception-method' value='"+method+"'></input>";
			footer += "<button class='btn btn-success receive-maturity' data-maturity='"+maturity_id+"' id='btn-sm-receive'>Enregistrer</button>";
			footer += "<button class='btn btn-danger cancel-reception float-right' data-maturity='"+maturity_id+"' id='btn-sm-cancel-receive'>Annuler réception</button>";
			if($("#reception-span-"+maturity_id).text() != ""){
				var default_date = moment($("#reception-span-"+maturity_id).text(), "DD/MM/YYYY");
			} else {
				var default_date = null;
			}
			$(".sub-modal").css({top : toffset.top+'px'});
			$(".sub-modal").css({left : toffset.left-200+'px'});
			$(".sub-modal-body").html(body);
			var options = {
				format: "DD/MM/YYYY",
				defaultDate: default_date,
				inline: true,
				locale: "fr"
			};
			break;

		case 'bank-maturity':
			var maturity_id = target.dataset.maturity;
			title = "Encaissement de l'échéance";
			body += "<input type='text' class='form-control datepicker bank-date'/>";
			footer += "<button class='btn btn-success bank-maturity' data-maturity='"+maturity_id+"' id='btn-sm-receive'>Recevoir</button>";
			footer += "<button class='btn btn-danger cancel-bank float-right' data-maturity='"+maturity_id+"' id='btn-sm-cancel-bank'>Annuler encaissement</button>";
			if($("#bank-span-"+maturity_id).text() != ""){
				var default_date = moment($("#bank-span-"+maturity_id).text(), "DD/MM/YYYY");
			} else {
				var default_date = null;
			}
			$(".sub-modal").css({top : tpos.top+51+'px'});
			$(".sub-modal-body").html(body);
			var options = {
				format: "DD/MM/YYYY",
				defaultDate: default_date,
				inline: true,
				locale: "fr"
			};
			break;

		case 'deadline-maturity':
			var maturity_id = target.dataset.maturity;
			title = "Modifier la date limite";
			body += "<input type='text' class='form-control datepicker deadline-date'/>";
			footer += "<button class='btn btn-success deadline-maturity' data-maturity='"+maturity_id+"' id='btn-sm-deadline'>Enregistrer</button>";
			$(".sub-modal").css({top : tpos.top+51+'px'});
			$(".sub-modal-body").html(body);
			if($("#deadline-maturity-span-"+maturity_id).text() != ""){
				var default_date = moment($("#deadline-maturity-span-"+maturity_id).text(), "DD/MM/YYYY");
			} else {
				var default_date = null;
			}
			var options = {
				format: "DD/MM/YYYY",
				defaultDate : default_date,
				inline: true,
				locale: "fr"
			};
			break;

		case 'user-tags':
		case 'session-tags':
			var target_type = document.getElementById($(this).attr("id")).dataset.targettype;
			var tag_type = /^([a-z]+)/i.exec(type);
			window.target = $(this).attr("id");
			title = "Ajouter une étiquette";
			$(".sub-modal").removeClass("col-lg-7");
			$(".sub-modal").addClass("col-lg-3");
			if(top.location.pathname === "/Salsabor/dashboard"){
				$(".sub-modal").css({top : toffset.top+25+'px', left: toffset.left-25+'px'});
			} else {
				$(".sub-modal").css({top : toffset.top+25+'px', left: toffset.left+25+'px'});
			}
			$.when(fetchTags(tag_type[0])).done(function(data){
				var construct = displayTargetTags(data, target_type, tag_type[0]);
				$(".sub-modal-body").html(construct);
			})
			break;

		case 'edit-tag':
			var target = document.getElementById($(this).attr("id")).dataset.target;
			var tag_type = document.getElementById($(this).attr("id")).dataset.tagtype;
			var initialValue = $("#tag-"+target).text();
			title = "Modifier une étiquette";
			$(".sub-modal").removeClass("col-lg-7");
			$(".sub-modal").addClass("col-lg-3");
			$(".sub-modal").css({top : toffset.top+'px', left: toffset.left+45+'px'});
			body += "<div class='input-group'>";
			body += "<input type='text' class='form-control' id='edit-tag-name' data-target='"+target+"' data-tagtype='"+tag_type+"' placeholder='Nom de l&apos;étiquette' value='"+initialValue+"'>";
			body += "<span class='input-group-btn'><button class='btn btn-success btn-tag-name' type='button'>Valider</button></span>";
			body += "</div>";
			$.when(fetchColors()).done(function(data){
				body += "<div class='container-fluid' id='colors'>";
				var colors = JSON.parse(data);
				for(var i = 0; i < colors.length; i++){
					body += "<div class='color-cube col-xs-3 col-md-3 col-lg-2' id='color-"+colors[i].color_id+"' style='background-color:"+colors[i].color_value+"' data-target='"+target+"'  data-tagtype='"+tag_type+"'>";
					if("#"+colors[i].color_value == $("#tag-"+target).css("backgroundColor")){
						body += "<span class='glyphicon glyphicon-ok color-selected'></span>";
					}
					body += "</div>";
				}
				body += "</div>";
				if(tag_type == "session"){
					var is_mandatory;
					if($("#tag-"+target).find(".glyphicon-star").length > 0){
						is_mandatory = 1;
					} else {
						is_mandatory = 0;
					}
					body += "<input name='is_mandatory' class='mandatory-tag-check' id='is_mandatory-"+target+"' data-target='"+target+"' value='"+is_mandatory+"'> Obligatoire <span class='glyphicon glyphicon-question-sign' id='mandatory-tooltip' data-toggle='tooltip' title='Restreint la compatibilité produit/cours aux étiquettes obligatoires sélectionnées. Si un produit doit être compatible avec une couleur entière, n&apos;ajoutez pas d&apos;étiquette.'></span>";
				}
				$(".sub-modal-body").html(body);
				$("#is_mandatory-"+target).checkboxX({
					threeState: false,
					size:'lg'
				});
				$("#mandatory-tooltip").tooltip();
			});
			break;

		case 'room-color':
			var target = document.getElementById($(this).attr("id")).dataset.target;
			title = "Modifier la colueur de la salle";
			$(".sub-modal").removeClass("col-lg-7");
			$(".sub-modal").addClass("col-lg-3");
			$(".sub-modal").css({top : toffset.top+'px', left: toffset.left+45+'px'});
			$.when(fetchColors()).done(function(data){
				body += "<div class='row' id='colors'>";
				var colors = JSON.parse(data);
				for(var i = 0; i < colors.length; i++){
					body += "<div class='color-cube col-xs-4 col-md-3 col-lg-2' id='color-"+colors[i].color_id+"' style='background-color:"+colors[i].color_value+"' data-target='"+target+"' data-color='"+colors[i].color_id+"'>";
					if("#"+colors[i].color_value == $("#room-color-cube-"+target).css("backgroundColor")){
						body += "<span class='glyphicon glyphicon-ok color-selected'></span>";
					}
					body += "</div>";
				}
				body += "</div>";
				$(".sub-modal-body").html(body);
			});
			break;

		default:
			title = "Sub modal";
			break;
	}
	$(".sub-modal-title").text(title);
	$(".sub-modal-footer").html(footer);
	$(".datepicker").datetimepicker(options);
	var re = /historique/i;
	if(re.exec(top.location.pathname) != null){
		console.log("Historique");
		$(".sub-modal").css({left: 74+'%'});
	}
	if($(".modal").length > 0){
		var sub_modal_classes = "col-xs-5 col-md-4 col-lg-3";
	} else {
		var sub_modal_classes = "col-xs-5 col-md-6 col-lg-3";
	}
	$(".sub-modal").addClass(sub_modal_classes);
	$(".sub-modal").show(0);
}).on('change', '.mandatory-tag-check', function(){
	var target = document.getElementById($(this).attr("id")).dataset.target;
	var value = $(this).val();
	console.log("checkbox of tag "+target+" changed to "+value);
	$.when(updateColumn("tags_session", "is_mandatory", value, target)).done(function(){
		if(value == 1)
			$("#tag-"+target).prepend("<span class='glyphicon glyphicon-star'></span> ");
		else
			$("#tag-"+target).remove($(".glyphicon-star"));

		logAction("tags_session", "Modification", target);
	})
}).on('click', '.sub-menu-toggle', function(){
	console.log("toggling");
	$(".small-sidebar-container").toggle();
}).on('show.bs.modal', '#edit-modal', function(event){
	/*
	Code responsible for the standard edition of every field. If you want some fields to be editable by this method, you need to do the following:
	== In general ==
		- Include the edit_modal.php file in the page you're planning to have editable things in

	== For the edit butotn ==
		- Add the data-entry to the dataset with the value database_entry_id and don't forget to link the modal by data-toggle='modal' and data-target='#edit-modal'.
		- Add the data-table as well to indicate which table has to be edited
		- If you need a secondary id to do additional things, add a data-secondary and add some logic below

	== For every editable field ==
		- For EVERY element of the entry you want to be editable, attach the class 'modal-editable-$' where $ is the database_entry_id.
		- For EVERY element then, add two fields in the dataset : data-field is the name of the field in database, data-name is the name that will display in the label of the form.
		- You can overwrite the deafult input type by adding a data-input as well.
		- Your field is empty? Set data-placeholder to true.

		Once you've done all this, you'll be set.
	*/
	event.stopPropagation();
	var entry_id = $(event.relatedTarget).data('entry'), secondary_id = $(event.relatedTarget).data('transaction'), table = $(event.relatedTarget).data('table'), modal = $(this);
	modal.find(".modal-title").text($(event.relatedTarget).attr('title'));

	// Constructing the form
	var edit_form = "<form class='form-horizontal' id='modal-form'>";

	// Form groups constructed from every editable field.
	$(".modal-editable-"+entry_id).each(function(){
		var element = $(this);
		var field_name = $(this).data("field"), name = $(this).data("name"), input_type = $(this).data('input'), is_placeholder = $(this).data('placeholder');
		edit_form += "<div class='form-group'>";
		edit_form += "<label for='"+field_name+"' class='col-lg-4 control-label'>"+name+"</label>";
		edit_form += "<div class='col-lg-8'>";

		// Overwriting default input_type (text)
		if(field_name == 'rate_ratio'){
			edit_form += '<select name="'+field_name+'" class="form-control">';
			if(element.text() == "heure")
				edit_form += '<option selected="selected" value="heure">heure</option>';
			else
				edit_form += '<option value="heure">heure</option>';

			if(element.text() == "personne")
				edit_form += '<option selected="selected" value="personne">personne</option>';
			else
				edit_form += '<option value="personne">personne</option>';

			if(element.text() == "prestation")
				edit_form += '<option selected="selected" value="prestation">prestation</option>';
			else
				edit_form += '<option value="prestation">prestation</option>';

			edit_form += '</select>';
		} else {
			if(input_type === undefined){
				edit_form += '<input type="text" class="form-control';
				if($(this).data('complete') !== undefined){
					console.log($(this).data('complete-filter'));
					edit_form += ' name-input" data-filter="'+$(this).data('complete-filter')+'"';
				} else {
					edit_form += '"';
				}
				edit_form += ' name="'+field_name+'"';
				if(is_placeholder){
					edit_form += 'placeholder="'+element.text()+'">';
				} else {
					edit_form += ' value="'+element.text()+'">';
				}
			} else {
				if(input_type == "textarea"){
					edit_form += "<textarea class='form-control' name='"+field_name+"'>"+element.text()+"</textarea>";
				}
			}
		}
		edit_form += "</div>";
		edit_form += "</div>";
	})

	edit_form += "</form>";
	modal.find(".edit-form-space").html(edit_form);

	// Binding the edit code to the update button
	modal.find(".send-edit-data").on('click', function(){
		var values = modal.find("#modal-form").serialize();
		console.log(entry_id);
		var m = /\D*/.exec(entry_id);
		if(m === null || m == "" || table == "transactions"){
			console.log("no chars");
			var real_entry_id = entry_id;
		} else {
			console.log("chars");
			var real_entry_id = entry_id.replace(/(\D*)/i, '');
		}
		console.log("obtained entry: "+real_entry_id);
		$.when(updateEntry(table, values, real_entry_id)).done(function(data){
			/*console.log(data);*/
			var updated_values = modal.find("#modal-form").serializeArray(), i = 0;
			// We find all the field again, they're in the same order as the array of values since it's how the form has been constructed.
			$(".modal-editable-"+entry_id).each(function(){
				if(updated_values[i].value != ""){
					$(this).text(updated_values[i].value);
					$(this).data('placeholder', false);
				} else {
					$(this).text('-');
					$(this).data('placeholder', true);
				}
				i++;
			})
			// Additional logic goes there
			if(table == 'produits_echeances'){
				showAmountDiscrepancy(secondary_id);
			}
			// End of additional logic
			modal.modal('hide');
			showNotification("Modifications enregistrées", "success");
		})
	})
}).on('hide.bs.modal', '#edit-modal', function(e){
	console.log("unbinding edit button");
	// End of additional logic
	$(this).find(".send-edit-data").off('click');
}).on('show.bs.modal', '#delete-modal', function(event){
	var entry_id = $(event.relatedTarget).data('entry'), table = $(event.relatedTarget).data('table'), to_delete = $(event.relatedTarget).data('delete'), modal = $(this);
	if(table == 'produits_echeances'){
		var transaction_id = $(event.relatedTarget).data('transaction');
	}
	modal.find(".modal-title").text($(event.relatedTarget).attr('title'));
	modal.find(".modal-body").text("Êtes-vous sûr de vouloir supprimer cette entrée ?");
	modal.find(".delete-target").on('click', function(){
		console.log(entry_id);
		$.when(deleteEntry(table, entry_id)).done(function(data){
			console.log(data);
			$(to_delete).remove();
			// Additional logic
			if(transaction_id){
				showAmountDiscrepancy(transaction_id);
			}
			// End of additional logic
			modal.modal('hide');
			showNotification("Suppression effectuée", "success");
		})
	})
}).on('hide.bs.modal', '#delete-modal', function(e){
	$(this).find(".delete-target").off('click');
}).on('show.bs.modal', '#archive-modal', function(e){
	var entry_id = $(e.relatedTarget).data('entry'), table = $(e.relatedTarget).data('table'), modal = $(this), button = $(e.relatedTarget);
	modal.find(".archive-data").on('click', function(){
		$.when(updateColumn(table, "archived", 1, entry_id)).done(function(data){
			$(".user-legend").append("<span class='archived-state'>(Archivé)</span>");
			if(top.location.pathname === "/Salsabor/regularisation/participations/all"){
				$("#participation-"+entry_id).remove();
				var current_count = $(".irregular-participations-title>span").text();
				$(".irregular-participations-title>span").text(--current_count);
			} else {
				button.replaceWith("<span class='col-xs-1 glyphicon glyphicon-folder-open glyphicon-button glyphicon-button-alt glyphicon-button-big dearchive-data' title='Désarchiver' data-entry='"+entry_id+"' data-table='users'></span>");
			}
			modal.modal('hide');
			logAction(table, "Archivage", entry_id);
			showNotification("Entrée archivée", "Success");
		})
	})
}).on('hide.bs.modal', '#archive-modal', function(e){
	$(this).find(".archive-data").off('click');
}).on('click', '.dearchive-data', function(){
	var entry_id = $(this).data('entry'), table = $(this).data('table');
	$.when(updateColumn(table, "archived", 0, entry_id)).done(function(data){
		$(".archived-state").empty();
		if(top.location.pathname === "/Salsabor/regularisation/participations/old"){
			$("#participation-"+entry_id).remove();
		} else {
			$(".dearchive-data").replaceWith("<span class='col-xs-1 glyphicon glyphicon-folder-close glyphicon-button glyphicon-button-alt glyphicon-button-big' title='Archiver' data-toggle='modal' data-target='#archive-modal' data-entry='"+entry_id+"' data-table='users'></span>");
		}
		logAction(table, "Désarchivage", entry_id);
		showNotification("Entrée désarchivée", "Success");
	})
}).on('click', '.selectable', function(){
	var selected = $(this);
	// Get the group
	var group_name = /([a-z]*-selectable)/gi.exec($(this).attr('class'))[0];

	// Remove the selected from the whole group
	$("."+group_name).removeClass("selected");

	// Add selected to the item clicked
	$(this).addClass("selected");

	// Cue it's selected (additional logic goes here)

}).on('loading', '.loading-container', function(){
	// Custom event to place the loading gif before displaying fetched data.
	if($(this).is(':empty')){
		$(this).append("<img src='assets/img/loading.gif' class='loading-indicator'>");
		console.log("Inserting loading gif in"+$(this));
	}
}).on('loaded', '.loading-container', function(){
	$(this).find('.loading-indicator').remove();
	/*$(this).remove(".loading-indicator");*/
}).on('keyup keypress', '.no-submit', function(e){
	var keyCode = e.keyCode || e.which;
	if (keyCode === 13) {
		e.preventDefault();
		$(document.activeElement).blur();
		return false;
	}
})

$(".has-name-completion").on('click blur keyup', function(){
	if($(this).val() != ""){
		$(this).addClass("completed");
	} else {
		$(this).removeClass("completed");
	}
})

// Surveille les participations à un cours non associés à un produit (abonnement, vente spontanée, invitation...)
function notifCoursParticipants(firstCount){
	$.post("functions/watch_participations.php").done(function(data){
		if(data == 0){
			$("#badge-participants").hide();
		} else {
			if(data > $("#badge-participations").html() && firstCount != 0){
				$.notify("Nouvelles participations non associées.", {globalPosition: "bottom right", className:"info"});
			}
			$("#badge-participations").show();
			$("#badge-participations").html(data);
			/*$(".irregular-participations-title>span").text(data);*/
		}
	})
}

// Surveille le nombre d'échéances qui ne sont pas réglées après leur date
function notifEcheancesDues(firstCount){
	$.post("functions/watch_maturities.php").done(function(data){
		if(data == 0){
			$("#badge-echeances").hide();
		} else {
			if(data > $("#badge-echeances").html() && firstCount != 0){
				$.notify("De nouvelles échéances ont dépassé leur date.", {globalPosition: "bottom right", className:"info"});
			}
			$("#badge-echeances").show();
			$("#badge-echeances").html(data);
		}
	})
}

function badgeTasks(){
	$.post("functions/watch_tasks.php").done(function(data){
		if(data == 0){
			$("#badge-tasks").hide();
		} else {
			$("#badge-tasks").show();
			$("#badge-tasks").html(data);
		}
		setTimeout(badgeTasks, 10000);
	})
}

function showNotification(message, notif_type){
	$.notify(message, {globalPosition: "bottom right", className:notif_type});
}

// FONCTIONS UTILITAIRES //
// Insert la date d'aujourd'hui dans un input de type date supportant la fonctionnalité
$("*[date-today='true']").click(function(){
	var today = new moment().format("YYYY-MM-DD");
	$(this).parent().prev().val(today);
	$(this).parent().prev().blur();
});

// Vérifie si un adhérent a des échéances impayées lors de la vente d'un forfait
function checkMaturities(data){
	$.post('functions/check_unpaid.php', {search_id : data}).done(function(maturities){
		if(maturities != 0){
			$('#err_adherent').empty();
			$('#unpaid').show();
			$("#maturities-checked").hide();
		} else {
			$('#err_adherent').empty();
			$('#unpaid').hide();
			$("#maturities-checked").show();
		}
	})
}

// Effectue une inscription rapide dans le cas d'un adhérent inexistant à la réservation d'une salle ou l'achat d'un forfait
function addAdherent(){
	var identite_prenom = $('#identite_prenom').val();
	var identite_nom = $('#identite_nom').val();
	var rfid = $("[name='rfid']").val();
	var rue = $('#rue').val();
	var code_postal = $('#code_postal').val();
	var ville = $('#ville').val();
	var mail = $('#mail').val();
	var telephone = $('#telephone').val();
	var date_naissance = $('#date_naissance').val();
	$.post("functions/add_adherent.php", {identite_prenom : identite_prenom, identite_nom : identite_nom, rfid : rfid, rue : rue, code_postal : code_postal, ville : ville, mail : mail, telephone : telephone, date_naissance : date_naissance}).done(function(data){
		console.log(data);
		var parse = JSON.parse(data);
		$(".has-name-completion:not(.completed)").val(identite_prenom+" "+identite_nom);
		if(window.miniCart != ""){
			window.miniCart["id_beneficiaire"] = parse["id"];
			window.miniCart["nom_beneficiaire"] = identite_prenom+" "+identite_nom;
		}
		showNotification(parse["success"], "success");
		$(":regex(id,^unknown-user)").hide('500');
	});
}

// Checks if holiday exists when attempting to create events on calendar
function checkHoliday(){
	var date_debut = $('#date_debut').val();
	$.post("functions/check_holiday.php", {date_debut : date_debut}).done(function(data){
		console.log(data);
		if(data != "0"){
			$("#holiday-alert").empty();
			$("#holiday-alert").append("Ce jour est chômé. Impossible d'ajouter une réservation à cette date.");
			$('.confirm-add').prop('disabled', true);
		} else {
			$('#holiday-alert').empty();
			$('.confirm-add').prop('disabled', false);
			checkCalendar(true, false);
		}
	});
}

function notifPanier(){
	if(sessionStorage.getItem("panier") != null){
		var cartSize = JSON.parse(sessionStorage.getItem("panier"));
		if(cartSize.length == 0){
			$("#badge-panier").hide();
			$(".table-panier").empty();
		} else {
			$("#badge-panier").show();
			$("#badge-panier").html(cartSize.length);
			fillShoppingCart();
		}
	}
}

function fillShoppingCart(){
	$(".table-panier").empty();
	if(sessionStorage.getItem("panier") != null){
		var cart = JSON.parse(sessionStorage.getItem("panier"));
		var cartSize = JSON.parse(sessionStorage.getItem("panier-noms"));
		var line = "";
		if(cart.length != 0){
			for(var i = 0; i < cartSize.length; i++){
				line += "<tr>"
				line += "<td class='col-lg-11'>"+cartSize[i]+"</td>";
				line += "<td class='col-lg-1'><span class='glyphicon glyphicon-trash glyphicon-button glyphicon-button-alt' onclick='removeCartElement("+i+")'></span></td>";
				line += "<tr>";
			}
			$(".table-panier").append(line);
			composeURL(cart[0]);
		}
	}
}

function removeCartElement(key){
	var cart = JSON.parse(sessionStorage.getItem("panier"));
	var cartNames = JSON.parse(sessionStorage.getItem("panier-noms"));
	cart.splice(key, 1);
	cartNames.splice(key, 1);
	sessionStorage.setItem("panier", JSON.stringify(cart));
	sessionStorage.setItem("panier-noms", JSON.stringify(cartNames));
	notifPanier();
}

// Prepares the URL when purchasing items
function composeURL(token){
	var url = "personnalisation.php?element=";
	url += token;
	url += "&order=0";
	$("[name='next']").attr('href', url);
	$("[name='previous']").attr('href', url);
}

// Adds a row
function addEntry(table, values){
	return $.post("functions/add_entry.php", {table : table, values : values});
}

// Updates a single column in a row of a table
function updateColumn(table, column, value, target){
	return $.post("functions/update_column.php", {table : table, column : column, value : value, target_id : target});
}

// Updates a whole row
function updateEntry(table, values, target){
	return $.post("functions/update_entry.php", {table : table, target_id : target, values : values});
}

// Deletes an entry in a table of the database
function deleteEntry(table, entry_id){
	return $.post("functions/delete_entry.php", {table : table, entry_id : entry_id});
}

// Deletes tasks by the target, not the ID (used to eliminate orphan tasks)
function deleteTasksByTarget(token, target_id){
	return $.post("functions/delete_tasks_by_target.php", {token : token, target_id : target_id});
}

function logAction(table, action, target_id){
	return $.post("functions/log_action.php", {table : table, action : action, target_id : target_id});
}

function postNotification(token, target, recipient){
	return $.post("functions/post_notifications.php", {token : token, target : target, recipient : recipient});
}

function fetchColors(){
	return $.get("functions/fetch_colors.php");
}

function fetchLogs(DOMcontainer, target, last_id){
	$(DOMcontainer).trigger('loading');
	$.get("functions/fetch_logs.php", {target: target, last_id : last_id}).done(function(data){
		last_id = renderLogs(DOMcontainer, data, last_id);
		console.log(last_id);
		setTimeout(fetchLogs, 10000, DOMcontainer, target, last_id)
	})
}

function renderLogs(DOMcontainer, data, last_id){
	$(DOMcontainer).trigger('loaded');
	var logs = JSON.parse(data);
	console.log(last_id);
	for(var i = 0; i < logs.length; i++){
		var content = "", content_action = "", action_icon = "", type_text = "", type_icon = "";
		// Switch on action token
		switch(logs[i].action){
			case 'Ajout':
				content_action = " a ajouté";
				action_icon = "plus";
				break;

			case 'Archivage':
				content_action = " a archivé";
				break;

			case 'Connexion':
				content_action = " s'est connecté(e)";
				action_icon = "log-off";
				break;

			case 'Déconnexion':
				content_action = " s'est déconnecté(e)";
				action_icon = "log-off";
				break;

			case 'Désarchivage':
				content_action = " a désarchivé";
				break;

			case 'Fermeture':
				content_action =  " a fermé";
				break;

			case 'Invalidation':
				content_action = " a invalidé";
				break;

			case 'Modification':
				content_action = " a modifié";
				action_icon = "pencil";
				break;

			case 'Prolongation':
				content_action = " a prolongé";
				break;

			case 'Suppression':
				content_action = " a supprimé";
				action_icon = "trash";
				break;

			case 'Transaction':
				content_action = " a conclu";
				action_icon = "refresh";
				break;

			case 'Validation':
				content_action = " a validé";
				break;
		}

		switch(logs[i].target_type){
			case 'locations':
				type_text = " la région ";
				type_icon = "pushpin";
				break;

			case 'participations':
				type_text = " la participation";
				break;

			case 'product_categories':
				type_text = " la catégorie de produits ";
				type_icon = "list";
				break;

			case 'produits':
				type_text = " le produit ";
				type_icon = "credit-card";
				break;

			case 'produits_echeances':
				type_text = " l'échéance ";
				type_icon = "refresh";
				break;

			case 'rooms':
				type_text = " la salle ";
				type_icon = "pushpin";
				break;

			case 'sessions':
				type_text = " le cours ";
				type_icon = "eye-open";
				break;

			case 'session_groups':
				type_text = " le groupe de récurrence ";
				type_icon = "eye-open";
				break;

			case 'tags_session':
				type_text = " l'étiquette cours ";
				type_icon = "tags";
				break;

			case 'tags_user':
				type_text = " l'étiquette utilisateur ";
				type_icon = "tags";
				break;

			case 'tasks':
				type_text = " la tâche ";
				type_icon = "list-alt";
				break;
			case 'task_comments':
				type_text = " un commentaire sur la tâche ";
				type_icon = "bubble";
				break;

			case 'transactions':
				type_text = " la transaction ";
				break;

			case 'users':
				type_text = " le profil de ";
				type_icon = "user";
				break;
		}

		content += "<div class='log-row' id='row-"+logs[i].id+"' data-entry='"+logs[i].id+"'>";
		// User icon column
		content += "<span class='col-xs-1 centered'>";
		content += "<img class='log-picture' src='"+logs[i].user_photo+"'>";
		content += "</span>";
		content += "<span>";
		content += "<strong>"+logs[i].user_name+"</strong>";
		content += content_action;
		if(logs[i].action != "Connexion" && logs[i].action != "Déconnexion"){
			content += type_text;
			if(logs[i].url){
				content += "<a href='"+logs[i].url+"'>"+logs[i].target_name+"</a>";
			} else {
				content += logs[i].target_name;
			}
		}
		content += "</span>";
		content += "<span class='timestamp'>"+moment(logs[i].timestamp).fromNow()+"</span>";
		content += "</div>";
		last_id = logs[i].id;
		$(DOMcontainer).prepend(content);
	}
	return last_id;
}

// http://stackoverflow.com/questions/19491336/get-url-parameter-jquery-or-how-to-get-query-string-values-in-js
function getUrlParameter(sParam) {
	var sPageURL = decodeURIComponent(window.location.search.substring(1)),
		sURLVariables = sPageURL.split('&'),
		sParameterName,
		i;

	for (i = 0; i < sURLVariables.length; i++) {
		sParameterName = sURLVariables[i].split('=');

		if (sParameterName[0] === sParam) {
			return sParameterName[1] === undefined ? true : sParameterName[1];
		}
	}
};

function provideAutoComplete(target, filter){
	$.get("functions/fetch_user_list.php", {filter : filter}).done(function(data){
		var userList = JSON.parse(data);
		var autocompleteList = [];
		for(var i = 0; i < userList.length; i++){
			autocompleteList.push(userList[i].user);
		}
		$(target).textcomplete('destroy');
		$(target).textcomplete([{
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
	});
}

function fillInvoiceSelect(DOMContainer, user_credentials, select_id){
	console.log(DOMContainer);
	$(DOMContainer).empty();
	$.get("functions/fetch_user_invoices.php", {user_credentials : user_credentials}).done(function(data){
	console.log(data);
		if(data){
			var options = JSON.parse(data);
			for(var i = 0; i < options.length; i++){
				$(DOMContainer).append(
					$("<option></option>").text(options[i].token).val(options[i].value)
				);
			}
			if(select_id)
				$(DOMContainer).val(select_id).change();
		}
	})
}
