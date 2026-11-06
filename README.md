# Beddy Web Agency

Gestionale interno per la web agency: clienti, domini e servizi, progetti con requisiti, checklist, attività, commenti, allegati, promemoria giornalieri e registro delle attività. Costruito con Laravel 13 e Filament 5, interfaccia in italiano.

## Installazione locale (Laragon)

Requisiti: Laragon con PHP 8.4, Composer, MySQL e Node.js.

1. Aggiungi PHP 8.4 e Composer al `PATH` (le cartelle `bin\php\php-8.4...` e `bin\composer` di Laragon).
2. Crea il database MySQL `beddy_web_agency` (utf8mb4).
3. Copia la configurazione e genera la chiave:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Adatta `DB_USERNAME` / `DB_PASSWORD` se necessario.
4. Installa le dipendenze e compila gli asset:
   ```bash
   composer install
   npm ci
   npm run build
   ```
5. Crea le tabelle e i dati (modelli e, in locale, dati demo):
   ```bash
   php artisan migrate --seed
   ```
6. Avvia con `php artisan serve` (oppure usa il virtual host di Laragon) e apri l'indirizzo mostrato.

Nota: se un antivirus intercetta il traffico TLS, `composer` e `npm` possono fallire con errori di certificato. In quel caso imposta `COMPOSER_CAFILE` e `NODE_EXTRA_CA_CERTS` con un bundle di certificati CA che includa anche la radice dell'antivirus.

## Accesso demo (solo locale)

Con `APP_ENV=local`, `php artisan migrate:fresh --seed` crea dati fittizi di esempio. Utente demo: `demo@example.test` / `password` (anche `collega@example.test`). La password serve solo in locale: non usarla mai in produzione.

## Test

```bash
php artisan test
```

I test usano SQLite in memoria: non toccano il database locale.

## Documentazione

- [Guida al deploy sul server aziendale](docs/DEPLOY.md)
- [Design](docs/superpowers/specs/2026-11-03-beddy-web-agency-design.md)
- [Piano di implementazione](docs/superpowers/plans/2026-11-03-beddy-web-agency-v1.md)
- [Mockup grafico](docs/design/demo-grafica-v2.html)

Comandi utili: `php artisan beddy:invite "Nome" email` (invita un utente), `php artisan beddy:reminders` (invia subito i promemoria).
