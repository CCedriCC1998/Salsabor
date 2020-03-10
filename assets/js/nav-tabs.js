$(document).ready(toggleNavTabs());
$("li[id$=-toggle]").css('cursor', 'pointer');

function toggleNavTabs(){
	$("section").hide();
	var token = $(".active").attr('id').replace("-toggle", "");
	$("section#"+token).show();
}

$("li[id$=-toggle]").click(function(){
	$("li[id$=-toggle]").attr('class', '');
	$(this).attr('class', 'active');
	toggleNavTabs();
});

$(".nav-tabs-toggle").click(function(){
	$(".nav-tabs-toggle").removeClass('active');
	var id = $(".nav-tabs-toggle").attr('id');
	$("li[id$=-toggle]").removeClass('active');
	$("li[id="+id+"]").attr('id', id).addClass('active');
	$(this).addClass('active');
	toggleNavTabs();
	$(this).removeClass('active');
});
