<?php
session_start();
if(!isset($_SESSION["username"])){
	header('location: portal');
}
require_once 'functions/db_connect.php';
$db = PDOFactory::getConnection();

?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Echeances | Salsabor</title>
    <base href="../">
		<?php include "styles.php";?>
		<?php include "scripts.php";?>
		<script src="assets/js/maturities.js"></script>
		<script src="assets/js/list.min.js"></script>
	</head>
	<body>
		<?php include "nav.php";?>
		<div class="container-fluid">
			<div class="row">
				<?php include "side-menu.php";?>
				<div class="col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
          <legend>
            <span class="glyphicon glyphicon-repeat"></span> Echéances
            <a class="btn btn-dark" href="echeances?region=0">Retour</a>
          </legend>
          <div class="container-fluid">
              <p class="help-block">Filtrez les échéances par période</p>
              <form role="form" action="echeances/echeances_filter?region=<?php echo $_GET["region"] ?>" method="post">
              <div class="form-group">
                  <label for="" class="control-label col-xs-4">Date de début</label>
                  <label for="" class="control-label col-xs-4">Date de fin</label>
                  <label class="control-label col-xs-4"> &#128131;     </label>

                  <div class="col-xs-3">
                      <input type="date" class="form-control date-filter"  name="date_start" value="<?php if (!empty($_POST)) { echo $period_start = $_POST['date_start'];} ?>">
                  </div>
                  <div class="col-xs-3">
                      <input type="date" class="form-control date-filter"  name="date_end" value="<?php if (!empty($_POST)) { echo $period_end = $_POST['date_end'];} ?>">
                  </div>
              </div>
              <div class="form-actions col-xs-1">
                <button type="submit" class="btn btn-warning">Filtrer</button>
              </div>
							<div class="form-actions col-xs-2">
                <a href="export_excel/export_echeances.php?debut=<?php echo $period_start?>&fin=<?php echo $period_end?>" class="btn btn-success">Exporter</a>
              </div>
              </form>
              <div class="col-xs-2">
                <?php if($_GET["region"] == "1"){?>
                <a href="echeances_filter?region=0" class="btn btn-primary float-right"><span class="glyphicon glyphicon-globe"></span> Inclure toutes les régions</a>
                <?php } else { ?>
                <a href="echeances_filter?region=1" class="btn btn-primary float-right"><span class="glyphicon glyphicon-globe"></span> Exclure les autres régions</a>
              <?php } ?></div>
          </div>
          <br><br>

          <?php //if (!empty($POST)) {?>
					<div class="panel panel-purchase  maturities-container" id="maturities-list">
						<div class="panel-heading container-fluid">
							<p class="col-xs-4 col-md-4">Encaissement entre la période filtrer</p>
							<p class="col-xs-2 col-md-1"><span class="glyphicon glyphicon-repeat"></span> <span class="maturities-total-count">0</span></p>
							<p class="col-xs-2 col-md-1"><span class="glyphicon glyphicon-ok"></span> <span class="maturities-received-count">0</span></p>
							<p class="col-xs-3 col-md-2"><span class="glyphicon glyphicon-piggy-bank"></span> <span class="total-value">0</span> €</p>
							<span class="glyphicon glyphicon-download-alt col-xs-1 col-md-1 col-md-offset-3 glyphicon-button glyphicon-button-alt glyphicon-button-big bank-all" title="Marquer toutes les échéances comme encaissées"></span>
						</div>
						<div class="panel-body row">
							<ul class="purchase-inside-list maturities-list"></ul>
						</div>
					</div>
        <?php //} ?>
				</div>
			</div>
		</div>
		<?php include "inserts/sub_modal_product.php";?>
		<?php include "inserts/edit_modal.php";?>
		<?php include "inserts/delete_modal.php";?>
		<script>
			$(document).ready(function(){
				var region_flag = /[0-9]/.exec(window.location.search)[0];
				console.log(region_flag);
				$(".maturities-container>.panel-body").trigger('loading');
        var filters = [];
        $(".date-filter").each(function(){
            filters.push(moment($(this).val(), "YYYY/MM/DD").format("YYYY-MM-DD"));
            });
				$.get("functions/fetch_filter_echeances.php", {region : region_flag, filters: filters}).done(function(data){
					var maturities = JSON.parse(data);
					var display = displayMaturities(maturities);
					$(".maturities-list").append(display);
					$(".maturities-total-count").text($(".maturity-item").length);
					var total_price = 0;
					for(var i = 0; i < maturities.length; i++){
						total_price += parseFloat(maturities[i].price);
					}
					$(".total-value").text(total_price.toFixed(2));
					$(".maturities-container>panel-body").trigger('loaded');
				})

				setInterval(keepCounts, 5000);
				function keepCounts(){
					$(".maturities-received-count").text($(".status-partial-success").length + $(".status-success").length);
				}
			})
		</script>
	</body>
</html>
