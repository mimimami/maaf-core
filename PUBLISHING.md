# MAAF Core - Publikálási Útmutató

## Composer Csomag Publikálása

### 1. Git Repository Létrehozása

```bash
cd maaf-core
git init
git add .
git commit -m "Initial commit: MAAF Core v1.0.0"
git remote add origin https://github.com/yourusername/maaf-core.git
git push -u origin main
```

### 2. Verzió Tag Létrehozása

```bash
git tag -a v1.0.0 -m "Version 1.0.0 - Initial release"
git push origin v1.0.0
```

### 3. Packagist Regisztráció

1. Menj a https://packagist.org/ oldalra
2. Regisztrálj vagy jelentkezz be
3. Kattints a "Submit" gombra
4. Add meg a repository URL-t: `https://github.com/yourusername/maaf-core`
5. Várj a validációra

### 4. GitHub Hook Beállítása (Automatikus Frissítés)

**Miért kell?**
- Amikor push-olsz a GitHub-ra, automatikusan frissül a Packagist
- Nem kell manuálisan frissíteni a Packagist-en

**Hogyan?**

#### Lépés 1: Packagist API Token Létrehozása

1. Menj a https://packagist.org/ oldalra
2. Kattints a profilodra (jobb felső sarok)
3. Kattints **"Show API Token"**
4. Másold ki a token-t (pl. `xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`)

#### Lépés 2: GitHub Webhook Beállítása

1. Menj a GitHub repository-hoz: https://github.com/Tamas-Bloch/maaf-core
2. Kattints **Settings** (felső menü)
3. Kattints **Webhooks** (bal oldali menü)
4. Kattints **Add webhook**
5. Töltsd ki:
   - **Payload URL:** `https://packagist.org/api/github?username=Tamas-Bloch`
   - **Content type:** `application/json`
   - **Secret:** (hagyd üresen, vagy add meg a Packagist API token-t)
   - **Which events:** Válaszd ki: **Just the push event**
6. Kattints **Add webhook**

#### Lépés 3: Packagist Webhook Beállítása (Alternatíva)

Ha a fenti nem működik, használd a Packagist webhook URL-t:

1. Packagist-en: https://packagist.org/packages/maaf/core
2. Kattints **"Settings"** vagy **"Update"**
3. Másold ki a **"GitHub Hook URL"**-t (pl. `https://packagist.org/api/github?username=Tamas-Bloch&repository=maaf-core`)
4. GitHub-on: **Settings** > **Webhooks** > **Add webhook**
5. **Payload URL:** Add meg a másolt URL-t
6. **Content type:** `application/json`
7. **Secret:** (hagyd üresen)
8. **Which events:** **Just the push event**
9. Kattints **Add webhook**

#### Lépés 4: Tesztelés

```bash
# Változtass valamit a kódban
echo "# Test" >> README.md
git add README.md
git commit -m "Test webhook"
git push origin main
```

A Packagist automatikusan frissül néhány másodpercen belül.

### 5. Telepítés

```bash
composer require maaf/core
```

## VS Code Extension Készítése

### 1. Projekt Létrehozása

```bash
mkdir maaf-core-vscode-extension
cd maaf-core-vscode-extension
npm init -y
```

### 2. package.json

```json
{
    "name": "maaf-core-snippets",
    "displayName": "MAAF Core Snippets",
    "description": "Code snippets for MAAF Core framework",
    "version": "1.0.0",
    "publisher": "your-publisher-name",
    "engines": {
        "vscode": "^1.60.0"
    },
    "categories": ["Snippets"],
    "contributes": {
        "snippets": [
            {
                "language": "php",
                "path": "./snippets/php.json"
            }
        ]
    }
}
```

### 3. Snippets Másolása

```bash
mkdir snippets
cp ../maaf-core/snippets/vscode.json ./snippets/php.json
```

### 4. Publikálás

```bash
npm install -g @vscode/vsce
vsce package
vsce publish
```

## PhpStorm Plugin (Opcionális)

Lásd: `docs/maaf-core-package-publishing.md`
