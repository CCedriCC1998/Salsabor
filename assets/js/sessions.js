$(document).on('click', '.completion-option', function(e){
	e.preventDefault();
	if($(this).text() == "Ne pas suggérer"){
		$(".suggestion-text").html("Suggérer parmi... <span class='caret'></span>");
	} else {
		$(".suggestion-text").html("Suggérer parmi <span class='suggestion-token'>"+$(this).text()+"</span> <span class='caret'></span>");
	}
}).on('focus', '.filtered-complete', function(){
	var token = $(this).prev().find(".suggestion-token").text();
	if(token != ""){
		var id = $(this).attr("id");
		$.get("functions/fetch_user_list.php", {filter : token}).done(function(data){
			var userList = JSON.parse(data);
			var autocompleteList = [];
			for(var i = 0; i < userList.length; i++){
				autocompleteList.push(userList[i].user);
			}
			$("#"+id).textcomplete('destroy');
			$("#"+id).textcomplete([{
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
})
