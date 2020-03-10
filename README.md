# Salsabor Gestion

Salsabor Gestion est l'application de gestion de Salsabor, un studio de danse basé à Paris dans le 3ème arrondissement.

Cette application permet le suivi des cours, des professeurs, des danseurs, et fournit des outils de gestion de ressources et de monitoring.

1. Gestion des utilisateurs

Afin de suivre les utilisateurs qui viennent en cours, l'application dispose d'un suivi en temps réel des usagers lorsqu'ils sont sur place, grâce à un système de passages.

== Passages ==
90 minutes avant chaque cours, l'application "ouvre" le cours; elle le rend réceptif aux passages en temps réel, enregistrés grâce à un lecteur de cartes RFID dans chaque salle qui capte les passes des usagers. Ainsi, lorsqu'une personne valide son passe RFID dans une salle, l'application peut identifier qui est la personne, mais également à quel cours il s'est présenté. Ce système nécessite une vérification humaine à cause de l'irrégularité comportementale des usagers, mais permet d'avoir des informations toujours pertinentes quant au statut des abonnements de chacun.

== Abonnements ==
Tous les abonnements des utilisateurs sont également stockés en base. En lien direct avec les passages, la consommation des abonnements est automatisée; déduction d'heures, activation & expiration, extension de dates... L'application supporte l'intégralité des fonctionnalités permettant de suivre facilement et efficacement les produits de tous les utilisateurs. Elle propose également des outils de régularisation au cas où un utilisateur aurait des produits en état irrégulier (comme après une consommation excessive - ces situations ont été déclenchées par la migration vers le nouveau système en 2015 et sont maintenant réglées)

== Données ==
Tous les utilisateurs sont conservés en base de données, avec au minimum une identité et un moyen de contact (adresse mail, numéro de téléphone, adresse postale). Avec ses informations, on peut lier les utilisateurs et leurs forfaits et leur envoyer des mails, qu'ils soient notificatifs ou promotionnels.

Pour que toutes ces informations n'échappent pas au staff qui interagit avec l'application quotidiennement, Salsabor Gestion propose également des outils de fonctionnement.

2. Outils de fonctionnement

== Système de tâches ==
Similaire à Trello, Salsabor Gestion inclut un système de tâches à faire; il est possible de créer des tâches indiquant des options précises concernant n'importe quel utilisateur : un forfait à régulariser, des informations manquantes, un rendez-vous... Ces tâches peuvent également être assignées à un groupe de personnes ou à une seule spécifiquement grâce à un système d'étiquettes.

== Etiquettes ==
Les étiquettes sont des labels personnalisables et disponible en quantité virtuellement illimitée qui permettent de créer des groupes d'utilisateurs. Exemple : toutes les personnes responsable de l'accueil et la prise en charge de clients sont regroupés sous l'étiquette "Accueil". Il est possible d'affecter un nombre illimité d'étiquettes à chaque personne; cette liberté permet d'optimiser la fluidité des tâches à faire, mais également de cloisonner l'application pour restreindre l'accès à certaines catégories sensibles à un groupe de personnes contrôlé par ses étiquettes.

== Notifications ==
Lorsque l'application doit faire passer un message, elle crée des notifications. Visibles par un nombre variable d'utilisateurs, ces notifications permettent d'informer sur des évènements qui se déroule au sein de l'application (début de cours, enregistrements, expirations de produits, retards d'échéances...)
Certaines notifications sont en général accompagnées d'une tâche à faire.

3. Données

En plus des utilisateurs et de leurs produits, Salsabor Gestion enregistre tous les cours, les tarifs et les salles.

== Salles ==
Il est possible de manipuler les salles à volonté. Addition, suppression, modification des noms, adresses, lecteurs RFID associés, l'application permet de rapidement créer une salle pour un besoin unique à court-terme comme gérer toutes les salles permanentes. Cette rapidité sert lorsqu'une prestation change soudainement d'endroit où qu'un contrat avec une salle privée est obtenue.


Tous ces outils décrivent l'application en date du 13 juin 2016. L'application a été entièrement développée par Andréas Pinbouen, en reprenant les idées du système antérieur développé par Patrick Cardot et avec les retours permanents du gérant de Salsabor, Didier Galvani.

