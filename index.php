<?php
/**
 * Point d'entrée principal - Bienetre Pharma
 * Seul fichier accessible publiquement
 */

// Démarrage de la session et de l'application
session_start();

// Chargement de la configuration
require_once '../app/Config/App.php';

// Chargement des helpers essentiels
require_once '../app/Helpers/Security.php';
require_once '../app/Helpers/Session.php';
require_once '../app/Helpers/Validator.php';

// Protection contre les attaques basiques
Security::basicProtection();

// Initialisation de l'application
try {
    $app = new App();
    $app->run();
} catch (Exception $e) {
    // En production, ne pas révéler les erreurs
    if ($_ENV['DEBUG'] === 'true') {
        die('Erreur: ' . $e->getMessage());
    } else {
        die('Une erreur est survenue. Veuillez réessayer plus tard.');
    }
}