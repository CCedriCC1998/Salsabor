<?php
/**
 * Created by PhpStorm.
 * User: AngelZatch
 * Date: 11/03/2017
 * Time: 13:42
 */
include "db_connect.php";
$db = PDOFactory::getConnection();

//"filters" provient de la page analysis_test.php
$start_date = $_GET["filters"][0];
$end_date = $_GET["filters"][1];
$products = [];
if(isset($_GET["products"]))
    $products = $_GET["products"];

//"general" et "maturities" sont appelé dans analysis_test
$stats = array(
    "general" => generalStats($db, $start_date, $end_date, $products),
    "maturities" => maturitiesStats($db, $start_date, $end_date, $products),
    //"products" => productStats($db, $start_date, $end_date)
);

//pour pouvoir parse les data car il faut le format JSON
echo json_encode($stats);

function generalStats($db, $start_date, $end_date, $products)
{
    //permet de retourner les infos du select
    $query = "SELECT t.id_transaction,
            m.montant,
            m.echeance_effectuee,
            m.statut_banque,
            m.methode_paiement,
            m.date_paiement,
            m.date_encaissement
            FROM produits_echeances m
            JOIN transactions t ON t.id_transaction = m.reference_achat";

    //selection selon produits
    if($products != []){
        $query .= " JOIN produits_adherents pa ON pa.id_transaction_foreign = t.id_transaction";
    }

    //lis le critère de la période
    $query .= " WHERE t.date_achat BETWEEN '$start_date' AND '$end_date'";

    //selection selon produit aussi (un ou plusieurs !!!!!!!!!!)
    if($products != []){
        $query .= ' AND pa.id_produit_foreign IN (SELECT product_id FROM produits WHERE product_name IN ("'.implode('", "', $products).'"))';
    }

    $query .= " ORDER BY date_achat DESC";

//    echo $query;
    //On stocke la requete query
    $stmt = $db->query($query);

    //Pour savoir l'état de la transaction vis à vis de la banque.
    $total = $received = $banked = $pending = $late = 0;
    $credit_card = $check = $cash = $voucher = $various = 0;
    while($maturity = $stmt->fetch()){
        //pour montant total et chaque méthode
        $value = $maturity["montant"];
        $method = $maturity["methode_paiement"];

        //2-->en retard pour les chèques
        if($maturity["echeance_effectuee"] == 2)
            $late += $value;
        //1-->payé pour chèque,carte,especes
        else if($maturity["statut_banque"] == 1 || $method == "Carte bancaire" || $maturity["date_encaissement"] != null){
            $banked += $value; //encaissé en banque
            if((stripos($method, "chèque") !== false || stripos($method, "cheque") !== false) && stripos($method, "vacances") !== true)
                $check += $value;
            if(stripos($method, "carte") !== false)
                $credit_card += $value;
            if($method == "Espèces")
                $cash += $value;
            if(stripos($method, "vacances") !== false)
                $voucher += $value;
        } else if(($maturity["echeance_effectuee"] == 1 || $maturity["date_paiement"] != null) && $method != "Carte bancaire")
            $received += $value; //recu mais pas encore à la banque
        else
            $pending += $value;

        $total += $value;
    }
    //data appelé dans le fetchTransactionsStats et utilisé dans le renderTransactionsStats etc...
    $data = array(
        "total" => $total,
        "received" => $received,
        "banked" => $banked,
        "pending" => $pending,
        "late" => $late,

        "methods" => array(
            "credit_card" => $credit_card,
            "cash" => $cash,
            "check" => $check,
            "voucher" => $voucher,
            "other" => $banked - ($credit_card+$cash+$check+$voucher)
        )

    );

    //echo json_encode($data);
    return $data;
}

//additionne le CA selon la période et le nbre de produit
function maturitiesStats($db, $start_date, $end_date, $products){
    $query = "SELECT m.date_echeance as date,
            SUM(CASE WHEN m.date_encaissement IS NOT NULL OR m.statut_banque = 1 THEN m.montant ELSE 0 END) AS value
            FROM produits_echeances m
            JOIN transactions t ON t.id_transaction = m.reference_achat";

    if($products != []){
        $query .= " JOIN produits_adherents pa ON pa.id_transaction_foreign = t.id_transaction";
    }

    $query .= " WHERE m.date_echeance BETWEEN '$start_date' AND '$end_date'";

    if($products != []){
        $query .= ' AND pa.id_produit_foreign IN (SELECT product_id FROM produits WHERE product_name IN ("'.implode('", "', $products).'"))';
    }

    $query .= " GROUP BY m.date_echeance
                ORDER BY date_echeance DESC";

    $stmt = $db->query($query);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


//fonction qui va retourner la catégorie, le nom du produit et le nombre de produit
function productStats($db, $start_date, $end_date){
    $query = "SELECT pc.category_name,
                    p.product_name,
                    COUNT(p.product_name) AS count
            FROM produits_adherents pa
            JOIN produits p ON pa.id_produit_foreign = p.product_id
            JOIN product_categories pc ON p.product_category = pc.category_id
            JOIN transactions t ON pa.id_transaction_foreign = t.id_transaction
            WHERE t.date_achat BETWEEN '$start_date' AND '$end_date'
            GROUP BY p.product_id
            ORDER BY category_id ASC";

    $stmt = $db->query($query);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
