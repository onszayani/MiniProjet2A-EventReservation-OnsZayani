# MiniProjet2A-EventReservation-OnsZayani
#  MiniEvent
### Application Web de Gestion de Réservations d'Événements
> A2-GL · ISSAT Sousse · 2025-2026

---

## 🚀 Technologies

![Symfony](https://img.shields.io/badge/Symfony-7.0-black?logo=symfony)
![JWT](https://img.shields.io/badge/JWT-LexikBundle-orange)
![WebAuthn](https://img.shields.io/badge/Passkeys-WebAuthn%2FFIDO2-blue)
![Twig](https://img.shields.io/badge/Frontend-Twig-green)
![MySQL](https://img.shields.io/badge/Database-MySQL-lightblue)

---

## 📋 Description

MiniEvent est une application web complète de gestion d'événements développée avec **Symfony 7**.
Elle permet aux utilisateurs de découvrir des événements, de réserver des places
et de gérer leurs réservations — le tout sécurisé par une authentification moderne
sans mot de passe via **Passkeys (WebAuthn)** et des tokens **JWT**.

---

## ✨ Fonctionnalités

- 🔐 **Authentification sans mot de passe** via Passkeys (empreinte, FaceID, PIN)
- 🎫 **Réservation d'événements** avec vérification des places disponibles
- 📋 **Gestion des réservations** (confirmer, annuler)
- 🛡️ **Dashboard Admin** pour gérer les événements et réservations
- 🔄 **JWT + Refresh Tokens** pour des sessions sécurisées et stateless
- 📱 **Interface responsive** avec Twig templates

---

## 🗂️ Structure du projet
```
app-reservations/
├── config/
│   ├── jwt/                          # Clés RSA 4096 bits
│   └── packages/
│       ├── lexik_jwt_authentication.yaml
│       ├── gesdinet_jwt_refresh_token.yaml
│       ├── webauthn.yaml
│       └── security.yaml
├── migrations/
├── public/
│   ├── css
|    |    ├── auth.js                   # WebAuthn + JWT
│   │   ├── api.js                    # authFetch + API calls
│   │   ├── app.js                    # Logique métier
│   ├── js/
│   │   ├── auth.js                   # WebAuthn + JWT
│   │   ├── api.js                    # authFetch + API calls
│   │   ├── app.js                    # Logique métier
│   └── images/
├── src/
│   ├── Controller/
│   │   ├── PageController.php        # Routes Twig
│   │   ├── AuthApiController.php     # /api/auth/*
│   │   └── Api/
│   │       ├── EventController.php
│   │       └── ReservationController.php
│   ├── Entity/
│   │   ├── User.php
│   │   ├── WebauthnCredential.php
│   │   ├── Event.php
│   │   └── Reservation.php
│   ├── Repository/
│   ├── Service/
│   │   └── PasskeyAuthService.php
│   └── DataFixtures/
│       └── AppFixtures.php
└── templates/
    ├── base.html.twig                # Layout global
    ├── events/
    │   └── index.html.twig           # Liste événements
    ├── auth/
    │   └── login.html.twig           # Connexion / Inscription
    └── reservations/
        └── my_res.html.twig          # Mes réservations
```

---

## ⚙️ Installation

### Prérequis

- PHP 8.1+
- Composer 2.x
- MySQL 8+ ou PostgreSQL 14+
- Symfony CLI 
- Navigateur supportant WebAuthn (Chrome 108+, Firefox 113+, Safari 16+)

### 1. Cloner le projet
```bash
git clone https://github.com/username/app-reservations.git
cd app-reservations
```

### 2. Installer les dépendances
```bash
composer install
```

### 3. Configurer l'environnement
```bash
cp .env .env.local
```

Modifier `.env.local` :
```env
# Base de données
DATABASE_URL="mysql://user:password@127.0.0.1:3306/app_reservation"

# JWT
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=votre_passphrase_secrete
JWT_TOKEN_TTL=3600

# WebAuthn
APP_DOMAIN=localhost
WEBAUTHN_RP_NAME="MiniEvent"
```

### 4. Générer les clés JWT
```bash
mkdir -p config/jwt

openssl genpkey \
  -out config/jwt/private.pem \
  -aes256 \
  -algorithm rsa \
  -pkeyopt rsa_keygen_bits:4096

openssl pkey \
  -in config/jwt/private.pem \
  -out config/jwt/public.pem \
  -pubout

chmod 600 config/jwt/private.pem config/jwt/public.pem
```

### 5. Base de données
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 6. Données de test 
```bash
php bin/console doctrine:fixtures:load
```

Crée : **1 admin + 5 utilisateurs + 9 événements + 20 réservations**

### 7. Lancer le serveur
```bash
symfony server:start
# ou
php -S localhost:8000 -t public/
```

➡️ **http://localhost:8000**

---

## 🌿 Organisation Git

### Branches

| Branche | Rôle |
|---------|------|
| `main` | Production stable — tags de release |
| `dev` | Intégration continue |
| `feature/auth` | JWT + Passkeys WebAuthn |
| `feature/events` | Événements  |
| `feature/reservations` | Réservations |
| `feature/templated` | Templates Twig + JS |

### Workflow
```bash
# Créer une feature
git checkout develop && git pull origin develop
git checkout -b feature/nom-feature

# Développer + committer
git add .
git commit -m " description"

# Merger dans develop
git checkout develop
git merge --no-ff feature/nom-feature -m "Merge feature/nom-feature"
git push origin dev
```



## 🔌 API Endpoints

### Authentification `(public)`

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `POST` | `/api/auth/register/options` | Options WebAuthn inscription |
| `POST` | `/api/auth/register/verify` | Valider passkey → JWT |
| `POST` | `/api/auth/login/options` | Options WebAuthn connexion |
| `POST` | `/api/auth/login/verify` | Vérifier passkey → JWT |
| `GET` | `/api/auth/me` | Profil utilisateur connecté |
| `POST` | `/api/token/refresh` | Renouveler le JWT |

### Événements `(public)`

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `GET` | `/api/events` | Liste des événements |
| `GET` | `/api/events/{id}` | Détail d'un événement |
| `POST` | `/api/events` | Créer `[ADMIN]` |
| `PUT` | `/api/events/{id}` | Modifier `[ADMIN]` |
| `DELETE` | `/api/events/{id}` | Supprimer `[ADMIN]` |

### Réservations `(JWT requis)`

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `POST` | `/api/reservations` | Créer une réservation |
| `GET` | `/api/reservations/my` | Mes réservations |
| `POST` | `/api/reservations/{id}/cancel` | Annuler |
| `POST` | `/api/reservations/{id}/confirm` | Confirmer `[ADMIN]` |

---

## 🗄️ Modèle de données
```
User
 ├── id (int, auto-increment)
 ├── email (unique)
 ├── displayName
 ├── roles
 ├── password (nullable — auth par passkey)
 ├── WebauthnCredential[] ──→ clé publique passkey
 └── Reservation[]

Event
 ├── id
 ├── title
 ├── description
 ├── dateDeb / dateFin
 ├── location
 ├── seats
 ├── image
 └── Reservation[]

Reservation
 ├── id
 ├── user → User
 ├── event → Event
 ├── status (pending | accepted | rejected)
 └── createdAt
```


---

## 🔒 Sécurité

| Point | Détail |
|-------|--------|
| **JWT** | RSA 4096 bits, expiration 1h |
| **Refresh Token** | Durée 30 jours, usage unique |
| **Passkeys** | Cryptographie asymétrique, anti-phishing |
| **CORS** | Origines restreintes (NelmioCorsBundle) |
| **HTTPS** | Obligatoire en production (WebAuthn l'exige) |
| **Passwords** | Nullable — authentification 100% par passkey |

---

## 👤 Auteur

**Ons Zayani** — A2-GL · ISSAT Sousse · 2025-2026

