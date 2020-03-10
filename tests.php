<?php
session_start();
//require_once 'functions/db_connect.php';
require_once "functions/mails.php";
//require_once "functions/tools.php";
require_once "functions/post_task.php";
require_once "functions/attach_tag.php";
require_once "functions/activate_product.php";
$db = PDOFactory::getConnection();

?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Template - Salsabor</title>
		<?php include "styles.php";?>
		<?php include "scripts.php";?>
		<script src="assets/js/raphael-min.js"></script>
		<script src="assets/js/morris.min.js"></script>
	</head>
	<body>
		<?php include "nav.php";?>
		<div class="container-fluid">
			<div class="row">
				<?php include "side-menu.php";?>
				<div class="col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
					<legend><span class="glyphicon glyphicon-warning-sign"></span> Page Test !</legend>

					<div class="container-fluid">
              <p class="help-block">Filtrez les transactions effectuées par période</p>
							<form action="tests.php" method="post">
              <div class="form-group">
                  <label for="" class="control-label col-xs-3">Date de début</label>
                  <label for="" class="control-label col-xs-3">Date de fin</label>
                  <label for="" class="control-label col-xs-6">Produits
                      <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" title="Vous pouvez ajouter des produits à l'espace ci-dessous pour filtrer les résutlats. Si vous laissez l'encadré vide, tous les produits seront sélectionnés (par défaut)."></span>
                  </label>
                  <div class="col-xs-3">
                      <input type="text" class="form-control date-filter" id="datepicker-start" name="date_start" >
                  </div>
                  <div class="col-xs-3">
                      <input type="text" class="form-control date-filter" id="datepicker-end" name="date_end" >
                  </div>
                  <div class="col-xs-4">
                      <input type="text" class="form-control" id="product-box-input" name="product" placeholder="Cherchez un produit...">
                      <div class="product-box" name="product[]"></div>
                  </div>
              </div>
							<div class="form-actions col-xs-2">
                <button type="submit" class="btn btn-success">Filtrer</button>
              </div>
							</form>
          </div>
					<br><br>
					<?php
					$loading = microtime();
					$loading = explode(' ', $loading);
					$loading = $loading[1] + $loading[0];
					$start = $loading;
					/** CODE **/

					?>
					<pre>
						<?php
						//echo $id_transaction;

						echo "<br>";
						print_r();
						//echo implode(", ", );
						?>
					</pre>

					<?php
					/** /CODE **/
					$loading = microtime();
					$loading = explode(' ', $loading);
					$loading = $loading[1] + $loading[0];
					$finish = $loading;
					$total = round(($finish - $start), 4);
					echo "<br>Traitement effectué en ".$total." secondes";
					?>
				</div>
			</div>
		</div>
		<script>
				$(document).ready(function(){
						window.products_array = [];
						$("#datepicker-start").datetimepicker({
								format: "DD/MM/YYYY",
								defaultDate: moment().subtract(1, 'year'),
								locale: "fr",
								sideBySide: true,
								stepping: 15
						}).on('dp.change', function(e){
								fetchTransactionsStats();
						});
						$("#datepicker-end").datetimepicker({
								format: "DD/MM/YYYY",
								defaultDate: moment(),
								locale: "fr",
								sideBySide: true,
								stepping: 15
						}).on('dp.change', function(e){
								fetchTransactionsStats();
						});

						$.get("functions/get_product_list.php").done(function(data){
								var product_list = JSON.parse(data);
								$("#product-box-input").textcomplete([{
										match: /(^|\b)(\w{2,})$/,
										search: function(term, callback){
												callback($.map(product_list, function (item) {
														return item.toLocaleLowerCase().indexOf(term.toLocaleLowerCase()) === 0 ? item : null;
												}));
										},
										//enlever l'item qui à été ajouté
										replace: function(item){
												var random_id = Math.random() * (100000 - 1) + 1;
												$(".product-box").append("<span class='label label-default label-filter' id='label-"+random_id+"'>"+item+" <span class='glyphicon glyphicon-remove'></span></span>");
												$("#product-box-input").val("");
												products_array.push(item);
												fetchTransactionsStats();
												//return item;
										}
								}]);
						});
				}).on('click', '.label-filter', function () {
						products_array.splice(products_array.indexOf($(this).text()), 1);
						$(this).remove();
						fetchTransactionsStats();
				});

		</script>
	</body>
</html>
<script>
</script>
