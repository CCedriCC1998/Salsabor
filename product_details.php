<?php
session_start();
if(!isset($_SESSION["username"])){
	header('location: portal');
}
require_once 'functions/db_connect.php';
include "functions/tools.php";
$db = PDOFactory::getConnection();

$data = $_GET["id"];

// Product details
$queryProduit = $db->prepare("SELECT * FROM produits WHERE product_id=?");
$queryProduit->bindParam(1, $data, PDO::PARAM_INT);
$queryProduit->execute();
$produit = $queryProduit->fetch(PDO::FETCH_ASSOC);

// Product categories
$categories = $db->query("SELECT * FROM product_categories ORDER BY category_name ASC");

// Locations
$locations = $db->query("SELECT * FROM locations ORDER BY location_name ASC");

// Labels
$labels = $db->prepare("SELECT * FROM assoc_product_tags apt
						JOIN tags_session ts ON apt.tag_id_foreign = ts.rank_id
						WHERE product_id_foreign = ?
						ORDER BY tag_color DESC");
$labels->bindParam(1, $data, PDO::PARAM_INT);
$labels->execute();

if(isset($_POST["edit"])){
	if($_POST["validite_jour"] == "1"){
		$validite = $_POST["validite"];
	} else {
		$validite = 7 * $_POST["validite"];
	}
	$actif = 1;

	$product_category = ($_POST["product_category"]=="0")?null:$_POST["product_category"];
	$product_location = ($_POST["product_location"]=="0")?null:$_POST["product_location"];

	try{
		$db->beginTransaction();
		$edit = $db->prepare("UPDATE produits SET product_name = :product_name,
												product_code = :product_code,
												description = :description,
												product_category = :product_category,
												product_location = :product_location,
												product_size = :product_size,
												product_validity = :validite,
												product_price = :product_price,
												counts_holidays = :counts_holidays,
												actif = :actif,
												echeances_paiement = :echeances,
												autorisation_report = :autorisation_report
												WHERE product_id = :product_id");
		$edit->bindParam(':product_name', $_POST["product_name"], PDO::PARAM_STR);
		$edit->bindParam(':product_code', $_POST["product_code"], PDO::PARAM_STR);
		$edit->bindParam(':description', $_POST["description"], PDO::PARAM_STR);
		$edit->bindValue(':product_category', $product_category, PDO::PARAM_INT);
		$edit->bindValue(':product_location', $product_location, PDO::PARAM_INT);
		if($_POST["size_null"] == '1')
			$edit->bindParam(':product_size', $_POST["product_size"], PDO::PARAM_INT);
		else
			$edit->bindValue(':product_size', NULL, PDO::PARAM_NULL);
		$edit->bindParam(':validite', $validite, PDO::PARAM_INT);
		$edit->bindParam(':counts_holidays', $_POST["counts_holidays"], PDO::PARAM_INT);
		$edit->bindParam(':product_price', $_POST["product_price"], PDO::PARAM_INT);
		$edit->bindParam(':actif', $actif, PDO::PARAM_INT);
		$edit->bindParam(':echeances', $_POST["echeances"]);
		$edit->bindParam(':autorisation_report', $_POST["autorisation_report"], PDO::PARAM_INT);
		$edit->bindParam(':product_id', $_GET["id"], PDO::PARAM_INT);
		$edit->execute();
		logAction($db, "Modification", "produits-".$_GET["id"]);
		$db->commit();
		header('Location: ../forfaits?region=1');
	}catch (PDOException $e){
		$db->rollBack();
		var_dump($e->getMessage());
		/*var_dump($edit->debugDumpParams());*/
	}
}
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Détails du forfait <?php echo $produit["product_name"];?> | Salsabor</title>
		<base href="../">
		<?php include "styles.php";?>
		<?php include "scripts.php";?>
		<script src="assets/js/tags.js"></script>
	</head>
	<body>
		<?php include "nav.php";?>
		<div class="container-fluid">
			<div class="row">
				<?php include "side-menu.php";?>
				<div class="col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
					<form action="" class="form-horizontal" id="form-product" method="post">
						<legend><span class="glyphicon glyphicon-credit-card"></span> <?php echo $produit["product_name"];?>
							<input type="submit" name="edit" role="button" class="btn btn-primary hidden-xs edit-product" value="Enregistrer">
						</legend>
						<p class="sub-legend">Informations générales</p>
						<div class="form-group">
							<label for="product_name" class="control-label col-lg-3">Intitulé</label>
							<div class="col-lg-9">
								<input type="text" class="form-control" name="product_name" value="<?php echo $produit["product_name"];?>" placeholder="Nom du produit">
							</div>
						</div>
						<div class="form-group">
							<label for="" class="col-lg-3 control-label">&Eacute;tiquettes</label>
							<div class="col-lg-9 session-tags">
								<h4 class="tags_container">
									<?php while($label = $labels->fetch(PDO::FETCH_ASSOC)){
	if($label["is_mandatory"] == 1){
		$label_name = "<span class='glyphicon glyphicon-star'></span> ".$label["rank_name"];
	} else {
		$label_name = $label["rank_name"];
	}
									?>
									<span class="label label-salsabor" title="<?php echo $label_name;?>" style="background-color:<?php echo $label["tag_color"];?>"><?php echo $label_name;?></span>
									<?php } ?>
									<span class="label label-default label-clickable label-add trigger-sub" id="label_add" data-subtype="session-tags" data-targettype="product" title="Ajouter une étiquette">+</span>
								</h4>
							</div>
						</div>
						<div class="form-group">
							<label for="product_price" class="col-lg-3 control-label">Prix d'achat</label>
							<div class="col-lg-9">
								<div class="input-group">
									<input type="number" step="any" class="form-control" name="product_price" id="product-price" value="<?php echo $produit["product_price"];?>">
									<span class="input-group-addon">€</span>
								</div>
							</div>
						</div>
						<div class="form-group">
							<label for="description" class="col-lg-3 control-label">Description</label>
							<div class="col-lg-9">
								<textarea rows="5" class="form-control" name="description" placeholder="Décrivez rapidement le produit en 100 caractères maximum (facultatif)"><?php echo $produit["description"];?></textarea>
							</div>
						</div>
						<div class="form-group">
							<label for="size_null" class="col-lg-3 control-label">Utilisable en cours <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" title="Autorise la consommation du produit lors de cours ou d'événements"></span></label>
							<div class="col-lg-9">
								<input name="size_null" id='size-null' data-toggle="checkbox-x" data-size="lg" data-three-state="false" value="<?php echo ($produit["product_size"]!=null)?1:0;?>">
							</div>
						</div>
						<div class="form-group" id="product-size-group">
							<label for="product_size" class="col-lg-3 control-label">Volume de cours (en heures) <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" title="Spécifiez 0 pour une formule illimitée."></span></label>
							<div class="col-lg-9">
								<input type="number" class="form-control" name="product_size" id='product-size-input' value="<?php echo $produit["product_size"];?>" placeholder="Spécifiez 0 pour une formule illimitée.">
							</div>
						</div>
						<div class="form-group">
							<label for="validite" class="col-lg-3 control-label">Durée de validité</label>
							<div class="col-lg-9">
								<input type="number" class="form-control" name="validite" value="<?php echo $produit["product_validity"];?>" placeholder="Exemple : 48">
								<label for="validate_jour" class="control-label">Jours</label>
								<input name="validite_jour" id="validite_jour" data-toggle="checkbox-x" data-size="lg" data-three-state="false" value="1"><p class="help-block">Si décoché, la durée sera calculée en semaines.</p>
							</div>
						</div>
						<p class="sub-legend">Informations de gestion</p>
						<div class="form-group">
							<label for="product_code" class="control-label col-lg-3">Code produit</label>
							<div class="col-lg-9">
								<input type="text" class="form-control" name="product_code" value="<?php echo $produit["product_code"];?>" placeholder="Code du produit">
							</div>
						</div>
						<div class="form-group">
							<label for="product_category" class="control-label col-lg-3">Catégorie</label>
							<div class="col-lg-9">
								<select name="product_category" class="form-control">
									<option value="0">Aucune catégorie</option>
									<?php while($category = $categories->fetch(PDO::FETCH_ASSOC)){
	if($produit["product_category"] == $category["category_id"]) {?>
									<option selected="selected" value="<?php echo $category["category_id"];?>"><?php echo $category["category_name"];?></option>
									<?php } else { ?>
									<option value="<?php echo $category["category_id"];?>"><?php echo $category["category_name"];?></option>
									<?php }
} ?>
								</select>
							</div>
						</div>
						<div class="form-group">
							<label for="product_location" class="control-label col-lg-3">Région de disponibilité <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" title="Restreint la vente du produit à la région désignée. Laissez vide pour que le produit soit disponible partout."></span></label>
							<div class="col-lg-9">
								<select name="product_location" class="form-control">
									<option value="0">Pas de région</option>
									<?php while($location = $locations->fetch(PDO::FETCH_ASSOC)){
	if($produit["product_location"] == $location["location_id"]){ ?>
									<option selected value="<?php echo $location["location_id"];?>"><?php echo $location["location_name"];?></option>
									<?php } else { ?>
									<option value="<?php echo $location["location_id"];?>"><?php echo $location["location_name"];?></option>
									<?php }
} ?>
								</select>
							</div>
						</div>
						<div class="form-group">
							<label for="autorisation_report" class="col-lg-3 control-label">Extension de validité autorisée</label>
							<div class="col-lg-9">
								<input name="autorisation_report" data-toggle="checkbox-x" data-size="lg" data-three-state="false" value="<?php echo $produit["autorisation_report"];?>">
							</div>
						</div>
						<div class="form-group">
							<label for="counts_holidays" class="col-lg-3 control-label">Prise en compte des jours chômés <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" title="Détermine si le calcul de la validité doit prendre en compte les jours chômés ou non."></span></label>
							<div class="col-lg-9">
								<input name="counts_holidays" data-toggle="checkbox-x" data-size="lg" data-three-state="false" value="<?php echo $produit["counts_holidays"];?>">
							</div>
						</div>
						<div class="form-group">
							<label for="echeances" class="col-lg-3 control-label">Nombre d'échéances autorisées</label>
							<div class="col-lg-9">
								<input type="number" class="form-control" name="echeances" value="<?php echo $produit["echeances_paiement"];?>">
							</div>
						</div>
						<input type="submit" name="edit" role="button" class="btn btn-primary btn-block visible-xs edit-product" value="Enregistrer">
					</form>
				</div>
			</div>
		</div>
		<?php include "inserts/sub_modal_product.php";?>
	</body>
	<script>
		$(document).ready(function(){
			initial_tags = createTagsArray();
			console.log(initial_tags);
			if($("#size-null").val() == 0){
				$("#product-size-group").hide();
			}
			window.initial_form = $("#form-product").serialize();
			$(window).on('beforeunload', function(){
				var current_form = $("#form-product").serialize();
				console.log(window.initial_form, current_form);
				if(current_form !== window.initial_form)
					return "Vous avez des modifications non enregistrées, êtes-vous sûr de vouloir quitter la page ?";
			})
		}).on('click', '.edit-product', function(){
			var current_tags = createTagsArray(), entry_id = /([0-9]+)/.exec(top.location.pathname)[0];
			updateTargetTags(initial_tags, current_tags, entry_id, "product");
			window.initial_form = $("#form-product").serialize();
		})
		$('#size-null').on('change', function(){
			console.log($(this));
			if($(this).val() == "1"){
				$("#product-size-group").show();
			} else {
				$("#product-size-group").hide();
			}
		})
	</script>
</html>
