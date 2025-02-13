<?php
require_once('../../../private/config.php');

class GestionPlantes {
    private $idP;
    private $nom;
    private $nomLatin;
    private $origine;
    private $hauteur;
    private $arrosage;
    private $remarques;
    private $connexion;

    public function __construct($idP, $nom, $nomLatin, $origine, $hauteur, $arrosage, $remarques, $connexion) {
        $this->idP = $idP;
        $this->nom = $nom;
        $this->nomLatin = $nomLatin;
        $this->origine = $origine;
        $this->hauteur = $hauteur;
        $this->arrosage = $arrosage;
        $this->remarques = $remarques;
        $this->connexion = $connexion;
    }
    

    public function __toString() {
        return "ID" . $this->idP . "Nom: " . $this->nom . ", nomLatin: " . $this->nomLatin . "origine: " . $this->origine . ", hauteur: " . $this->hauteur . ", arrosage: " . $this->arrosage . ", remarques: " . $this->remarques;
    }

    public function setIdP($idP) {
        $this->idP = $idP;
    }

    public function getIdP() {
        return $this->idP;
    }

    public function getNom() {
        return $this->nom;
    }

    public function getNomLatin() {
        return $this->nomLatin;
    }

    public function getOrigine() {
        return $this->origine;
    }

    public function getHauteur() {
        return $this->hauteur;
    }

    public function getArrosage() {
        return $this->arrosage;
    }

    public function getRemarques() {
        return $this->remarques;
    }

    public function enregistrer(): bool {
        $erreurs = $this->validerDonnees();
        if (!empty($erreurs)) {
            echo "Erreur lors de l'enregistrement : " . implode(" ", $erreurs);
            return false;
        }
    
        // Préparation de la requête SQL
        $requete = "INSERT INTO Plante (nom, nomLatin, origine, hauteur, arrosage, remarques) VALUES (:nom, :nomLatin, :origine, :hauteur, :arrosage, :remarques)";
        $stmt = $this->connexion->prepare($requete);
    
        // Liaison des paramètres
        $stmt->bindParam(':nom', $this->nom);
        $stmt->bindParam(':nomLatin', $this->nomLatin);
        $stmt->bindParam(':origine', $this->origine);
        $stmt->bindParam(':hauteur', $this->hauteur, PDO::PARAM_INT);
        $stmt->bindParam(':arrosage', $this->arrosage);
        $stmt->bindParam(':remarques', $this->remarques);
    
        // Exécution de la requête
        if ($stmt->execute()) {
            $this->idP = $this->connexion->lastInsertId();
            return true;
        } else {
            // test pour savoir si echec
            echo "Échec de l'enregistrement : " . implode(" ", $stmt->errorInfo());
            return false;
        }
    }
    
    private function validerDonnees(): array {
        $erreurs = [];
        
        // Validation de la hauteur
        if (!is_numeric($this->hauteur) || $this->hauteur < 0) {
            $erreurs[] = "Hauteur invalide. La hauteur doit être un nombre positif.";
        }
    
        // Validation des champs obligatoires
        if (empty($this->nom)) {
            $erreurs[] = "Le nom est requis.";
        }
        if (empty($this->nomLatin)) {
            $erreurs[] = "Le nom latin est requis.";
        }
        if (empty($this->origine)) {
            $erreurs[] = "L'origine est requise.";
        }
        if (empty($this->arrosage)) {
            $erreurs[] = "L'arrosage est requis.";
        }
        if (empty($this->remarques)) {
            $erreurs[] = "L'arrosage est requis.";
        }
        
    
        return $erreurs;
    }
    
    public function modifier(string $nom, string $nomLatin, string $origine, int $hauteur, string $arrosage, string $remarques): bool {
        $requete = "UPDATE Plante SET nom = :nom, nomLatin = :nomLatin, origine = :origine, hauteur = :hauteur, arrosage = :arrosage, remarques = :remarques WHERE idP = :idP";
        $stmt = $this->connexion->prepare($requete);
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':nomLatin', $nomLatin);
        $stmt->bindParam(':origine', $origine);
        $stmt->bindParam(':hauteur', $hauteur);
        $stmt->bindParam(':arrosage', $arrosage);
        $stmt->bindParam(':remarques', $remarques);
        $stmt->bindParam(':idP', $this->idP);

        $resultat = $stmt->execute();
        return $resultat;
    }

    public function supprimer(): bool {
        $requete = "DELETE FROM Plante WHERE idP = :idP";
        $stmt = $this->connexion->prepare($requete);
        $stmt->bindParam(':idP', $this->idP);

        $resultat = $stmt->execute();
        return $resultat;
    }


    public static function charger_base(): array {
        $connection = connecter();
        $plantes = []; 
        $requete = "SELECT * FROM Plante";
        try {
            $query = $connection->query($requete);
            $query->setFetchMode(PDO::FETCH_ASSOC);
    
            while ($enregistrement = $query->fetch()) {
                $plante = new GestionPlantes($enregistrement['idP'], $enregistrement['nom'], $enregistrement['nomLatin'], $enregistrement['origine'], $enregistrement['hauteur'], $enregistrement['arrosage'], $enregistrement['remarques'], $connection);
                $plantes[] = $plante;
            }
        } catch (PDOException $e) {
            error_log("Erreur SQL : " . $e->getMessage()); // Stocke l'erreur dans les logs
            die("Une erreur est survenue, merci de réessayer plus tard.");            
        } finally {
            $query = null;
            $connection = null;
        }
        return $plantes; 
    }
    
    
}

function connecter(): ?PDO {
    global $connexion;
    // Options de connexion
    $options = [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ];
    try {
        $dsn = DB_HOST . DB_NAME;
        $connexion = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $connexion;
    } catch (PDOException $e) {
        error_log("Erreur SQL : " . $e->getMessage()); // Stocke l'erreur dans les logs
        die("Une erreur est survenue, merci de réessayer plus tard.");        
        //exit(); // Arrêter l'exécution du script en cas d'échec de connexion
        return null;
    }
}

$connexion = connecter();
?>
