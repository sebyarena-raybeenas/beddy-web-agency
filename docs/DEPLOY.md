# Guida al deploy – Beddy Web Agency

Questa guida porta l'applicazione sul server aziendale (stesso server che ospita i siti dei clienti). Tutti i valori tra `<...>` sono segnaposto da sostituire.

## 1. Requisiti del server

- PHP 8.4 (CLI e PHP-FPM/Apache) con le estensioni: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `intl`, `json`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `zip`, `gd` (o `imagick`)
- MySQL 8 / MariaDB 10.6+
- Composer 2
- Node.js 20+ e npm (solo per la build degli asset; si può fare in locale, vedi punto 3)
- Accesso SSH, cron, e un server SMTP aziendale per l'invio delle email
- Un virtual host con HTTPS attivo, con document root su `<percorso-app>/public`

## 2. Scaricare il codice

```bash
cd <percorso-genitore>
git clone <url-repository> beddy-web-agency
cd beddy-web-agency
git checkout v1.0        # usare sempre un tag, mai un branch di lavoro
```

## 3. Dipendenze e asset

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

Se sul server non c'è Node.js, esegui `npm ci && npm run build` sul tuo PC e copia la cartella `public/build` sul server (stessa posizione).

## 4. Database

Crea un database vuoto e un utente dedicato:

```sql
CREATE DATABASE beddy_web_agency CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'beddy'@'localhost' IDENTIFIED BY '<password-database>';
GRANT ALL PRIVILEGES ON beddy_web_agency.* TO 'beddy'@'localhost';
```

## 5. Configurazione `.env`

```bash
cp .env.example .env
php artisan key:generate
```

Modifica `.env` con questi valori:

```dotenv
APP_NAME="Beddy Web Agency"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<dominio-gestionale>
APP_TIMEZONE=Europe/Rome
APP_LOCALE=it

LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=beddy_web_agency
DB_USERNAME=beddy
DB_PASSWORD=<password-database>

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

MAIL_MAILER=smtp
MAIL_HOST=<host-smtp-aziendale>
MAIL_PORT=587
MAIL_USERNAME=<utente-smtp>
MAIL_PASSWORD=<password-smtp>
MAIL_FROM_ADDRESS=<mittente@dominio-aziendale>
MAIL_FROM_NAME="${APP_NAME}"
```

Note:

- `APP_DEBUG=false` e `APP_ENV=production` sono obbligatori: con `local` i seeder di demo creerebbero utenti con password nota.
- `APP_URL` deve essere l'indirizzo HTTPS reale: viene usato nei link delle email di invito.
- Per SMTP su porta 465 (TLS implicito) imposta `MAIL_SCHEME=smtps`; su 587 lascia il valore predefinito.
- Gli allegati sono salvati sul disco privato `local` (`storage/app/private`), mai raggiungibili direttamente dal web. Non serve `storage:link`.

## 6. Migrazioni e dati iniziali

```bash
php artisan migrate --force
php artisan db:seed --class=TemplateSeeder --force
```

`TemplateSeeder` carica i modelli di requisiti e la checklist «Messa online». **Non** lanciare `db:seed` senza `--class`: in produzione non serve e non va ripetuto.

## 7. Permessi

L'utente del web server (es. `www-data`) deve poter scrivere in `storage` e `bootstrap/cache`:

```bash
sudo chown -R <utente-deploy>:www-data storage bootstrap/cache
sudo chmod -R ug+rwX storage bootstrap/cache
```

## 8. Ottimizzazione

```bash
php artisan filament:upgrade      # pubblica gli asset di Filament (già eseguito da composer)
php artisan optimize              # config:cache, route:cache, view:cache, event:cache
```

Dopo ogni modifica al `.env` rilancia `php artisan optimize:clear && php artisan optimize`.

## 9. Scheduler (promemoria giornalieri)

I promemoria (`beddy:reminders`) partono ogni giorno alle 09:00 (Europe/Rome) tramite lo scheduler di Laravel. Aggiungi **una sola** riga al crontab dell'utente di deploy (`crontab -e`):

```cron
* * * * * cd <percorso-app> && php artisan schedule:run >> /dev/null 2>&1
```

Verifica con `php artisan schedule:list`. Per provare subito l'invio: `php artisan beddy:reminders`.

## 10. Primo utente

Non esiste registrazione pubblica: gli utenti si creano per invito. Dal server:

```bash
php artisan beddy:invite "Nome Cognome" nome@dominio-aziendale
```

Il comando crea l'utente e invia un'email con il link per impostare la password. Se la validazione fallisce (email non valida o già presente) stampa l'errore in italiano ed esce con codice diverso da zero. Serve SMTP funzionante: se l'email non arriva controlla `MAIL_*` e `storage/logs/laravel.log`.

Gli altri utenti possono poi essere invitati dall'interfaccia, dalla sezione Utenti.

## 11. Verifica finale

1. Apri `https://<dominio-gestionale>/` e accedi con il primo utente (link ricevuto via email).
2. Crea un cliente e un progetto di prova; controlla che compaiano le checklist dai modelli.
3. Carica un allegato e verifica che si scarichi solo da utente autenticato.
4. `php artisan schedule:list` mostra `beddy:reminders` alle 09:00.

## 12. Aggiornamenti futuri

```bash
php artisan down
git fetch --tags && git checkout <nuovo-tag>
composer install --no-dev --optimize-autoloader
npm ci && npm run build        # oppure copia public/build
php artisan migrate --force
php artisan optimize:clear && php artisan optimize
php artisan up
```

## 13. Backup

Salva regolarmente il database (`mysqldump beddy_web_agency`) e la cartella `storage/app/private` (allegati), oltre al file `.env` in un luogo sicuro.
