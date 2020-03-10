<ul class="nav nav-tabs">
	<li role="presentation" class="active"><a href="user/<?php echo $user_id;?>">Informations personnelles</a></li>
	<li role="presentation"><a href="user/<?php echo $user_id;?>/abonnements">Abonnements</a></li>
	<li role="presentation"><a href="user/<?php echo $user_id;?>/historique">Participations</a></li>
	<li role="presentation"><a href="user/<?php echo $user_id;?>/achats">Achats</a></li>
	<li role="presentation"><a href="user/<?php echo $user_id;?>/reservations">Réservations</a></li>
	<li role="presentation"><a href="user/<?php echo $user_id;?>/taches">Tâches</a></li>
	<?php if($is_teacher == 1){ ?>
	<li role="presentation"><a>Cours donnés</a></li>
	<li role="presentation"><a>Tarifs</a></li>
	<li role="presentation"><a>Statistiques</a></li>
	<?php } ?>
</ul>
