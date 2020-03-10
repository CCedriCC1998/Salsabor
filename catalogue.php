<?php
session_start();
if(!isset($_SESSION["username"])){
	header('location: portal');
}
require_once 'functions/db_connect.php';
$db = PDOFactory::getConnection();

// On obtient la liste des produits
if(isset($_GET["user"])){
	$beneficiaireInitial = $_GET["user"];
}

$query = "SELECT * FROM produits p
			LEFT JOIN product_categories pc ON p.product_category = pc.category_id
			LEFT JOIN locations l ON p.product_location = l.location_id";
if(isset($_SESSION["location"]))
	$query .= " WHERE product_location = $_SESSION[location]";
$query .= " ORDER BY category_name ASC, product_name ASC";

$produits = $db->query($query);
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Catalogue de produits | Salsabor</title>
		<?php include "styles.php";?>
	</head>
	<body>
		<?php include "nav.php";?>
		<div class="container-fluid">
			<div class="row">
				<?php include "side-menu.php";?>
				<div class="col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
					<legend><span class="glyphicon glyphicon-shopping-cart"></span> Vente de produits <a href="personnalisation.php" role="button" class="btn btn-success" name="next"><span class="glyphicon glyphicon-erase"></span> Valider <span class="glyphicon glyphicon-arrow-right"></span></a>
					</legend>
					<div class="progress">
						<div class="progress-bar" role="progressbar" aria-valuenow="33" aria-valuemin="33" aria-valuemax="100" style="width:33.33%;">
							<span class="glyphicon glyphicon-th"></span> Etape 1/3 : Choix des produits
						</div>
					</div>
					<?php
					// Product list
					$current_category = -1;
					while($produit = $produits->fetch(PDO::FETCH_ASSOC)){
						$validite_semaines = $produit["product_validity"] / 7;
						if($validite_semaines < 1){
							$validite = $produit["product_validity"]." jour(s)";
						} else {
							$validite = $validite_semaines." semaine(s)";
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
										<span class="label label-salsabor" id="product-tag-<?php echo $label["entry_id"];?>" style="background-color:<?php echo $label["tag_color"];?>"><?php echo $label_name;?></span>
										<?php } ?>
									</h5>
								</div>
								<?php if($produit["description"] != ""){ ?>
								<p class="product-description"><?php echo $produit["description"];?></p>
								<?php } else { ?>
								<p class="product-description purchase-sub">Pas de description</p>
								<?php } ?>
								<p class="product-price"><?php echo $produit["product_price"];?> €</p>
								<input type="hidden" value="<?php echo $produit["product_id"];?>">
								<a href="#" class="btn btn-primary btn-block" role="button" name="add-shopping">Ajouter au panier</a>
							</div>
						</div>
					</div>
					<?php
						$current_category = $produit["product_category"];
					} ?>
				</div>
				<a href="" role="button" class="btn btn-success btn-block" name="next"><span class="glyphicon glyphicon-erase"></span> Valider les achats <span class="glyphicon glyphicon-arrow-right"></span></a>
			</div>
		</div>
		</div>
	<?php include "scripts.php";?>
	<script>
		$(document).ready(function(){
			// Bénéficiaire principal si la procédure a été entammée sur la page d'un utilisateur
			<?php if(isset($_GET["user"])){ ?>
			sessionStorage.setItem("beneficiaireInitial", '<?php echo $beneficiaireInitial;?>');
			<?php } else { ?>
			sessionStorage.removeItem("beneficiaireInitial");
			<?php } ?>
			$("[name='add-shopping']").click(function(){
				if(sessionStorage.getItem("panier") == null){
					var globalCart = [];
					var globalCartNames = [];
				} else {
					var globalCart = JSON.parse(sessionStorage.getItem("panier"));
					var globalCartNames = JSON.parse(sessionStorage.getItem("panier-noms"));
					composeURL(globalCart[0]);
				}
				var product_id = $(this).parents("div").children("input").val();
				var product_name = $(this).parents("div").children(".product-title").html();
				globalCart.push(product_id);
				globalCartNames.push(product_name);
				sessionStorage.setItem("panier", JSON.stringify(globalCart));
				sessionStorage.setItem("panier-noms", JSON.stringify(globalCartNames));
				composeURL(globalCart[0]);
				notifPanier();
			});
		});
	</script>
	</body>
</html>
