<?php
session_start();
if(!isset($_SESSION["username"])){
	header('location: portal');
}
require_once 'functions/db_connect.php';
$db = PDOFactory::getConnection();
$queryAdherentsNom = $db->query("SELECT user_id, user_prenom, user_nom FROM users ORDER BY user_nom ASC");
$array_eleves = array();
while($adherents = $queryAdherentsNom->fetch(PDO::FETCH_ASSOC)){
	$e = array();
	$e["value"] = $adherents["user_prenom"]." ".$adherents["user_nom"];
	$e["label"] = $adherents["user_prenom"]." ".$adherents["user_nom"];
	$e["id"] = $adherents["user_id"];
	array_push($array_eleves, $e);
}

$articlePanier = $_GET["element"];
$detailsArticle = $db->query("SELECT * FROM produits WHERE product_id=$articlePanier");
$article = $detailsArticle->fetch(PDO::FETCH_ASSOC);
$date_now = date_create("now")->format("Y-m-d");

$indicePanier = $_GET["order"];
?>
<html>
	<head>
		<meta charset="UTF-8">
		<title>Vente | Salsabor</title>
		<?php include "styles.php";?>
	</head>
	<body>
		<?php include "nav.php";?>
		<div class="container-fluid">
			<div class="row">
				<div class="col-sm-10 col-lg-offset-1 main">
					<legend><span class="glyphicon glyphicon-shopping-cart"></span> Acheter des produits
						<a href="catalogue.php" role="button" class="btn btn-default"><span class="glyphicon glyphicon-arrow-left"></span> <span class="glyphicon glyphicon-th"></span> Retourner au catalogue</a>
					</legend>
					<div class="progress">
						<div class="progress-bar" role="progressbar" aria-valuenow="66" aria-valuemin="33" aria-valuemax="100" style="width:66.67%;">
							<span class="glyphicon glyphicon-erase"></span> Etape 2/3 : Personnalisation des produits
						</div>
					</div>
					<p class="product-title"><?php echo $article["product_name"];?></p>
					<span role="button" class="input-group-btn">
						<a href="#produit-details" class="btn btn-default btn-block" data-toggle="collapse" aria-expanded="false"><span class="glyphicon glyphicon-search"></span> Détails...</a>
					</span>
					<div id="produit-details" class="collapse">
						<div id="produit-content" class="well">
							<?php if($article["product_name"]=="Invitation"){?>
							<p>Cette invitation est à usage unique. Si elle n'est pas liée à un cours, sa période de validité est alors de <?php echo $article["product_validity"];?> jours.</p>
							<?php } else { ?>
							<p>Cet abonnement est valable pendant <?php echo $article["product_validity"]/7;?> semaines.</p>
							<?php } ?>
							<p>Il donne accès à <?php echo $article["product_size"];?> heures de cours pendant toute sa durée d'activation.</p>
							<p>L'extension de durée (AREP) n'est pas autorisée.</p>
						</div>
					</div>
					<div class="form-group"> <!-- Bénéficiaire -->
						<label for="personne">Bénéficiaire</label>
						<input type="text" name="identite_nom" class="form-control has-check has-name-completion input-lg" placeholder="Nom">
						<div class="alert alert-danger" id="unpaid" style="display:none;"><strong>Cet adhérent a des échéances impayées. Impossible de continuer la procédure</strong></div>
					</div>
					<div id="maturities-checked">
						<div class="row">
							<div class="col-lg-6">
								<div class="form-group">
									<label for="date_activation">Date d'activation</label>
									<div class="input-group input-group-lg">
										<?php if(stristr($article["product_name"], "adhésion")){ ?>
										<input type="date" name="date_activation" id="date_activation" class="form-control" value="<?php echo $date_now;?>">
										<?php } else { ?>
										<input type="date" name="date_activation" id="date_activation" class="form-control">
										<?php } ?>
										<span role="buttton" class="input-group-btn"><a class="btn btn-info" role="button" date-today="true">Insérer aujourd'hui</a></span>
									</div>
									<p class="help-block">Par défaut : activation au premier passage</p>
								</div>
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label for="date_expiration">Date indicative d'expiration</label>
									<div class="input-group input-group-lg">
										<input type="date" name="date_expiration" class="form-control">
										<span role="button" class="input-group-btn"><a class="btn btn-info" role="button">Rafraîchir</a></span>
									</div>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-lg-6">
								<div class="form-group">
									<label for="promotion-e">Réduction (en €)</label>
									<div class="input-group input-group-lg">
										<span class="input-group-addon"><input type="radio" id="promotion-euros" name="promotion" class="checkbox-x">Réduction en €</span>
										<input type="number" step="any" name="promotion-e" class="form-control">
									</div>
								</div>
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<label for="promotion-p">Réduction (en %)</label>
									<div class="input-group input-group-lg">
										<span class="input-group-addon"><input type="radio" name="promotion" id="promotion-pourcent">Réduction en %</span>
										<input type="number" step="any" name="promotion-p" class="form-control">
									</div>
								</div>
							</div>
						</div>
						<div class="form-group">
							<label for="prix_achat">Montant</label>
							<div class="input-group">
								<span class="input-group-addon">€</span>
								<input type="number" step="any" name="prix_achat" id="prix-calcul" class="form-control price-display" value="<?php echo $article["product_price"];?>">
							</div>
						</div>
						<div class="next-options">
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php include "scripts.php";?>
		<script>
			$(document).ready(function(){
				/** Panier individuel **/
				window.miniCart = {
					id_produit : "<?php echo $articlePanier;?>",
					nom_produit : "<?php echo $article["product_name"];?>",
					ordre_panier : "<?php echo $indicePanier;?>",
					id_beneficiaire : null,
					nom_beneficiaire : null,
					date_activation : ($("#date_activation").val()!="")?$("#date_activation").val():null,
					prix : "<?php echo $article["product_price"];?>",
					reduction : null,
					prix_final : null
				};
				if(sessionStorage.getItem("beneficiaireInitial") != null){
					var id_adherent = sessionStorage.getItem("beneficiaireInitial");
					miniCart["id_beneficiaire"] = id_adherent;
					$.post("functions/solve_adherent_id.php", {id: id_adherent}).done(function(data){
						var res = JSON.parse(data);
						var identiteUser = res["user_prenom"]+" "+res["user_nom"];
						miniCart["nom_beneficiaire"] = identiteUser;
						$(".has-name-completion").val(identiteUser);
					})
				}
				var listeAdherents = JSON.parse('<?php echo json_encode($array_eleves);?>');
				$(".has-name-completion").autocomplete({
					source: listeAdherents,
					select: function(event, ui){
						$(":regex(id,^unknown-user)").remove();
						$(this).val(ui.item.label);
						miniCart["id_beneficiaire"] = ui.item.id;
						miniCart["nom_beneficiaire"] = ui.item.label;
					}
				});
				$("#date_activation").on('keyup, change, blur', function(){
					console.log($(this).val());
					if($(this).val() != ""){
						miniCart["date_activation"] = $(this).val();
					} else {
						miniCart["date_activation"] = null;
					}
				})
				$("[name^='promotion']").keyup(function(){
					if($(this).val().length != '0'){
						$(this).prev().children().prop("checked", true);
						var lastChar = $(this).attr('name').substr($(this).attr('name').length - 1);
					} else {
						$(this).prev().children().prop("checked", false);
						var lastChar = $(this).attr('name').substr($(this).attr('name').length - 1);
					}
					var reductionEuros = $("[name='promotion-e']").val();
					var reductionPourcent = $("[name='promotion-p']").val();
					var prixInitial = <?php echo $article["product_price"];?>;
					var prixReduit = prixInitial;
					if($("#promotion-euros").prop("checked")){
						prixReduit = prixInitial - reductionEuros;
					} else if($("#promotion-pourcent").prop("checked")){
						prixReduit = prixInitial - ((prixInitial * reductionPourcent)/100);
					}
					$("#prix-calcul").val(prixReduit.toFixed(2));
					miniCart["prix_final"] = prixReduit.toFixed(2);
				}).blur(function(){
					console.log(miniCart);
				})

				/** Construction du lien du bouton vers le prochain produit **/
				var globalCart = JSON.parse(sessionStorage.getItem("panier"));
				var nextArticle = <?php echo $indicePanier;?> + 1;
				if(nextArticle > globalCart.length - 1){
					$(".next-options").append("<a class='btn btn-success btn-block next-article' id='check-memory'>Régler les achats</a>");
					var url = "paiement.php";
				} else {
					$(".next-options").append("<a class='btn btn-primary btn-block next-article' id='check-memory'>Produit suivant</a>");
					var url = "personnalisation.php?element="+globalCart[nextArticle]+"&order="+nextArticle;
				}
				$(".next-article").attr('href', url);

				// Stockage du micro-panier avant de passer à la page suivante
				$("#check-memory").click(function(){
					sessionStorage.setItem('cart-'+(nextArticle-1)+'', JSON.stringify(miniCart));
					var url = "paiement.php";
					window.location = url;
				})
			})
			function showExpDate(digit){
				var date_activation = new moment($("[name='date_activation-"+digit+"']").val());
				var validite = $("[name='validite_produit-"+digit+"']").val();
				var date_desactivation = date_activation.add(validite, 'days').format('YYYY-MM-DD');
				console.log(validite);
				$("[name='date_expiration-"+digit+"']").val(date_desactivation);
			}
		</script>
	</body>
</html>
