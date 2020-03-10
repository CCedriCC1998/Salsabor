<?php
session_start();
if(!isset($_SESSION["username"])){
	header('location: portal');
}
include "functions/db_connect.php";
$db = PDOFactory::getConnection();

$user_tags = $db->query("SELECT tag_id_foreign FROM assoc_user_tags aut
					JOIN tags_user tu ON aut.tag_id_foreign = tu.rank_id
					WHERE user_id_foreign = $_SESSION[user_id]")->fetchAll(PDO::FETCH_COLUMN);
$page = str_replace("/Salsabor/", "", $_SERVER["REQUEST_URI"]);
$page_tags = $db->query("SELECT tag_id_foreign FROM app_pages ap
						LEFT JOIN assoc_page_tags apt ON ap.page_id = apt.page_id_foreign
						LEFT JOIN tags_user tu ON apt.tag_id_foreign = tu.rank_id
						WHERE page_url = '$page'")->fetchAll(PDO::FETCH_COLUMN);
if(count(array_intersect($user_tags, $page_tags)) == 0){
	header("Location: my/profile");
}

$date = date_create('now')->format('H:i:s');
$welcome = "";
if($date > "06:00:00" && $date <= "10:00:00"){
	$time_message = array(
		"Vous êtes là tôt aujourd'hui ! Bonjour !",
		"J'entends les oiseaux dehors... Bonjour !",
		"C'est une belle journée aujourd'hui. Les oiseaux chantent, les fleurs éclosent...",
		"Bonjour !"
	);
} else if($date > "10:00:00" && $date <= "12:00:00"){
	$time_message = array(
		"Bonjour !",
		"Un matin tranquille... Bonjour !",
		"Il fait un peu frais, non ?",
		"Pas de panne de réveil ? Bonjour !"
	);
} else if($date > "12:00:00" && $date <= "13:30:00") {
	$welcome = "N'oubliez pas de prendre des pauses... Bonjour !";
	$time_message = array(
		"Bonjour !",
		"N'oubliez pas de prendre des pauses... Bonjour !",
		"Vous devriez aller manger si ce n'est pas déjà le cas. Bonjour !",
		"Salade de fruits, jolie, jolie...",
		"J'ai faim. Et vous ?"
	);
} else if($date > "13:30:00" && $date <= "18:00:00") {
	$time_message = array(
		"Du travail vous attend ? Bon courage !",
		"Longue après-midi en perspective ? Bonjour !",
		"Bonjour !",
		"Vous êtes plutôt thé ou café ?"
	);
} else if($date > "18:00:00" && $date <= "21:00:00") {
	$time_message = array(
		"Bonsoir !",
		"Pas de panique, votre application est là !",
		"Je me demande bien combien de fois vous avez lu ce message...",
		"Bonjour ? Ah non. C'est bonsoir je crois.",
		"This is the rhythm of the night!"
	);
} else if($date > "21:00:00" && $date <= "23:00:00") {
	$time_message = array(
		"Courage, c'est bientôt fini !",
		"Plus que quelques heures !",
		"Alors c'est l'histoire de Toto qui... En fait peut-être pas.",
		"J'espère que vous n'êtes pas là depuis 5 heures ce matin..."
	);
} else {
	$time_message = array(
		"Bienvenue, M. Wayne.",
		"Sérieusement, vous avez vu l'heure ?!",
		"Pas de répit pour les héros... Bonsoir !",
		"Bonsoir ou Bonjour ? Je vous laisse décider, vu l'heure."
	);
}
$rand = rand(0, sizeof($time_message) - 1);
$welcome = $time_message[$rand];
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Accueil d'administration | Salsabor</title>
		<?php include "styles.php";?>
		<?php include "scripts.php";?>
		<script src="assets/js/tasks-js.php"></script>
		<script src="assets/js/tags.js"></script>
	</head>
	<body>
		<?php include "nav.php";?>
		<div class="container-fluid">
			<div class="row">
				<?php include "side-menu.php";?>
				<div class="col-sm-offset-3 col-lg-10 col-lg-offset-2 main-home">
					<div class="jumbotron jumbotron-home">
						<h1><?php echo $welcome;?></h1>
						<p>Bienvenue sur Salsabor Gestion ! Cliquez sur les icônes pour un accès rapide !</p>
						<div class="quick-access">
							<ul class="quick-access-list">
								<li>
									<a href="inscription" class="link-glyphicon">
										<span class="glyphicon glyphicon-user glyphicon-button-alt"></span>
										<span class="glyphicon-text">Inscription</span>
									</a>
								</li>
								<li>
									<a href="vente" class="link-glyphicon">
										<span class="glyphicon glyphicon-th"></span>
										<span class="glyphicon-text">Vente</span>
									</a>
								</li>
								<li><a href="participations" class="link-glyphicon"><span class="glyphicon glyphicon-map-marker"></span><span class="glyphicon-text">Participations</span></a></li>
								<li><a href="echeances?region=0" class="link-glyphicon"><span class="glyphicon glyphicon-repeat"></span><span class="glyphicon-text">&Eacute;chéances</span></a></li>
								<li><a href="planning" class="link-glyphicon"><span class="glyphicon glyphicon-time"></span><span class="glyphicon-text">Planning</span></a></li>
							</ul>
						</div>
					</div>
					<?php if($_SESSION["location"] == null){ ?>
					<p class="alert alert-warning"><strong>Attention !</strong> Vous n'appartenez à aucune région d'activité. Editez votre profil pour y assigner une région ou contacter votre administrateur local.</p>
					<?php } ?>
					<div class="col-lg-6 dashboard-zones clearfix container-fluid">
						<p class="sub-legend"><span class="glyphicon glyphicon-bell"></span> Récemment...</p>
						<ul class="dashboard-notifications-container container-fluid"></ul>
					</div>
					<div class="col-lg-6 dashboard-zones clearfix container-fluid">
						<p class="sub-legend"><span class="glyphicon glyphicon-list-alt"></span> Il vous reste à faire...</p>
						<div class="tasks-container dashboard-task-container container-fluid"></div>
					</div>
				</div>
			</div>
		</div>
		<?php include "inserts/sub_modal_product.php";?>
		<?php include "inserts/edit_modal.php";?>
		<?php include "inserts/delete_modal.php";?>
		<script>
			$(document).ready(function(){
				moment.locale('fr');
				fetchNotifications(10, "new", "dashboard-notifications-container");
				fetchTasks(null, 0, <?php echo $_SESSION["user_id"];?>, "pending", 0);
			})
		</script>
	</body>
</html>
