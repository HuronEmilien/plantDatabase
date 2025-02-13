DROP TABLE IF EXISTS `Plante`;
CREATE TABLE Plante (
  idP INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
  nom VARCHAR(100) NOT NULL,
  nomLatin VARCHAR(100) NOT NULL,
  origine VARCHAR(100) NOT NULL,
  hauteur INT NOT NULL,
  arrosage VARCHAR(50) NOT NULL,
  remarques TEXT

);

INSERT INTO `Plante` (`idP`, `nom`, `nomLatin`, `origine`, `hauteur`, `arrosage`, `remarques`) VALUES
(1, 'Rose', 'Rosa', 'Europe, Asie', 120, 'Modéré', 'Taille annuelle nécessaire'),
(2, 'Lavande', 'Lavandula angustifolia', 'Méditerranée', 60, 'Faible', 'Parfum agréable, attire les abeilles'),
(3, 'Tulipe', 'Tulipa', 'Asie', 30, 'Modéré', 'Multiplication par bulbes'),
(4, 'Orchidée', 'Orchidaceae', 'Diverses régions tropicales', 50, 'Élevé', 'Nécessite une humidité élevée');

