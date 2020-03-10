<?php
session_start();
include "functions/db_connect.php";
$db = PDOFactory::getConnection();
$transaction_id = $_GET["transaction"];
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Merci ! | Salsabor</title>
		<?php include "styles.php";?>
	</head>
	<body>
		<?php include "nav.php";?>
		<div class="container-fluid">
			<div class="row">
				<?php include "side-menu.php";?>
				<div class="col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
					<div class="jumbotron jumbotron-home">
						<h1>Merci beaucoup de cet achat !</h1>
						<div class="row admin-buttons">
							<button class="col-xs-6 btn btn-primary create-contract" id="contract-<?php echo $transaction_id;?>" data-transaction="<?php echo $transaction_id;?>"><span class="glyphicon glyphicon-briefcase"></span> Générer le contrat</button>
							<button class="col-xs-6 btn btn-primary create-invoice" id="invoice-<?php echo $transaction_id;?>" data-transaction="<?php echo $transaction_id;?>"><span class="glyphicon glyphicon-file"></span> Générer la facture</button>
						</div>
						<a href="dashboard" role="button" class="btn btn-default btn-block">Retour au panneau principal</a>
					</div>
				</div>
			</div>
		</div>
		<?php include "scripts.php";?>
		<script>
			sessionStorage.clear();
			$(".create-invoice").click(function(e){
				e.stopPropagation();
				var transaction_id = document.getElementById($(this).attr("id")).dataset.transaction;
				window.open("create_invoice.php?transaction="+transaction_id, "_blank", "location=yes,height=570,width=520,scrollbars=yes,status=yes");
			})
			$(".create-contract").click(function(e){
				e.stopPropagation();
				var transaction_id = document.getElementById($(this).attr("id")).dataset.transaction;
				window.open("create_contract.php?transaction="+transaction_id, "_blank", "location=yes,height=570,width=520,scrollbars=yes,status=yes");
			})
		</script>
	</body>
</html>
