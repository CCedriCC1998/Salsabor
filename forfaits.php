<?php
session_start();
if(!isset($_SESSION["username"])){
	header('location: portal');
}
require_once 'functions/db_connect.php';
$db = PDOFactory::getConnection();

$is_admin = $db->query("SELECT COUNT(*) FROM assoc_user_tags aut
				JOIN tags_user tu ON aut.tag_id_foreign = tu.rank_id
				WHERE rank_name = 'Super Admin' AND aut.user_id_foreign = $_SESSION[user_id]")->fetch(PDO::FETCH_COLUMN);

$query = "SELECT * FROM produits p
			LEFT JOIN product_categories pc ON p.product_category = pc.category_id
			LEFT JOIN locations l ON p.product_location = l.location_id";
if($_GET["region"] == "1" && $_SESSION["location"] != null)
	$query .= " WHERE product_location = $_SESSION[location]";
$query .= " ORDER BY category_name ASC, product_name ASC";

$produits = $db->query($query);
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Forfaits | Salsabor</title>
		<?php include "styles.php";?>
	</head>
	<body>
		<?php include "nav.php";?>
		<div class="container-fluid">
			<div class="row">
				<?php include "side-menu.php";?>
				<div class="col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
					<legend><span class="glyphicon glyphicon-credit-card"></span> Forfaits
						<div class="btn-group float-right">
							<?php if($is_admin == 1){
							if($_GET["region"] == "1"){ ?>
							<a href="forfaits?region=0" role="button" class="btn btn-primary"><span class="glyphicon glyphicon-globe"></span> Inclure toutes les régions</a>
							<?php } else { ?>
							<a href="forfaits?region=1" role="button" class="btn btn-primary"><span class="glyphicon glyphicon-globe"></span> Exclure les autres régions</a>
							<?php }
} ?>
							<a href="forfait_add.php" role="button" class="btn btn-primary"><span class="glyphicon glyphicon-plus"></span> Ajouter un forfait</a>
						</div>
					</legend>
					<?php
					$current_category = -1;
					while($produit = $produits->fetch(PDO::FETCH_ASSOC)){
						$validite_semaines = $produit["product_validity"] / 7;
						if($validite_semaines < 1){
							$validite = $produit["product_validity"]." jour(s)";
						} else {
							$validite = $validite_semaines." sem.";
						}
						if($produit["product_category"] != $current_category){
							if($current_category != -1){?>
				</div> <!-- Closing previous category -->
				<?php } ?>
				<p class='sub-legend'><?php echo $produit["category_name"];?></p>
				<div class="category row">
					<?php } ?>
					<div class="col-xs-12 col-md-4 col-lg-3 panel-product-container">
						<div class="panel panel-product">
							<div class="panel-body">
								<p class="product-title"><?php echo $produit["product_name"];?></p>
								<?php $labels = $db->prepare("SELECT * FROM assoc_product_tags apt
						JOIN tags_session ts ON apt.tag_id_foreign = ts.rank_id
						WHERE product_id_foreign = ?
						ORDER BY tag_color DESC");
						$labels->bindParam(1, $produit["product_id"], PDO::PARAM_INT);
						$labels->execute(); ?>
								<div class="row">
									<p class="col-xs-6"><span class="glyphicon glyphicon-time" title="Durée de validité"></span> <?php echo $validite;?></p>
									<p class="col-xs-6"><span class="glyphicon glyphicon-globe" title="Région de disponibilité"></span> <?php echo $produit["location_name"];?></p>
								</div>
								<div class="tags-display">
									<h5>
										<?php while($label = $labels->fetch(PDO::FETCH_ASSOC)){
							if($label["is_mandatory"] == 1){
								$label_name = "<span class='glyphicon glyphicon-star'></span> ".$label["rank_name"];
							} else {
								$label_name = $label["rank_name"];
							}
										?>
										<span class="label label-salsabor" title="Supprimer l'étiquette" id="product-tag-<?php echo $label["entry_id"];?>" data-target="<?php echo $label["entry_id"];?>" data-targettype="product" style="background-color:<?php echo $label["tag_color"];?>"><?php echo $label_name;?></span>
										<?php } ?>
									</h5>
								</div>
								<?php if($produit["description"] != ""){ ?>
								<p class="product-description"><?php echo $produit["description"];?></p>
								<?php } else { ?>
								<p class="product-description purchase-sub">Pas de description</p>
								<?php } ?>
								<p class="product-price"><?php echo $produit["product_price"];?> €</p>
								<a href="forfait/<?php echo $produit["product_id"];?>" class="btn btn-default btn-block"><span class="glyphicon glyphicon-search"></span> Détails...</a>
							</div>
						</div>
					</div>
					<?php $current_category = $produit["product_category"];
					} ?>
				</div>
			</div>
		</div>
		<?php include "scripts.php";?>
	</body>
</html>
