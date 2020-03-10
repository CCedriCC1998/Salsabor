<?php
session_start();
if(!isset($_SESSION["username"])){
	header('location: portal');
}
require_once 'functions/db_connect.php';
$db = PDOFactory::getConnection();

$categories = $db->query("SELECT * FROM product_categories");
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Catégories | Salsabor</title>
		<?php include "styles.php";?>
		<?php include "scripts.php";?>
	</head>
	<body>
		<?php include "nav.php";?>
		<div class="container-fluid">
			<div class="row">
				<?php include "side-menu.php";?>
				<div class="col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
					<legend><span class="glyphicon glyphicon-credit-card"></span> Catégories de produits
						<button class="btn btn-primary maturities-button" data-toggle="modal" data-target="#add-modal">Ajouter une catégorie</button>
					</legend>
					<div>
						<div class="categories-list">
							<?php while($category = $categories->fetch(PDO::FETCH_ASSOC)){ ?>
							<div class="category-<?php echo $category["category_id"];?>" id="category-<?php echo $category["category_id"];?>">
								<p class="panel-item-title bf col-xs-10 modal-editable-<?php echo $category["category_id"];?>" id="editable-title-<?php echo $category["category_id"];?>" data-field="category_name" data-name="Catégorie"><?php echo $category["category_name"];?></p>
								<p class="col-xs-1">
									<span class="glyphicon glyphicon-pencil glyphicon-button glyphicon-button-alt edit-entity" id="edit-'<?php echo $category["category_id"];?>'" data-toggle="modal" data-target="#edit-modal" data-entry="<?php echo $category["category_id"];?>" data-table="product_categories" title="Modifier la catégorie"></span>
								</p>
								<p class="col-xs-1">
									<span class="glyphicon glyphicon-trash glyphicon-button glyphicon-button-alt delete-entity" id="delete-<?php echo $category["category_id"];?>" data-toggle="modal" data-target="#delete-modal" data-entry="<?php echo $category["category_id"];?>" data-delete=".category-<?php echo $category["category_id"];?>" data-table="product_categories" title="Supprimer la catégorie <?php echo $category["category_name"];?>"></span>
								</p>
							</div>
							<?php } ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php include "inserts/edit_modal.php";?>
		<?php include "inserts/delete_modal.php";?>
		<!-- Add modal is specific -->
		<div class="modal fade" id="add-modal" tabindex="-1" role="dialog">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<button class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						<h4 class="modal-title">Ajouter une catégorie</h4>
					</div>
					<div class="modal-body container-fluid">
						<div class="add-form-space">
							<form action="form-horizontal" id="modal-add-form">
								<div class="form-group">
									<label for="category_name" class="control-label col-lg-4">Catégorie</label>
									<div class="col-lg-8">
										<input type="text" class="form-control" name="category_name" value="">
									</div>
								</div>
							</form>
						</div>
					</div>
					<div class="modal-footer">
						<button class="btn btn-primary add-data">Ajouter la catégorie</button>
					</div>
				</div>
			</div>
		</div>
		<script>
			$(document).on('click', '.add-data', function(){
				var values = $("#modal-add-form").serialize();
				var category_name = $("input[name='category_name']").val();
				/*console.log(values);*/
				$.when(addEntry("product_categories", values)).done(function(data){
					var new_rate = "<div class='category-"+data+"' id='category-"+data+"'>";
					new_rate += "<p class='panel-item-title bf col-xs-10 modal-editable-"+data+"' id='editable-title-"+data+"' data-field='category_name' data-name='Catégorie'>"+category_name+"</p>";
					new_rate += "<p class='col-xs-1'>";
					new_rate += "<span class='glyphicon glyphicon-pencil glyphicon-button glyphicon-button-alt' id='edit-"+data+"' data-toggle='modal' data-target='#edit-modal' data-entry='"+data+"' data-table='product_categories' title='Modifier la catégorie'>";
					new_rate += "</p>";
					new_rate += "<p class='col-xs-1'>";
					new_rate += "<span class='glyphicon glyphicon-trash glyphicon-button glyphicon-button-alt' id='delete-"+data+"' data-toggle='modal' data-target='#delete-modal' data-entry='"+data+"' data-delete='.category-"+data+"' data-table='product_categories' title='Supprimer la catégorie'>";
					new_rate += "</p>";
					$(".categories-list").append(new_rate);
					showNotification("Catégorie ajoutée", "success");
					// Empty the fields
					$("input[name='category_name']").val("");
					$("#add-modal").modal('hide');
				})
			})
		</script>
	</body>
</html>
