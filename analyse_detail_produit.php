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
		<title>Détails Analyse | Salsabor</title>
		<?php include "styles.php";?>
		<?php include "scripts.php";?>
    <link rel="stylesheet" href="assets/css/bootstrap-slider.min.css">
    <script src="assets/js/raphael-min.js"></script>
    <script src="assets/js/morris.min.js"></script>
    <script src="assets/js/bootstrap-slider.min.js"></script>
    <script src="assets/js/products.js"></script>
    <script src="assets/js/maturities.js"></script>
		<script src="assets/js/canvasjs.min.js"></script>
	</head>
	<body>
		<?php include "nav.php";?>
		<div class="container-fluid">
			<div class="row">
				<?php include "side-menu.php";?>
				<div class="col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
          <a class="btn btn-dark" href="analyse/analyse_detail">Retour</a>
          <div class="row money-stats">
              <div class="col-lg-6">
                  <p class="stat-title">Répartition par état</p>
                  <div class="col-lg-12">
										<div id="money-chart" style="height: 370px; width: 100%;"></div>
                  </div>
                  <!--<div class="col-lg-6 data-display" id="money-chart-legend"></div>-->
              </div>
              <div class="col-lg-6">
                  <p class="stat-title">Répartition des encaissements (<strong><span id="bank-price"></span>€</strong>) par méthode de paiement</p>
                  <div class="col-lg-12">
											<div id="method-chart" style="height: 370px; width: 100%;"></div>
                  </div>
                  <!--<div class="col-lg-6 data-display" id="method-chart-legend"></div>-->
              </div>
          </div>
          <?php

          $produit = $_GET['idProd'];
          $date_start = $_GET['dateDebut'];
          $date_end = $_GET['dateFin'];

					$query1 = $db->query("SELECT t.id_transaction,t.date_achat,t.prix_total
																	FROM transactions t
																	WHERE t.date_achat BETWEEN '$date_start' AND '$date_end'
																	ORDER BY t.date_achat DESC");

				  $total = $received = $banked = $pending = $late = 0;
				  $credit_card = $check = $cash = $voucher = $weez = $various = 0;
				  while ($transac = $query1->fetch(PDO::FETCH_ASSOC))
				  {
						$prodAdh = $db->query("SELECT pa.id_transaction_foreign,pa.id_produit_foreign,SUM(pa.prix_achat)
																	FROM produits_adherents pa
																	WHERE pa.id_produit_foreign = '$produit' AND pa.id_transaction_foreign = '$transac[id_transaction]'")->fetch();

						$query5 = $db->query("SELECT t.id_transaction,pa.id_produit_foreign
																	FROM transactions t
																	JOIN produits_adherents pa ON pa.id_transaction_foreign = t.id_transaction
																	WHERE date_achat BETWEEN '$date_start' AND '$date_end' AND id_transaction_foreign = '$transac[id_transaction]'
																	GROUP BY pa.id_produit_foreign
																	HAVING pa.id_produit_foreign = 16")->rowCount();

						if($prodAdh['id_produit_foreign'] == $produit)
						{
						if($transac['prix_total']==$prodAdh['SUM(pa.prix_achat)'])
						{
							$nbEcheances = $db->query("SELECT pe.reference_achat,COUNT(pe.reference_achat)
																					FROM produits_echeances pe
																					WHERE pe.reference_achat = '$transac[id_transaction]'")->fetch();
							if($nbEcheances['COUNT(pe.reference_achat)'] == 1)
							{
								$value = $prodAdh['SUM(pa.prix_achat)'];
							}
							else {
								$value = $transac['prix_total'];
							}
						}
						else {
							if($prodAdh['id_produit_foreign'] == 16)
							{
								$value = $prodAdh['SUM(pa.prix_achat)'];
							}
							else {
								$nbEcheanceselse = $db->query("SELECT pe.reference_achat,COUNT(pe.reference_achat)
																						FROM produits_echeances pe
																						WHERE pe.reference_achat = '$transac[id_transaction]'")->fetch();
								if($nbEcheanceselse['COUNT(pe.reference_achat)'] == 1)
								{
									$value = $prodAdh['SUM(pa.prix_achat)'];
								}
								else {
									$value = $transac['prix_total'];
								}
							}
						}
						$query4 = $db->query("SELECT pe.reference_achat,pe.montant,pe.echeance_effectuee,
																	        pe.statut_banque,pe.methode_paiement,pe.date_paiement,
																	        pe.date_encaissement
																	        FROM produits_echeances pe
																	        WHERE pe.reference_achat = '$transac[id_transaction]'");


									while($resultat = $query4->fetch(PDO::FETCH_ASSOC))
									{
										$method = $resultat["methode_paiement"];
							    if($resultat["echeance_effectuee"] == 2)
							         $late += $value;
							     //1-->payé pour chèque,carte,especes
							     else if($resultat["echeance_effectuee"] == 1 && $resultat["date_encaissement"] != null) //la transac a été payée et est en banque
							          {
							             $banked += $resultat['montant'] - ($query5*30); //encaissé en banque
							             if((stripos($method, "chèque") !== false || stripos($method, "cheque") !== false || stripos($method, "chq") !== false) && stripos($method, "vacances") !== true)
							                 $check += $resultat['montant']- ($query5*30);
							             if(stripos($method, "carte") !== false || stripos($method, "cb")  !== false)
							                 $credit_card += $resultat['montant'] - ($query5*30);
							             if($method == "Espèces")
							                 $cash += $resultat['montant']- ($query5*30);
							             if(stripos($method, "cheque vacances") !== false)
							                 $voucher += $resultat['montant']- ($query5*30);
													 if(stripos($method,"weez") !== false)
													 	   $weez += $resultat['montant']- ($query5*30);
							           } else if($resultat['date_encaissement'] == null && $resultat["echeance_effectuee"] == 1 && $resultat['statut_banque'] ==0)
							               $received += $resultat['montant'] - ($query5*30); //recu mais pas encore à la banque

										     else if($resultat["echeance_effectuee"] == 0){
														 $pending += $value;
													 }
										         $total += $resultat['montant'];
														 /*
													 echo $resultat['reference_achat']. " ";
													 echo $resultat['montant'];
													 echo "<br>";*/
												 }
												 //$banked = $banked - ($query5*30);
					/*
					 echo $value . " " ."<br />";
					 echo $check . " cheque" . "<br />";
					 echo $credit_card . ' carte';
 					echo "<br>";*/
				 		}
				  }

					$dataPoints = array(
													array("label"=>"Reçu","y"=>$received),
													array("label"=>"Encaissé","y"=>$banked),
													array("label"=>"En attente","y"=>$pending),
													array("label"=>"En retard","y"=>$late)
												);

					$methods = array(
													array("label"=>"Carte de crédit","y"=>$credit_card),
													array("label"=>"Espèces","y"=>$cash),
													array("label"=>"Chèque","y"=>$check),
													array("label"=>"Chèque vacances","y"=>$voucher),
													array("label"=>"Weez","y"=>$weez),
													array("label"=>"Autre","y"=>$banked - ($credit_card+$cash+$check+$voucher+$weez))
												);

           ?>
					 <p>Il se peut que le résultat des diagrammes soit légèrement différent du tableau récapitulatif car les adhésions annuelles sont calculés malgré tout lorsqu'une transaction possède des échéances ou la transaction comporte plusieurs produits différents et l'adhésion annuelle</p>
           <?php
               $productTotal = $db->query("SELECT pa.id_produit_adherent FROM transactions t
                                           JOIN produits_adherents pa ON t.id_transaction = pa.id_transaction_foreign
                                           JOIN users u ON u.user_id = t.transaction_handler
                                           WHERE t.date_achat BETWEEN '$date_start' AND '$date_end'
                                           AND pa.id_produit_foreign IN (SELECT product_id FROM produits WHERE product_id = '$produit')
                                           ORDER BY t.date_achat DESC")->rowCount();

               $sumTotalproduct = $db->query("SELECT SUM(pa.prix_achat),AVG(pa.prix_achat)
                                              FROM produits_adherents pa
                                              JOIN transactions t ON t.id_transaction = pa.id_transaction_foreign
                                              WHERE t.date_achat BETWEEN '$date_start' AND '$date_end'
                                              AND pa.id_produit_foreign IN (SELECT product_id FROM produits WHERE product_id = '$produit')")->fetch();
            ?>
           <div class="col-md-12">
             <table class="table table-bordered table-striped">
               <thead>
                 <tr>
                   <th>Nombre de produit vendus</th>
                   <th>Prix moyen par produit vendus</th>
									 <th>Total</th>
                 </tr>
               </thead>
               <tbody>
                 <?php
                     echo "<tr>";
                     echo "<td>" . $productTotal . "</td>";
										 echo "<td>" . $sumTotalproduct['AVG(pa.prix_achat)'] . "€" . "</td>";
										 echo "<td>" . $sumTotalproduct['SUM(pa.prix_achat)'] . "€" . "</td>";
                     echo "</tr>";
                 ?>
               </tbody>
             </table>
						 <p class="sub-legend">Transactions concernés</p>
             <?php

             $panels = $db->query("SELECT DISTINCT t.id_transaction,
             pa.prix_achat,
             t.date_achat,
             t.prix_total
             FROM transactions t
             JOIN produits_adherents pa ON pa.id_transaction_foreign = t.id_transaction
             WHERE t.date_achat BETWEEN '$date_start' AND '$date_end'
             AND pa.id_produit_foreign IN (SELECT product_id FROM produits WHERE product_id = '$produit')
             ORDER BY date_achat DESC");

                while($panel = $panels->fetch())
                {
                  $productQty = $db->query("SELECT id_produit_adherent FROM produits_adherents WHERE id_transaction_foreign='$panel[id_transaction]'")->rowCount();
              ?>

              <div class="panel panel-purchase" id="purchase-<?php echo $panel["id_transaction"];?>">
    							<div class="panel-heading container-fluid" onClick="displayPurchase('<?php echo $panel["id_transaction"];?>')">
    								<p class="purchase-id col-xs-3">Transaction <?php echo $panel["id_transaction"];?></p>
    								<p class="col-xs-2"><?php echo $productQty;?> produit(s)</p>
    								<p class="purchase-sub col-xs-4">
    									<span class="modal-editable-<?php echo $panel["id_transaction"];?>" data-field="date_achat" data-name="Date" id="date-<?php echo $panel["id_transaction"];?>"><?php echo date_create($panel["date_achat"])->format('d/m/Y');?></span> -
    									<span class="modal-editable-<?php echo $panel["id_transaction"];?>" data-field="prix_total" data-name="Prix" id="price-<?php echo $panel["id_transaction"];?>"><?php echo $panel["prix_total"];?></span> €</p>
    								<!--Les glyphes présents sur chaque bande de transaction-->
    								<span class="glyphicon glyphicon-pencil glyphicon-button glyphicon-button-alt glyphicon-button-big col-xs-1" id="edit-<?php echo $panel["id_transaction"];?>" data-toggle="modal" data-target="#edit-modal" data-entry="<?php echo $panel["id_transaction"];?>" data-table="transactions" title="Modifier les détails de la transaction"></span>
    								<span class="glyphicon glyphicon-briefcase glyphicon-button glyphicon-button-alt glyphicon-button-big create-contract col-xs-1" id="create-contract-<?php echo $panel["id_transaction_foreign"];?>" data-transaction="<?php echo $panel["id_transaction"];?>"title="Afficher le contrat"></span>
    								<span class="glyphicon glyphicon-file glyphicon-button glyphicon-button-alt glyphicon-button-big create-invoice col-xs-1" id="create-invoice-<?php echo $panel["id_transaction_foreign"];?>" data-transaction="<?php echo $panel["id_transaction"];?>" title="Afficher la facture"></span>
    							</div>
    							<!--Pour faire le bordereau déroulant-->
    							<div class="panel-body collapse" id="body-purchase-<?php echo $panel["id_transaction"];?>">
    							</div>
    						</div>
          <?php } ?>
          <?php include "inserts/modal_product.php";?>
          <?php include "inserts/sub_modal_product.php";?>
          <?php include "inserts/edit_modal.php";?>
          <?php include "inserts/delete_modal.php";?>
           </div>
				</div>
			</div>
		</div>
		<style>
				.control-label{
						text-align: center;
				}

				.stat-value{
						padding-left: 20px;
				}

				#total{
						font-size: 1.4em;
						font-weight: 700;
				}

				.stat-title, .lien{
						font-size: 1.2em;
						text-align: center;
						text-decoration: underline;
				}

		</style>
    <script>

		window.onload = function() {

		var chart = new CanvasJS.Chart("money-chart", {
			theme: "light2",
			animationEnabled: false,

			data: [{
				type: "doughnut",
				//indexLabel: "{symbol} - {y}",
				//yValueFormatString: "#,##0.0\"%\"",
				indexLabel: "{label} - {y} €",
				toolTipContent: "<b>{label}:</b> {y} €",

				dataPoints: <?php echo json_encode($dataPoints, JSON_NUMERIC_CHECK); ?>

			}]
		});
		chart.render();

		var chartmethod = new CanvasJS.Chart("method-chart", {
			theme: "light2",
			animationEnabled: false,

			data: [{
				type: "doughnut",
				indexLabel: "{label} - {y} €",
				toolTipContent: "<b>{label}:</b> {y} €",

				dataPoints: <?php echo json_encode($methods, JSON_NUMERIC_CHECK); ?>

			}]
		});
		chartmethod.render();

	}
	$("#bank-price").text(<?php echo $banked ?>);

    $(document).ready(function(){
      var m, re = /purchase-([a-z0-9]+)/i;
      if((m = re.exec(top.location.hash)) !== null){
        var target_transaction = m[1];
        $("#purchase-"+target_transaction+">div").click();
      }
    })
    //Ouvre une fenêtre pour voir la facture
    $(".create-invoice").click(function(e){
      e.stopPropagation();
      var transaction_id = document.getElementById($(this).attr("id")).dataset.transaction;
      window.open("create_invoice.php?transaction="+transaction_id, "_blank", "location=yes,height=570,width=520,scrollbars=yes,status=yes");
    })
    //Ouvre la fenêtre pour voir le contrat
    $(".create-contract").click(function(e){
      e.stopPropagation();
      var transaction_id = document.getElementById($(this).attr("id")).dataset.transaction;
      window.open("create_contract.php?transaction="+transaction_id, "_blank", "location=yes,height=570,width=520,scrollbars=yes,status=yes");
    })
    </script>
	</body>
</html>
