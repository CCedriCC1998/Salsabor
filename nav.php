<?php
require_once 'functions/db_connect.php';
$db = PDOFactory::getConnection();

$locationsNotif = $db->query("SELECT * FROM reservations WHERE booking_paid=0 AND priorite=1")->rowCount();
?>

<div class="visible-xs-block">
	<nav class="navbar navbar-inverse">
		<div class="container-fluid">
			<div class="navbar-header">
				<a href="dashboard" class="navbar-brand"><img src="assets/images/logotest.png" alt="Salsabor Gestion" style="height:100%;"></a>
				<?php if(isset($_SESSION["username"])){ ?>
				<button class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar">
					<div class="nav-pp">
						<img src="<?php echo $_SESSION["photo"];?>" alt="<?php echo $_SESSION["username"];?>" style="width:inherit;">
					</div>
				</button>
				<button class="navbar-toggle sub-menu-toggle">
					<span class="glyphicon glyphicon-menu-hamburger"></span>
				</button>
				<?php } else { ?>
				<button class="navbar-toggle collapse" data-toggle="collapse" data-target="#navbar">
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<?php } ?>
			</div>
			<div id="navbar" class="navbar-collapse collapse">
				<ul class="nav navbar">
					<li><a class="col-xs-3 small-nav" href="taches/user"><span class="glyphicon glyphicon-list-alt"></span> Tâches</a></li>
					<li><a class="col-xs-3 small-nav" href="notifications"><span class="glyphicon glyphicon-bell"></span> Notifications</a></li>
					<li><a href="user/<?php echo $_SESSION["user_id"];?>" class="col-xs-3 small-nav"><span class="glyphicon glyphicon-user"></span> Profil</a></li>
					<li><a class="col-xs-3 small-nav" href="logout.php"><span class="glyphicon glyphicon-off"></span> Déconnexion</a></li>
				</ul>
			</div>
		</div>
	</nav>
</div>

<div class="visible-sm visible-md visible-lg">
	<nav class="navbar navbar-inverse navbar-fixed-top">
		<div class="container-fluid">
			<div class="navbar-header">
				<a href="dashboard" class="navbar-brand"><img src="assets/images/logotest.png" alt="Salsabor Gestion" style="height:100%;"></a>
			</div>
			<?php if(isset($_SESSION["username"])){ ?>
			<div class="col-sm-6 col-lg-7">
				<form action="search.php" class="navbar-form navbar-left" role="search">
					<div class="input-group">
						<span class="input-group-addon"><span class="glyphicon glyphicon-search"></span></span>
						<input type="text" class="form-control nav-input" name="search_terms" placeholder="Rechercher">
						<input type="hidden" name="archive" value="1">
						<input type="hidden" name="region" value="1">
					</div>
					<!--<button type="submit" class="btn btn-default">Rechercher</button>-->
				</form>
			</div>
			<ul class="nav navbar-nav navbar-right">
				<li class="notification-option">
					<a class="notification-icon trigger-nav">
						<span class="glyphicon glyphicon-bell"></span>
						<span class="badge badge-notifications" id="badge-notifications"></span>
					</a>
				</li>
				<li class="notification-option">
					<a href="#" class="notification-icon" data-toggle="popover-x" data-target="#popoverPanier" data-trigger="focus" data-placement="bottom bottom-right">
						<span class="glyphicon glyphicon-shopping-cart"></span>
						<span class="badge" id="badge-panier"></span>
					</a>
					<div class="popover popover-default popover-md" id="popoverPanier">
						<div class="arrow"></div>
						<div class="popover-title"><span class="close" data-dismiss="popover-x">&times;</span>Panier en cours</div>
						<div class="popover-content">
							<table class="table-panier">
							</table>
						</div>
						<div class="popover-footer">
							<a href="" class="btn btn-success btn-block" role="button" name="next">Valider les achats</a>
						</div>
					</div>
				</li>
				<li class="dropdown notification-option">
					<a href="#" class="dropdown-toggle notification-icon nav-img-container" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
						<div class="nav-pp">
							<img src="<?php echo $_SESSION["photo"];?>" alt="" style="width:inherit;">
						</div>
					</a>
					<ul class="dropdown-menu dropdown-custom">
						<li><a href="user/<?php echo $_SESSION["user_id"];?>"><span class="glyphicon glyphicon-user"></span> Profil</a></li>
						<li><a href="logout.php"><span class="glyphicon glyphicon-off"></span> Déconnexion</a></li>
					</ul>
				</li>
			</ul>
			<?php }?>
		</div>
	</nav>
</div>

<?php include "inserts/sub_modal_notifications.php";?>
