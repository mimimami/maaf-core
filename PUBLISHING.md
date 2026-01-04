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

### 3. Packagist Regisztráció (Opcionális)

1. Menj a https://packagist.org/ oldalra
2. Regisztrálj vagy jelentkezz be
3. Kattints a "Submit" gombra
4. Add meg a repository URL-t: `https://github.com/yourusername/maaf-core`
5. Várj a validációra

### 4. Telepítés

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

