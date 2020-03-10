<?php
session_start();
if(!isset($_SESSION["username"])){
	header('location: portal');
}
require_once 'functions/db_connect.php';
include "functions/tools.php";
$db = PDOFactory::getConnection();

// Product categories
$categories = $db->query("SELECT * FROM product_categories ORDER BY category_name ASC");

// Locations
$locations = $db->query("SELECT * FROM locations ORDER BY location_name ASC");

if(isset($_POST["add"])){
	$product_size = 0;
	if($_POST["product_size"] != 0){
		$product_size = $_POST["product_size"];
	}
	$validite = 7 * $_POST["validite"];
	$actif = 1;
	$product_category = ($_POST["product_category"]=="0")?null:$_POST["product_category"];
	$product_location = ($_POST["product_location"]=="0")?null:$_POST["product_location"];
	$product_size = ($_POST["size_null"]=="0")?null:$_POST["product_size"];

	try{
		$db->beginTransaction();
		$new = $db->prepare("INSERT INTO produits(product_name, description, product_category, product_location, product_size, product_validity, product_price, counts_holidays, actif, echeances_paiement, autorisation_report)
		VALUES(:intitule, :description, :product_category, :product_location, :product_size, :validite, :product_price, :counts_holidays, :actif, :echeances, :autorisation_report)");
		$new->bindParam(':intitule', $_POST["intitule"]);
		$new->bindParam(':description', $_POST["description"]);
		$new->bindValue(':product_category', $product_category, PDO::PARAM_INT);
		$new->bindValue(':product_location', $product_location, PDO::PARAM_INT);
		$new->bindValue(':product_size', $product_size, PDO::PARAM_INT);
		$new->bindParam(':validite', $validite);
		$new->bindParam(':counts_holidays', $_POST["counts_holidays"], PDO::PARAM_INT);
		$new->bindParam(':product_price', $_POST["product_price"]);
		$new->bindParam(':actif', $actif);
		$new->bindParam(':echeances', $_POST["echeances"]);
		$new->bindParam(':autorisation_report', $_POST["autorisation_report"]);
		$new->execute();
		logAction($db, "Ajout", "produits-".$db->lastInsertId());
		$db->commit();
		header("Location: forfaits?region=1");
	}catch (PDOException $e){
		$db->rollBack();
		var_dump($e->getMessage());
	}
}
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Ajouter un forfait | Salsabor</title>
		<?php include "styles.php";?>
		<?php include "scripts.php";?>
	</head>
	<body>
		<?php include "nav.php";?>
		<div class="container-fluid">
			<div class="row">
				<?php include "side-menu.php";?>
				<div class="col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
					<form action="forfait_add.php" class="form-horizontal" method="post">
						<legend><span class="glyphicon glyphicon-plus"></span> Ajouter un forfait
							<input type="submit" name="add" role="button" class="btn btn-primary" value="ENREGISTRER">
						</legend>
						<p class="sub-legend">Informations générales</p>
						<div class="form-group">
							<label for="intitule" class="control-label col-lg-3">Intitulé</label>
							<div class="col-lg-9">
								<input type="text" class="form-control" name="intitule" placeholder="Nom du produit">
							</div>
						</div>
						<div class="form-group">
							<label for="product_price" class="col-lg-3 control-label">Prix d'achat</label>
							<div class="col-lg-9">
								<div class="input-group">
									<input type="number" step="any" class="form-control" name="product_price">
									<span class="input-group-addon">€</span>
								</div>
							</div>
						</div>
						<div class="form-group">
							<label for="description" class="col-lg-3 control-label">Description</label>
							<div class="col-lg-9">
								<textarea rows="5" class="form-control" name="description" placeholder="Décrivez rapidement le produit en 100 caractères maximum (facultatif)"></textarea>
							</div>
						</div>
						<div class="form-group">
							<label for="size_null" class="col-lg-3 control-label">Utilisable en cours <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" title="Autorise la consommation du produit lors de cours ou d'événements"></span></label>
							<div class="col-lg-9">
								<input name="size_null" id='size-null' data-toggle="checkbox-x" data-size="lg" data-three-state="false" value="1">
							</div>
						</div>
						<div class="form-group" id="product-size-group">
							<label for="product_size" class="col-lg-3 control-label">Volume de cours (en heures) <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" title="Spécifiez 0 pour une formule illimitée"></span></label>
							<div class="col-lg-9">
								<input type="number" class="form-control" name="product_size" placeholder="Spécifiez 0 pour une formule illimitée.">
							</div>
						</div>
						<div class="form-group">
							<label for="validite" class="col-lg-3 control-label">Durée de validité</label>
							<div class="col-lg-9">
								<input type="number" class="form-control" name="validite" placeholder="Exemple : 48">
								<label for="validite_jour" class="control-label">Jours</label>
								<input name="validite_jour" id="validite_jour" data-toggle="checkbox-x" data-size="lg" data-three-state="false" value="0"><span class="help-block">Si décoché, la durée sera calculée en semaines.</span>
							</div>
						</div>
						<p class="sub-legend">Informations de gestion</p>
						<div class="form-group">
							<label for="product_code" class="control-label col-lg-3">Code produit</label>
							<div class="col-lg-9">
								<input type="text" class="form-control" name="product_code" value="" placeholder="Code du produit">
							</div>
						</div>
						<div class="form-group">
							<label for="product_category" class="control-label col-lg-3">Catégorie</label>
							<div class="col-lg-9">
								<select name="product_category" class="form-control">
									<option value="0">Aucune catégorie</option>
									<?php while($category = $categories->fetch(PDO::FETCH_ASSOC)){ ?>
									<option value="<?php echo $category["category_id"];?>"><?php echo $category["category_name"];?></option>
									<?php } ?>
								</select>
							</div>
						</div>
						<div class="form-group">
							<label for="product_location" class="control-label col-lg-3">Région de disponibilité <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" title="Restreint la vente du produit à la région désignée. Laissez vide pour que le produit soit disponible partout."></span></label>
							<div class="col-lg-9">
								<select name="product_location" class="form-control">
									<option value="0">Pas de région</option>
									<?php while($location = $locations->fetch(PDO::FETCH_ASSOC)){ ?>
									<option value="<?php echo $location["location_id"];?>"><?php echo $location["location_name"];?></option>
									<?php } ?>
								</select>
							</div>
						</div>
						<div class="form-group">
							<label for="autorisation_report" class="col-lg-3 control-label">Extension de validité autorisée</label>
							<div class="col-lg-9">
								<input name="autorisation_report" data-toggle="checkbox-x" data-size="lg" data-three-state="false" value="1">
							</div>
						</div>
						<div class="form-group">
							<label for="counts_holidays" class="col-lg-3 control-label">Prise en compte des jours chômés <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" title="Détermine si le calcul de la validité doit prendre en compte les jours chômés ou non."></span></label>
							<div class="col-lg-9">
								<input name="counts_holidays" data-toggle="checkbox-x" data-size="lg" data-three-state="false" value="1">
							</div>
						</div>
						<div class="form-group">
							<label for="echeances" class="col-lg-3 control-label">Nombre d'échéances autorisées</label>
							<div class="col-lg-9">
								<input type="number" class="form-control" name="echeances" value="1">
							</div>
						</div>
						<!--<p class="sub-legend">Période de vente</p>
<p class="help-block">Dans le cas d'une offre promotionnelle limitée dans le temps</p>
<div class="form-group">
<label for="date_activation" class="col-lg-3 control-label">Ouverture à l'achat</label>
<div class="col-lg-9">
<div class="input-group">
<input type="date" class="form-control" name="date_activation">
<span role="button" class="input-group-btn">
<a class="btn btn-info" role="button" date-today="true">Insérer aujourd'hui</a>
</span>
</div>
</div>
</div>
<div class="form-group">
<label for="date_limite_achat" class="col-lg-3 control-label">Fermeture à l'achat</label>
<div class="col-lg-9">
<div class="input-group">
<input type="date" class="form-control" name="date_limite_achat">
<span role="buttton" class="input-group-btn"><a class="btn btn-info" role="button" date-today="true">Insérer aujourd'hui</a></span>
</div>
</div>
</div>-->
					</form>
				</div>
			</div>
		</div>
		<script>
			$('#size-null').on('change', function(){
				console.log($(this));
				if($(this).val() == "1"){
					$("#product-size-group").show();
				} else {
					$("#product-size-group").hide();
				}
			})
		</script>
	</body>
</html>
