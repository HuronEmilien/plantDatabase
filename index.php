<?php
include("squelettes/fragments/frag_deb.php");
require('Lib.php');
require('squelettes/Recherche.php');

//definit l'action par defaut avec accueil pour avoir la case accueil comme page de base (ca ma permit de ne pas avoir une page vide lorsque l'on arrive sur le site)
$actionParDefaut = "accueil";

// Récupère l'action de l'URL sinon utilise l'action accueil
$action = isset($_GET['action']) ? $_GET['action'] : $actionParDefaut;

// Définit un critère de tri par défaut si aucun n'est donné
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'idP'; 

// Définit un ordre de tri par défaut asc ou desc
$order = isset($_GET['order']) && $_GET['order'] === 'desc' ? 'desc' : 'asc'; 

// Récupère le numéro de la page actuelle ou mets la 1
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; 

// Récupère la limite d'éléments à afficher par page sinon 10
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10; 

// fait un lien pour trier selon cette colonne
function TrieLien($colonne, $cSort, $cOrder) {
    $new = $cSort === $colonne && $cOrder === 'asc' ? 'desc' : 'asc';
    return "index.php?action=afficher&sort=$colonne&order=$new&page=1";
}

// fait un lien pour la limte
function TriePage($cLimite) {
    return "index.php?action=afficher&sort=$sort&order=$order&page=$page&limit=$cLimite";
}

switch ($action) {
    case "afficher":
        $connection = connecter();

        // la position de départ pour commencer à récupérer des données
        $offset = ($page - 1) * $limit;

        // récupérer les plantes avec tri et pagination
        $requete = "SELECT * FROM Plante ORDER BY " . htmlspecialchars($sort) . " " . htmlspecialchars($order) . " LIMIT $limit OFFSET $offset";

        try {
            $query = $connection->query($requete);
            $query->setFetchMode(PDO::FETCH_OBJ);

            // Début de la construction du contenu HTML
            $corps = "<div class='content-container'>";
            $corps .= "<h2 class='plante-table'>Liste des plantes</h2>";
            $corps .= "<form action='index.php' method='get' class='limite-form'>";
            $corps .= "<input type='hidden' name='action' value='afficher'>";
            $corps .= "<input type='hidden' name='sort' value='$sort'>";
            $corps .= "<input type='hidden' name='order' value='$order'>";
            $corps .= "<input type='hidden' name='page' value='$page'>";
            $corps .= "Afficher <select name='limit' onchange='this.form.submit()' class='limit-selector'>";

            // le nombre d'éléments affichés
            foreach ([2, 3, 4, 5, 6] as $opt) {
                $selected = $opt == $limit ? 'selected' : '';
                $corps .= "<option value='$opt' $selected>$opt</option>";
            }
            $corps .= "</select> nombre par page.";
            $corps .= "</form>";

            // Construction du tableau pour afficher les plantes
            $corps .= "<table class='plante-table'><tr>";
            $corps .= "<th><a href='" . TrieLien('idP', $sort, $order) . "'>ID</a></th>";
            $corps .= "<th><a href='" . TrieLien('nom', $sort, $order) . "'>Nom</a></th>";
            $corps .= "</tr>";

            // Boucle pour afficher chaque plante
            while ($enregistrement = $query->fetch()) {
                $corps .= "<tr><td>".$enregistrement->idP."</td>";
                $corps .= "<td>".$enregistrement->nom."</td>";
                $corps .= "<td>";
                $corps .= "<a href='index.php?action=modifier&idP=".$enregistrement->idP."' class='btn-primary'>Modifier</a> ";
                $corps .= "<a href='index.php?action=supprimer&idP=".$enregistrement->idP."' class='btn-danger'>Supprimer</a> ";
                $corps .= "<a href='index.php?action=voir&idP=".$enregistrement->idP."' class='btn-info'>Voir</a>";
                $corps .= "</td></tr>";                
            }
            $corps .= "</table>";

            // calcul du nombre total de pages
            $cmpt = $connection->query("SELECT COUNT(*) FROM Plante");
            $totalR = $cmpt->fetchColumn();
            $totalP = ceil($totalR / $limit);

            // Construction de la pagination
            $corps .= "<div class='plante-pagination'>";
            for ($i = 1; $i <= $totalP; $i++) {
                $class = ($i == $page) ? 'current' : ''; // Ajoute la classe 'current' si c'est la page active
                $corps .= "<a href='index.php?action=afficher&sort=$sort&order=$order&page=$i&limit=$limit' class='$class'>$i</a> ";
            }
            $corps .= "</div>";
            $corps .= "</div>"; // Fermeture de la div content-container

        // si erreur 
        } catch (PDOException $e) {
            error_log("Erreur SQL : " . $e->getMessage()); // Stocke l'erreur dans les logs
            die("Une erreur est survenue, merci de réessayer plus tard.");
        } finally {
            $query = null;
            $connection = null;
        }
        $zonePrincipale = $corps;
        break;
    
    case "delete":
        $connection = connecter();
        $idP = isset($_POST['idP']) ? $_POST['idP'] : null;
        if ($idP !== null) {
            $plante = new GestionPlantes($idP, "", "", "", 0, "", "", $connection);
            // supprime la plante en utilisant la méthode supprimer()
            if ($plante->supprimer()) {
                $corps = "<div class='update-message'>";
                $corps .= "<h2>Suppression de la plante $idP</h2>";
                $corps .= "</div>";
            }
        }    
        $zonePrincipale = $corps;
        break;
        
    case "modifier":
        $idP = $_GET["idP"] ?? ($_POST["idP"] ?? null);
        // Définit la cible comme "modifier"
        $cible = 'modifier';
        $connection = connecter();
        $erreur = ['nom' => '', 'nomLatin' => '', 'origine' => '', 'hauteur' => '', 'arrosage' => '', 'remarques' => ''];
    
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm'])) {

            // Récupère les données modifiées depuis le formulaire
            $nom = $_POST['nom'];
            $nomLatin = $_POST['nomLatin'];
            $origine = $_POST['origine'];
            $hauteur = $_POST['hauteur'];
            $arrosage = $_POST['arrosage'];
            $remarques = $_POST['remarques'];
    
            // Crée un objet GestionPlantes mets à jour la base de données
            $plante = new GestionPlantes($idP,$nom, $nomLatin, $hauteur, $origine, $arrosage, $remarques, $connection);
            $corps = "<div class='update-message'>";
            if ($plante->modifier($nom, $nomLatin, $origine, $hauteur, $arrosage, $remarques)) {
                // Affiche un message de succès en cas de mise à jour réussie
                $corps .= "<h2>Mise à jour de la Plante " . htmlspecialchars($idP) . "</h2>";
                $corps .= "<h2>" . htmlspecialchars($idP) . " " . htmlspecialchars($nom) . " " . htmlspecialchars($nomLatin) . " " . htmlspecialchars($origine) . " " . htmlspecialchars($hauteur) . " " . htmlspecialchars($arrosage) . " " . htmlspecialchars($remarques) ."</h2>";
            }
            $corps .= "</div>";
        } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Récupère les données modifiées depuis le formulaire
            $nom = $_POST['nom'] ?? '';
            $nomLatin = $_POST['nomLatin'] ?? '';
            $origine = $_POST['origine'] ?? '';
            $hauteur = $_POST['hauteur'] ?? '';
            $arrosage = $_POST['arrosage'] ?? '';
            $remarques = $_POST['remarques'] ?? '';
    
            // Préparation du formulaire de confirmation
            $corps = "<div class='confirmation-form'>";
            $corps .= "<p>Etes vous sûr de vouloir mettre à jour cette plante ?</p>";
            $corps .= "<form action='index.php?action=modifier' method='post'>";
            $corps .= "<input type='hidden' name='idP' value='" . htmlspecialchars($idP) . "'>";
            $corps .= "<input type='hidden' name='nom' value='" . htmlspecialchars($nom) . "'>";
            $corps .= "<input type='hidden' name='nomLatin' value='" . htmlspecialchars($nomLatin) . "'>";
            $corps .= "<input type='hidden' name='origine' value='" . htmlspecialchars($origine) . "'>";
            $corps .= "<input type='hidden' name='hauteur' value='" . htmlspecialchars($hauteur) . "'>";
            $corps .= "<input type='hidden' name='arrosage' value='" . htmlspecialchars($arrosage) . "'>";
            $corps .= "<input type='hidden' name='remarques' value='" . htmlspecialchars($remarques) . "'>";
            $corps .= "<input type='hidden' name='confirm' value='true'>";
            $corps .= "<button type='submit' class='btn btn-warning'>Enregistrer</button> ";
            $corps .= "<a href='index.php' class='btn btn-secondary'>Annuler</a>";
            $corps .= "</form>";
            $corps .= "</div>";
            
            $zonePrincipale = $corps;
        } else {
            //  afficher le formulaire initial pré-rempli pour modification
            $stmt = $connection->prepare("SELECT * FROM Plante WHERE idP = :idP");
            $stmt->bindParam(':idP', $idP);
            $stmt->execute();
            $resultat = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($resultat) {
                // Si la plante existe, récupère ses données et inclut le formulaire HTML pré-rempli
                $nom = $resultat['nom'];
                $nomLatin = $resultat['nomLatin'];
                $origine = $resultat['origine'];
                $hauteur = $resultat['hauteur'];
                $arrosage = $resultat['arrosage'];
                $remarques = $resultat['remarques'];
                include("contenu/formulairePlante.html"); 
                $zonePrincipale = $corps;
            }
        }
        $zonePrincipale = $corps;
        break;
        
    case "update":
        $idP = isset($_POST["idP"]) ? $_POST["idP"] : null; // ID de la plante à mettre à jour
        $nom = isset($_POST["nom"]) ? $_POST["nom"] : null;
        $nomLatin = isset($_POST["nomLatin"]) ? $_POST["nomLatin"] : null;
        $origine = isset($_POST["origine"]) ? $_POST["origine"] : null;
        $hauteur = isset($_POST["hauteur"]) ? $_POST["hauteur"] : null;
        $arrosage = isset($_POST["arrosage"]) ? $_POST["arrosage"] : null;
        $remarques = isset($_POST["remarques"]) ? $_POST["remarques"] : null;
        
        // Vérifie si toutes les données sont présentes pour effectuer la mise à jour
        if ($idP && $nom && $nomLatin && $origine && $hauteur && $arrosage && $remarques) {
            $connection = connecter();
                
            // requête SQL pour mettre à jour les informations de la plante
            $requete = "UPDATE Plante SET nom=:nom, nomLatin=:nomLatin, hauteur=:hauteur, origine=:origine, arrosage=:arrosage, remarques=:remarques WHERE idP=:idP";
            $stmt = $connection->prepare($requete);
                
            // Prépare les données à insérer dans la requête
            $data = array(
                ':idP' => $idP,
                ':nom' => $nom,
                ':nomLatin' => $nomLatin,
                ':origine' => $origine,
                ':hauteur' => $hauteur,
                ':arrosage' => $arrosage,
                ':remarques' => $remarques,
            );
        
            // Exécute la requête avec les données préparées
            if ($stmt->execute($data)) {
                $corps = "<div class='update-message'>";
                $corps .= "<h2>Mise à jour de la plante $idP</h>";
                $corps .= "<h2>$idP $nom $nomLatin $origine $hauteur $arrosage $remarques</h2>";
                $corps .= "</div>";
                $zonePrincipale = $corps;                
            }
        }
        break;

    case "saisir":
        // j'ai été obligé d'initialiser les variables ici puisque je n'arrivais pas initialiser le formulaire sans les initialiser de base dans la case
        $cible = 'saisir';
        $nom = ''; 
        $nomLatin = ''; 
        $origine = ''; 
        $hauteur = ''; 
        $arrosage = ''; 
        $remarques = '';
        $erreur = ['nom' => '', 'nomLatin' => '', 'origine' => '', 'hauteur' => '', 'arrosage' => '', 'remarques' => ''];
        $corps = '';
    
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            // Récupère les données du formulaire
            $nom = $_POST['nom'] ?? '';
            $nomLatin = $_POST['nomLatin'] ?? '';
            $origine = $_POST['origine'] ?? '';
            $hauteur = $_POST['hauteur'] ?? '';
            $arrosage = $_POST['arrosage'] ?? '';
            $remarques = $_POST['remarques'] ?? '';
    
            // Validation des données
            if (empty($nom)) {
                $erreur["nom"] = "Le nom est requis.";
            }
            if (empty($nomLatin)) {
                $erreur["nomLatin"] = "Le nom latin est requis.";
            }
            if (empty($origine)) {
                $erreur["origine"] = "L'origine est requise.";
            }
            if (empty($hauteur) || !is_numeric($hauteur)) {
                $erreur["hauteur"] = "Une hauteur valide (numérique) est requise.";
            }
            if (empty($arrosage)) {
                $erreur["arrosage"] = "L'information sur l'arrosage est requise.";
            }
            if (empty($remarques)) {
                $erreur["remarques"] = "Des remarques sont requises.";
            }
    
            // Si aucune erreur de validation insertion des données
            if (empty($erreur["nom"]) && empty($erreur["nomLatin"]) && empty($erreur["origine"]) && empty($erreur["hauteur"]) && empty($erreur["arrosage"]) && empty($erreur["remarques"])) {
                $connection = connecter();
                $plante = new GestionPlantes(null, $nom, $nomLatin, $origine, $hauteur, $arrosage, $remarques, $connection);
                // Appelle la méthode enregistrer() pour insérer
                if ($plante->enregistrer()) {
                    $corps = "<span class='white-text'>Insertion de : ". htmlspecialchars($nom) . " nom latin de " . htmlspecialchars($nomLatin) . " de hauteur " . htmlspecialchars($hauteur) . " d'origine " . htmlspecialchars($origine) . " l'arrosage " . htmlspecialchars($arrosage) . " avec pour remarques : " . htmlspecialchars($remarques) . " et avec comme clé principale <u>" . htmlspecialchars($plante->getIdP()) . "</u>.</span>";
                    
                    $zonePrincipale = $corps;
                }
            }

        }
        
        // Générer le formulaire avec les messages d'erreur
        $corps .= "<form method='post' action='index.php?action={$cible}'>";
        $corps .= "<label> Nom </label><input type='text' name='nom' value='{$nom}'><span>{$erreur['nom']}</span><br>";
        $corps .= "<label> Nom Latin </label><input type='text' name='nomLatin' value='{$nomLatin}'><span>{$erreur['nomLatin']}</span><br>";
        $corps .= "<label> Origine </label><input type='text' name='origine' value='{$origine}'><span>{$erreur['origine']}</span><br>";
        $corps .= "<label> Hauteur </label><input type='text' name='hauteur' value='{$hauteur}'><span>{$erreur['hauteur']}</span><br>";
        $corps .= "<label> Arrosage </label><input type='text' name='arrosage' value='{$arrosage}'><span>{$erreur['arrosage']}</span><br>";
        $corps .= "<label> Remarques </label><input type='text' name='remarques' value='{$remarques}'><span>{$erreur['remarques']}</span><br>";
        $corps .= "<input type='submit' name='submit' value='Envoyer'>";
        $corps .= "</form>";
                
        $zonePrincipale = $corps;
        break;
                

    case "supprimer": 
        $idP=$_GET["idP"];
          
        // Construction du formulaire de confirmation
        $corps = "<div class='confirmation-form'>";
        $corps .= "<form action='index.php?action=delete' method='post'>";
        $corps .= "<input type='hidden' name='action' value='confirmer_suppression'>";
        $corps .= "<input type='hidden' name='idP' value='$idP'>";
        $corps .= "<p>Êtes-vous sûr de vouloir supprimer cette plante ?</p>";
        $corps .= "<input type='submit' class='btn btn-danger' value='Confirmer'>";
        $corps .= "<a href='index.php' class='btn btn-secondary'>Annuler</a>";
        $corps .= "</form>";
        $corps .= "</div>";
        $zonePrincipale=$corps ;
        $connection = null;
        break;


    case "rechercher":
        $searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
    
        // formulaire de recherche
        $corps = "<form action='index.php' method='get'>";
        $corps .= "<input type='hidden' name='action' value='rechercher'>";
        $corps .= "<input type='text' name='search' value='".htmlspecialchars($searchQuery, ENT_QUOTES)."'>";
        $corps .= "<button type='submit' class='btn-warning'>Rechercher</button>";
        $corps .= "</form>";

    
        if (!empty($searchQuery)) {
            // Effectue la recherche dans la base de données avec la donnée
            $resultats = Recherche::rechercherDansBase($searchQuery);
    
            // Affiche les résultats de la recherche
            if (count($resultats) > 0) {
                $corps .= "<h2 class='recherche-resultats'>Résultats de la recherche pour '".htmlspecialchars($searchQuery)."'</h2>";
                $corps .= "<table class='recherche-resultats'>";
                $corps .= "<tr>";
                $corps .= "<th>ID</th>";
                $corps .= "<th>Nom</th>";
                $corps .= "<th>Nom Latin</th>";
                $corps .= "<th>Actions</th>"; 
                $corps .= "</tr>";
                foreach ($resultats as $plante) {
                    $corps .= "<tr>";
                    $corps .= "<td>" . $plante->getIdP() . "</td>";
                    $corps .= "<td>" . $plante->getNom() . "</td>";
                    $corps .= "<td>" . $plante->getNomLatin() . "</td>";
                    $corps .= "<td>";
                    // Ajoute modifier supprimer voir
                    $corps .= "<div class='boutonsRecherche'>";
                    $corps .= "<a href='index.php?action=modifier&idP=" . $plante->getIdP() . "' class='btn-primary'>Modifier</a> ";
                    $corps .= "<a href='index.php?action=supprimer&idP=" . $plante->getIdP() . "' class='btn-danger'>Supprimer</a> ";
                    $corps .= "<a href='index.php?action=voir&idP=" . $plante->getIdP() . "' class='btn-info'>Voir</a>";
                    $corps .= "</div>";
                    $corps .= "</td>";
                    $corps .= "</tr>";
                }
                $corps .= "</table>";
            } else {
                // si aucune correspondance
                $corps .= "<div class='no-resultats'>";
                $corps .= "<h2>Aucun résultat trouvé pour '".htmlspecialchars($searchQuery)."'</h2>";
                $corps .= "</div>";
            }                
        } 
        $zonePrincipale = $corps;
        break;
        
        
    case "accueil":
        $corps = "<div class=main-content>";
        $corps .= "<div>";
        $corps .= "<h2>Découverte des Plantes</h2>" ;
        $corps .= "<p>Les plantes, constituant essentiel des écosystèmes terrestres, fascinent par leur diversité et complexité. Elles sont partout autour de nous, jouant des rôles cruciaux dans la régulation du climat et la production d'oxygène. La découverte des plantes commence souvent par l'observation de leur morphologie : racines, tiges, feuilles, et fleurs. Chaque partie a sa fonction, des racines qui absorbent les nutriments à la fleur qui attire les pollinisateurs. En explorant le monde végétal, on apprend aussi sur leur adaptation incroyable à des environnements variés, des déserts arides aux forêts tropicales humides.</p>";
        $corps .= "</div>";
        $corps .= "<div>";
        $corps .= "<h2>Définitions Botaniques</h2>";
        $corps .= "<p>La botanique est la science qui étudie les plantes sous toutes leurs formes et aspects. Elle couvre une gamme de sujets allant de la taxonomie, qui classe les plantes en différentes catégories, à la physiologie végétale, qui explique comment les plantes transforment la lumière en énergie via la photosynthèse. Les termes tels que angiospermes et gymnospermes classifient les plantes en fonction de la présence ou absence de graines protégées. Comprendre la structure d'une plante, de ses cellules à ses systèmes de reproduction, permet aux botanistes de déterminer ses besoins.</p>";
        $corps .= "</div>";
        $corps .= "<div>";
        $corps .= "<h2>Utilités des Plantes</h2>";
        $corps .= "<p>Les plantes ont une multitude d'utilités qui impactent de nombreux aspects de la vie humaine. Sur le plan écologique, elles sont indispensables pour l'absorption de CO2 et la production d'oxygène, jouant ainsi un rôle vital dans la lutte contre le changement climatique. Économiquement, elles sont au cœur de secteurs tels que l'agriculture, la pharmacie, et même la cosmétique, fournissant nourriture, médicaments, et autres produits dérivés. Sur le plan social, les plantes enrichissent notre environnement, contribuant au bien-être par leur présence dans les espaces verts urbains et les jardins.</p>";
        $corps .= "</div>";
        $corps .= "</div>";
        $corps .= "<div class=images>";
        $corps .= "<img src=images/parties_d'une_plante.png alt=Image2>";
        $corps .= "<img src=images/image_plante.jpg alt=Image1>";
        $corps .= "<img src=images/image3.jpg alt=Image3>";
        $corps .= "</div>";

        $zonePrincipale = $corps;
        break;

    case "apropos":
        $corps = "<div class='about-section'>";
        
        // Premier bloc
        $corps .= "<div>";
        $corps .= "<h2>À Propos du Projet :</h2>";
        $corps .= "<p>Ce site est le rendu final de la matière : TW3 Programmation en PHP. Lors de ce projet, nous devions réaliser individuellement un mini-site avec le thème de notre choix. J'ai choisi les plantes comme exemple, avec de nombreuses caractéristiques différentes. Le site contient 4 pages : accueil, affichage, recherche et à propos.</p>";
        $corps .= "<h2>Informations Personnelles :</h2>";
        $corps .= "<ul>";
        $corps .= "<li>Numéro Étudiant : 22103719</li>";
        $corps .= "<li>Nom : Huron</li>";
        $corps .= "<li>Prénom : Emilien</li>";
        $corps .= "<li>Groupe : 1A</li>";
        $corps .= "</ul>";
        $corps .= "</div>";
        
        // deuxieme bloc
        $corps .= "<div>";
        $corps .= "<h2>Points Réalisés :</h2>";
        $corps .= "<ul>";
        $corps .= "<li>Un formulaire de saisie : similaire à celui des personnes, il permet d’ajouter des plantes dans la base de données. La hauteur est forcément de type int.</li>";
        $corps .= "<li>Une page dédiée : chaque plante a une page personnelle qui permet de voir toutes les données enregistrées dans la base de données pour cette dernière.</li>";
        $corps .= "<li>Trois fonctionnalités : On peut modifier, supprimer et voir, ce qui permet de supprimer de la base de données une plante choisie pour la première, de modifier les données d’une plante pour la deuxième et d’accéder à la page personnelle avec la dernière fonctionnalité.</li>";
        $corps .= "<li>Filtrage : lors de l’affichage, on peut choisir si l'on veut les premières plantes avec l’ID le plus petit ou le plus grand, de même avec le nom, qui sera alphabétiquement.</li>";
        $corps .= "<li>L’affichage de la liste : contenant la liste de toutes les plantes de la base de données avec leur ID, leur nom et leur colonne des trois fonctionnalités.</li>";
        $corps .= "<li>Pagination : on peut choisir combien de plantes on veut afficher par page et ensuite naviguer à travers les pages de plantes.</li>";
        $corps .= "</ul>";
        $corps .= "</div>";
        
        // troiseme bloc
        $corps .= "<div>";
        $corps .= "<h2>Points Spéciaux :</h2>";
        $corps .= "<p>La page d’accueil est la case 'accueil' directement instanciée au début de l'index. Cela permet d'avoir, lorsque l'on lance la case 'accueil', qui vient remplir la zone Principale de la page.</p>";
        $corps .= "<h2>Améliorations :</h2>";
        $corps .= "<ul>";
        $corps .= "<li>Images : l'une des améliorations possibles serait l’ajout d’une image pour chaque plante.</li>";
        $corps .= "<li>Une meilleure recherche : selon un détail précis sur la plante et non toute en même temps.</li>";
        $corps .= "<li>Meilleure page personnelle : cela viendra de l’ajout de plusieurs données dans la base de données et cela permettra d’avoir une vraie page de données sur les plantes.</li>";
        $corps .= "</ul>";
        $corps .= "</div>";
        
        $corps .= "</div>";
        
        $zonePrincipale = $corps;
        break;
        
    
    case "voir":
        $idP = $_GET["idP"] ?? null;
        if ($idP !== null) {
            $connection = connecter();
    
            // Préparation de la requête SQL pour récupérer les informations de la plante
            $requete = "SELECT * FROM Plante WHERE idP = :idP";
            $stmt = $connection->prepare($requete);
            $stmt->bindParam(':idP', $idP);
    
            $stmt->execute();
            $plante = $stmt->fetch(PDO::FETCH_ASSOC);
    
            // Vérification si la plante a été trouvée
            if ($plante) {
                $corps = "<div class='plante-details'>";
                $corps .= "<h2>Détails de la plante</h2>";
                $corps .= "<p>ID: " . htmlspecialchars($plante['idP']) . "</p>";
                $corps .= "<p>Nom: " . htmlspecialchars($plante['nom']) . "</p>";
                $corps .= "<p>Nom Latin: " . htmlspecialchars($plante['nomLatin']) . "</p>";
                $corps .= "<p>Origine: " . htmlspecialchars($plante['origine']) . "</p>";
                $corps .= "<p>Hauteur: " . htmlspecialchars($plante['hauteur']) . " cm</p>";
                $corps .= "<p>Arrosage: " . htmlspecialchars($plante['arrosage']) . "</p>";
                $corps .= "<p>Remarques: " . htmlspecialchars($plante['remarques']) . "</p>";
                $corps .= "</div>";
            }    
            $stmt = null;
            $connection = null;
        }
    
        $zonePrincipale = $corps;
        break;
}

include("squelettes/fragments/frag_fin.php");
?>
