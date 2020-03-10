<?php
/**
 * Created by PhpStorm.
 * User: AngelZatch
 * Date: 11/03/2017
 * Time: 12:20
 */
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
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Analyse</title>
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
                <legend><span class="glyphicon glyphicon-stats"></span> Analyse</legend>
                <ul class="nav nav-tabs">
                    <li role="presentation" class="active"><a href="">Transactions</a></li>
                    <li role="detail"><a href="analyse/analyse_detail">Analyse détaillées</a></li>
                    <!--<li role="presentation"><a href="">Cours</a></li>
                    <li role="presentation"><a href="">Produits</a></li>-->
                </ul>
                <div class="container-fluid">
                    <p class="help-block">Filtrez les transactions effectuées par période</p>
                    <div class="form-group">
                        <label for="" class="control-label col-xs-3">Date de début</label>
                        <label for="" class="control-label col-xs-3">Date de fin</label>
                        <label for="" class="control-label col-xs-6">Produits
                            <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" title="Vous pouvez ajouter des produits à l'espace ci-dessous pour filtrer les résutlats. Si vous laissez l'encadré vide, tous les produits seront sélectionnés (par défaut)."></span>
                        </label>
                        <div class="col-xs-3">
                            <input type="text" class="form-control date-filter" id="datepicker-start">
                        </div>
                        <div class="col-xs-3">
                            <input type="text" class="form-control date-filter" id="datepicker-end">
                        </div>
                        <div class="col-xs-6">
                            <input type="text" class="form-control" id="product-box-input" placeholder="Cherchez un produit...">
                            <div class="product-box"></div>
                        </div>
                    </div>
                </div>

                <p class="sub-legend">Totaux</p>
                Total des transactions sur cette période : <span class="total-price"></span>
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
                <p class="sub-legend">Revenus encaissés par semaine</p>
                <div class="col-lg-12">
                    <div class="chart" id="maturities-chart"></div>
                </div>

                <!--<p class="sub-legend">Découpage par catégories de produits</p>-->
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

        .stat-title{
            font-size: 1.2em;
            text-align: center;
            text-decoration: underline;
        }

        .product-box{
            height:150px;
            background-color: white;
            padding: 10px;
        }

        .product-box > .label {
            display: inline-block;
            cursor: pointer;
            margin: 5px;

        }
    </style>
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
            fetchTransactionsStats();
            money_chart = Morris.Donut({
                element: 'money-chart',
                data: [
                    {label: "encaissé", value: '1'},
                    {label: "reçu", value: '1'},
                    {label: "en attente", value: '1'},
                    {label: "en retard", value: '1'}
                ],
                colors: ['00D600', 'FFE600', 'edcb65','FF0059'],
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
                colors: ['4450FF', 'D60600', 'FF9919', 'EB44FF', 'A2EA8A'],
                formatter: function(y, data){ return y + '€'; }
            });
            maturities_chart = Morris.Line({
                element: 'maturities-chart',
                data: null,
                xkey: 'date',
                ykeys: ['value'],
                labels: ['Montant'],
                lineColors: ['#A80139']
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

        function fetchTransactionsStats(){
            var filters = [];
            $(".date-filter").each(function(){
                filters.push(moment($(this).val(), "DD/MM/YYYY").format("YYYY-MM-DD"));
            });
            $.get("functions/stats_transactions.php", {filters: filters, products: window.products_array}).done(function(data){
                var stats = JSON.parse(data);
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

        function renderMaturitiesStats(data){
            maturities_chart.setData(data);
        }
    </script>
</body>
</html>
