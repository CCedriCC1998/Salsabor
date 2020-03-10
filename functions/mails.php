<?php

include "db_connect.php";
$db = PDOFactory::getConnection();

function nearExpiration($db, $product_id){
	$product_details = $db->query("SELECT *, IF(date_prolongee IS NOT NULL, date_prolongee,
							IF (date_fin_utilisation IS NOT NULL, date_fin_utilisation, date_expiration)
							) AS produit_validity FROM produits_adherents pa
								JOIN users u ON pa.id_user_foreign = u.user_id
								JOIN produits p ON pa.id_produit_foreign = p.product_id
								WHERE id_produit_adherent='$product_id'")->fetch(PDO::FETCH_ASSOC);
	$to = "cedric.bis95@gmail.com";
	setlocale(LC_ALL, 'fr_FRA');
	//$to = $product_details["user_mail"];
	if(!preg_match("#^[a-z0-9._-]+a-z@(hotmail|live|msn).[a-z]{2,4}$#", $to)){
		$line_break = "\r\n";
	} else {
		$line_break = "\n";
	}

	// Subject of the mail
	$subject = "Salsabor. Votre ".$product_details["product_name"]." arrive bientôt à expiration";

	// Mail contents
	$message = "<html>
					<head></head>
					<body>
						<p style='font-weight: 600; font-size:24px; color:#A80139'>Bonjour ".ucfirst(strtolower($product_details["user_prenom"])).",<p>
						Votre ".$product_details["product_name"]." arrive bientôt à expiration. Vous pouvez consulter les détails ci-dessous :
						<ul>
							<li><span style='color: #A80139'>Produit</span> : ".$product_details["product_name"]."</li>
							<li><span style='color: #A80139'>Date d'expiration</span> : ".utf8_encode(strftime("%d %B %Y", strtotime($product_details["produit_validity"])))."</li>";
	if($product_details["product_size"] != '0' && $product_details["product_size"] != null){
		if($product_details["volume_cours"] == '1'){
			$hours = "heure";
		} else {
			$hours = "heures";
		}
		$message .= "<li><span style='color: #A80139'>Volume de cours restant</span> : ".$product_details["volume_cours"]." ".$hours."</li>";
	}
	$message .= "</ul>
						Pour renouveler votre forfait et pouvoir continuer d'aller en cours, n'hésitez pas à passer nous voir à l'accueil ! Nous nous tiendrons à votre disposition.<br><br>
						Bonne journée !<br>L'équipe Salsabor.<br><br>
						<p style='font-style: italic'>Ceci est un message automatique. Vous pouvez toujours y répondre, mais malheureusement vous n'aurez pas de retour :(</p>
					</body>
					</html>";
	$message = wordwrap($message, 70, $line_break);
	echo $message;

	// Header
	$header = "From: Salsabor <cedric.cardot95@gmail.com>".$line_break;
	/*$header .= "Reply-to: \"AngelZatch\" <angelzatch@gmail.com>".$line_break;*/
	$header .= "MIME-Version: 1.0".$line_break;
	$header .= "Content-Type: text/html; charset=iso-8859-1".$line_break;
	$header .= "Content-Transfer-Encoding: 8bit".$line_break;

	mail($to, $subject, $message, $header);
}

?>
