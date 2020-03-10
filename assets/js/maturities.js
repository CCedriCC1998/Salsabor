function updateMaturityState(maturity_id){
	/*
	- over : not received, not banked, deadline is before today
	- pre-success : not received, not banked, deadline is after today
	- partial-success : received, not banked, deadline doesn't matter
	- success : received, banked, deadline doesn't matter
	*/
	var maturity_line = $("#maturity-"+maturity_id);
	var deadline_span = $("#deadline-"+maturity_id);
	var reception_span = $("#reception-span-"+maturity_id);
	var bank_span = $("#bank-span-"+maturity_id);

	maturity_line.removeClass("status-pre-success");
	maturity_line.removeClass("status-success");
	maturity_line.removeClass("status-partial-success");
	maturity_line.removeClass("status-over");

	if(reception_span.text() != ""){
		maturity_line.addClass("status-partial-success");
	}
	if(bank_span.text() != ""){
		// Clean previous if, for credit cards.
		maturity_line.removeClass("status-partial-success");
		maturity_line.addClass("status-success");
	}
	if(bank_span.text() == "" && reception_span.text() == ""){
		if(moment(deadline_span.text(), "DD/MM/YYYY") < moment()){
			deadline_span.addClass("deadline-expired");
			maturity_line.addClass("status-over");
		} else {
			deadline_span.removeClass("deadline-expired");
			maturity_line.addClass("status-pre-success");
		}
	} else {
		deadline_span.removeClass("deadline-expired");
	}
	showNotification("Echéance mise à jour", "success");
}

function showAmountDiscrepancy(purchase_id){
	// Compute total timetable price
	var timetable_price = 0, transaction_price = parseFloat($("#price-"+purchase_id).text());
	$(".maturity-price-transaction-"+purchase_id).each(function(){
		timetable_price += parseFloat($(this).text());
	})

	var remaining_price = (transaction_price - timetable_price).toFixed(2);

	if(remaining_price != 0){
		$("#maturities-incomplete-"+purchase_id).show();
		if(remaining_price > 0){
			var warning_message = "<span>"+remaining_price+"</span> € sont actuellement ignorés par l'échéancier.</p>";
		} else if(remaining_price < 0){
			var warning_message = "<span>"+remaining_price+"</span> € sont actuellement ajoutés par l'échéancier.";
		}
		$("#maturities-incomplete-"+purchase_id).html(warning_message);
	} else {
		$("#maturities-incomplete-"+purchase_id).hide();
		$("#maturities-incomplete-"+purchase_id).empty();
	}
}

function displayMaturities(maturities){
	var totalPrice = 0, contents = "";
	for(var i = 0; i < maturities.length; i++){
		var redirection_link = "user/"+maturities[i].transaction_user+"/achats";
		if(i == 0 && top.location.pathname != "/Salsabor/"+redirection_link){
			var transaction_price = $("#price-"+maturities[i].transaction_id).text();
		}
		contents += renderMaturity(maturities[i]);
		totalPrice += parseFloat(maturities[i].price);
	}
	var maturities_price = transaction_price - totalPrice;
	return contents;
}

function renderMaturity(maturity){
	var contents = "", item_status = "status-pre-success", reception_date = "", bank_date = "", deadline_date = "", deadline_class = "", redirection_link = "user/"+maturity.transaction_user+"/achats";
	if(maturity.method != undefined || maturity.method != ""){
		var method = maturity.method;
	} else {
		var method = "En attente";
	}

	if(maturity.payer != undefined){
		var payer = maturity.payer;
	} else {
		var payer = "Pas de payeur";
	}

	if(maturity.date != undefined){
		deadline_date = moment(maturity.date).format("DD/MM/YYYY");
		if(moment(maturity.date) < moment()){
			item_status = "status-over";
		}
	}

	if(maturity.date_reception != undefined){
		reception_date = moment(maturity.date_reception).format("DD/MM/YYYY");
		item_status = "status-partial-success";
	}

	if(maturity.date_bank != undefined){
		bank_date = moment(maturity.date_bank).format("DD/MM/YYYY");
		item_status = "status-success";
	}

	contents += "<li class='purchase-item panel-item maturity-item "+item_status+" container-fluid' id='maturity-"+maturity.id+"' data-maturity='"+maturity.id+"'>";
	contents += "<div class='delete-animation-holder' id='dah-"+maturity.id+"' data-target='"+maturity.id+"'><p class='hold-text'>Suppression...(Relâchez pour annuler)</p></div>";
	contents += "<div class='container-fluid'>";

	// Method and price
	contents += "<p class='col-xs-9 panel-item-title bf' id='maturity-"+maturity.id+"-method'><span class='modal-editable-"+maturity.id+"' id='editable-method-"+maturity.id+"' data-field='methode_paiement' data-name='Méthode de paiement'>"+maturity.method+"</span> pour <span class='maturity-price-transaction-"+maturity.transaction_id+" modal-editable-"+maturity.id+"'' id='maturity-price-"+maturity.price+"' data-field='montant' data-name='Montant'>"+maturity.price+"</span> € - Catégorie TVA <span class='categoryTVA-"+maturity.category_TVA+" modal-editable-"+maturity.id+"' id='maturity-categoryTVA-"+maturity.category_TVA+"' data-field='category_TVA' data-name='Categorie TVA'>"+maturity.category_TVA+"</span></p>";

	if(top.location.pathname != "/Salsabor/"+redirection_link && maturity.transaction_user != undefined){
		contents += "<p class='col-xs-1'><a href='user/"+maturity.transaction_user+"/achats#purchase-"+maturity.transaction_id+"' class='link-glyphicon'><span class='glyphicon glyphicon-share-alt glyphicon-button-alt' title='Aller à la transaction'></span></a></p>";
	} else {
		contents += "<p class='col-xs-1'></p>";
	}

	contents += "<p class='col-xs-1'><span class='glyphicon glyphicon-pencil glyphicon-button glyphicon-button-alt edit-maturity' id='edit-"+maturity.id+"' data-toggle='modal' data-target='#edit-modal' data-entry='"+maturity.id+"' data-secondary='"+maturity.transaction_id+"' data-table='produits_echeances' title='Modifier l&apos;échéance'></span></p>";

	contents += "<p class='col-xs-1'><span class='glyphicon glyphicon-trash glyphicon-button glyphicon-button-alt delete-maturity' id ='delete-"+maturity.id+"' data-toggle='modal' data-target='#delete-modal' data-entry='"+maturity.id+"' data-table='produits_echeances' data-delete='#maturity-"+maturity.id+"' data-transaction='"+maturity.transaction_id+"' title='Supprimer l&apos;échéance'></span></p>";

	contents += "</div>"
	contents += "<div class='container-fluid'>";
	contents += "<p class='col-xs-3 modal-editable-"+maturity.id+"' id='editable-payer-"+maturity.id+"' data-field='payeur_echeance' data-name='Payeur'>"+payer+"</p>";

	// Deadline
	if(moment(maturity.date) < moment() && bank_date == ""){
		deadline_class = "deadline-expired";
	}
	contents += "<p class='col-xs-3 col-sm-2 trigger-sub trigger-editable "+deadline_class+"' data-subtype='deadline-maturity' id='deadline-"+maturity.id+"' data-maturity='"+maturity.id+"' title='Modifier la date limite'><span class='glyphicon glyphicon-time' title='Date de réception limite'></span> <span class='deadline-maturity-span' id='deadline-maturity-span-"+maturity.id+"'>"+deadline_date+"</span></p>";

	// Reception
	contents += "<p class='col-xs-3 col-sm-2 trigger-sub trigger-editable' data-subtype='receive-maturity' id='receive-"+maturity.id+"' data-maturity='"+maturity.id+"' title='Valider la réception'><span class='glyphicon glyphicon-ok' title='Date de réception'></span> <span class='reception-span' id='reception-span-"+maturity.id+"'>"+reception_date+"</span></p>";

	// Bank
	contents += "<p class='col-xs-3 col-sm-2 trigger-sub trigger-editable' data-subtype='bank-maturity' id='bank-"+maturity.id+"' data-maturity='"+maturity.id+"' title='Encaisser l&apos;échéance'><span class='glyphicon glyphicon-download-alt' title='Date d&apos;encaissement'></span> <span class='bank-span' id='bank-span-"+maturity.id+"'>"+bank_date+"</span></p>";
	contents += "</div></li>";
	return contents;
}

$(document).on('click', '.receive-maturity', function(){
	var maturity_id = document.getElementById($(this).attr("id")).dataset.maturity;
	var table = "produits_echeances";
	var reception_date = $(".reception-date").val();
	// Modal to set the date and the method. If the method is credit card, the maturity will automatically be banked.
	var values = {
		date_paiement: moment(reception_date, "DD/MM/YYYY").format("DD/MM/YYYY HH:mm:ss"),
		methode_paiement: $(".reception-method").val()
	};
	if($(".reception-method").val() == "Carte bancaire"){
		values["date_encaissement"] = moment(reception_date, "DD/MM/YYYY").format("DD/MM/YYYY HH:mm:ss");
	}

	// As we contact updateEntry (which handles a URL), we $.param() to send the correct format
	$.when(updateEntry(table, $.param(values), maturity_id)).done(function(){
		$(".sub-modal").hide(0);
		$("#maturity-"+maturity_id+"-method>span").first().text($(".reception-method").val());
		$("#reception-span-"+maturity_id).text(reception_date);
		if($(".reception-method").val() == "Carte bancaire"){
			$("#bank-span-"+maturity_id).text(reception_date);
		}
		updateMaturityState(maturity_id);
	})
}).on('click', '.cancel-reception', function(){
	var maturity_id = document.getElementById($(this).attr("id")).dataset.maturity;
	var table = "produits_echeances";

	// We cancel the date of reception.
	var value = {
		date_paiement: null
	};

	$.when(updateEntry(table, $.param(value), maturity_id)).done(function(){
		$(".sub-modal").hide(0);
		$("#reception-span-"+maturity_id).text("");
		updateMaturityState(maturity_id);
	})
}).on('click', '.bank-maturity', function(){
	var maturity_id = document.getElementById($(this).attr("id")).dataset.maturity;
	var table = "produits_echeances";
	var bank_date = $(".bank-date").val();

	// Depending on the class of the icon, we set the date.
	var value = {
		date_encaissement: moment(bank_date, "DD/MM/YYYY").format("DD/MM/YYYY HH:mm:ss")
	};

	// As we contact updateEntry (which handles a URL), we $.param() to send the correct format
	$.when(updateEntry(table, $.param(value), maturity_id)).done(function(){
		$(".sub-modal").hide(0);
		$("#bank-span-"+maturity_id).text(moment(bank_date, "DD/MM/YYYY").format("DD/MM/YYYY"));
		updateMaturityState(maturity_id);
	});
}).on('click', '.cancel-bank', function(){
	var maturity_id = document.getElementById($(this).attr("id")).dataset.maturity;
	var table = "produits_echeances";
	var value = {
		date_encaissement: null
	};
	$.when(updateEntry(table, $.param(value), maturity_id)).done(function(){
		$(".sub-modal").hide(0);
		$("#bank-span-"+maturity_id).text("");
		updateMaturityState(maturity_id);
	});
}).on('focus', '.reception-method', function(){
	$(".reception-method").textcomplete([{
		match: /(^|\b)(\w{2,})$/,
		search: function(term, callback){
			var methods = ["Carte bancaire","Chèque n°","Espèces","Virement compte à compte","Chèques vacances","En attente"];
			callback($.map(methods, function(item){
				return item.toLowerCase().indexOf(term.toLowerCase()) === 0 ? item : null;
			}));
		},
		replace: function(item){
			return item;
		}
	}]);
}).on('click', '.bank-all', function(){
	// We bank all maturities
	$(".maturity-item:not(.status-success)").each(function(){
		var maturity_id = document.getElementById($(this).attr("id")).dataset.maturity;
		var value = {
			date_encaissement: moment().format("DD/MM/YYYY HH:mm:ss")
		};
		$.when(updateEntry("produits_echeances", $.param(value), maturity_id)).done(function(){
			updateMaturityState(maturity_id);
		});
	})
}).on('click', '.deadline-maturity', function(){
	var maturity_id = document.getElementById($(this).attr("id")).dataset.maturity;
	var table = "produits_echeances";
	var deadline = $(".deadline-date").val();
	var value = {
		date_echeance: moment(deadline, "DD/MM/YYYY").format("DD/MM/YYYY HH:mm:ss")
	};

	$.when(updateEntry(table, $.param(value), maturity_id)).done(function(){
		$(".sub-modal").hide(0);
		$("#deadline-maturity-span-"+maturity_id).text(deadline);
		updateMaturityState(maturity_id);
	})
}).on('click', '.add-maturity', function(){
	var transaction_id = document.getElementById($(this).attr("id")).dataset.transaction;
	var remaining_price = 0;
	if($("#maturities-incomplete-"+transaction_id+">span").first().text() != "")
		var remaining_price = $("#maturities-incomplete-"+transaction_id+">span").first().text();
	if(remaining_price < 0)
		remaining_price = 0;

	console.log(transaction_id, remaining_price);
	var new_maturity = {
		reference_achat: transaction_id,
		montant: remaining_price,
		methode_paiement: "En attente"
	};
	$.when(addEntry("produits_echeances", $.param(new_maturity))).done(function(data){
		var render_maturity = {
			id: data,
			transaction_id: transaction_id,
			price: remaining_price,
			method: "En attente"
		}
		var contents = renderMaturity(render_maturity);
		$("#maturities-display-"+transaction_id).prepend(contents);
		showAmountDiscrepancy(transaction_id);
	});
})
