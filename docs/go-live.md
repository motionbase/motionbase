# Go-Live auf Hostpoint

Deploy wie bei Offyce: Jeder Push auf `main` startet
[deploy.yml](../.github/workflows/deploy.yml). GitHub Actions baut die Assets, lädt die Dateien per scp nach
`~/www/motionbase.ch` und führt dort per ssh aus:

```
composer install --no-dev --optimize-autoloader
php artisan passport:keys        # nur, wenn noch keine da sind
openssl genrsa  …                # LTI-Schlüsselpaar, nur wenn noch keins da ist
php artisan storage:link
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Auf dem Server bleiben unangetastet: `.env` und `storage/` – dort liegen die Mediathek, die interaktiven
Grafiken, die Passport-Schlüssel (MCP-Zugänge) und das LTI-Schlüsselpaar (Moodle).

Die Schritte 1–6 sind **einmalig** und müssen vor dem ersten Push erledigt sein – sonst bricht der erste Deploy
bei `migrate` ab (keine `.env`). Dann einfach den Workflow nach Schritt 5 erneut starten.

| | |
|---|---|
| Server | `sl2679.web.hostpoint.ch` |
| Account | `uhenotav` |
| Verzeichnis | `~/www/motionbase.ch` |
| PHP | 8.3 (`/usr/local/php83/bin/`) |

---

## 1. PHP 8.3 auf dem Server prüfen

```
ssh uhenotav@sl2679.web.hostpoint.ch
ls /usr/local/php83/bin/
```

Dort müssen `php` und `composer` liegen. `public/.htaccess` wählt für die Website mit `Use php-fpm php83`
dieselbe Version. Soll es eine andere sein, beide Stellen und `deploy.yml` (Schritt «Execute Remote Commands»)
gemeinsam ändern.

## 2. Website anlegen (Hostpoint Control Panel)

- **Websites → neue Website** `motionbase.ch`
- **Verzeichnis:** `www/motionbase.ch/public` – wichtig ist `/public`, sonst liegen `.env` und der Code im Web.
- **PHP-Version:** 8.3
- **SSL:** Let's-Encrypt-Zertifikat aktivieren und «HTTP auf HTTPS umleiten» einschalten.
- **DNS:** `motionbase.ch` zeigt noch auf Metanet. Erst umstellen, wenn Schritt 7 (Daten) erledigt ist.

## 3. Datenbank anlegen

**Datenbanken → neue MariaDB-Datenbank** mit eigenem Benutzer. Host, Datenbankname, Benutzer und Passwort
notieren.

## 4. `.env` auf dem Server

```
mkdir -p ~/www/motionbase.ch
nano ~/www/motionbase.ch/.env
```

Inhalt:

```
APP_NAME=MotionBase
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://motionbase.ch

APP_LOCALE=de
APP_FALLBACK_LOCALE=de
APP_FAKER_LOCALE=de_CH

LOG_CHANNEL=daily
LOG_LEVEL=warning

DB_CONNECTION=mariadb
DB_HOST=
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true

FILESYSTEM_DISK=local
CACHE_STORE=database
QUEUE_CONNECTION=sync

# KI-Assistent und Chat
OPENAI_API_KEY=

# Moodle über LTI 1.3
LTI_KEY_ID=motionbase-1
LTI_SESSION_DURATION=120
LTI_JWKS_CACHE_DURATION=60
LTI_DEBUG=false

MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS="noreply@motionbase.ch"
MAIL_FROM_NAME="${APP_NAME}"
```

**`APP_KEY` und `LTI_KEY_ID` vom alten Server übernehmen**, nicht neu erzeugen:

```
ssh -p 2121 motionbase@motionbase.ch 'grep -E "^(APP_KEY|LTI_KEY_ID|OPENAI_API_KEY|MAIL_)" ~/motionbase/.env'
```

⚠️ Ein neuer `APP_KEY` macht alle verschlüsselten Werte unlesbar, unter anderem die Zwei-Faktor-Geheimnisse.
Ein neuer `LTI_KEY_ID` passt nicht mehr zu dem, was die Moodles für die Schlüssel abgelegt haben. Beides
zusätzlich im Passwortmanager ablegen.

`QUEUE_CONNECTION=sync`: Auf Hostpoint läuft kein Queue-Worker.

## 5. GitHub-Secrets

GitHub → Repository **motionbase/motionbase** → **Settings → Secrets and variables → Actions**. Drei Secrets,
Name exakt so – die bisherigen Werte gehören zu Metanet und müssen **ersetzt** werden:

| Secret     | Wert                                      |
| ---------- | ----------------------------------------- |
| `SSH_HOST` | `sl2679.web.hostpoint.ch`                 |
| `SSH_USER` | `uhenotav`                                |
| `SSH_KEY`  | ganzer Inhalt von `~/.ssh/github-actions` |

```
pbcopy < ~/.ssh/github-actions
```

Der öffentliche Teil liegt auf dem Server bereits. Test vom Mac aus – muss ohne Passwortabfrage durchlaufen:

```
ssh -i ~/.ssh/github-actions uhenotav@sl2679.web.hostpoint.ch 'ls /usr/local/php83/bin/'
```

## 6. Cronjob für die Aufräumarbeiten

`routes/console.php` lässt `lti:prune` laufen: Es löscht abgelaufene LTI-Sitzungen und Nonces. Ohne Cron wachsen
diese Tabellen endlos.

**Control Panel → Cronjobs**, jede Minute:

```
/usr/local/php83/bin/php /home/uhenotav/www/motionbase.ch/artisan schedule:run >> /dev/null 2>&1
```

## 7. Daten von Metanet übernehmen

Erst nach dem ersten erfolgreichen Deploy, kurz vor der DNS-Umstellung – dann sind Datenbank und Mediathek aktuell.

```
# Datenbank
ssh -p 2121 motionbase@motionbase.ch 'cd ~/motionbase && /opt/php83/bin/php artisan db:show --json'   # Zugangsdaten prüfen
ssh -p 2121 motionbase@motionbase.ch 'mysqldump --single-transaction -u BENUTZER -p DATENBANK' > motionbase.sql
mysql -h HOSTPOINT_DB_HOST -u HOSTPOINT_BENUTZER -p HOSTPOINT_DATENBANK < motionbase.sql

# Mediathek, interaktive Grafiken, Schlüssel
rsync -avz -e 'ssh -p 2121' motionbase@motionbase.ch:~/motionbase/storage/app/ \
  -e 'ssh -i ~/.ssh/github-actions' uhenotav@sl2679.web.hostpoint.ch:~/www/motionbase.ch/storage/app/
rsync -avz -e 'ssh -p 2121' motionbase@motionbase.ch:~/motionbase/storage/lti/ \
  uhenotav@sl2679.web.hostpoint.ch:~/www/motionbase.ch/storage/lti/
```

Die beiden `rsync` gehen von Server zu Server nicht direkt; am einfachsten in zwei Schritten über den Mac
(`rsync` herunter, `rsync` hinauf).

Werden die Schlüssel aus `storage/lti` und `storage/oauth-*.key` mitgenommen, bleiben die Moodle-Verbindungen
und die MCP-Zugänge bestehen. Fehlen sie, erzeugt der Deploy neue – dann müssen die MCP-Clients neu angemeldet
werden.

## 8. Umstellen

1. DNS von `motionbase.ch` auf Hostpoint zeigen lassen.
2. `https://motionbase.ch/admin/lti` öffnen: Die Tool-URLs müssen auf `https://motionbase.ch` zeigen.
3. Eine Lektion, eine interaktive Grafik und einen Moodle-Start prüfen.
4. Den alten Server erst abschalten, wenn alles läuft.

## Was es nicht mehr gibt

Das Deployment auf die Beta-Umgebung bei Metanet (`deploy-beta.yml`, Branch `beta`) ist entfernt. Es zeigte auf
`~/motionbase-beta` und auf `/opt/php83` und würde mit den neuen Secrets ins Leere laufen. Soll es eine Beta auf
Hostpoint geben, braucht sie eine eigene Website (`beta.motionbase.ch`), eine eigene Datenbank und eine eigene
`.env`; der Workflow dafür ist eine Kopie von `deploy.yml` mit anderem Zielverzeichnis.
