# Alys — Calendrier de traitement

Application mobile **Android** pour suivre le calendrier de traitement d'Alys.  
Usage familial uniquement · Données stockées localement · Aucune connexion réseau requise.

---

## Fonctionnalités

- **Calendrier** — visualisation des événements de traitement jour par jour
- **Traitements** — création, modification, archivage des traitements (quotidiens, hebdomadaires, cycliques, actes médicaux)
- **Historique des posologies** — traçabilité des changements de dose avec date d'entrée en vigueur
- **Multi-profils** — plusieurs profils de suivi sur le même appareil
- **Dashboard** — widgets configurables par traitement
- **Export / Import chiffré** — sauvegarde au format `.alys` (chiffrement ECIES P-256)
- **Transfert de clé par QR code** — partage sécurisé entre appareils

## Stack technique

| Couche | Technologie |
|---|---|
| Runtime mobile | [NativePHP Mobile](https://nativephp.com/mobile) (APK Android, PHP embarqué) |
| Framework | Laravel 12 · PHP 8.4 |
| Base de données | SQLite (embarquée, locale) |
| UI réactive | Livewire 3 · Alpine.js |
| Style | Tailwind CSS · Preline UI |
| Chiffrement | OpenSSL · ECIES P-256 (courbe `prime256v1`) |

## Prérequis

- PHP 8.4
- Composer
- Node.js / npm
- Android SDK (pour le build APK)

## Installation

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

php artisan migrate
```

## Développement

```bash
# Serveur de développement
php artisan native:serve

# Assets front-end
npm run dev
```

## Build Android

```bash
php artisan native:build android
```

## Tests

```bash
php artisan test
```

## Format `.alys`

Les exports sont chiffrés en **ECIES P-256** : chaque appareil génère une paire de clés stockée dans le `SecureStorage` natif. L'export est chiffré avec la clé publique du destinataire. Le transfert de clé publique s'effectue via QR code dans l'écran **Transfert de clé**.

## Licence

Distribué sous licence [MIT](LICENSE).
