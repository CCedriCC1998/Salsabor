$(document).ready(function(){
	moment.locale('fr');
}).on('show.bs.modal', '#product-modal', function(e){
	var product_id = $(e.relatedTarget).data('argument'), modal = $(this);
	var token = {};
	token["product_id"] = product_id;
	$.when(fetchProducts($.param(token)), fetchSessions(product_id)).done(function(product, sessions){
		renderProductModal(modal, JSON.parse(product[0]), JSON.parse(sessions[0]));
	})
}).on('hide.bs.modal', '#product-modal', function(e){
	$(".sub-modal").hide();
}).on('click', '.activate-product', function(){
	var date = moment($(".datepicker").val(),"DD/MM/YYYY").format("YYYY-MM-DD");
	var product_id = document.getElementById($(this).attr("id")).dataset.argument;
	activateProductWithDate(product_id, date);
}).on('click', '.extend-product', function(){
	$(".sub-modal").hide();
	var date = moment($(".datepicker").val(),"DD/MM/YYYY").format("YYYY-MM-DD 23:59:59");
	var product_id = document.getElementById($(this).attr("id")).dataset.argument;
	$.when(updateColumn("produits_adherents", "date_prolongee", date, product_id)).done(function(){
		$("#btn-arep").attr("data-arep", date);
		logAction("produits_adherents", "Prolongation", product_id);
		computeRemainingHours(product_id, true);
	})
}).on('click', '.btn-arep', function(){
	var product_id = document.getElementById($(this).attr("id")).dataset.argument;
	var table = "produits_adherents";
	var column = "date_prolongee";
	$.when(updateColumn(table, column, null, product_id)).done(function(){
		$("#btn-arep").attr("data-arep", "null");
		computeRemainingHours(product_id, true);
	})
}).on('click', '.product-participation', function(){
	var session = $(this);
	var participation_id = document.getElementById($(this).attr("id")).dataset.argument;
	if(!$(this).hasClass("options-shown")){
		session.addClass("options-shown");
		var content = "<div class='session-options'><button class='btn btn-default btn-modal trigger-sub' data-participation='"+participation_id+"' data-subtype='set-participation-product' id='btn-session-report'><span class='glyphicon glyphicon-credit-card'></span> Réaffecter</button> ";
		content += "<button class='btn btn-default btn-modal trigger-sub' data-argument='"+participation_id+"' data-subtype='unlink' id='btn-session-unlink'><span class='glyphicon glyphicon-link'></span> Délier</button> ";
		content += "<button class='btn btn-danger btn-modal trigger-sub' data-argument='"+participation_id+"' data-subtype='delete' id='btn-session-delete'><span class='glyphicon glyphicon-trash'></span> Supprimer</button></div>";
		session.append(content);
	} else {
		$(this).find(".session-options").remove();
		session.removeClass("options-shown");
	}
}).on('click', '.sub-modal-product', function(e){
	e.stopPropagation();
	$(".sub-modal-product>span").remove();
	$(".sub-modal-product").attr("id", "");
	$(this).append("<span class='glyphicon glyphicon-ok'></span>");
	$(this).attr("id", "product-selected");
}).on('click', '.delete-participation', function(){
	var participation_id = document.getElementById($(this).attr("id")).dataset.session;
	deleteParticipation(participation_id);
}).on('click', '.unlink-session', function(){
	var session_target = document.getElementById($(this).attr("id")).dataset.session;
	unlinkParticipation(session_target);
}).on('click', '.form-control', function(e){
	e.stopPropagation();
}).on('click', '.btn-boolean', function(e){
	e.stopPropagation();
	var button = $(this);
	var boolean_name = $(this).attr("id");
	var product_id = document.getElementById($(this).attr("id")).dataset.product;
	var maturity_id = document.getElementById($(this).attr("id")).dataset.maturity;

	if($(this).hasClass("status-disabled")){
		var value = 1;
		switch(button.attr("id")){
			case "lock_montant":
				var title = "Verrouillé : le montant de l'échéance ne variera pas, peu importe les autres échéances de la transaction.";
				break;

			case "lock_status":
				var title = "Verrouillé : le système n'a désormais pas l'autorisation de changer l'état (en attente, valide, expiré) du produit. Vous pouvez cependant toujours le modifier.";
				break;

			case "lock_dates":
				var title = "Verrouilé : le système n'a désormais pas l'autorisation de changer les dates de validité, d'activation ni d'expiration du produit. Vous pouvez néanmoins fixer toutes ces dates.";
				break;

			default:
				break;
		}
	} else {
		var value = 0;
		switch(button.attr("id")){
			case "lock_montant":
				var title = "Non verrouillé : le montant de l'échéance sera affecté par des changements dans d'autres échéances";
				break;

			case "lock_status":
				var title = "Libre : le système modifiera l'état du produit de façon appropriée en fonction des dates de validité.";
				break;

			case "lock_dates":
				var title = "Libre : le système modifiera les dates en fonction du premier cours enregistré, de la validité du produit et d'une potentielle extension de validité.";
				break;

			default:
				break;
		}
	}
	if(product_id != null){
		updateColumn("produits_adherents", boolean_name, value, product_id);
		computeRemainingHours(product_id, true);
	} else {
		updateColumn("produits_echeances", boolean_name, value, product_id);
	}
	button.removeClass("status-disabled");
	button.removeClass("status-enabled");
	if(value == 1){
		button.addClass("status-enabled");
		button.children("span").removeClass("glyphicon-floppy-remove");
		button.children("span").addClass("glyphicon-lock");
		if(button.attr("id") == "lock_status"){
			$("#manual-expire").removeClass("disabled");
			$("#manual-expire").addClass("enabled");
		}
	} else {
		button.addClass("status-disabled");
		button.children("span").removeClass("glyphicon-lock");
		button.children("span").addClass("glyphicon-floppy-remove");
		if(button.attr("id") == "lock_status"){
			$("#manual-expire").removeClass("enabled");
			$("#manual-expire").addClass("disabled");
		}
	}
	button.attr("title", title);
}).on('click', '.delete-product', function(){
	var product_id = document.getElementById($(this).attr("id")).dataset.product;
	deleteProduct(product_id);
})

function activateProductWithDate(product_id, start_date){
	$.post("functions/activate_product.php", {product_id : product_id, start_date : start_date}).done(function(data){
		var dates = JSON.parse(data);
		var activation = dates[0], expiration = dates[1];
		/*console.log(dates);*/
		if(moment(dates[1]).format("YYYY-MM-DD") > moment().format("YYYY-MM-DD")){
			$("#product-validity-"+product_id).html("Valide du <br><span class='highlighted-value'> "+moment(activation).format("DD/MM/YYYY")+"</span><br>au<br><span class='highlighted-value'>"+moment(expiration).format("DD/MM/YYYY")+"</span>");
			$("#purchase-item-"+product_id+">p.purchase-product-validity").html("Valide du <span>"+moment(activation).format("DD/MM/YYYY")+"</span> au <span>"+moment(expiration).format("DD/MM/YYYY")+"</span>");
			$("#purchase-item-"+product_id).removeClass("item-pending");
			$("#purchase-item-"+product_id).removeClass("item-expired");
			$("#purchase-item-"+product_id).addClass("item-active");
			$("#btn-activate-"+product_id).html("<span class='glyphicon glyphicon-ban-circle'></span> Désactiver");
			document.getElementById("btn-activate-"+product_id).onclick = function(){ deactivateProduct(product_id); };
			$("#btn-activate-"+product_id).removeClass("trigger-sub");
			$("#btn-activate-"+product_id).attr("data-argument", null);
			$("#btn-activate-"+product_id).attr("data-subtype", null);
		} else {
			$("#product-validity-"+product_id).html("<span class='highlighted-value'>Activé</span><br>le "+moment(activation).format("DD/MM/YYYY")+"<br><span class='highlighted-value'>Expiré</span><br>le "+moment(expiration).format("DD/MM/YYYY"));
			$("#purchase-item-"+product_id+">p.purchase-product-validity").html("Expiré le "+moment(expiration).format("DD/MM/YYYY"));
			$("#purchase-item-"+product_id).removeClass("item-pending");
			if(dates[2] < 0){
				$("#purchase-item-"+product_id).addClass("item-overused");
			} else {
				$("#purchase-item-"+product_id).addClass("item-expired");
			}
			$("#btn-activate-"+product_id).html("<span class='glyphicon glyphicon-play-circle'></span> Réactiver");
			$("#btn-activate-"+product_id).attr("data-argument", product_id);
			$("#btn-activate-"+product_id).attr("data-subtype", "activate");
			$("#btn-activate-"+product_id).addClass("trigger-sub");
			$("btn-activate-"+product_id).prop('onclick', null).off('click');
		}
		$(".sub-modal").hide();
		computeRemainingHours(product_id, true);
	})
}

/** Compute the remaining hours of a product **/
function computeRemainingHours(product_id, refresh){
	console.log("Computing product "+product_id);
	$.post("functions/compute_product.php", {product_id : product_id}).done(function(computed_product_details){
		console.log(JSON.parse(computed_product_details));
		if(refresh){
			$.when(fetchSessions(product_id)).done(function(sessions){
				renderProductModal($("#product-modal"), JSON.parse(computed_product_details), JSON.parse(sessions));
			});
		}
		refreshProductBanner(JSON.parse(computed_product_details));
		console.log("Computing done.");
	})
}

function deactivateProduct(product_id, value){
	$.post("functions/deactivate_product.php", {product_id : product_id, value : value}).done(function(data){
		var value = JSON.parse(data);
		if(value == 0){
			$("#product-validity-"+product_id).html("<span class='highlighted-value'>En attente</span><br>d'activation");
			$("#purchase-item-"+product_id+">p.purchase-product-validity").html("En attente");
			$("#purchase-item-"+product_id).addClass("item-pending");
			$("#purchase-item-"+product_id).removeClass("item-expired");
			$("#btn-activate-"+product_id).html("<span class='glyphicon glyphicon-play-circle'></span> Activer");
		} else {
			$("#product-validity-"+product_id).html("<span class='highlighted-value'>Expiré</span><br>le "+moment(value).format("DD/MM/YYYY"));
			$("#purchase-item-"+product_id+">p.purchase-product-validity").html("Expiré le "+moment(value).format("DD/MM/YYYY"));
			$("#purchase-item-"+product_id).removeClass("item-pending");
			$("#purchase-item-"+product_id).addClass("item-expired");
			$("#btn-activate-"+product_id).html("<span class='glyphicon glyphicon-play-circle'></span> Réactiver");
		}
		$("#purchase-item-"+product_id).removeClass("item-active");
		/*document.getElementById("btn-activate-"+product_id).onclick = function(){ activateProduct(product_id); };*/
		$("#btn-activate-"+product_id).attr("data-argument", product_id);
		$("#btn-activate-"+product_id).attr("data-subtype", "activate");
		$("#btn-activate-"+product_id).addClass("trigger-sub");
		$("btn-activate-"+product_id).prop('onclick', null).off('click');
		computeRemainingHours(product_id, true);
	})
}

function deleteProduct(product_id){
	console.log(product_id);
	$.post("functions/delete_product.php", {product_id : product_id}).done(function(data){
		$("#purchase-item-"+product_id).remove();
		if(data != null){
			$("#purchase-"+data).remove();
		}
		$(".sub-modal").hide();
	})
}

/** Displays the products that can be target of a participation reassignment **/
function displayEligibleProducts(data){
	var products_list = JSON.parse(data), product_status, product_flavor_text, product_hours, product_purchase_date;
	var body = "<ul class='purchase-inside-list'>";
	if(products_list.length == 0){
		body += "Aucun produit n'est disponible";
	} else{
		for(var i = 0; i < products_list.length; i++){
			var usage_date;
			if(products_list[i].date_achat != null){
				product_purchase_date = "Acheté le "+moment(products_list[i].date_achat).format("DD/MM/YYYY");
			} else {
				product_purchase_date = "Pas de transaction";
			}
			if(products_list[i].usage_date)
				usage_date = products_list[i].usage_date;
			else
				usage_date = products_list[i].expiration;
			switch(products_list[i].status){
				case '1':
					product_status = "item-active";
					product_flavor_text = "Valide du "+moment(products_list[i].activation).format("DD/MM/YYYY")+" au "+moment(usage_date).format("DD/MM/YYYY");
					break;

				case '2':
					product_status = "item-expired";
					product_flavor_text = "Valide du "+moment(products_list[i].activation).format("DD/MM/YYYY")+" au "+moment(usage_date).format("DD/MM/YYYY");
					break;

				case '0':
					product_status = "item-pending";
					product_flavor_text = "En attente";
					break;
			}
			if(products_list[i].remaining_hours < 0 && products_list[i].product_size != 0){
				product_status = "item-overused";
			}
			body += "<li class='sub-modal-product "+product_status+"' data-argument='"+products_list[i].id+"'>";
			body += "<p class='smp-title'>"+products_list[i].product_name+"</p>";
			body += "<p>"+product_purchase_date+"</p>";
			body += "<p>"+product_flavor_text+"</p>";
			if(products_list[i].product_size != 0){
				if(products_list[i].hours < 0){
					product_hours = -1 * products_list[i].remaining_hours+" heures en excès";
				} else {
					product_hours = 1 * products_list[i].remaining_hours+" heures restantes";
				}
				body += "<p>"+product_hours+"</p>";
			}
			body += "</li>";
		}
	}
	body += "</ul>";
	return body;
}

// Forcefully expires a product
function expireProduct(product_id){
	$.post("functions/expire_product.php", {product_id : product_id}).done(function(){
		computeRemainingHours(product_id, true);
	})
}

/** Fetch the purchase : products and maturities of the purchase **/
function fetchMaturities(purchase_id){
	return $.post("functions/fetch_maturities.php", {purchase_id : purchase_id});
}

function fetchProducts(filter_token){
	return $.get("functions/fetch_products.php", {filter_token : filter_token});
}

function displayPurchase(purchase_id){
	if($("#body-purchase-"+purchase_id).hasClass("in")){
		$("#body-purchase-"+purchase_id).collapse("hide");
		$("#body-purchase-"+purchase_id).empty();
	} else {
		var token = {};
		token["purchase_id"] = purchase_id;
		$.when(fetchProducts($.param(token)), fetchMaturities(purchase_id)).done(function(data1, data2){
			var purchase_list = JSON.parse(data1[0]);
			var maturities_list = JSON.parse(data2[0]);

			// Handle purchases
			var contents = "<p class='purchase-subtitle'>Liste des produits</p>";
			contents += "<div class='row purchase-product-list-container' id='products-"+purchase_id+"'>";
			contents += "<ul class='purchase-inside-list purchase-product-list'>";
			contents += renderProductBanners(purchase_list);
			contents += "</ul></div>";

			// Handle maturities
			contents += "<p class='purchase-subtitle'>Echéancier</p>";
			contents += "<div class='row purchase-maturities-container' id='maturities-"+purchase_id+"'>";
			contents += "<p class='maturities-incomplete' id='maturities-incomplete-"+purchase_id+"'></p>";
			contents += "<ul class='purchase-inside-list maturities-list' id='maturities-list-"+purchase_id+"'>";
			contents += "<div class='maturities-button'>";
			contents += "<button class='btn btn-primary add-maturity' id='add-maturity-"+purchase_id+"' data-transaction='"+purchase_id+"'><span class='glyphicon glyphicon-plus'></span> Ajouter une échéance</button>";
			contents += "</div>";
			contents += "<div class='maturities-display' id='maturities-display-"+purchase_id+"'>";
			var maturities = displayMaturities(maturities_list);
			contents += maturities;
			contents += "</div>";
			contents += "</ul></div>";
			// Add the total price to each input once it's calculated.
			// Lock the appropriate sliders.
			$("#body-purchase-"+purchase_id).append(contents);

			showAmountDiscrepancy(purchase_id);
			$("#body-purchase-"+purchase_id).collapse("show");
		})
	}
}

function fetchSessions(product_id){
	/** Fetch the details of a product : product and all the sessions of this product **/
	return $.get("functions/fetch_sessions_product.php", {product_id : product_id});
}

function fillSessions(sessions_list){
	$(".participations-list").empty();
	/*console.log(sessions);*/
	var valid_sessions = "", over_sessions = "", out_sessions = "", previousSessions = [], valid_indicator = -1, over_indicator = -1;
	for(var i = 0; i < sessions_list.length; i++){
		/*console.log(sessions_list[i]);*/
		if(sessions_list[i].valid == 2){
			previousSessions.push(i);
			over_indicator = -2;
		} else {
			/*console.log(sessions_list[i]);*/
			if(valid_indicator == -1){
				valid_sessions += "<p id='over-session-alert'>Cours validés :</p>";
				valid_indicator = 0;
			}
			if(sessions_list[i].status == 2){
				var participation_status = "status-success";
			} else {
				var participation_status = "status-pre-success";
			}
			valid_sessions += "<li class='product-participation "+participation_status+" container-fluid' data-argument='"+sessions_list[i].id+"' id='participation-"+sessions_list[i].id+"'>";
			valid_sessions += "<p class='col-lg-12 session-title'>"+sessions_list[i].title+"</p>";
			valid_sessions += "<p class='col-lg-12 session-hours'>"+moment(sessions_list[i].start).format("DD/MM/YYYY")+" : "+moment(sessions_list[i].start).format("HH:mm")+" - "+moment(sessions_list[i].end).format("HH:mm")+"</p>";
			valid_sessions += "</li>";
		}
	}
	for(var j = 0; j < previousSessions.length; j++){
		if(over_indicator == -2){
			out_sessions += "<p id='over-session-alert'>Cours hors forfait :</p>";
			over_indicator = 0;
		}
		out_sessions += "<li class='product-participation participation-over container-fluid' data-argument='"+sessions_list[previousSessions[j]].id+"' id='participation-"+sessions_list[previousSessions[j]].id+"'>";
		out_sessions += "<p class='col-lg-12 session-title'>"+sessions_list[previousSessions[j]].title+"</p>";
		out_sessions += "<p class='col-lg-12 session-hours'>"+moment(sessions_list[previousSessions[j]].start).format("DD/MM/YYYY")+" : "+moment(sessions_list[previousSessions[j]].start).format("HH:mm")+" - "+moment(sessions_list[previousSessions[j]].end).format("HH:mm")+"</p>";
		out_sessions += "</li>";
	}
	$(".participations-list").append("<h2 class='modal-body-title'>Liste des cours</h2>");
	$(".participations-list").append("<ul class='purchase-inside-list'>"+out_sessions+over_sessions+valid_sessions+"</ul>");
}

function fetchSingleParticipation(participation_id){
	return $.get("functions/fetch_single_participation.php", {participation_id : participation_id});
}
function displaySingleParticipation(participation_details){
	var participation_details = JSON.parse(participation_details);
	$("#participation-"+participation_details.id).removeClass("status-over");
	$("#participation-"+participation_details.id).removeClass("status-success");
	var participation = "<div class='col-lg-4'>";
	participation += "<p class='col-lg-12 session-title'>"+participation_details.cours_name+"</p>";
	participation += "<p class='col-lg-12 session-hours'>"+moment(participation_details.date).format("DD/MM/YYYY")+" : "+moment(participation_details.hour_start).format("HH:mm")+" -  "+moment(participation_details.hour_end).format("HH:mm")+"</p>";
	participation += "</div>";
	participation += "<div class='col-lg-8'>";
	if(participation_details.product == null){
		participation += "<p class='col-lg-12 session-title'>Pas de produit associé</p>";
		participation += "<p class='col-lg-12 session-hours'>Cliquez pour chercher un produit à associer</p>";
		$("#participation-"+participation_details.id).addClass("status-over");
	} else {
		participation += "<p class='col-lg-12 session-title'>"+participation_details.product_name+"</p>";
		participation += "<p class='col-lg-12 session-hours'>Acheté le "+moment(participation_details.achat).format("DD/MM/YYYY")+" / Valide du "+moment(participation_details.product_activation).format("DD/MM/YYYY")+" au "+moment(participation_details.product_expiration).format("DD/MM/YYYY")+"</p>";
		$("#participation-"+participation_details.id).addClass("status-success");
	}
	participation += "</div>";
	$("#participation-"+participation_details.id).html(participation);
}

function link(map, index){
	if(map.length > index){
		setTimeout(function(){
			reportSession(null, map[index]);
			link(map, ++index);
		}, 700);
	} else {
		var re = /historique/i;
		if(top.location.pathname == "/Salsabor/regularisation/participations" || re.exec(top.location.pathname) != null){
			$("#link-all").html("<span class='glyphicon glyphicon-credit-card'></span> Associer toutes les participations irrégulières");
		} else {
			$("#link-all").html("<span class='glyphicon glyphicon-arrow-right'></span> Trouver assoc.");
		}
	}
}

function linkAll(){
	// This function will try to find the correct product for every invalid participation.
	if(top.location.pathname == "/Salsabor/regularisation/participations"){
		var invalidMap = $(".irregular-participation").map(function(){
			return this.dataset.argument;
		}).get();
	} else {
		var re = /historique/i;
		if(re.exec(top.location.pathname) != null){
			var invalidMap = $(".participation-over").map(function(){
				return this.dataset.argument;
			}).get().reverse();
		} else {
			var invalidMap = $(".participation-over").map(function(){
				return this.dataset.argument;
			}).get();
		}
	}
	$("#link-all").text("En cours...");
	link(invalidMap, 0);
}

function renderProductModal(modal, product_details, participations_list){
	// Renders product in the modal
	// Title
	modal.find(".modal-title").text(product_details.product_name+" (ID : "+product_details.id+")");
	modal.find(".purchase-sub").text("Transaction "+product_details.transaction_id+" du "+moment(product_details.date_achat).format("DD/MM/YYYY")+"; utilisé par "+product_details.user);

	// Status block
	if(product_details.activation)
		modal.find(".activation-slot-date").text(moment(product_details.activation).format("DD/MM/YYYY"));
	else
		modal.find(".activation-slot-date").text(" -");

	if(product_details.expiration)
		modal.find(".expiration-slot-date").text(moment(product_details.expiration).format("DD/MM/YYYY"));
	else
		modal.find(".expiration-slot-date").text(" -");

	if(product_details.usage_date)
		modal.find(".usage-slot-date").text(moment(product_details.usage_date).format("DD/MM/YYYY"));
	else
		modal.find(".usage-slot-date").text(" -");

	var product_validity = "<p id='product-status-"+product_details.id+"'><span class='highlighted-value'>";
	if(product_details.product_size > 0){
		if(product_details.remaining_hours < 0){
			product_validity += -1 * product_details.remaining_hours+" heures</span> de consommation excessive</p>";
		} else {
			var product_validity = "<p id='product-status-"+product_details.id+"'><span class='highlighted-value'>"+product_details.remaining_hours+" heures</span><br>restantes</p>";
		}
	} else {
		$(".participations-list").empty();
		if(product_details.status == '1'){ // If the product is active
			product_validity += moment(product_details.expiration).toNow(true)+"</span><br> avant expiration</p>";
		} else {
			product_validity += "Expiré</span> depuis "+moment(product_details.expiration).fromNow(true)+"</p>";
		}
	}
	modal.find(".product-validity").empty();
	modal.find(".product-validity").html(product_validity);

	// Actions block
	var buttons = "";
	switch(product_details.status){
		case '0':
		case 0:
			/*buttons += "<button class='btn btn-default btn-block btn-modal' id='btn-activate-"+product_details.id+"' onclick='activateProduct("+product_details.id+")'><span class='glyphicon glyphicon-play-circle'></span> Activer</button>";*/
			// Activation button
			buttons += "<button class='btn btn-default btn-block btn-modal trigger-sub' id='btn-activate-"+product_details.id+"' data-argument='"+product_details.id+"' data-subtype='activate'><span class='glyphicon glyphicon-play-circle'></span> Activer</button>";
			break;

		case '1':
		case 1:
			// Deactivation button
			buttons += "<button class='btn btn-default btn-block btn-modal' id='btn-activate-"+product_details.id+"' onclick='deactivateProduct("+product_details.id+")'><span class='glyphicon glyphicon-ban-circle'></span> Désactiver</button>";
			// Extension button
			buttons += "<button class='btn btn-default btn-block btn-modal trigger-sub' id='btn-arep' data-argument='"+product_details.id+"' data-arep='"+product_details.extended+"' data-subtype='AREP'><span class='glyphicon glyphicon-calendar'></span> AREP</button>";
			break;

		case '2':
		case 2:
			// Reactivation button
			buttons += "<button class='btn btn-default btn-block btn-modal trigger-sub' id='btn-activate-"+product_details.id+"' data-argument='"+product_details.id+"' data-subtype='activate'><span class='glyphicon glyphicon-play-circle'></span> Réactiver</button>";
			// Extension button
			buttons += "<button class='btn btn-default btn-block btn-modal trigger-sub' id='btn-arep' data-argument='"+product_details.id+"' data-arep='"+product_details.extended+"' data-subtype='AREP'><span class='glyphicon glyphicon-calendar'></span> AREP</button>";
			break;
	}
	// Computing hours button
	buttons += "<button class='btn btn-default btn-block btn-modal' onclick='computeRemainingHours("+product_details.id+", true)'><span class='glyphicon glyphicon-scale'></span> Recalculer</button>";
	buttons += "<button class='btn btn-default btn-block btn-modal' onclick='unlinkAll()' title='Délier tous les cours hors forfait'><span class='glyphicon glyphicon-link'></span> Délier inval.</button>";
	buttons += "<button class='btn btn-default btn-block btn-modal' id='link-all' onclick='linkAll()' title='Délier tous les cours hors forfait'><span class='glyphicon glyphicon-arrow-right'></span> Trouver assoc.</button>";
	// Handling the sessions
	if(product_details.lock_status == 0){
		var expiredAffix = "disabled";
	} else {
		var expiredAffix = "enabled";
	}
	buttons += "<button class='btn btn-default btn-block btn-modal "+expiredAffix+"' id='manual-expire' onclick='expireProduct("+product_details.id+")'><span class='glyphicon glyphicon-hourglass'></span> Expirer</button>";
	buttons += "<button class='btn btn-danger btn-block btn-modal trigger-sub' id='delete-product' data-subtype='delete-product' data-product='"+product_details.id+"'><span class='glyphicon glyphicon-trash'></span> Supprimer</button>";
	buttons += "<h2 class='modal-body-title'>Verrous</h2>";
	// Button to toggle automatic computing of this product.
	if(product_details.lock_status == 1){
		buttons += "<button class='btn btn-default btn-block btn-modal btn-boolean status-enabled' id='lock_status' data-product='"+product_details.id+"' title='Verrouillé : le système n&apos;a désormais pas l&apos;autorisation de changer l&apos;état (en attente, valide, expiré) du produit. Vous pouvez cependant toujours le modifier.'><span class='glyphicon glyphicon-lock'></span> Etat</button>";
	} else {
		buttons += "<button class='btn btn-default btn-block btn-modal btn-boolean status-disabled' id='lock_status' data-product='"+product_details.id+"' title='Libre : le système modifiera l&apos;état du produit de façon appropriée en fonction des dates de validité.'><span class='glyphicon glyphicon-floppy-remove'></span> Etat</button>";
	}
	if(product_details.lock_dates == 1){
		buttons += "<button class='btn btn-default btn-block btn-modal btn-boolean status-enabled' id='lock_dates' data-product='"+product_details.id+"' title='Verrouilé : le système n&apos;a désormais pas l&apos;autorisation de changer les dates de validité, d&apos;activation ni d&apos;expiration du produit. Vous pouvez néanmoins fixer toutes ces dates.'><span class='glyphicon glyphicon-lock'></span> Dates</button>";
	} else {
		buttons += "<button class='btn btn-default btn-block btn-modal btn-boolean status-disabled' id='lock_dates' data-product='"+product_details.id+"' title='Libre : le système modifiera les dates en fonction du premier cours enregistré, de la validité du produit et d&apos;une potentielle extension de validité.'><span class='glyphicon glyphicon-floppy-remove'></span> Dates</button>";
	}

	modal.find(".modal-actions").html(buttons);

	// Participations block
	fillSessions(participations_list);
}

function refreshProductBanner(product_details){
	var banner = $("#purchase-item-"+product_details.id);

	// Display
	banner.removeClass("item-overused item-pending item-active item-expired");

	// Refreshing status
	if(product_details.remaining_hours < 0 && product_details.product_size != 0){
		$("#purchase-item-validity-"+product_details.id).text("Surconsommé à partir du "+moment(product_details.display_expiration).format("DD/MM/YYYY"));
		$("#purchase-item-status-"+product_details.id).addClass("item-overused");
	} else {
		switch(product_details.status){
			case '0':
			case 0:
				$("#purchase-item-validity-"+product_details.id).text("En attente");
				banner.addClass("item-pending");
				break;

			case 1:
			case '1':
				$("#purchase-item-validity-"+product_details.id).text("Valide du "+moment(product_details.activation).format("DD/MM/YYYY")+" au "+moment(product_details.display_expiration).format("DD/MM/YYYY"));
				banner.addClass("item-active");
				break;

			case 2:
			case '2':
				$("#purchase-item-validity-"+product_details.id).text("Expiré le "+moment(product_details.display_expiration).format("DD/MM/YYYY"));
				banner.addClass("item-expired");
				break;
		}
	}

	// Refreshing size
	var size = "";
	if(product_details.product_size != null){
		if(product_details.product_size != 0){
			if(product_details.remaining_hours > 0)
				size += product_details.remaining_hours+" heures restantes";
			else
				size += -1 * product_details.remaining_hours+" heures en excès";
		} else {
			size += -1 * product_details.remaining_hours+" heures utilisées";
		}
	} else {
		size += "N'accepte pas les participations";
	}
	$("#purchase-item-status-"+product_details.id).text(size);

}

function renderProductBanners(purchase_items){
	console.log(purchase_items);
	var contents = "";
	for(var i = 0; i < purchase_items.length; i++){
		var item_status = "", text_status = "", size = "";
		if(purchase_items[i].remaining_hours < 0 && purchase_items[i].product_size != 0){
			item_status = "item-overused";
			text_status = "Sur-consommé à partir du "+moment(purchase_items[i].display_expiration).format("DD/MM/YYYY");
		} else {
			switch(purchase_items[i].status){
				case '0':
				case 0:
					item_status = "item-pending";
					text_status = "En attente";
					break;

				case '1':
				case 1:
					item_status = "item-active";
					text_status = "Valide du "+moment(purchase_items[i].activation).format("DD/MM/YYYY")+" au "+moment(purchase_items[i].display_expiration).format("DD/MM/YYYY");
					break;

				case '2':
				case 2:
					item_status = "item-expired";
					text_status = "Expiré le "+moment(purchase_items[i].display_expiration).format("DD/MM/YYYY");
					break;
			}
		}

		contents += "<li class='purchase-item panel-item "+item_status+" container-fluid' id='purchase-item-"+purchase_items[i].id+"' data-toggle='modal' data-target='#product-modal' data-argument='"+purchase_items[i].id+"'>";
		contents += "<p class='col-lg-12 panel-item-title bf'>"+purchase_items[i].product_name+"</p>";
		contents += "<div>";
		contents += "<p class='col-lg-3 purchase-item-validity' id='purchase-item-validity-"+purchase_items[i].id+"'>"+text_status+"</p>";
		contents += "<p class='col-lg-3 purchase-item-user' id='purchase-item-user-"+purchase_items[i].id+"'>"+purchase_items[i].user+"</p>";
		if(purchase_items[i].product_size != null){
			if(purchase_items[i].product_size != 0){
				if(purchase_items[i].remaining_hours > 0)
					contents += "<p class='col-lg-3 purchase-item-status' id='purchase-item-status-"+purchase_items[i].id+"'>"+purchase_items[i].remaining_hours+" heures restantes</p>";
				else
					contents += "<p class='col-lg-3 purchase-item-status' id='purchase-item-status-"+purchase_items[i].id+"'>"+-1 * purchase_items[i].remaining_hours+" heures en excès</p>";
			} else {
				contents += "<p class='col-lg-3 purchase-item-status' id='purchase-item-status-"+purchase_items[i].id+"'>"+-1 * purchase_items[i].remaining_hours+" heures utilisées</p>";
			}
		} else {
			contents += "<p class='col-lg-3 purchase-item-status' id='purchase-item-status-"+purchase_items[i].id+"'>N'accepte pas les participations</p>";
		}
		contents += "<p class='col-lg-3 purchase-item-price' id='purchase-item-price-"+purchase_items[i].id+"'>"+purchase_items[i].price+" €</p>";
		contents += "</div>";
		contents += "</li>";
	}
	return contents;
}

function unlinkAll(){
	// This function will find all invalid participations (identified in display by .participation-over) and log their data-argument
	var invalidMap = $(".participation-over").map(function(){
		unlinkParticipation(this.dataset.argument);
	});
}

function unlinkParticipation(participation_id){
	// This function is used when a session has to be delinked from its product.
	$.post("functions/unlink_participation.php", {participation_id : participation_id}).done(function(old_product){
		$(".sub-modal").hide();
		var re = /historique/i;
		if(re.exec(top.location.pathname) != null){
			computeRemainingHours(old_product, false);
			$.when(fetchSingleParticipation(participation_id)).done(function(participation){
				displaySingleParticipation(participation);
				$("#valid-count").text($(".status-success").length);
				$("#over-count").text($(".status-over").length);
			});
		} else {
			computeRemainingHours(old_product, true);
		}
	})
}
