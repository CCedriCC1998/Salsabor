<?php
session_start();
if(!isset($_SESSION["username"])){
	header('location: portal');
}
require_once 'functions/db_connect.php';
$db = PDOFactory::getConnection();
$searchTerms = $_GET["search_terms"];

$criteria_array = array("%".$searchTerms."%",
						"%".$searchTerms."%",
						"%".$searchTerms."%",
						"%".$searchTerms."%");

if($_GET["region"] == "0" || !isset($_SESSION["location"])){
	$search_query = "SELECT user_id, CONCAT(user_prenom, ' ', user_nom) AS identity, mail, telephone, photo, actif, archived FROM users WHERE (user_nom LIKE ? OR user_prenom LIKE ? OR mail LIKE ? OR telephone LIKE ?)";
	if(isset($_GET["archive"]) && $_GET["archive"] == "0")
		$search_query .= " AND archived = 0";
	$search_query .= " ORDER BY user_nom ASC";
	$standard = $db->prepare($search_query);
	$standard->execute($criteria_array);
	$result = $standard->fetchAll(PDO::FETCH_ASSOC);
} else {
	$general_query = "SELECT u.user_id, CONCAT(u.user_prenom, ' ', u.user_nom) AS identity, u.user_prenom, u.user_nom, u.mail, u.telephone, u.photo, u.user_location, u.actif, u.archived FROM users u";
	$general_where = " (u.user_nom LIKE ? OR u.user_prenom LIKE ? OR u.mail LIKE ? OR u.telephone LIKE ?)";

	// Query to find staff
	$staff_query = $general_query." WHERE user_location = $_SESSION[location] AND".$general_where;
	if(isset($_GET["archive"]) && $_GET["archive"] == "0")
		$staff_query .= " AND archived = 0";
	$match_by_staff = $db->prepare($staff_query);
	$match_by_staff->execute($criteria_array);

	// Query to find by participations
	$by_participations_query = $general_query." RIGHT JOIN participations p ON u.user_id = p.user_id
				LEFT JOIN sessions s ON p.session_id = s.session_id
				LEFT JOIN rooms r ON s.session_room = r.room_id
				LEFT JOIN locations l ON r.room_location = l.location_id
				WHERE".$general_where." AND (l.location_id = $_SESSION[location] OR u.user_location = $_SESSION[location])";
	if(isset($_GET["archive"]) && $_GET["archive"] == "0")
		$by_participations_query .= " AND archived = 0";
	$by_participations_query .= " GROUP BY u.user_id ORDER BY u.archived ASC, u.actif DESC, u.user_nom ASC, u.user_prenom ASC";
	$match_by_participations = $db->prepare($by_participations_query);
	$match_by_participations->execute($criteria_array);

	// Query to find by transactions
	$by_transactions_query = $general_query." RIGHT JOIN transactions t on u.user_id = t.payeur_transaction
				LEFT JOIN users u2 ON t.transaction_handler = u2.user_id
				LEFT JOIN locations l ON u2.user_location = l.location_id
				WHERE".$general_where." AND (u2.user_location = $_SESSION[location])";
	if(isset($_GET["archive"]) && $_GET["archive"] == "0")
		$by_transactions_query .= " AND u.archived = 0";
	$by_transactions_query .= " GROUP BY l.location_id ORDER BY u.archived ASC, u.actif DESC, u.user_nom ASC, u.user_prenom ASC";
	$match_by_transactions = $db->prepare($by_transactions_query);
	$match_by_transactions->execute($criteria_array);

	$result_array = array();
	while($match = $match_by_staff->fetch(PDO::FETCH_ASSOC)){
		array_push($result_array, $match);
	}
	while($match = $match_by_participations->fetch(PDO::FETCH_ASSOC)){
		array_push($result_array, $match);
	}
	while($match = $match_by_transactions->fetch(PDO::FETCH_ASSOC)){
		array_push($result_array, $match);
	}
	$result = array_intersect_key($result_array, array_unique(array_map('serialize' , $result_array)));
	usort($result, function($a, $b){
		if($a['user_nom'] == $b['user_prenom']){
			return ($a['user_prenom'] < $b['user_prenom']) ? -1 : 1;
		}
		return ($a['user_nom'] < $b['user_nom']) ? -1 : 1;
	});
}

$searchTransactions = $db->prepare("SELECT * FROM transactions WHERE id_transaction LIKE ?");
$searchTransactions->execute(array("%".$searchTerms."%"));
$numberTransactions = $searchTransactions->rowCount();
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Résultats de recherche | Salsabor</title>
		<?php include "styles.php";?>
	</head>
	<body>
		<?php include "nav.php";?>
		<div class="container-fluid">
			<div class="row">
				<?php include "side-menu.php";?>
				<div class="col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
					<legend>Résultats de recherche</legend>
					<div class="row sub-legend">
						<p class="search-title col-xs-8">
							<span class="glyphicon glyphicon-user"></span> <?php echo sizeof($result);?> utilisateur(s) trouvé(s)
						</p>
						<div class="btn-group float-right">
							<?php if($_GET["region"] == "1"){ ?>
							<a href="search.php?search_terms=<?php echo $searchTerms;?>&archive=<?php echo $_GET["archive"];?>&region=0" class="btn btn-primary"><span class="glyphicon glyphicon-globe"></span> Inclure toutes les régions</a>
							<?php } else { ?>
							<a href="search.php?search_terms=<?php echo $searchTerms;?>&archive=<?php echo $_GET["archive"];?>&region=1" class="btn btn-primary"><span class="glyphicon glyphicon-globe"></span> Exclure les autres régions</a>
							<?php } ?>
							<?php if(!isset($_GET["archive"]) || $_GET["archive"] == "1"){ ?>
							<a href="search.php?search_terms=<?php echo $searchTerms;?>&archive=0&region=<?php echo $_GET["region"];?>" class="btn btn-primary"><span class="glyphicon glyphicon-folder-close"></span> Exclure les archives</a>
							<?php } else { ?>
							<a href="search.php?search_terms=<?php echo $searchTerms;?>&archive=1&region=<?php echo $_GET["region"];?>" class="btn btn-primary"><span class="glyphicon glyphicon-folder-open"></span> Inclure les archives</a>
							<?php } ?>
						</div>
					</div>
					<div class="row">
						<?php foreach($result as $users){
	if($users["archived"] == 1){
		$archived_class = "user-archived";
	} else {
		$archived_class = "";
	}?>
						<div class="col-md-6 col-lg-4">
							<div class="panel panel-search <?php echo $archived_class;?>">
								<div class="panel-body user-entry" title="<?php echo $users["identity"];?>">
									<a href="user/<?php echo $users["user_id"];?>">
										<div class="col-lg-4 col-md-3 photo-space">
											<div class="small-user-pp visible-lg-block">
												<img src="<?php echo $users["photo"];?>" alt="<?php echo $users["identity"];?>">
											</div>
											<div class="notif-pp hidden-lg">
												<img src="<?php echo $users["photo"];?>" alt="<?php echo $users["identity"];?>">
											</div>
										</div>
										<div class="col-lg-8 col-md-9 details-space">
											<p class="panel-item-title bf"><?php echo $users["identity"];?></p>
											<p>
												<?php if($users["actif"] == 1){ ?>
												<span class="label label-success">Actif</span>
												<?php } else {  ?>
												<span class="label label-danger">Inactif</span>
												<?php } ?>
											</p>

											<p class="no-overflow">
												<span class="glyphicon glyphicon-envelope"></span> <?php echo ($users["mail"]!=null)?$users["mail"]:"-";?>
											</p>
											<p class="no-overflow">
												<span class="glyphicon glyphicon-phone"></span> <?php echo ($users["telephone"])?$users["telephone"]:"-";?>
											</p>
										</div>
									</a>
								</div>
							</div>
						</div>
						<?php }?>
					</div>
					<p class="search-title">
						<span class="glyphicon glyphicon-piggy-bank"></span> <?php echo $numberTransactions;?> transaction(s) trouvé(es)
					</p>
					<div class="list-group">
						<?php while ($transaction = $searchTransactions->fetch(PDO::FETCH_ASSOC)){ ?>
						<a href="user/<?php echo $transaction["payeur_transaction"];?>/achats#purchase-<?php echo $transaction["id_transaction"];?>" class="list-group-item">
							<div class="row">
								<div class="col-lg-6">
									<?php echo $transaction["id_transaction"];?>
								</div>
							</div>
						</a>
						<?php } ?>
					</div>
				</div>
			</div>
		</div>
		<style>
			.sub-legend{
				margin-bottom: 10px;
			}
		</style>
		<?php include "scripts.php";?>
		<script>
			$(window).on('load ready resize', function(){
				if(window.innerWidth >= 1200 && window.innerWidth < 1860){
					$(".details-space").removeClass("col-lg-8");
					$(".details-space").addClass("col-lg-12");
				} else {
					$(".details-space").removeClass("col-lg-12");
					$(".details-space").addClass("col-lg-8");
				}
			});
		</script>
	</body>
</html>
