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

---

# Ambiente di prova su cPanel (es. `dev.raybeenas.it`)

Procedura usata per pubblicare l'ambiente di prova su un hosting con **cPanel** e accesso SSH. Segnaposto: `<utente>` è il nome utente dell'account cPanel; l'app sta in `/home/<utente>/beddy-dev`.

Legenda: 🖥️ PC di sviluppo (codice e Laragon) · 🌐 cPanel dal browser · 🔐 SSH sul server.

> **Lavora sempre con l'utente dell'account cPanel, mai come `root`.** Se ti colleghi come root, passa all'utente con `su - <utente>` prima di qualsiasi comando: altrimenti i file finiscono in `/root` e appartengono a root, e il sito non può scrivere in `storage`.
>
> Se cPanel ha la funzione **Terminal** (sezione *Avanzate*), è un terminale SSH nel browser: si può fare tutto da un solo PC.

## 1. Preparazione in cPanel 🌐

1. **Sottodominio** (*Domini*): crea `dev.<dominio>` con **document root** `beddy-dev/public`. Se i DNS non sono gestiti da questo cPanel, aggiungi dove sono un record **A** `dev` con l'IP del server.
2. **HTTPS** (*Stato SSL/TLS*): seleziona il sottodominio → **Esegui AutoSSL**.
3. **PHP** (*MultiPHP Manager*): PHP **8.4** per il sottodominio. Controlla le estensioni (*Seleziona versione PHP* o *MultiPHP INI Editor*): `bcmath`, `intl`, `mbstring`, `pdo_mysql`, `zip`, `fileinfo`, `gd`, `xml`, `curl`.
4. **Database** (*Procedura guidata database MySQL*): database + utente con **tutti i privilegi**. cPanel aggiunge il prefisso dell'account ai nomi (`<utente>_...`). Usa una password di **sole lettere e numeri**: un `#` nel `.env` viene letto come inizio di un commento e tronca la password (errore `1045 Access denied`).
5. **Casella email** (*Account email*): es. `noreply@<dominio>`; da *Connetti dispositivi* annota server SMTP (di solito `mail.<dominio>`) e porta SSL (465).

## 2. Codice sul server 🔐

```bash
php -v                       # deve essere 8.4; altrimenti usa /opt/cpanel/ea-php84/root/usr/bin/php
```

Chiave di sola lettura per il repository privato:

```bash
ssh-keygen -t ed25519 -C "deploy dev" -f ~/.ssh/beddy_deploy -N ""
cat ~/.ssh/beddy_deploy.pub  # da aggiungere su GitHub: Settings → Deploy keys (senza scrittura)
git config --global core.sshCommand "ssh -i ~/.ssh/beddy_deploy"
```

```bash
cd ~ && git clone git@github.com:sebyarena-raybeenas/beddy-web-agency.git beddy-dev
cd ~/beddy-dev && git checkout <ramo-o-tag>
php $(which composer) install --no-dev --optimize-autoloader
# senza composer: curl -sS https://getcomposer.org/installer | php && php composer.phar install --no-dev --optimize-autoloader
```

## 3. Asset grafici 🖥️ + 🌐

Di solito sui server cPanel non c'è Node.js: la build si fa sul PC di sviluppo.

1. 🖥️ `npm run build`, poi comprimi `public/build` in uno zip.
2. 🌐 *Gestione file* → `beddy-dev/public` → carica lo zip → **Estrai** → elimina lo zip.
3. Verifica che esista `public/build/manifest.json` (non `public/build/build/...`).

## 4. Dati (facoltativo) 🖥️ + 🌐

Per portare i dati del PC di sviluppo:

1. 🖥️ `mysqldump -uroot beddy_web_agency > beddy-export.sql`
2. 🌐 *phpMyAdmin* → database dell'app → **Importa** il file.
3. Allegati: copia `storage/app/private` nella stessa cartella sul server.
4. **Elimina `beddy-export.sql`** dal PC e dal server: contiene dati personali.
5. Dopo il primo accesso, **elimina gli utenti demo** (`demo@example.test`, `collega@example.test`, password nota) dalla sezione Utenti.

## 5. Configurazione 🔐

```bash
cd ~/beddy-dev && cp .env.example .env && php artisan key:generate
```

Modifica il `.env` **senza cancellare la riga `APP_KEY`** generata (senza chiave l'app risponde 500 con `MissingAppKeyException`; si rimedia con `php artisan key:generate`):

```dotenv
APP_ENV=staging
APP_DEBUG=false
APP_URL=https://dev.<dominio>
APP_TIMEZONE=Europe/Rome
APP_LOCALE=it

DB_HOST=localhost
DB_DATABASE=<utente>_<database>
DB_USERNAME=<utente>_<utente-db>
DB_PASSWORD=<password-solo-lettere-e-numeri>

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=mail.<dominio>
MAIL_PORT=465
MAIL_USERNAME=noreply@<dominio>
MAIL_PASSWORD=<password-casella>
MAIL_FROM_ADDRESS=noreply@<dominio>
```

`APP_ENV` non deve mai essere `local`. Se una password contiene simboli, mettila tra apici singoli (`'...'`).

```bash
php artisan migrate --force
php artisan db:seed --class=TemplateSeeder --force   # solo su database vuoto (non dopo un'importazione)
chmod 711 ~ && chmod 755 ~/beddy-dev ~/beddy-dev/public && chmod -R 755 storage bootstrap/cache
php artisan optimize
php artisan beddy:invite "Nome Cognome" nome@dominio   # primo utente
```

Su cPanel PHP gira con l'utente dell'account: bastano permessi 755 (le cartelle scrivibili dal gruppo, 775/777, possono causare 403/500).

## 6. Cron e protezione 🌐

- *Processi Cron* → **Una volta al minuto**, comando:

  ```bash
  cd /home/<utente>/beddy-dev && /opt/cpanel/ea-php84/root/usr/bin/php artisan schedule:run >> /dev/null 2>&1
  ```

  Verifica con `php artisan schedule:list` (deve comparire `beddy:reminders` alle 09:00).
- Consigliato: *Privacy directory* su `beddy-dev/public` per proteggere con password l'intero ambiente di prova.

## 7. Aggiornamenti

1. 🖥️ Se è cambiata la grafica: `npm run build` e ricarica `public/build` (punto 3).
2. 🔐

   ```bash
   cd ~/beddy-dev && php artisan down && git pull && php $(which composer) install --no-dev --optimize-autoloader && php artisan migrate --force && php artisan optimize:clear && php artisan optimize && php artisan up
   ```

## 8. Problemi già incontrati

| Sintomo | Causa | Soluzione |
|---|---|---|
| `1045 Access denied` su `migrate` | `#` nella password del `.env` | Password di sole lettere e numeri, o tra apici singoli; poi `php artisan config:clear` |
| 403 Forbidden | Document root sbagliato o app clonata in `/root` | Document root su `beddy-dev/public`; app in `/home/<utente>`, proprietario l'utente dell'account (`chown -R <utente>:<utente>`) |
| 500 con `MissingAppKeyException` | `APP_KEY` mancante nel `.env` | `php artisan key:generate && php artisan optimize:clear && php artisan optimize` |
| Errori dopo aver spostato la cartella | Configurazione memorizzata con il percorso vecchio | `php artisan optimize:clear && php artisan optimize` |
| Diagnosi | — | cPanel → *Metriche → Errori*; `tail -40 storage/logs/laravel.log` |
