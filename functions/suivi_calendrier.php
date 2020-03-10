<?php
require_once 'db_connect.php';
$db = PDOFactory::getConnection();

$nom = $_GET['nom'];

$query = "SELECT pr.passage_id,pr.user_rfid,pr.room_token,pr.passage_date,pr.produit_adherent_id,
		             u.user_id,u.user_prenom,u.user_nom,
                 s.session_name,s.session_start,s.session_end,s.session_teacher,
                 r.room_name
          FROM participations pr
          LEFT JOIN sessions s ON pr.session_id = s.session_id
          LEFT JOIN rooms r ON s.session_room = r.room_id
          LEFT JOIN users u ON u.user_id = pr.user_id
          WHERE u.user_nom LIKE '$nom'";

$load = $db->query($query);

$calendrier = array();
while($recherche = $load->fetch(PDO::FETCH_ASSOC)){
  $cal = array(
    //"passage_id" => $recherche['passage_id'],
    //"user_rfid" => $recherche['user_rfid'],
    //"room_token" => $recherche['room_token'],
    //"passage_date" => $recherche['passage_date'],
    //"produit_adherent_id" => $recherche['produit_adherent_id'],
    "id" => $recherche['user_nom'],
    //"user_prenom" => $recherche['user_prenom'],
    //"user_nom" => $recherche['user_nom'],
    "title" => $recherche['session_name'],
    "start" => $recherche['session_start'],
    "end" => $recherche['session_end'],
    //"session_teacher" => $recherche['session_teacher'],
    //"room_name" => $recherche['room_name']
  );
  array_push($calendrier, $cal);
}
echo json_encode($calendrier);

?>
