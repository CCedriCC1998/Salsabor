<?php
require_once 'functions/db_connect.php';
$db = PDOFactory::getConnection();

$locationsNotif = $db->query("SELECT * FROM reservations WHERE booking_paid=0 AND priorite=1")->rowCount();
?>

<nav class="navbar navbar-inverse navbar-fixed-top">
	<div class="container-fluid">
		<div class="hidden-sm hidden-md hidden-lg">
			<div class="navbar-header">
				<a href="my/profile" class="navbar-brand"><img src="assets/images/salsabor_perso.png" alt="Mon Salsabor" style="height:100%;"></a>
				<?php if(isset($_SESSION["username"])){ ?>
				<button class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar">
					<div class="nav-pp">
						<img src="<?php echo $_SESSION["photo"];?>" alt="<?php echo $_SESSION["username"];?>" style="width:inherit;">
					</div>
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
					<li><a href="my/profile" class="small-nav"><span class="glyphicon glyphicon-user"></span> Mes infos</a></li>
					<li><a href="my/products" class="small-nav"><span class="glyphicon glyphicon-th"></span> Ma conso</a></li>
					<li><a class="small-nav" href="logout.php"><span class="glyphicon glyphicon-off"></span> Déconnexion</a></li>
				</ul>
			</div>
		</div>
		<div class="visible-sm visible-md visible-lg">
			<div class="navbar-header">
				<a href="my/profile" class="navbar-brand"><img src="assets/images/salsabor_perso.png" alt="Salsabor Gestion" style="height:100%;"></a>
			</div>
			<?php if(isset($_SESSION["username"])){ ?>
			<ul class="nav navbar-nav navbar-right">
				<li class="dropdown notification-option">
					<a href="#" class="dropdown-toggle notification-icon nav-img-container" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
						<div class="nav-pp">
							<img src="<?php echo $_SESSION["photo"];?>" alt="" style="width:inherit;">
						</div>
					</a>
					<ul class="dropdown-menu dropdown-custom">
						<li><a href="my/profile"><span class="glyphicon glyphicon-user"></span> Mes infos</a></li>
						<li><a href="my/products"><span class="glyphicon glyphicon-th"></span> Ma conso</a></li>
						<li><a href="logout.php"><span class="glyphicon glyphicon-off"></span> Déconnexion</a></li>
					</ul>
				</li>
			</ul>
			<?php }?>
		</div>
	</div>
</nav>
<?php include "inserts/sub_modal_notifications.php";?>

