<?php

session_start();
if(!isset($_SESSION["username"])){
    header('location: portal');
}
require_once 'functions/db_connect.php';
$db = PDOFactory::getConnection();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Détails analyses</title>
    <base href="../">
    <?php include "styles.php";?>
    <?php include "scripts.php";?>
    <link rel="stylesheet" href="assets/css/bootstrap-slider.min.css">
    <script src="assets/js/raphael-min.js"></script>
    <script src="assets/js/morris.min.js"></script>
    <script src="assets/js/bootstrap-slider.min.js"></script>
    <script src="assets/js/products.js"></script>
    <script src="assets/js/maturities.js"></script>
</head>
  <body>
    <?php include "nav.php";?>
    <div class="container-fluid">
      <div class="row">
        <?php include "side-menu.php";?>
        <div class="col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
          <legend><span class="glyphicon glyphicon-stats"></span> Détail des analyses</legend>
          <ul class="nav nav-tabs">
              <li role="presentation"><a href="analyse">Transactions</a></li>
              <li role="presentation" class="active"><a href="analyse_detail">Historique par période</a></li>
          </ul>

          <!--Partie filtrage-->
          <div class="container-fluid">
              <p class="help-block">Filtrez les transactions effectuées par période</p>
              <form role="form" action="analyse/analyse_detail" method="post">
              <div class="form-group">
                  <label for="" class="control-label col-xs-4">Date de début</label>
                  <label for="" class="control-label col-xs-4">Date de fin</label>
                  <label class="control-label col-xs-4"> &#128131;     </label>
                  <!--<label for="" class="control-label col-xs-4">Produits
                    <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" title="Vous pouvez ajouter des produits à l'espace ci-dessous pour filtrer les résutlats. Si vous laissez l'encadré vide, tous les produits seront sélectionnés (par défaut)."></span>
                  </label>-->
                  <div class="col-xs-4">
                      <input type="date" class="form-control date-filter"  name="date_start" value="<?php if (!empty($_POST)) {echo $period_start = $_POST['date_start'];}?>">
                  </div>
                  <div class="col-xs-4">
                      <input type="date" class="form-control date-filter"  name="date_end" value="<?php if (!empty($_POST)) { echo $period_end = $_POST['date_end'];} ?>">
                  </div>
                  <!--<div class="col-xs-4">
                      <input type="text" class="form-control" id="product-box-input" placeholder="Cherchez un produit..." >
                      <div class="product-box" name="product[]"></div>
                  </div>-->
              </div>
              <div class="form-actions col-xs-3">
                <button type="submit" class="btn btn-warning">Filtrer</button>
              </div>
              </form>
          </div>
          <br>
          <?php
            if (!empty($_POST)) {
              $productTotal = $db->query("SELECT pa.id_produit_adherent FROM transactions t
                                          JOIN produits_adherents pa ON t.id_transaction = pa.id_transaction_foreign
                                          JOIN users u ON u.user_id = t.transaction_handler
                                          WHERE t.date_achat BETWEEN '$period_start' AND '$period_end'
                                          ORDER BY t.date_achat DESC")->rowCount();

              $sumTotalproduct = $db->query("SELECT SUM(pa.prix_achat)
                                            FROM produits_adherents pa
                                            WHERE pa.id_transaction_foreign
                                            IN (SELECT t.id_transaction
                                                FROM transactions t
                                                WHERE t.date_achat BETWEEN '$period_start' AND '$period_end')")->fetch();
            }
           ?>
           <?php if (!empty($_POST)) { ?>
          <p class="sub-legend">Totaux</p>
                  <p>Nombre total de produit vendus sur cette période: <?php if (!empty($_POST)) {echo $productTotal;} ?></p>
          Total des transactions sur cette période: <span class="total-price"></span>
          <br><br>
          <div class="row money-stats">
              <div class="col-lg-6">
                  <p class="stat-title">Répartition par état</p>
                  <div class="col-lg-6">
                      <div class="chart" id="money-chart"></div>
                  </div>
                  <div class="col-lg-6 data-display" id="money-chart-legend"></div>
              </div>
              <div class="col-lg-6">
                  <p class="stat-title">Répartition des encaissements (<strong><span id="bank-price"></span></strong>) par méthode de paiement</p>
                  <div class="col-lg-6">
                      <div class="chart" id="method-chart"></div>
                  </div>
                  <div class="col-lg-6 data-display" id="method-chart-legend"></div>
              </div>
          </div>
        <?php } ?>
        <?php
        if (!empty($_POST)){
        $produits_achetes = array();

            $productBuy = $db->query("SELECT p.product_name, SUM(pa.prix_achat),COUNT(pa.id_produit_adherent)
                                      FROM produits p
                                      JOIN produits_adherents pa ON p.product_id = pa.id_produit_foreign
                                      WHERE pa.id_transaction_foreign IN (SELECT t.id_transaction FROM transactions t WHERE t.date_achat BETWEEN '$period_start' AND '$period_end')
                                      GROUP BY p.product_id
                                      ORDER BY p.product_id");

          //attribution de la requete pour afficher les produits vendus
          while ($produit = $productBuy->fetchAll(PDO::FETCH_ASSOC))
          {
            //place les variables de product name dans un tableau exploitable en dehors du while ($produits_achetes)
            foreach($produit as $nom_prod)
            {
              array_push($produits_achetes, $nom_prod['product_name']);
            }
          }

          //-------------------------------------------------------------------

          ?>
          <p class="sub-legend">Liste des produits vendus sur la période </p>
          <a href='export_excel/export_transactions_periode.php?debut=<?php echo $period_start?>&fin=<?php echo $period_end?>' class="btn btn-success">Export table transactions</a>
          <a href='export_excel/export_prod_adh.php?debut=<?php echo $period_start?>&fin=<?php echo $period_end?>' class="btn btn-success">Export table produits adherents</a>
          <a href='export_excel/export_users_periode.php?debut=<?php echo $period_start?>&fin=<?php echo $period_end?>' class="btn btn-success">Export table users</a>
          <div class="col-md-12">
            <br>
            <table class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th>Nom du produit</th>
                  <th>Nombre de produits vendus</th>
                  <th>Montant total des transactions</th>
                  <th>Prix moyens des produits vendus</th>
                  <th>Détails</th>
                  <th>Exporter les transactions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $productBuy2 = $db->query("SELECT p.product_id, p.product_name, SUM(pa.prix_achat),COUNT(pa.id_produit_adherent),AVG(pa.prix_achat)
                                          FROM produits p
                                          JOIN produits_adherents pa ON p.product_id = pa.id_produit_foreign
                                          WHERE pa.id_transaction_foreign IN (SELECT t.id_transaction FROM transactions t WHERE t.date_achat BETWEEN '$period_start' AND '$period_end')
                                          GROUP BY p.product_id
                                          ORDER BY p.product_id");
                //Liste les noms produits achetés
                  while ($produit2 = $productBuy2->fetch())
                  {
                    echo "<tr>";
                    echo "<td>" . $produit2['product_name'] . "</td>";
                    echo "<td>" . $produit2['COUNT(pa.id_produit_adherent)'] . "</td>";
                    echo "<td>" . $produit2['SUM(pa.prix_achat)'] . "€" . "</td>";
                    echo "<td>" . $produit2['AVG(pa.prix_achat)'] . "€" . "</td>";
                    echo "<td>" . "<a target='_blank' href='analyse_detail_produit.php?idProd=" . $produit2['product_id'] ."&dateDebut=$period_start&dateFin=$period_end'><span class='glyphicon glyphicon-eye-open'></span> Voir</a>" ."</td>";
                    echo "<td>" . "<a href='export_excel/export_transacParProd.php?produit=" . $produit2['product_id'] ."&debut=$period_start&fin=$period_end'> Exporter</a>" ."</td>";
                    echo "<tr>";
                  }
                  echo "<tr>";
                  echo "<td>" . "<strong>Total</strong>" ."</td>";
                  echo "<td>" . $productTotal . "</td>";
                  echo "<td>" . $sumTotalproduct['SUM(pa.prix_achat)'] . "€" . "</td>";
                  echo "<td>" . "<a href='export_excel/export_tableau_general.php'>Exporter le tableau</a>" . "</td>";
                  echo "</tr>";
                ?>
              </tbody>
            </table>
          </div>
          <p class="sub-legend">Liste des produits non vendus sur la période</p>

          <?php
          //Requete pour lister tt les produits puis filtrage pour afficher que les produits non vendus sur la période

          $listeProduit = $db->query("SELECT p.product_name,p.product_id FROM produits p");
          $tous_les_produits_non_vendus=array();
          //attribution de la liste des produits puis traitement par comparaison avec produits achetés
          while ($liste = $listeProduit->fetchAll(PDO::FETCH_ASSOC))
          {
            $j = count($liste);
            $ii=0;
            foreach ($liste as $liste_produit)
            {
              $liste_produit['product_name'];
              $liste_produit['product_id'];
              if(!(in_array($liste_produit['product_name'],$produits_achetes)))
              {
                array_push($tous_les_produits_non_vendus,$liste_produit['product_name']);
                $ii++;
              }
//rajouter un query pour afficher les produits par catégories.
            }
          }
          ?>
          <!-- affichage tableau des produits non vendus -->
          <div class="col-md-4">
            <table class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th>1. RECHARGE LIBERTE</th>
                  <th>Montant</th>
                </tr>
              </thead>
              <tbody>
                <?php
                //print_r($tous_les_produits_non_vendus);
                //---------------------------------------------------------------------
                //affichage du tableau produit non vendus
                foreach ($tous_les_produits_non_vendus as $liste_prod_non_vendus_cat1)
                //foreach ($produits_achetes as $liste_prod_non_vendus)
                {
                  if ($liste_prod_non_vendus_cat1 == 'Recharge liberté 10 heures' || $liste_prod_non_vendus_cat1 == 'Recharge Liberté 20 heures' ||
                      $liste_prod_non_vendus_cat1 == 'Recharge Liberté 30 heures' || $liste_prod_non_vendus_cat1 == 'Recharge Liberté 40 heures' ||
                      $liste_prod_non_vendus_cat1 == 'Recharge Liberté 60 heures' || $liste_prod_non_vendus_cat1 == 'Recharge Liberté 80 heures' ||
                      $liste_prod_non_vendus_cat1 == 'Recharge Liberté 120 heures')
                  {
                  echo "<tr>";
                  echo "<td>" . $liste_prod_non_vendus_cat1 . "</td>";
                  echo "<td>". "0€". "</td>";
                  echo "<tr>";
                  }
                }
                ?>
              </tbody>
          </table>
          </div>

          <div class="col-md-4">
            <table class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th>2. PASS ILLIMITE</th>
                  <th>Montant</th>
                </tr>
              </thead>
              <tbody>
                <?php
                //print_r($tous_les_produits_non_vendus);
                //---------------------------------------------------------------------
                //affichage du tableau produit non vendus
                foreach ($tous_les_produits_non_vendus as $liste_prod_non_vendus_cat2)
                //foreach ($produits_achetes as $liste_prod_non_vendus)
                {
                  if ($liste_prod_non_vendus_cat2 == 'Pass Illimité 1 mois' || $liste_prod_non_vendus_cat2 == 'Pass Illimité 3 mois' ||
                      $liste_prod_non_vendus_cat2 == 'Pass Illimité 6 mois' || $liste_prod_non_vendus_cat2 == 'Forfait Illimité 12 mois')
                  {
                  echo "<tr>";
                  echo "<td>" . $liste_prod_non_vendus_cat2 . "</td>";
                  echo "<td>". "0€". "</td>";
                  echo "<tr>";
                  }
                }
                ?>
              </tbody>
          </table>
          </div>

          <div class="col-md-4">
            <table class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th>3. PACK INTENSIF</th>
                  <th>Montant</th>
                </tr>
              </thead>
              <tbody>
                <?php
                //print_r($tous_les_produits_non_vendus);
                //---------------------------------------------------------------------
                //affichage du tableau produit non vendus
                foreach ($tous_les_produits_non_vendus as $liste_prod_non_vendus_cat3)
                //foreach ($produits_achetes as $liste_prod_non_vendus)
                {
                  if ($liste_prod_non_vendus_cat3 == 'Pack Grand Débutant' || $liste_prod_non_vendus_cat3 == 'Pack Rattrapage 5 semaines' ||
                      $liste_prod_non_vendus_cat3 == 'PACK ETE' || $liste_prod_non_vendus_cat3 == 'Pack Debutant ' ||
                      $liste_prod_non_vendus_cat3 == 'BOOST ON SUNDAY' || $liste_prod_non_vendus_cat3 == 'BOOST ON SUNDAY +++')
                  {
                  echo "<tr>";
                  echo "<td>" . $liste_prod_non_vendus_cat3 . "</td>";
                  echo "<td>". "0€". "</td>";
                  echo "<tr>";
                  }
                }
                ?>
              </tbody>
          </table>
          </div>

          <div class="col-md-12">
            <table class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th>4. COURS PARTICULIER</th>
                  <th>Montant</th>
                </tr>
              </thead>
              <tbody>
                <?php
                //print_r($tous_les_produits_non_vendus);
                //---------------------------------------------------------------------
                //affichage du tableau produit non vendus
                foreach ($tous_les_produits_non_vendus as $liste_prod_non_vendus_cat4)
                //foreach ($produits_achetes as $liste_prod_non_vendus)
                {
                  if ($liste_prod_non_vendus_cat4 == 'Cours particulier 10h solo ADH' || $liste_prod_non_vendus_cat4 == 'Cours Particulier Solo Non ADH' ||
                      $liste_prod_non_vendus_cat4 == 'Cours Particulier Solo ADH' || $liste_prod_non_vendus_cat4 == 'Coaching Direction' ||
                      $liste_prod_non_vendus_cat4 == '10 H COURS PARTICULIERS NON-ADHERENTS' || $liste_prod_non_vendus_cat4 == 'COURS PARTICULIER COUPLE NON-ADHERENTS' ||
                      $liste_prod_non_vendus_cat4 == 'carte cours particulier couple Non-Adhérents' || $liste_prod_non_vendus_cat4 == 'carte cours particulier couple Adhérents' ||
                      $liste_prod_non_vendus_cat4 == 'Cours Particulier Couple ADH')
                  {
                  echo "<tr>";
                  echo "<td>" . $liste_prod_non_vendus_cat4 . "</td>";
                  echo "<td>". "0€". "</td>";
                  echo "<tr>";
                  }
                }
                ?>
              </tbody>
          </table>
          </div>

          <div class="col-md-12">
            <table class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th>5. FORMATION</th>
                  <th>Montant</th>
                </tr>
              </thead>
              <tbody>
                <?php
                //print_r($tous_les_produits_non_vendus);
                //---------------------------------------------------------------------
                //affichage du tableau produit non vendus
                foreach ($tous_les_produits_non_vendus as $liste_prod_non_vendus_cat5)
                //foreach ($produits_achetes as $liste_prod_non_vendus)
                {
                  if ($liste_prod_non_vendus_cat5 == 'Formation professionnelle double cursus' || $liste_prod_non_vendus_cat5 == 'Formation professionnelle danseur' ||
                      $liste_prod_non_vendus_cat5 == 'Formation professionnelle enseignant' || $liste_prod_non_vendus_cat5 == 'NOUVELLE FoPro enseignant')
                  {
                  echo "<tr>";
                  echo "<td>" . $liste_prod_non_vendus_cat5 . "</td>";
                  echo "<td>". "0€". "</td>";
                  echo "<tr>";
                  }
                }
                ?>
              </tbody>
          </table>
          </div>

          <div class="col-md-12">
            <table class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th>6. SANS ENGAGEMENT</th>
                  <th>Montant</th>
                </tr>
              </thead>
              <tbody>
                <?php
                //print_r($tous_les_produits_non_vendus);
                //---------------------------------------------------------------------
                //affichage du tableau produit non vendus
                foreach ($tous_les_produits_non_vendus as $liste_prod_non_vendus_cat6)
                //foreach ($produits_achetes as $liste_prod_non_vendus)
                {
                  if ($liste_prod_non_vendus_cat6 == 'Carte Découverte 3 heures' || $liste_prod_non_vendus_cat6 == 'Carte Découverte 5 heures' ||
                      $liste_prod_non_vendus_cat6 == 'Invitation' || $liste_prod_non_vendus_cat6 == 'Pass Illimité 1 semaine' ||
                      $liste_prod_non_vendus_cat6 == 'Pass Illimité 1 semaine' || $liste_prod_non_vendus_cat6 == "Cours à l'unité" ||
                      $liste_prod_non_vendus_cat6 == 'Formule Afterwork' || $liste_prod_non_vendus_cat6 == 'Soiree afterwork' ||
                      $liste_prod_non_vendus_cat6 == 'Supplement stage 2heures' || $liste_prod_non_vendus_cat6 == 'Supplement stage 3heures' ||
                      $liste_prod_non_vendus_cat6 == 'stage 2 heures' || $liste_prod_non_vendus_cat6 == 'stage 3 heures' ||
                      $liste_prod_non_vendus_cat6 == "STAGE D'ETE" || $liste_prod_non_vendus_cat6 == "Unit STAGE D'ETE" ||
                      $liste_prod_non_vendus_cat6 == 'Stage 1h30' || $liste_prod_non_vendus_cat6 == "STAGE D'ETE cubaine" ||
                      $liste_prod_non_vendus_cat6 == "STAGE D'ETE BACHATA" || $liste_prod_non_vendus_cat6 == "STAGE D'ETE KIZOMBA" ||
                      $liste_prod_non_vendus_cat6 == "STAGE D'ETE PORTORICAINE" || $liste_prod_non_vendus_cat6 == "STAGE D'ETE SALSA COLOMBIENNE OU CHACHA/BOOGALOO" ||
                      $liste_prod_non_vendus_cat6 == 'Supplément 1h30 de Stage')
                  {
                  echo "<tr>";
                  echo "<td>" . $liste_prod_non_vendus_cat6 . "</td>";
                  echo "<td>". "0€". "</td>";
                  echo "<tr>";
                  }
                }
                ?>
              </tbody>
          </table>
          </div>

          <div class="col-md-12">
            <table class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th>7. ATELIER</th>
                  <th>Montant</th>
                </tr>
              </thead>
              <tbody>
                <?php
                //print_r($tous_les_produits_non_vendus);
                //---------------------------------------------------------------------
                //affichage du tableau produit non vendus
                foreach ($tous_les_produits_non_vendus as $liste_prod_non_vendus_cat7)
                //foreach ($produits_achetes as $liste_prod_non_vendus)
                {
                  if ($liste_prod_non_vendus_cat7 == 'ATELIER JAZZ' || $liste_prod_non_vendus_cat7 == 'Atelier SALSA CON RUMBA' ||
                      $liste_prod_non_vendus_cat7 == 'ATELIER PERFECTIONNEMENT' || $liste_prod_non_vendus_cat7 == 'ATELIER SAISON' ||
                      $liste_prod_non_vendus_cat7 == 'Atelier Choregraphique NON-ADH' || $liste_prod_non_vendus_cat7 == 'ATELIER CALI STYLE' ||
                      $liste_prod_non_vendus_cat7 == 'ATELIER KIZOMBA' || $liste_prod_non_vendus_cat7 == 'ATELIER DOMINICAN SWAG' ||
                      $liste_prod_non_vendus_cat7 == 'ATELIER TRIMESTRE' || $liste_prod_non_vendus_cat7 == 'Atelier LYRICAL LATIN FUSION' ||
                      $liste_prod_non_vendus_cat7 == '2 Ateliers LADY STYLING' || $liste_prod_non_vendus_cat7 == '3 Ateliers LADY STYLING' ||
                      $liste_prod_non_vendus_cat7 == 'ATELIER TRIMESTRE AGNES' || $liste_prod_non_vendus_cat7 == 'ATELIER SAISON AGNES' ||
                      $liste_prod_non_vendus_cat7 == 'ATELIER SEMESTRE' || $liste_prod_non_vendus_cat7 == 'At Lady styling Mayssane Decembre-Juin' ||
                      $liste_prod_non_vendus_cat7 == 'ATELIER JEREMY')
                  {
                  echo "<tr>";
                  echo "<td>" . $liste_prod_non_vendus_cat7 . "</td>";
                  echo "<td>". "0€". "</td>";
                  echo "<tr>";
                  }
                }
                ?>
              </tbody>
          </table>
          </div>

          <div class="col-md-12">
            <table class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th>8. DIVERS</th>
                  <th>Montant</th>
                </tr>
              </thead>
              <tbody>
                <?php
                //print_r($tous_les_produits_non_vendus);
                //---------------------------------------------------------------------
                //affichage du tableau produit non vendus
                foreach ($tous_les_produits_non_vendus as $liste_prod_non_vendus_cat8)
                //foreach ($produits_achetes as $liste_prod_non_vendus)
                {
                  if ($liste_prod_non_vendus_cat8 == 'Adhésion annuelle' || $liste_prod_non_vendus_cat8 == 'Produit Bouche Trou' ||
                      $liste_prod_non_vendus_cat8 == 'Assurance Report' || $liste_prod_non_vendus_cat8 == 'CARTE A REFAIRE' ||
                      $liste_prod_non_vendus_cat8 == 'CARTE PRE-RENTREE' || $liste_prod_non_vendus_cat8 == 'CONFLANS 1H par semaine' ||
                      $liste_prod_non_vendus_cat8 == 'CONFLANS 2H par semaine' || $liste_prod_non_vendus_cat8 == 'CONFLANS 3H par semaine')
                  {
                  echo "<tr>";
                  echo "<td>" . $liste_prod_non_vendus_cat8 . "</td>";
                  echo "<td>". "0€". "</td>";
                  echo "<tr>";
                  }
                }
                ?>
              </tbody>
          </table>
          </div>

          <div class="col-md-12">
            <table class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th>9. COURS SPECIAUX</th>
                  <th>Montant</th>
                </tr>
              </thead>
              <tbody>
                <?php
                //print_r($tous_les_produits_non_vendus);
                //---------------------------------------------------------------------
                //affichage du tableau produit non vendus
                foreach ($tous_les_produits_non_vendus as $liste_prod_non_vendus_cat9)
                //foreach ($produits_achetes as $liste_prod_non_vendus)
                {
                  if ($liste_prod_non_vendus_cat9 == 'EVJF 20€/pers' || $liste_prod_non_vendus_cat9 == 'EVJF 25€/ pers' ||
                      $liste_prod_non_vendus_cat9 == 'Danse Enfant & Ado' || $liste_prod_non_vendus_cat9 == 'Carte 5 heures Pilates & Mamalina ' ||
                      $liste_prod_non_vendus_cat9 == 'Carte 10 heures Pilates & Yoga' || $liste_prod_non_vendus_cat9 == 'Cours unité Pilates & Yoga' ||
                      $liste_prod_non_vendus_cat9 == 'Carte Senior 10' || $liste_prod_non_vendus_cat9 == 'Abo Senior 1 cours' ||
                      $liste_prod_non_vendus_cat9 == 'Pass Sénior ')
                  {
                  echo "<tr>";
                  echo "<td>" . $liste_prod_non_vendus_cat9 . "</td>";
                  echo "<td>". "0€". "</td>";
                  echo "<tr>";
                  }
                }
                ?>
              </tbody>
          </table>
          </div>

          <div class="col-md-12">
            <table class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th>11. LIBERTE JOURNEE</th>
                  <th>Montant</th>
                </tr>
              </thead>
              <tbody>
                <?php
                //print_r($tous_les_produits_non_vendus);
                //---------------------------------------------------------------------
                //affichage du tableau produit non vendus
                foreach ($tous_les_produits_non_vendus as $liste_prod_non_vendus_cat11)
                //foreach ($produits_achetes as $liste_prod_non_vendus)
                {
                  if ($liste_prod_non_vendus_cat11 == 'Danses journee 20heures' || $liste_prod_non_vendus_cat11 == 'Danses journée 10heures')
                  {
                  echo "<tr>";
                  echo "<td>" . $liste_prod_non_vendus_cat11 . "</td>";
                  echo "<td>". "0€". "</td>";
                  echo "<tr>";
                  }
                }
                ?>
              </tbody>
          </table>
          </div>

          <div class="col-md-12">
            <table class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th>12. PARTENARIAT</th>
                  <th>Montant</th>
                </tr>
              </thead>
              <tbody>
                <?php
                //print_r($tous_les_produits_non_vendus);
                //---------------------------------------------------------------------
                //affichage du tableau produit non vendus
                foreach ($tous_les_produits_non_vendus as $liste_prod_non_vendus_cat12)
                //foreach ($produits_achetes as $liste_prod_non_vendus)
                {
                  if ($liste_prod_non_vendus_cat12 == 'Cours unité Partenaire Tryndo' || $liste_prod_non_vendus_cat12 == 'Abonnement GYMPASS' ||
                      $liste_prod_non_vendus_cat12 == 'WEEZEVENT AFTERWORK' || $liste_prod_non_vendus_cat12 == 'WEEZEVENT STAGE 3H')
                  {
                  echo "<tr>";
                  echo "<td>" . $liste_prod_non_vendus_cat12 . "</td>";
                  echo "<td>". "0€". "</td>";
                  echo "<tr>";
                  }
                }
                ?>
              </tbody>
          </table>
          </div>
          <br>
          <p class="sub-legend">Historique des produits vendus sur la période</p>
          <br>
      <?php } ?>

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
          <?php
            if (!empty($_POST))
            {
            $period_start = $_POST['date_start'];
            $period_end = $_POST['date_end'];



            $test = $db->query("SELECT DISTINCT t.id_transaction, t.date_achat, CONCAT(user_prenom, ' ', user_nom) AS handler, t.prix_total
                                FROM transactions t
                                JOIN produits_adherents pa ON t.id_transaction = pa.id_transaction_foreign
                                JOIN users u ON u.user_id = t.transaction_handler
                                JOIN produits p ON p.product_id = pa.id_produit_foreign
                                WHERE t.date_achat BETWEEN '$period_start' AND '$period_end'
                                ORDER BY t.date_achat DESC;");

            while ($filtre = $test->fetch(PDO::FETCH_ASSOC)) {
              $productQty = $db->query("SELECT id_produit_adherent FROM produits_adherents WHERE id_transaction_foreign='$filtre[id_transaction]'")->rowCount();
              $handler = ($filtre["handler"]!=null)?$filtre["handler"]:"Pas de vendeur";
              ?>

					<div class="panel panel-purchase" id="purchase-<?php echo $filtre["id_transaction"];?>">
							<div class="panel-heading container-fluid" onClick="displayPurchase('<?php echo $filtre["id_transaction"];?>')">
								<p class="purchase-id col-xs-3">Transaction <?php echo $filtre["id_transaction"];?></p>
								<p class="col-xs-2"><?php echo $productQty;?> produit(s)</p>
								<p class="purchase-sub col-xs-4">
									<span class="modal-editable-<?php echo $filtre["id_transaction"];?>" data-field="date_achat" data-name="Date" id="date-<?php echo $filtre["id_transaction"];?>"><?php echo date_create($filtre["date_achat"])->format('d/m/Y');?></span> -
									<span class="modal-editable-<?php echo $filtre["id_transaction"];?>" data-field="transaction_handler" data-name="Vendeur" data-complete="true" data-complete-filter="staff" id="handler-<?php echo $filtre["id_transaction"];?>"><?php echo $handler;?></span> -
									<span class="modal-editable-<?php echo $filtre["id_transaction"];?>" data-field="prix_total" data-name="Prix" id="price-<?php echo $filtre["id_transaction"];?>"><?php echo $filtre["prix_total"];?></span> €</p>
								<!--Les glyphes présents sur chaque bande de transaction-->
								<span class="glyphicon glyphicon-pencil glyphicon-button glyphicon-button-alt glyphicon-button-big col-xs-1" id="edit-<?php echo $filtre["id_transaction"];?>" data-toggle="modal" data-target="#edit-modal" data-entry="<?php echo $filtre["id_transaction"];?>" data-table="transactions" title="Modifier les détails de la transaction"></span>
								<span class="glyphicon glyphicon-briefcase glyphicon-button glyphicon-button-alt glyphicon-button-big create-contract col-xs-1" id="create-contract-<?php echo $filtre["id_transaction_foreign"];?>" data-transaction="<?php echo $filtre["id_transaction"];?>"title="Afficher le contrat"></span>
								<span class="glyphicon glyphicon-file glyphicon-button glyphicon-button-alt glyphicon-button-big create-invoice col-xs-1" id="create-invoice-<?php echo $filtre["id_transaction_foreign"];?>" data-transaction="<?php echo $filtre["id_transaction"];?>" title="Afficher la facture"></span>
							</div>
							<!--Pour faire le bordereau déroulant-->
							<div class="panel-body collapse" id="body-purchase-<?php echo $filtre["id_transaction"];?>">
							</div>
						</div>
          <?php } ?>
        <?php } ?>

           <!--Modifier le bordereau-->
           <?php include "inserts/modal_product.php";?>
           <?php include "inserts/sub_modal_product.php";?>
           <?php include "inserts/edit_modal.php";?>
           <?php include "inserts/delete_modal.php";?>

          <script>
              fetchTransactionsStats();
              money_chart = Morris.Donut({
                  element: 'money-chart',
                  data: [
                      {label: "encaissé", value: '1'},
                      {label: "reçu", value: '1'},
                      {label: "en attente", value: '1'},
                      {label: "en retard", value: '1'}
                  ],
                  colors: ['#00D600', '#FFE600', '#edcb65','#FF0059'],
                  formatter: function(y, data){ return y + '€'; }
              });
              method_chart = Morris.Donut({
                  element: 'method-chart',
                  data: [
                      {label: "CB", value: '1'},
                      {label: "Chèque", value: '1'},
                      {label: "Espèces", value: '1'},
                      {label: "Chèques Vacances", value: '1'},
                      {label: "Non spécifié", value: '1'}
                  ],
                  colors: ['#4450FF', '#D60600', '#FF9919', '#EB44FF', '#A2EA8A'],
                  formatter: function(y, data){ return y + '€'; } //le titre à l'interieur du donut
              });



          function fetchTransactionsStats(){
              var filters = [];
              //push permet d'insérer une valeur dans le tableau filters
              $(".date-filter").each(function(){
                  filters.push(moment($(this).val(), "YYYY/MM/DD").format("YYYY-MM-DD"));
              });
              //{filters envoi la période sélectionné dans un tableau qu'on exploite dans la page transactions_stat.php}
              $.get("functions/stats_transactions.php", {filters: filters, products: window.products_array}).done(function(data){
                  var stats = JSON.parse(data);
                  //renderTransactionsStats prends en paramètre la var stats associé à un tableau dans transactions_stat.php
                  renderTransactionsStats(stats.general);
                  renderMaturitiesStats(stats.maturities);
              });
          }

          function renderTransactionsStats(data){
              var contents = "";
              $(".total-price").text(data.total+"€");
              $(".data-display").empty();
              contents += "<div><h4>Somme encaissée</h4><span id='total' class='stat-value'>"+data.banked+"€</span></div>";
              contents += "<div><h4>Somme reçue</h4><span id='total' class='stat-value'>"+data.received+"€</span></div>";
              contents += "<div><h4>Somme en attente</h4><span id='total' class='stat-value'>"+data.pending+"€</span></div>";
              contents += "<div><h4>Somme en retard</h4><span id='total' class='stat-value'>"+data.late+"€</span></div>";
              $("#money-chart-legend").append(contents);
              $("#bank-price").text(data.banked+"€");

              contents = "<div><h4>Carte bancaire</h4><span id='total' class='stat-value'>"+data.methods.credit_card+"€</span></div>";
              contents += "<div><h4>Chèques</h4><span id='total' class='stat-value'>"+data.methods.check+"€</span></div>";
              contents += "<div><h4>Espèces</h4><span id='total' class='stat-value'>"+data.methods.cash+"€</span></div>";
              contents += "<div><h4>Chèques Vacances</h4><span id='total' class='stat-value'>"+data.methods.voucher+"€</span></div>";
              contents += "<div><h4>Non spécifié</h4><span id='total' class='stat-value'>"+data.methods.other+"€</span></div>";
              $("#method-chart-legend").append(contents);
              var chart_data = [
                  {label: "encaissé", value: data.banked},
                  {label: "reçu", value: data.received},
                  {label: "en attente", value: data.pending},
                  {label: "en retard", value: data.late}
              ];
              var method_data = [
                  {label: "CB", value: data.methods.credit_card},
                  {label: "Chèque", value: data.methods.check},
                  {label: "Espèces", value: data.methods.cash},
                  {label: "Chèques Vacances", value: data.methods.voucher},
                  {label: "Non spécifié", value: data.methods.other}
              ]
              console.log(method_data);
              money_chart.setData(chart_data);
              method_chart.setData(method_data);
          }


          //Pour savoir quelle transaction est sélectionner ?
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

        </div>
      </div>
    </div>
  </body>
</html>
