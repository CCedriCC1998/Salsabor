<?php
session_start();
if(!isset($_SESSION["username"])){
	header('location: portal');
}
require_once 'functions/db_connect.php';
$db = PDOFactory::getConnection();
include 'functions/ventes.php';

$handler = $db->query("SELECT CONCAT(user_prenom, ' ', user_nom) AS handler FROM users WHERE user_id = $_SESSION[user_id]")->fetch(PDO::FETCH_COLUMN);

$queryAdherentsNom = $db->query("SELECT * FROM users ORDER BY user_nom ASC");
$array_eleves = array();
while($adherents = $queryAdherentsNom->fetch(PDO::FETCH_ASSOC)){
	array_push($array_eleves, $adherents["user_prenom"]." ".$adherents["user_nom"]);
}

$date_now = date_create('now')->format('Y-m-d');

if(isset($_POST["submit"])){
	vente();
}
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Récapitulatif de commande | Salsabor</title>
		<?php include "styles.php";?>
	</head>
	<body>
		<?php include "nav.php";?>
		<div class="container-fluid">
			<div class="row">
				<form action="paiement.php" method="post">
					<div class="col-lg-10 col-lg-offset-1 main">
						<legend><span class="glyphicon glyphicon-shopping-cart"></span> Acheter des produits</legend>
						<div class="progress">
							<div class="progress-bar" role="progressbar" aria-valuenow="100" aria-valuemin="25" aria-valuemax="100" style="width:100%;">
								<span class="glyphicon glyphicon-repeat"></span> Etape 3/3 : Ajustement des échéances
							</div>
						</div>
						<p class="sub-legend">Récapitulatif de la commande</p>
						<table class="table">
							<thead>
								<tr>
									<th>Produit</th>
									<th>Bénéficiaire</th>
									<th>Activation</th>
									<th>Prix</th>
								</tr>
							</thead>
							<tbody class="produits-recap">
							</tbody>
						</table>
						<div class="row">
							<div class="col-lg-2">
								<div class="form-group">
									<label for="prix_total">Prix total</label>
									<div class="input-group">
										<input type="number" step="any" name="prix_total" class="form-control input-lg">
										<span class="input-group-addon">€</span>
									</div>
								</div>
							</div>
							<div class="col-lg-3">
								<div class="form-group">
									<label for="date_achat">Date d'achat</label>
									<input type="date" name="date_achat" class="form-control input-lg" value="<?php echo $date_now;?>">
								</div>
							</div>
							<div class="col-lg-2">
								<div class="form-group">
									<label for="echeances">Echéances</label>
									<input type="number" name="echeances" class="form-control input-lg">
								</div>
							</div>
							<div class="col-lg-5">
								<div class="form-group">
									<label for="payeur">Payeur</label>
									<input type="text" name="payeur" class="form-control has-check mandatory has-name-completion input-lg" placeholder="Nom">
								</div>
							</div>
							<div class="col-sm-5">
								<div class="form-group">
									<label for="handler">Responsable de la vente <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" title="Par défaut, l&apos;utilisateur actuellement connecté. Vous seront suggérés l&apos;ensemble des utilisateurs du groupe 'Accueil'"></span></label>
									<input type="text" name="handler" class="form-control input-lg" id="transaction-handler" placeholder="Responsable de la vente" value="<?php echo $handler;?>">
								</div>
							</div>
						</div>
						<div class="form-group">
							<label for="numero_echeance">Détail des échances</label>
							<table class="table table-striped">
								<thead>
									<tr>
										<th class="col-lg-1">Date de l'échéance</th>
										<th class="col-lg-2">Montant</th>
										<th class="col-lg-4">Méthode de règlement</th>
										<th class="col-lg-4">Titulaire du moyen de paiement</th>
										<th class="col-lg-1">Réception</th>
									</tr>
								</thead>
								<tbody class="maturities-table">
								</tbody>
							</table>
						</div>
						<input type="hidden" name="nombre_produits">
						<a href="actions/validate_paiement.php" role="button" class="btn btn-primary btn-block" data-title="Validation du panier" data-toggle="lightbox" data-gallery="remoteload">PROCEDER</a>
						<input type="submit" style="display:none;" class="submit-relay-target" name="submit">
					</div>
				</form>
			</div>
		</div>
		<?php include "scripts.php";?>
		<script>
			$(document).ready(function(){
				var listeAdherents = JSON.parse('<?php echo json_encode($array_eleves);?>');
				$(".has-name-completion").autocomplete({
					source: listeAdherents
				});
				/*$(":regex(name,payeur)").val(sessionStorage.getItem('beneficiaire-principal'));*/
				var globalCart = JSON.parse(sessionStorage.getItem("panier")), recap, prixTotal = 0;
				for(var i = 0; i < globalCart.length; i++){
					var miniCart = JSON.parse(sessionStorage.getItem("cart-"+i));
					console.log(sessionStorage.getItem("cart-"+i));
					recap += "<tr>";
					recap += "<td>";
					recap += "<input type='hidden' value='"+miniCart["id_produit"]+"' name='product_id-"+i+"'>"+miniCart["nom_produit"];
					recap += "</td>";
					recap += "<td>";
					recap += "<input type='hidden' value='"+miniCart["id_beneficiaire"]+"' name='beneficiaire-"+i+"'>"+miniCart["nom_beneficiaire"];
					recap += "</td>";
					recap += "<td>";
					if(miniCart["date_activation"] == null){
						recap += "<input type='hidden' value='0' name='activation-"+i+"'>Activation automatique";
					} else recap += "<input type='hidden' value='"+miniCart["date_activation"]+"' name='activation-"+i+"'>Activation le "+moment(miniCart["date_activation"]).format("DD/MM/YYYY");
					recap += "</td>";
					recap += "<td>";
					if(miniCart["prix_final"] == null){
						recap += "<input type='hidden' value='"+miniCart["prix"]+"' name='prix-produit-"+i+"'>"+miniCart["prix"]+" €";
						prixTotal += parseFloat(miniCart["prix"]);
					} else {
						recap += "<input type='hidden' value='"+miniCart["prix_final"]+"' name='prix-produit-"+i+"'>"+miniCart["prix_final"]+" €";
						prixTotal += parseFloat(miniCart["prix_final"]);
					}
					recap += "</td>";
					recap += "</tr>";
					console.log(prixTotal);
				}
				$(".produits-recap").append(recap);
				$("[name='prix_total']").val(prixTotal);
				$("[name='nombre_produits']").val(globalCart.length);
				var methods = [
					"Carte bancaire",
					"Chèque n°",
					"Espèces",
					"Virement compte à compte",
					"Chèques vacances",
					"En attente"
				];
				// Gestion des échéances (nombre et valeur)
				$("[name='echeances']").on('keyup keydown change', function(){
					var nbEcheances = $(this).val();
					var i = 1;
					var start_date = moment();
					if(start_date.date() >= 1 && start_date.date() < 8){
						start_date.date(10);
					} else if(start_date.date() >= 9 && start_date.date() < 18){
						start_date.date(20);
					} else if(start_date.date() >= 19 && start_date.date() < 28){
						start_date.date(30);
					} else {
						var month = start_date.month();
						start_date.month(month).date(10);
					}
					var montant_total = prixTotal;
					var montant_restant = montant_total;
					if(montant_total != ''){
						var montant_echeance = (montant_total/nbEcheances).toFixed(2);
					}
					$(".maturities-table").empty();
					for(i; i <= nbEcheances; i++){
						if(i == nbEcheances){
							montant_echeance = montant_restant;
						}
						// Construction du tableau des échéances
						var echeance = "<tr>";
						var current_date = start_date.format("YYYY-MM-DD");
						echeance += "<td class='col-lg-1'><div class='input-group'><input type='date' class='form-control' value="+current_date+" name='date-echeance-"+i+"'><span role='button' class='input-group-btn'><a class='btn btn-info' role='button' name='propagation-date-"+i+"'><span class='glyphicon glyphicon-arrow-down'></span> Propager</a></span></div></td>";
						echeance += "<td class='col-lg-2'><div class='input-group'><input type='number' step='any' class='form-control' placeholder='Montant' value="+montant_echeance+" name='montant-echeance-"+i+"'><span class='input-group-addon'>€</span></div></td>";
						echeance += "<td class='col-lg-4'><div class='input-group'><input type='text' class='form-control' name='moyen-paiement-"+i+"' placeholder='En attente / Carte bancaire / Numéro de chèque / Mandat / Espèces...'><span role='buttton' class='input-group-btn'><a class='btn btn-info' role='button' name='propagation-methode-"+i+"'><span class='glyphicon glyphicon-arrow-down'></span> Propager</a></span></div></td>";
						echeance += "<td class='col-lg-4'><div class='input-group'><input type='text' class='form-control has-name-completion' name='titulaire-paiement-"+i+"' placeholder='Prénom Nom' value='"+$(":regex(name,payeur)").val()+"'><span role='button' class='input-group-btn'><a class='btn btn-info' role='button' name='propagation-titulaire-"+i+"'><span class='glyphicon glyphicon-arrow-down'></span> Propager</a></span></div></td>";
						echeance += "<td class='col-lg-1'><div class='input-group'><input name='statut-echeance-"+i+"'><span role='button' class='input-group-btn'><a class='btn btn-info' role='button' name='propagation-statut-"+i+"'><span class='glyphicon glyphicon-arrow-down'></span></a></span></div></td>";
						echeance += "</tr>";
						montant_restant -= montant_echeance;
						$(".maturities-table").append(echeance);
						start_date.add(1, 'month').format("YYYY-MM-DD");
					}
					$("[name^='montant-echeance']").keyup(function(){
						// Lorsqu'un montant est modifié.
						var echeance_fixe = $(this).val();
						if(echeance_fixe != '' || $(this).is(":focus")){
							$(this).addClass('fixed-value');
						} else {
							$(this).removeClass('fixed-value');
						}

						var montant_restant_auto = montant_total;
						$(".fixed-value").each(function(){
							montant_restant_auto -= $(this).val();
						})
						montant_restant = montant_restant_auto;
						var echeances_fixees = $(".fixed-value").length;
						var echeances_auto = $("[name^='montant-echeance']:not(.fixed-value)").length;
						i = 0;
						$("[name^='montant-echeance']:not(.fixed-value)").each(function(){
							if(i == echeances_auto - 1){
								montant_echeance = (montant_restant).toFixed(2);
							} else {
								montant_echeance = (montant_restant_auto/echeances_auto).toFixed(2);
							}
							$(this).val(montant_echeance);
							montant_restant -= montant_echeance;
							i++;
						})
					})
					$("[name^='moyen-paiement']").autocomplete({
						source: methods
					})
					$("[name^='statut-echeance']").checkboxX({threeState: false, size: 'lg', value: 0});
					$(":regex(name,^propagation-date)").click(function(){
						var date = $(this).parent().prev().val();
						var indice = $(this).attr('name').substr(17);
						for(var m = indice++; m <= nbEcheances; m++){
							$(":regex(name,date-echeance-"+m+")").val(date);
							date = moment(date).add(1, 'month').format('YYYY-MM-DD');
						}
					})
					$("[name^='propagation-methode']").click(function(){
						var clicked = $(this);
						var methode = clicked.parent().prev().val();
						console.log(methode);
						var indice = $(this).attr('name').substr(20);
						if(methode.indexOf("Chèque") != -1 && methode != "Chèques vacances"){
							var token = "Chèque n°";
							var numero = methode.substr(9);
							for(var m = indice++; m <= nbEcheances; m++){
								$("[name='propagation-methode-"+m+"']").parent().prev().val(token+""+numero);
								numero++;
							}
							clicked.parent().prev().val(methode);
						} else {
							for(var m = indice++; m <= nbEcheances; m++){
								$("[name^='propagation-methode-"+m+"']").parent().prev().val(methode);
							}
						}
					})
					$("[name^='propagation-titulaire']").click(function(){
						var titulaire = $(this).parent().prev().val();
						console.log(titulaire);
						$("[name^='propagation-titulaire']").parent().prev().val(titulaire);
					});
					$("[name^='propagation-statut']").click(function(){
						var indice = $(this).attr('name').substr(19);
						var statut = $("[name='statut-echeance-"+indice+"']").val();
						for(var m = indice++; m <= nbEcheances; m++){
							console.log("Avant propagation : "+$("[name='statut-echeance-"+m+"']").val());
							$("[name='statut-echeance-"+m+"']").val(statut).checkboxX('refresh');
							console.log("Après propagation : "+$("[name='statut-echeance-"+m+"']").val());
						}
					});
				})
				$(".mandatory").change();
			}).on('focus', '#transaction-handler', function(){
				var filter = "Accueil";
				provideAutoComplete($(this), filter);
			})
		</script>
		<script>
			$(document).ready(function(){
				composeURL();
			});
		</script>
	</body>
</html>
