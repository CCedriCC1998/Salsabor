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
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Suvi Utilisateurs</title>
    <?php  include "styles.php";?>
    <link rel="stylesheet" href="assets/js/fullcalendar2020/core/main.min.css">
    <link rel="stylesheet" href="assets/js/fullcalendar2020/daygrid/main.css">
    <link rel="stylesheet" href="assets/js/fullcalendar2020/timegrid/main.css">
    <link rel="stylesheet" href="assets/js/fullcalendar2020/bootstrap/main.css">

</head>
<body>
    <?php// include "scripts.php";?>
    <?php include "nav.php";?>
    <div class="container-fluid">
        <div class="row">
            <?php include "side-menu.php";?>
            <div class="col-sm-offset-3 col-lg-10 col-lg-offset-2 main">
              <legend><span class="glyphicon glyphicon-bishop"></span> Suivi des participations par utilisateur</legend>
    					<ul class="nav nav-tabs">
    						<li role="presentation"><a href="regularisation/participations/all/0">Tout afficher</a></li>
    						<li role="presentation"><a href="regularisation/participations/user/0">Par utilisateur</a></li>
                <li role="presentation" class="active">
                  <a href="suivi_utilisateur.php">Suivi par utilisateur</a>
                </li>
    					</ul>
                <div class="container-fluid">
                    <p class="help-block">Tapez le nom d'un utilisateur pour afficher son suivi des participations.</p>
                    <form role="form" action="suivi_utilisateur.php" method="post">
                    <div class="form-group">
                        <label for="" class="control-label col-xs-2">Utilisateurs
                            <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" title="Ecrivez le nom de l'utilisateur souhaité (n'ajoutez pas de caractères supplémentaire au nom, celà pourrait ne pas faire fonctionner la recherche)."></span>
                        </label>
                        <div class="col-xs-4">
                            <input type="text" class="form-control" id="user-box-input" name="user_nom" value="<?php if (!empty($_POST)) {echo $user_nom = $_POST['user_nom'];}?>" placeholder="Cherchez un utilisateur...">
                            <div class="user-box"></div>
                        </div>
                        <div class="form-actions col-xs-4">
                          <button type="submit" class="btn btn-warning">Rechercher</button>
                        </div>
                    </div>
                  </form>
                  <?php if (!empty($_POST)) {
                    $user_nom = $_POST['user_nom'];
                  }
                  ?>
                  <?php
                  $today= date('Y-m-d');
                  $date = date('Y-m-d',strtotime('-12 month',strtotime($today)));
                   ?>
                  <br><br>
                  <div id="calendrier" class="col-xs-12"></div>
                  <?php if(!empty($_POST)){ ?>
                  <p class="sub-legend">Historique des passages sur une année</p>
                  <a href='export_excel/export_participations.php?debut=<?php echo $date?>&fin=<?php echo $today?>&nom=<?php echo $user_nom ?>' class="btn btn-success">Export des participations</a>
                  <div class="col-md-12">
                    <br>
                    <table class="table table-bordered table-striped">
                      <thead>
                        <tr>
                          <th>Nom</th>
                          <th>Prénom</th>
                          <th>Nom du cours</th>
                          <th>Heure scan du badge</th>
                          <th>Début session</th>
                          <th>Fin session</th>
                          <th>ID professeur</th>

                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        $suivi = $db->query("SELECT pr.passage_id,pr.user_rfid,pr.room_token,pr.passage_date,pr.produit_adherent_id,
                                    		             u.user_id,u.user_prenom,u.user_nom,
                                                     s.session_name,s.session_start,s.session_end,s.session_teacher,
                                                     r.room_name
                                              FROM participations pr
                                              LEFT JOIN sessions s ON pr.session_id = s.session_id
                                              LEFT JOIN rooms r ON s.session_room = r.room_id
                                              LEFT JOIN users u ON u.user_id = pr.user_id
                                              WHERE s.session_start BETWEEN '$date' AND '$today' AND
                                              u.user_nom LIKE '$user_nom'
                                              ORDER BY s.session_start DESC");
                        //Liste les noms produits achetés
                          while ($historique = $suivi->fetch())
                          {
                            echo "<tr>";
                            echo "<td>" . $historique['user_nom'] . "</td>";
                            echo "<td>" . $historique['user_prenom'] . "</td>";
                            echo "<td>" . $historique['session_name'] . "</td>";
                            echo "<td>" . $historique['passage_date'] ."</td>";
                            echo "<td>" . $historique['session_start'] ."</td>";
                            echo "<td>" . $historique['session_end'] ."</td>";
                            echo "<td>" . $historique['session_teacher'] ."</td>";
                            echo "<tr>";
                          }
                        ?>
                      </tbody>
                    </table>
                  </div>
                <?php } ?>
                  <style>
                  #calendrier{
                    width: 80%;
                    margin: 5% 10%;
                  }
                  </style>
                </div>
                <script src="assets/js/fullcalendar2020/core/fullcal.js"></script>
                <script src="assets/js/fullcalendar2020/daygrid/main.js"></script>
                <script src="assets/js/fullcalendar2020/timegrid/main.js"></script>
                <script src="assets/js/fullcalendar2020/bootstrap/main.js"></script>
                <script src="assets/js/jquery-2.1.4.min.js"></script>
                <script src="assets/js/jquery.textcomplete.min.js"></script>
                <script>
                window.onload = () => {
                    // On va chercher la div dans le HTML
                    let calendarEl = document.getElementById('calendrier');

                    // On instancie le calendrier
                    let calendar = new FullCalendar.Calendar(calendarEl, {
                        // On charge le composant "dayGrid"
                        plugins: [ 'dayGrid','timeGrid','list' ],
                        defaultView: 'timeGridWeek',
                        locale: 'fr',
                        header: {
                          left: 'prev,next today',
                          center: 'title',
                          right: 'dayGridMonth,timeGridWeek,timeGridDay'
                        },
                        buttonText: {
                        today: 'Aujourd\'hui',
                        month: 'Mois',
                        week: 'Semaine',
                        day: 'Jour'
                      },
                      events: 'functions/suivi_calendrier.php?nom=<?php echo $user_nom?>'
                    });

                    // On affiche le calendrier
                    calendar.render();
                  }
                </script>
                <script>
                $(document).ready(function(){
                  window.users_array = [];
                $.get("functions/fetch_users.php").done(function(data){
                    var user_list = JSON.parse(data);
                    $("#user-box-input").textcomplete([{
                        match: /(^|\b)(\w{2,})$/,
                        search: function(term, callback){
                            callback($.map(user_list, function (item) {
                                return item.toLocaleLowerCase().indexOf(term.toLocaleLowerCase()) === 0 ? item : null;
                            }));
                        },
                        //enlever l'item qui à été ajouté
                        replace: function(item){
                            //var random_id = Math.random() * (100000 - 1) + 1;
                            //$(".user-box").append("<span class='label label-default label-filter' id='label-"+random_id+"'>"+item+" <span class='glyphicon glyphicon-remove'></span></span>");
                            //$("#user-box-input").val("");
                            users_array.push(item);
                            return item;
                        }
                    }]);
                });
              });
                </script>
            </div>
        </div>
    </div>
</body>
</html>
