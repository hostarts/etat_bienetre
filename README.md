# Bienetre Pharma - Système de Gestion Commerciale

## Description
Application web de gestion commerciale pour Bienetre Pharma, permettant la gestion des clients, transactions, factures et retours.

## Fonctionnalités
- ✅ Gestion des clients (CRUD)
- ✅ Gestion des transactions (factures et retours)
- ✅ Système de remises mensuelles
- ✅ Tableau de bord avec statistiques
- ✅ Export PDF et CSV
- ✅ Interface responsive
- ✅ Impression optimisée
- ✅ Sécurité renforcée (CSRF, XSS, SQL Injection)

## Technologies
- **Backend**: PHP 7.4+ (MVC Architecture)
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Base de données**: MySQL 5.7+
- **Serveur web**: Apache 2.4+ avec mod_rewrite

## Installation

### 1. Prérequis
- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Apache avec mod_rewrite activé
- Extensions PHP: PDO, PDO_MySQL, JSON, mbstring, OpenSSL

### 2. Configuration de la base de données
```bash
mysql -u root -p < database.sql
```

### 3. Configuration de l'environnement
```bash
cp .env.example .env
# Éditer .env avec vos paramètres
```

### 4. Installation automatique
```bash
php setup.php
```

### 5. Configuration du serveur web
Pointez le document root vers le dossier `public/`

## Structure du projet
```
bienetre-pharma/
├── public/                 # Point d'entrée web
├── app/
│   ├── Controllers/       # Contrôleurs MVC
│   ├── Models/           # Modèles de données
│   ├── Views/            # Vues et templates
│   ├── Config/           # Configuration
│   ├── Helpers/          # Classes utilitaires
│   └── Middleware/       # Middleware de sécurité
├── storage/
│   ├── logs/            # Fichiers de log
│   └── uploads/         # Fichiers uploadés
└── tests/               # Tests unitaires
```

## Utilisation

### Accès à l'application
- URL: `http://votre-domaine.com/`
- Interface responsive adaptée mobile/desktop

### Gestion des clients
1. Accéder à "Gestion Clients"
2. Ajouter/modifier/supprimer des clients
3. Consulter les statistiques par client

### Gestion des transactions
1. Sélectionner un client
2. Choisir un mois
3. Ajouter factures et retours
4. Appliquer des remises
5. Imprimer ou exporter

## API

### Endpoints principaux
- `GET /clients` - Liste des clients
- `POST /clients` - Créer un client
- `GET /clients/{id}/months/{month}` - Transactions du mois
- `POST /clients/{id}/months/{month}/invoices` - Ajouter facture
- `POST /clients/{id}/months/{month}/returns` - Ajouter retour

## Sécurité
- Protection CSRF sur tous les formulaires
- Validation et sanitisation des données
- Prévention des injections SQL
- Limitation du taux de requêtes
- Logging des événements de sécurité

## Backup
```bash
# Sauvegarde de la base de données
mysqldump -u user -p bienetre_pharma > backup_$(date +%Y%m%d_%H%M%S).sql

# Sauvegarde des fichiers
tar -czf backup_files_$(date +%Y%m%d_%H%M%S).tar.gz storage/ public/uploads/
```

## Maintenance
- Logs disponibles dans `storage/logs/`
- Nettoyage périodique des anciens logs
- Mise à jour régulière des dépendances

## Support
Pour le support technique, consulter les logs d'erreur et la documentation.

## Licence
Propriétaire - Bienetre Pharma
