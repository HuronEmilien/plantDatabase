<?php
require_once('Lib.php');

class Recherche {
    private $Tab_Plante = [];

    public function ajouterPlante(GestionPlantes $plante) {
        $this->$Tab_Plante[] = $plante;
    }

    public static function charger_base(): array {
        $connection = connecter();
        $plante = [];
        // Requête pour récupérer toutes les plantes depuis la base de données
        $plante = "SELECT * FROM Plante";
        try {
            // Exécution de la requête
            $query = $connection->query($requete);
            $query->setFetchMode(PDO::FETCH_ASSOC);
            
            // Parcours des résultats et création d'objets plante
            while ($enregistrement = $query->fetch()) {
                $plante = new Plante($enregistrement['idP'], $enregistrement['nom'], $enregistrement['nomLatin'], $enregistrement['hauteur'], $enregistrement['origine'], $enregistrement['arrosage'], $enregistrement['remarques'], $connection);
                $plante[] = $plante;
            }
        } catch (PDOException $e) {
            error_log("Erreur SQL : " . $e->getMessage()); // Stocke l'erreur dans les logs
            die("Une erreur est survenue, merci de réessayer plus tard.");
        } finally {
            $query = null; // Libération des ressources
            $connection = null;
        }
        return $plante;
    }

    public static function rechercherDansBase(string $choix): array {
        $connection = connecter();
        $plantes = [];
        // requete pour saoir si l'objet dans la base est pareil que celui donné
        $requete = "SELECT * FROM Plante WHERE nom LIKE :choix OR nomLatin LIKE :choix OR origine LIKE :choix OR hauteur LIKE :choix OR arrosage LIKE :choix OR remarques LIKE :choix";
    
        try {
            $stmt = $connection->prepare($requete);
            // après l'utilisation de LIKE je onfigcure le ter mde recherche
            $rentre = '%' . $choix . '%';
            // apres je fait la Liaison du paramètre
            $stmt->bindParam(':choix', $rentre);
            $stmt->execute();
            // objets GestionPlantes pour chaque résultat trouvé
            while ($enregistrement = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $plante = new GestionPlantes($enregistrement['idP'], $enregistrement['nom'], $enregistrement['nomLatin'], $enregistrement['origine'], $enregistrement['hauteur'], $enregistrement['arrosage'], $enregistrement['remarques'], $connection);
                $plantes[] = $plante;
            }
        } catch (PDOException $e) {
            error_log("Erreur SQL : " . $e->getMessage()); // Stocke l'erreur dans les logs
            die("Une erreur est survenue, merci de réessayer plus tard.");
        } finally {
            $stmt = null;
            $connection = null;
        }
        return $plantes; 
    }
    
}
?>
