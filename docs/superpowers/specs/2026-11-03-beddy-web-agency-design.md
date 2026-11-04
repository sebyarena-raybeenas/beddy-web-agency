# Beddy Web Agency — Documento di progetto

Data: 2026-11-03 · Stato: approvato dall'utente il 2026-11-03

## 1. Cos'è e perché

Beddy Web Agency è un gestionale web interno per l'agenzia che sviluppa siti per i clienti di Beddy. Oggi le informazioni su ogni cliente e sito sono sparse tra più strumenti, e il numero di clienti è diventato troppo alto per gestirle così.

Asana **resta** lo strumento condiviso tra i vari team dell'azienda. Ogni team lo compila a modo suo, senza uno standard, e le informazioni utili al team siti (contatti, acconto, dominio, hosting, assistenza) sono quasi sempre testo libero in note e commenti. Beddy Web Agency serve a:

- tenere in un unico posto, per ogni cliente e progetto, tutto ciò che serve al team siti;
- guidare ogni sito lungo un flusso di stati preciso, con scadenze e notifiche automatiche;
- far comunicare e assegnare lavoro tra colleghi **solo qui**, non su Asana;
- (dalla v2) leggere da Asana le informazioni già presenti, senza mai scriverci.

**Successo:** ogni collega apre il gestionale e sa subito quali siti sono fermi, cosa manca, cosa tocca a lui e cosa è in scadenza, senza cercare in Asana, mail o chat.

## 2. Obiettivi, non-obiettivi, anti-obiettivi

**Obiettivi**
- Anagrafica clienti con campi fissi utili al team siti, più contatti e domini.
- Progetti di tre tipi: nuovo sito, modifica, rifacimento.
- Flusso di stati con passaggi automatici, scadenze a 7 giorni e notifiche.
- Requisiti obbligatori e materiale (foto, testi, logo) tracciati con spunte.
- To-do assegnabili, checklist standard generate in automatico.
- Discussione tra colleghi su progetti e to-do.
- Sviluppo incrementale: le funzioni arrivano versione per versione.

**Non-obiettivi (per ora)**
- Sostituire Asana.
- Livelli di permesso diversi tra utenti (tutti uguali in v1; arriveranno in versioni future).
- Area riservata per i clienti: il gestionale è solo per il team.
- Fatturazione vera: i pagamenti sono solo tracciati.

**Anti-obiettivi (sarebbe un fallimento anche se funziona)**
- **Scrivere qualsiasi cosa su Asana.** Il collegamento è in sola lettura, sempre.
- Sovrascrivere dati confermati da un utente con dati letti da Asana o estratti automaticamente.
- Un'app che obbliga a compilare tutto prima di essere utile: deve funzionare anche con dati parziali.

## 3. Utenti

- Colleghi dell'agenzia, invitati nel gestionale. Oggi il team siti conta poche persone (in Asana compaiono 2 assegnatari).
- Tutti gli utenti hanno gli stessi permessi: possono creare clienti e progetti (presenti o no su Asana), modificare tutto, assegnare to-do.
- Il modello dati deve permettere di aggiungere ruoli in futuro senza riscrivere tutto.

## 4. I flussi di stati

Esistono **due flussi**, scelti in base al tipo di progetto:
- **Nuovo sito** e **rifacimento** → flusso "sito" (4.1). Un rifacimento è a tutti gli effetti un sito nuovo (decisione dell'utente).
- **Modifica** a un sito già pronto o online → flusso "modifica" (4.2). Non passa da "Pronto da sviluppare" ma da "Pronto da modificare" (decisione dell'utente).

Regole comuni ai due flussi (scelte di Claude, salvo dove indicato):
- Lo stato si cambia a mano liberamente, anche tornando indietro; ogni cambio resta nello storico.
- Lo stato "Pronto da…" si raggiunge **solo in automatico**, quando tutti i requisiti obbligatori sono soddisfatti. Se poi un requisito viene tolto, il progetto torna da solo a "In preparazione".
- Le scadenze a 7 giorni si calcolano dall'ultimo cambio di stato, quindi ripartono a ogni giro "presentazione → modifiche".
- **Ripetizione delle notifiche** (decisione dell'utente): dopo il 7° giorno la notifica parte e si ripete **ogni giorno** finché lo stato non cambia. Vale per gli avvisi a 7 giorni e per il materiale atteso. La notifica "Pronto da…" si ripete ogni giorno dal primo giorno.
- Le notifiche quotidiane partono alle 9:00 (scelta di Claude).

### 4.1 Flusso "sito" (nuovo sito e rifacimento)

Definito dall'utente e confermato con il collega di team.

| # | Stato | Come ci si arriva | Cosa succede |
|---|---|---|---|
| 1 | **In preparazione** | stato iniziale di ogni progetto | si spuntano i requisiti obbligatori (vedi sotto) |
| 2 | **Pronto da sviluppare** | **automatico**, quando tutti i requisiti obbligatori sono spuntati | notifica **ogni giorno** al responsabile del progetto, finché non passa a "In sviluppo" |
| 3 | **In sviluppo** | manuale | scadenza a **7 giorni**; al 7° giorno notifica «Hai finito? Contatta il cliente per l'appuntamento» |
| 4 | **Presentazione sito: in attesa di feedback** | manuale in v1; dalla v3 **automatico** quando passa la data dell'appuntamento di presentazione | si attende il riscontro del cliente |
| 5 | **Feedback cliente: modifiche** | manuale | scadenza a **7 giorni**; al 7° giorno notifica «Hai finito le modifiche? Contatta il cliente» |
| 6 | **In pubblicazione** | manuale, dopo l'OK del cliente | viene generata **in automatico** la checklist di messa online (17 voci) |
| 7 | **Sito pubblicato** | manuale | fine |

- Il giro 4 ⇄ 5 si ripete finché il cliente non approva.
- **Requisiti obbligatori** dello stato 1: informazioni preliminari complete (contatti, acconto, dominio, hosting, assistenza), "Cliente contattato", "Struttura sito inviata" (via mail), Foto ricevute, Testi ricevuti, Logo ricevuto.
- "Informazioni preliminari complete" è una **spunta manuale** in v1: l'acconto lo verifica il team, perché la tabella pagamenti arriva dopo (scelta di Claude).
- Un materiale può essere segnato **"non necessario"** (es. il cliente ha già il logo): conta come requisito soddisfatto (scelta di Claude).
- **Avviso materiale:** se un materiale è "atteso" da più di 7 giorni, notifica al responsabile.
- **Checklist di messa online** (dalla struttura già usata in Asana): Responsive, Traduzioni, Robots, Sitemap, Search Console, Analytics, Recaptcha, Wordfence, Cache, Disabilitare sitemap SEO Yoast, Cambio password, Rimozione plugin, Codice WPML, Contact form, 404, Google Business, Redirect vecchio sito.
- Ogni cambio di stato viene registrato (chi, quando, da→a; "sistema" se automatico).

Riferimento visivo: `Lavori/Beddy Web Agency - Flusso stati sito.pdf`.

### 4.2 Flusso "modifica"

L'utente ha deciso che le modifiche hanno un flusso proprio con "Pronto da modificare". **Gli stati intermedi e i requisiti qui sotto sono una proposta di Claude, da verificare con il collega di team.**

| # | Stato | Come ci si arriva | Cosa succede |
|---|---|---|---|
| 1 | **In preparazione** | stato iniziale | si spuntano i requisiti obbligatori |
| 2 | **Pronto da modificare** | **automatico**, requisiti soddisfatti | notifica ogni giorno al responsabile |
| 3 | **In modifica** | manuale | scadenza a 7 giorni; poi notifica quotidiana «Hai finito? Contatta il cliente» |
| 4 | **In attesa di feedback** | manuale | si attende il riscontro del cliente |
| 5 | **Feedback cliente: altre modifiche** | manuale | scadenza a 7 giorni, come sopra |
| 6 | **Modifiche pubblicate** | manuale | fine |

- Requisiti obbligatori proposti: "Richiesta del cliente chiara e registrata", "Cliente contattato", "Materiale ricevuto" (può essere "non necessario").
- Nessuna checklist di messa online automatica per le modifiche.
- I flussi sono definiti in un unico punto del codice (configurazione), così aggiungerne o cambiarne uno non richiede di riscrivere le regole.

## 5. Schermate e grafica

**Viste principali** (filtri sugli stessi dati):
- **Progetti** — vista **predefinita**. Contatori per stato che fanno anche da filtro, filtri rapidi, tre visualizzazioni (griglia di card, kanban per stato, lista). Ogni card mostra cliente, tipo, stato, responsabile, scadenza e lo stato dei requisiti.
- **Clienti** — elenco clienti; scheda cliente con anagrafica, contatti, domini, progetti.
- **Dettaglio progetto** — intestazione con stato e avanzamento lungo il flusso, requisiti, materiale, to-do, discussione, scheda cliente, storico attività.
- Pulsanti "Aggiungi cliente" / "Aggiungi progetto" sempre a portata di mano; indicazione dell'origine (Asana / manuale).

**Grafica** (approvata dall'utente): demo in `Lavori/.superpowers/brainstorm/1695-1793468272/content/demo-grafica-v2.html`.
- Font Plus Jakarta Sans; sidebar viola scura `#2b2257`; colore d'accento `#6c4ce0`.
- Riferimenti: layout generale stile pixelwibes "My-Task", card progetti stile Taskora `/projects`, dettaglio progetto stile Workload "project-details".
- Interfaccia in italiano.
- Le tre schermate principali (Progetti, Clienti, Dettaglio progetto) seguono **fedelmente** la demo, come pagine personalizzate dentro Filament. I moduli di inserimento e le schermate di servizio usano lo stile standard di Filament con i colori approvati (scelta di Claude).

## 6. Tecnologie e modo di lavorare

Scelta proposta da Claude e non contestata dall'utente:
- **Laravel** (PHP) + **Filament** per il pannello + **MySQL/MariaDB**.
- Notifiche quotidiane e scadenze tramite lo scheduler di Laravel (cron ogni minuto sul server).

Decisioni dell'utente:
- Si sviluppa **in locale** (Laragon su Windows). Anche le modifiche manuali del team si fanno in locale.
- Tutto va su un **repository GitHub privato**. Il ramo `main` su GitHub è il riferimento e contiene solo versioni stabili; ogni funzione in un ramo a parte; versioni numerate (v1.0, v1.1…).
- Si pubblicano online solo versioni definitive e funzionanti, sul **server aziendale** (lo stesso che ospita i siti dei clienti): **PHP 8.4, accesso SSH, cron personalizzabili**, e può fare da **server di posta**. Quindi si usano le versioni attuali di Laravel e Filament, e il cron ogni minuto per lo scheduler.
- Segreti (password database, token Asana, chiavi calendario) solo nel file `.env`, **mai** su GitHub.
- Il codice evolverà insieme a Claude: deve restare leggibile e con test.

Cartella del progetto: `C:\Users\sebya\Desktop\Lavori\beddy-web-agency`.

## 7. Modello dati

Diagramma completo: `Lavori/.superpowers/brainstorm/1076-1793652127/content/erd-v4.html`. Area A rivista e approvata dall'utente per la v1; aree B, C, D approvate "in linea di massima".

### A · Nucleo
- `users` — name, email, password, avatar, is_active, invited_at, last_login_at.
- `clients` — name (ragione sociale), vat_number, address, has_assistance, assistance_type, hosting_status (suo | nostro | da attivare), origin (asana | manuale), created_by.
- `client_contacts` — client_id, name, role, phone, email, is_primary.
- `domains` — client_id, project_id (vuoto se non ancora usato), name (unico), role (principale | secondario), ownership (suo | nostro | da acquistare). I secondari fanno redirect sul principale.
- `projects` — client_id, name, type (nuovo | modifica | rifacimento), status (i valori ammessi dipendono dal flusso del tipo, vedi §4), owner_id (responsabile), status_changed_at, due_date (scadenza corrente), primary_domain_id, origin, created_by.
- `project_members` — altri colleghi coinvolti nel progetto.
- `project_status_history` — project_id, from_status, to_status, changed_by (vuoto = sistema), changed_at.

Regola: quando si sceglie il dominio principale di un progetto, il software aggiorna `role` di tutti i domini di quel progetto, così `primary_domain_id` e `role` non vanno mai in contrasto.

### B · Lavoro sul progetto
Schema "modello → copia": i modelli si scrivono una volta; ogni progetto riceve una copia; cambiare un modello non tocca i progetti già avviati.
- `requirement_templates` — label, project_type, is_mandatory, sort.
- `project_requirements` — project_id, template_id, label, is_done, done_at, done_by.
- `materials` — project_id, requirement_id, kind (foto | testi | logo | altro), status (atteso | ricevuto | non necessario), requested_at, received_at. Quando un materiale diventa "ricevuto", il requisito collegato si spunta da solo.
- `attachments` — file caricati, collegabili a oggetti diversi (`attachable_type/id`).
- `checklist_templates` — name, project_type, trigger_status (es. in_pubblicazione).
- `checklist_template_items` — checklist_template_id, title, sort.
- `tasks` — project_id, template_item_id (vuoto per i to-do scritti a mano), title, description, assignee_id, due_date, completed_at, created_by.

### C · Contenuti, pagamenti, calendario
- `client_requests` — project_id, body, status (aperta | gestita), created_by.
- `example_sites` — project_id, url, note.
- `payments` — project_id, label, amount, percent, status (da pagare | pagato), due_date, paid_at, invoice_ref.
- `appointments` + `appointment_attendees` — titolo, inizio/fine, luogo o link, id dell'evento sul calendario esterno.
- `calendar_accounts` — user_id, provider (google | microsoft), token salvati cifrati.

### D · Trasversali e Asana
- `comments` (su progetti e to-do), `notes` (su clienti e progetti), `activity_log` (chi ha fatto cosa), `notifications` (standard Laravel).
- `asana_items` — copia grezza di ciò che si legge da Asana (gid, tipo, payload, hash del contenuto, data lettura).
- `asana_links` — abbinamento elemento Asana ↔ cliente/progetto Beddy.
- `field_suggestions` — valore proposto, frase originale da cui è estratto, stato (da confermare | accettato | scartato), chi l'ha rivisto.

## 8. Versioni

Divisione concordata con l'utente.

| Versione | Contenuto |
|---|---|
| **v1** | Utenti (invito via email e login), clienti con contatti e domini, progetti con flusso stati e scadenze, requisiti e materiale con file, to-do e checklist, commenti, notifiche in app, le tre viste con la grafica approvata |
| **v2** | Lettura da Asana: abbinamento manuale cliente/progetto ↔ elementi Asana (il software suggerisce per nome), sezione "Da Asana" con i dati grezzi in sola lettura, estrazione automatica (AI) di valori dal testo libero come proposte da confermare, segnalazione delle novità in Asana senza sovrascrivere dati confermati |
| **v3** | Calendario (Google o Microsoft Teams, da scegliere), appuntamenti, passaggio automatico a "in attesa di feedback" dopo la data della presentazione |
| **dopo** | Pagamenti dettagliati, notifiche via mail (il server di posta c'è già; in v1 la mail serve solo per gli inviti), livelli di permesso, altro da decidere |

Le tabelle delle versioni successive non vanno create in v1: il modello dati sopra serve a non chiudersi strade.

### Struttura di base (primo passo della v1)
Progetto Laravel creato in locale, repository GitHub privato, login, grafica applicata, e **dati d'esempio inventati** per vedere subito schermate piene (es. "Hotel Rossi", In preparazione, 4 requisiti su 6 spuntati, foto attese da 9 giorni). I dati d'esempio non devono contenere dati reali di clienti.

## 9. Asana (riferimento per la v2)

Struttura trovata nel workspace beddy.io (letta tramite connettore, sola lettura):
- Team **"Siti Web"** → unico progetto "Portale Siti Web": un task per sito; le sezioni fanno da stato (Primi Contatti, Siti Da Iniziare, Siti in Attesa di Materiale, Siti in sviluppo, Siti pubblicati, Attività su siti online); i subtask sono la checklist di messa online; note e campi personalizzati vuoti.
- Team **"Beddy"** → un progetto per ogni cliente del gestionale alberghiero, con sezioni GENERALE / CONFIGURAZIONE / CANALI / MASK / VERIFICA; contratto, fatture, pagamenti, rinnovi e ID struttura stanno in note e commenti in testo libero.

Accesso via API Asana con token in sola lettura, salvato nel `.env`.

## 10. Lasciato a chi costruisce

- Organizzazione interna del codice, nomi di classi, struttura delle cartelle.
- Libreria per lo storico attività e per i file caricati.
- Come rendere in Filament le viste griglia/kanban/lista, purché l'aspetto segua la demo approvata.
- Test: quali e quanti, purché le regole del flusso (passaggi automatici, scadenze, notifiche) siano coperte.
