# Beddy Web Agency v1 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build version 1 of Beddy Web Agency: an internal web app where the team manages clients, domains and website projects through two state flows, with mandatory requirements, materials, to-dos, checklists, comments and daily in-app reminders.

**Architecture:** One Laravel app with a single Filament panel at `/`. Business rules (state flows, automatic transitions, deadlines, checklist generation, reminders) live in plain service classes under `app/Domain/…` and are tested without the UI. Filament resources cover the data-entry screens. The three main screens (Progetti, Dettaglio progetto, Clienti) are custom Filament pages with Blade views that reproduce the approved demo.

**Tech Stack:** PHP 8.4, the latest stable Laravel, the latest stable Filament, MySQL/MariaDB (Laragon) for development, SQLite in memory for tests, Pest, spatie/laravel-activitylog.

**Spec:** `docs/superpowers/specs/2026-11-03-beddy-web-agency-design.md`

## Global Constraints

- PHP 8.4. Production server: SSH, custom cron, the company mail server.
- Development happens locally (Laragon on Windows). GitHub private repo; `main` holds only stable versions; one branch per feature; versions tagged `v1.0`, `v1.1`…
- Secrets only in `.env`, never committed. `.env.example` lists every key with an empty value.
- **The app never writes to Asana.** No Asana code ships in v1.
- Do not create tables that belong to later versions: `payments`, `appointments`, `appointment_attendees`, `calendar_accounts`, `client_requests`, `example_sites`, `notes`, `asana_items`, `asana_links`, `field_suggestions`.
- The interface is in Italian, `APP_LOCALE=it`, timezone `Europe/Rome`.
- Brand: font Plus Jakarta Sans; sidebar `#2b2257`; accent `#6c4ce0`. Reference demo: `C:\Users\sebya\Desktop\Lavori\.superpowers\brainstorm\1695-1793468272\content\demo-grafica-v2.html` (copied into the repo at `docs/design/demo-grafica-v2.html` in Task 1).
- All users have the same permissions. No roles table.
- Demo and seed data are fictional. No real client data, ever.
- Never invent people's names in UI copy or seeds beyond obviously fictional ones ("Utente Demo", "Hotel Rossi").
- Enum-like columns are `string` columns backed by PHP string-backed enums in `app/Enums/`.

## Review Focus

1. **The reminder command runs twice on the same day** (manual run plus cron, or a deploy retry). The user expects one reminder per project per day, not two. → test in Task 10.
2. **A project has no responsible user** (`owner_id` null). Reminders skip it silently, and screens show "Nessun responsabile" without crashing. → tests in Task 10 and Task 13.
3. **A domain of another client is chosen as a project's primary domain.** It is rejected with an Italian validation message. → test in Task 4.
4. **A project goes back from "In pubblicazione" and then returns to it.** The go-live checklist is not generated twice. → test in Task 8.
5. **Someone tries to change the type of a project that already has a flow running** (e.g. modifica → nuovo). The type is locked after creation, because the state sets differ. → test in Task 6.

---

## File structure

```
app/
  Enums/            ProjectType, MaterialKind, MaterialStatus, DomainRole, DomainOwnership, HostingStatus, Origin
  Models/           User, Client, ClientContact, Domain, Project, ProjectStatusChange,
                    RequirementTemplate, ProjectRequirement, Material, Attachment,
                    ChecklistTemplate, ChecklistTemplateItem, Task, Comment
  Domain/
    Clients/        DomainService.php
    Workflow/       Workflow.php, WorkflowRegistry.php, ProjectStatusService.php,
                    InvalidTransition.php
    Projects/       ProjectCreator.php, RequirementSync.php
    Checklists/     ChecklistGenerator.php
    Reminders/      ReminderService.php
    Users/          InviteUser.php
  Observers/        MaterialObserver.php, ProjectRequirementObserver.php
  Notifications/    UserInvitation.php, ProjectReadyReminder.php, DeadlineReminder.php,
                    MaterialOverdueReminder.php
  Console/Commands/ SendDailyReminders.php
  Filament/
    Pages/          ProjectsBoard.php, ProjectDetail.php, ClientsIndex.php
    Resources/      ClientResource, ProjectResource, UserResource,
                    RequirementTemplateResource, ChecklistTemplateResource
  Providers/Filament/AdminPanelProvider.php
config/workflows.php
database/migrations/…  database/seeders/{TemplateSeeder,DemoSeeder,DatabaseSeeder}.php
resources/css/filament/admin/theme.css
resources/views/filament/pages/{projects-board,project-detail,clients-index}.blade.php
resources/views/filament/components/…   (project-card, status-stepper, requirement-ticks)
tests/Feature/…  tests/Unit/…
docs/DEPLOY.md
```

---

### Task 1: Environment, Laravel scaffold, repository

**Files:**
- Create: the Laravel project in `C:\Users\sebya\Desktop\Lavori\beddy-web-agency` (keeping the existing `docs/`)
- Create: `docs/design/demo-grafica-v2.html` (copy of the approved demo)
- Modify: `.env.example`, `phpunit.xml`, `config/app.php`
- Test: `tests/Feature/SmokeTest.php`

**Interfaces:**
- Produces: a running Laravel app with Filament panel `admin` at path `/`, Pest working, git repo with `main`.

- [ ] **Step 1: The user installs Laragon (Full) with PHP 8.4, and creates a MySQL database `beddy_web_agency`.** This step is the user's: Claude does not download or run installers. Check: `php -v` shows 8.4, `composer -V` and `mysql --version` respond in a new terminal.
- [ ] **Step 2: Create the Laravel project.** `composer create-project` needs an empty folder. Move `docs/` to `..\beddy-docs-tmp`, run `composer create-project laravel/laravel .` inside `beddy-web-agency`, then move `docs/` back.
- [ ] **Step 3: Install Filament (latest stable) and create the panel** with id `admin` and path `''` (root). Install Pest (`pestphp/pest`, `pestphp/pest-plugin-laravel`) and `spatie/laravel-activitylog`.
- [ ] **Step 4: Configure.**
  - `.env`: `APP_NAME="Beddy Web Agency"`, `APP_LOCALE=it`, `APP_TIMEZONE=Europe/Rome`, MySQL credentials.
  - `config/app.php`: read the timezone from `APP_TIMEZONE`.
  - `phpunit.xml`: `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`.
  - Install the Italian translation strings for Laravel (`laravel-lang/common` or an equivalent).
- [ ] **Step 5: Write the failing test** `tests/Feature/SmokeTest.php`:

```php
it('shows the login page', function () {
    $this->get('/login')->assertOk()->assertSee('Beddy Web Agency');
});
```

- [ ] **Step 6: Run** `php artisan test`. Expected: PASS once the panel is set up; if it fails, fix the panel path or the brand name.
- [ ] **Step 7: Git.** Run `git init -b main`. Make sure `.env` is in `.gitignore`. Copy the demo into `docs/design/`. Commit `chore: scaffold Laravel + Filament`.
- [ ] **Step 8: GitHub.** The user creates an empty **private** repo on GitHub and gives Claude its URL. Then `git remote add origin <url>` and `git push -u origin main`. Do not push before the user provides the URL.

---

### Task 2: Panel theme and brand

**Files:**
- Modify: `app/Providers/Filament/AdminPanelProvider.php`
- Create: `resources/css/filament/admin/theme.css`, `vite.config.js` entry
- Test: `tests/Feature/PanelBrandTest.php`

**Interfaces:**
- Produces: CSS custom properties `--side:#2b2257`, `--acc:#6c4ce0`, `--accSoft:#efeafe` and font Plus Jakarta Sans, available to every page in the panel. Later Blade pages use these variables.

- [ ] **Step 1: Write the failing test.** Log in as a user. Assert that `/` responds 200 and that the HTML contains `Plus Jakarta Sans` and the brand name.
- [ ] **Step 2: Run it.** Expected: FAIL (no theme yet).
- [ ] **Step 3: Implement.**
  - Panel settings: `->brandName('Beddy Web Agency')`, `->colors(['primary' => Color::hex('#6c4ce0')])`, `->font('Plus Jakarta Sans')`, `->viteTheme('resources/css/filament/admin/theme.css')`, `->databaseNotifications()`, `->sidebarCollapsibleOnDesktop()`.
  - The theme sets the sidebar background `#2b2257` with light text, and card radius `16px` as in the demo. Read the demo's `<style>` block for spacing and radii.
- [ ] **Step 4: Run** `npm run build`, then the test. Expected: PASS.
- [ ] **Step 5: Commit** `feat: brand theme`.

---

### Task 3: Users and invitations

**Files:**
- Modify: `app/Models/User.php`, users migration (add `avatar` nullable, `is_active` bool default true, `invited_at` nullable datetime, `last_login_at` nullable datetime)
- Create: `app/Domain/Users/InviteUser.php`, `app/Notifications/UserInvitation.php`, `app/Filament/Resources/UserResource.php`, listener updating `last_login_at` on `Illuminate\Auth\Events\Login`
- Test: `tests/Feature/Users/InviteUserTest.php`

**Interfaces:**
- Produces: `InviteUser::handle(string $name, string $email): User`. `User::canAccessPanel(Panel $panel): bool` returns `$this->is_active`.

- [ ] **Step 1: Write the failing tests:**
  - `it('creates the user and emails an invitation')`: `Notification::fake()`; call `handle('Utente Demo', 'demo@example.test')`. Assert the user exists with `invited_at` set and `is_active` true. Assert `UserInvitation` was sent to that user.
  - `it('rejects an email that already exists')`: expect `ValidationException`.
  - `it('denies panel access to inactive users')`: `canAccessPanel()` returns false when `is_active=false`.
- [ ] **Step 2: Run them.** Expected: FAIL.
- [ ] **Step 3: Implement.**
  - `handle()` creates the user with a random unusable password, then creates a token with `Password::broker()->createToken($user)`.
  - `UserInvitation` is a mail notification. Subject: «Sei stato invitato su Beddy Web Agency». Its button «Imposta la password» links to Filament's password-reset page with that token and email.
  - In `UserResource`, the create action calls `InviteUser` instead of a password field. Columns: name, email, attivo, ultimo accesso. A toggle sets `is_active`.
- [ ] **Step 4: Run the tests.** Expected: PASS.
- [ ] **Step 5: Commit** `feat: user invitations`.

---

### Task 4: Clients, contacts, domains

**Files:**
- Create: migrations and models `Client`, `ClientContact`, `Domain`; enums `HostingStatus` (`suo`, `nostro`, `da_attivare`), `Origin` (`asana`, `manuale`), `DomainRole` (`principale`, `secondario`), `DomainOwnership` (`suo`, `nostro`, `da_acquistare`)
- Test: `tests/Feature/Clients/ClientModelTest.php`

**Interfaces:**
- Produces:
  - Columns as spec §7 A.
  - `domains.name` is unique and stored lowercase without `http(s)://` or `www.`.
  - `domains.project_id` is a nullable `unsignedBigInteger` here. Its foreign key, `projects.primary_domain_id` and `DomainService::setPrimary()` come in Task 6, once `projects` exists.
  - `Client::contacts()`, `Client::domains()` relations (`Client::projects()` added in Task 6).

- [ ] **Step 1: Write the failing tests:**
  - `it('normalizes domain names')`: saving `https://www.Esempio.it/` stores `esempio.it`.
  - `it('rejects a duplicate domain')`: a second `esempio.it`, even for another client, throws a validation error with the message «Questo dominio è già registrato».
  - `it('keeps one primary contact per client')`: setting a second contact `is_primary` clears it on the first.
- [ ] **Step 2: Run them.** Expected: FAIL.
- [ ] **Step 3: Implement** migrations, models and enums, plus the name normalisation in a `Domain` mutator.
- [ ] **Step 4: Run the tests.** Expected: PASS.
- [ ] **Step 5: Commit** `feat: clients, contacts, domains`.

---

### Task 5: Workflow definitions

**Files:**
- Create: `config/workflows.php`, `app/Domain/Workflow/Workflow.php`, `app/Domain/Workflow/WorkflowRegistry.php`, `app/Enums/ProjectType.php` (`nuovo`, `modifica`, `rifacimento`)
- Test: `tests/Unit/Workflow/WorkflowRegistryTest.php`

**Interfaces:**
- Produces:
  - `ProjectType::flow(): string`: `nuovo` and `rifacimento` → `'sito'`, `modifica` → `'modifica'`.
  - `WorkflowRegistry::for(ProjectType $type): Workflow` and `WorkflowRegistry::get(string $flow): Workflow`.
  - `Workflow` (readonly) exposes:
    - `key: string`, `initial(): string`, `ready(): string`, `states(): array<string,string>` (key → Italian label, in order);
    - `has(string $state): bool`, `label(string $state): string`;
    - `deadlineDays(string $state): ?int`, `deadlineMessage(string $state): ?string`;
    - `readyMessage(): string`, `checklistTrigger(): ?string`, `isFinal(string $state): bool`.

`config/workflows.php` holds exactly these values (spec §4):

```php
'sito' => [
  'states' => [
    'in_preparazione'      => 'In preparazione',
    'pronto_da_sviluppare' => 'Pronto da sviluppare',
    'in_sviluppo'          => 'In sviluppo',
    'in_attesa_feedback'   => 'Presentazione sito: in attesa di feedback',
    'feedback_modifiche'   => 'Feedback cliente: modifiche',
    'in_pubblicazione'     => 'In pubblicazione',
    'pubblicato'           => 'Sito pubblicato',
  ],
  'initial' => 'in_preparazione', 'ready' => 'pronto_da_sviluppare', 'final' => 'pubblicato',
  'ready_message' => 'Il sito :project è pronto da sviluppare, puoi iniziare.',
  'deadlines' => [
    'in_sviluppo'        => [7, "Hai finito? Contatta il cliente per l'appuntamento."],
    'feedback_modifiche' => [7, 'Hai finito le modifiche? Contatta il cliente.'],
  ],
  'checklist_trigger' => 'in_pubblicazione',
],
'modifica' => [
  'states' => [
    'in_preparazione'          => 'In preparazione',
    'pronto_da_modificare'     => 'Pronto da modificare',
    'in_modifica'              => 'In modifica',
    'in_attesa_feedback'       => 'In attesa di feedback',
    'feedback_altre_modifiche' => 'Feedback cliente: altre modifiche',
    'modifiche_pubblicate'     => 'Modifiche pubblicate',
  ],
  'initial' => 'in_preparazione', 'ready' => 'pronto_da_modificare', 'final' => 'modifiche_pubblicate',
  'ready_message' => 'La modifica :project è pronta, puoi iniziare.',
  'deadlines' => [
    'in_modifica'              => [7, 'Hai finito? Contatta il cliente.'],
    'feedback_altre_modifiche' => [7, 'Hai finito le modifiche? Contatta il cliente.'],
  ],
  'checklist_trigger' => null,
],
```

- [ ] **Step 1: Write the failing tests.**
  - `rifacimento` maps to flow `sito`.
  - The `modifica` flow has no `pronto_da_sviluppare` state.
  - `deadlineDays('in_sviluppo') === 7` in `sito`.
  - `checklistTrigger()` is null for `modifica`.
  - `states()` keeps the order above.
- [ ] **Step 2: Run them.** Expected: FAIL.
- [ ] **Step 3: Implement** the registry to read the config and build `Workflow` objects.
- [ ] **Step 4: Run the tests.** Expected: PASS.
- [ ] **Step 5: Commit** `feat: workflow definitions`.

---

### Task 6: Projects, status history, manual transitions

**Files:**
- Create: migrations and models `Project`, `ProjectStatusChange` (table `project_status_history`), pivot `project_members`; a migration adding the `domains.project_id` foreign key; `app/Domain/Workflow/ProjectStatusService.php`, `app/Domain/Workflow/InvalidTransition.php`, `app/Domain/Clients/DomainService.php`
- Test: `tests/Feature/Workflow/ProjectStatusServiceTest.php`, `tests/Feature/Projects/ProjectModelTest.php`, `tests/Feature/Clients/DomainServiceTest.php`

**Interfaces:**
- Consumes: `WorkflowRegistry::for()`, `ProjectType`.
- Produces:
  - `Project` columns per spec §7 A, including `primary_domain_id`. `type` is cast to `ProjectType`.
  - `Project::workflow(): Workflow`, `Project::statusLabel(): string`.
  - Relations: `owner()`, `members()`, `client()`, `primaryDomain()`, `domains()`, `statusHistory()`.
  - `ProjectStatusService::change(Project $project, string $to, ?User $by): void`. This is the manual path. It throws `InvalidTransition` with an Italian message when `$to` is not in the flow, equals the current status, or is the flow's `ready()` state.
  - `ProjectStatusService::applySystem(Project $project, string $to): void`. This is the automatic path: it allows `ready()` and records `changed_by = null`.
  - Both paths record the history row, set `status_changed_at = now()`, and set `due_date = today + deadlineDays` when the new state has a deadline (otherwise `null`). Both dispatch the event `ProjectStatusChanged($project, $from, $to)`.
  - `DomainService::setPrimary(Project $project, Domain $domain): void`, in a DB transaction. It sets `primary_domain_id`, gives the chosen domain the role `principale` and the project's other domains `secondario`, and attaches the domain to the project if it was unassigned.

- [ ] **Step 1: Write the failing tests:**
  - `it('starts new projects in the initial state')`.
  - `it('allows any manual move inside the flow, also backwards')`: `in_sviluppo` → `in_preparazione` works and writes a history row with `changed_by` = user.
  - `it('refuses a manual move to the ready state')`: throws `InvalidTransition`.
  - `it('refuses a state of the other flow')`: a modifica project → `in_sviluppo` throws.
  - `it('sets a 7-day deadline when entering in_sviluppo')`: freeze time at 2026-11-10; `due_date` = 2026-11-17.
  - `it('restarts the deadline on each feedback round')`: `feedback_modifiche` → `in_attesa_feedback` → `feedback_modifiche` gives a fresh `due_date` from the second entry.
  - `it('clears the deadline in states without one')`.
  - `it('locks the project type after creation')` (Review Focus 5): updating `type` on an existing project throws `InvalidTransition` («Il tipo di progetto non si può cambiare dopo la creazione»).
  - `it('sets the primary domain and demotes the others')`: a project with domains A and B; `setPrimary($project, $b)` → `primary_domain_id == B.id`, B.role `principale`, A.role `secondario`.
  - `it('rejects a domain of another client')` (Review Focus 3): throws `ValidationException` with the message «Il dominio non appartiene al cliente di questo progetto».
  - `it('attaches an unassigned domain of the same client when set as primary')`.
- [ ] **Step 2: Run them.** Expected: FAIL.
- [ ] **Step 3: Implement.** Enforce the type lock in a `Project::updating` hook.
- [ ] **Step 4: Run the tests.** Expected: PASS.
- [ ] **Step 5: Commit** `feat: projects and state transitions`.

---

### Task 7: Requirements, materials, automatic "Pronto"

**Files:**
- Create: migrations and models `RequirementTemplate` (`label`, `flow`, `is_mandatory`, `material_kind` nullable, `sort`), `ProjectRequirement`, `Material`, `Attachment`; enums `MaterialKind` (`foto`, `testi`, `logo`, `altro`), `MaterialStatus` (`atteso`, `ricevuto`, `non_necessario`); `app/Domain/Projects/ProjectCreator.php`, `app/Domain/Projects/RequirementSync.php`, `app/Observers/MaterialObserver.php`, `app/Observers/ProjectRequirementObserver.php`; `database/seeders/TemplateSeeder.php` with the requirement rows (table in Task 11), called from `DatabaseSeeder`
- Test: `tests/Feature/Projects/ProjectCreatorTest.php`, `tests/Feature/Projects/RequirementSyncTest.php`

Note: `requirement_templates` uses `flow` (`sito` | `modifica`) instead of the ERD's `project_type`, because nuovo and rifacimento share a list. The same applies to `checklist_templates` in Task 8. It also gains `material_kind`, so that creating a project can create the matching material rows.

**Interfaces:**
- Consumes: `ProjectStatusService::applySystem()`, `Workflow::ready()`, `Workflow::initial()`.
- Produces:
  - `ProjectCreator::create(array $attributes, ?User $by): Project`. It creates the project in `initial()`, copies the active templates of the project's flow into `project_requirements` (label copied), and creates one `Material` (`atteso`, `requested_at = now()`) for each template with a `material_kind`, linked through `requirement_id`. All of this runs in one transaction.
  - `RequirementSync::evaluate(Project $project): void`:
    - in `initial()` with every mandatory requirement done → `applySystem(ready())`;
    - in `ready()` with any mandatory requirement not done → `applySystem(initial())`;
    - otherwise nothing.
  - `MaterialObserver::saved`: when `status` changes, sets the linked requirement's `is_done` (true for `ricevuto` and `non_necessario`, false for `atteso`), sets `received_at` when it becomes `ricevuto`, and fills `done_at`/`done_by` with the authenticated user or `null`.
  - `ProjectRequirementObserver::saved`: when `is_done` changes → `RequirementSync::evaluate()`.
  - `Attachment` is polymorphic (`attachable_type`, `attachable_id`, `path`, `filename`, `uploaded_by`), with a `Material::attachments()` morphMany. Files are stored on the `local` (private) disk under `attachments/{project_id}/`.

- [ ] **Step 1: Write the failing tests:**
  - `it('copies the sito requirements and creates the three materials')`: after `TemplateSeeder` runs, a `nuovo` project gets 6 requirements and 3 materials (foto, testi, logo).
  - `it('uses the modifica list for modifica projects')`: 3 requirements and 1 material (`altro`).
  - `it('does not change existing projects when a template changes')`.
  - `it('moves to Pronto when the last mandatory requirement is done')`: history row with `changed_by` null.
  - `it('ticks the requirement when material is received')`, and the same for `non_necessario`.
  - `it('goes back to In preparazione when a material returns to atteso while Pronto')`.
  - `it('ignores non-mandatory requirements')`.
  - `it('does nothing outside the initial and ready states')`: unticking during `in_sviluppo` keeps the state.
- [ ] **Step 2: Run them.** Expected: FAIL.
- [ ] **Step 3: Implement.**
- [ ] **Step 4: Run the tests.** Expected: PASS.
- [ ] **Step 5: Commit** `feat: requirements, materials and automatic ready state`.

---

### Task 8: Checklists and to-dos

**Files:**
- Create: migrations and models `ChecklistTemplate` (`name`, `flow`, `trigger_status`), `ChecklistTemplateItem`, `Task`; `app/Domain/Checklists/ChecklistGenerator.php`, a listener on `ProjectStatusChanged`
- Modify: `database/seeders/TemplateSeeder.php` (add the "Messa online" checklist, list in Task 11)
- Test: `tests/Feature/Checklists/ChecklistGeneratorTest.php`, `tests/Feature/Tasks/TaskTest.php`

**Interfaces:**
- Consumes: event `ProjectStatusChanged`.
- Produces:
  - `ChecklistGenerator::generate(Project $project, string $status): int` returns the number of tasks created. For each template with `flow = project flow` and `trigger_status = $status`, it creates one `Task` per item (title copied, `template_item_id` set, `assignee_id = project owner`). It skips any item that already has a task for this project.
  - `Task` scopes: `open()`, `completed()`, `assignedTo(User)`. `Task::complete(): void` and `Task::reopen(): void` set or clear `completed_at`.

- [ ] **Step 1: Write the failing tests:**
  - `it('generates the 17 go-live tasks when entering in_pubblicazione')`.
  - `it('does not duplicate the checklist when re-entering the state')` (Review Focus 4): go in, back to `feedback_modifiche`, in again → still 17.
  - `it('generates nothing for modifica projects')`.
  - `it('keeps manual to-dos with no template item')`.
  - `it('completes and reopens a task')`.
- [ ] **Step 2: Run them.** Expected: FAIL.
- [ ] **Step 3: Implement.**
- [ ] **Step 4: Run the tests.** Expected: PASS.
- [ ] **Step 5: Commit** `feat: checklists and to-dos`.

---

### Task 9: Comments and activity log

**Files:**
- Create: migration and model `Comment` (`commentable_type`, `commentable_id`, `user_id`, `body`); publish the activity log migration
- Modify: `Client`, `Project`, `Task`, `Material`, `ProjectRequirement`, `Domain` with the `LogsActivity` trait
- Test: `tests/Feature/CommentsTest.php`, `tests/Feature/ActivityLogTest.php`

**Interfaces:**
- Produces:
  - `Project::comments()` and `Task::comments()` (morphMany, newest first).
  - `Project::timeline(): Collection`. It returns activity for the project and its requirements, materials and tasks, merged with status changes, newest first. Each item is an `activity_log` row or a `ProjectStatusChange`.

- [ ] **Step 1: Write the failing tests:**
  - A comment saved on a project and on a task.
  - Updating a project's `owner_id` writes an activity row with causer = the authenticated user.
  - `timeline()` includes a status change and a ticked requirement, in date order.
- [ ] **Step 2: Run them.** Expected: FAIL.
- [ ] **Step 3: Implement.** Log only changed attributes (`logOnlyDirty`), with Italian event descriptions.
- [ ] **Step 4: Run the tests.** Expected: PASS.
- [ ] **Step 5: Commit** `feat: comments and activity log`.

---

### Task 10: Daily reminders

**Files:**
- Create: `app/Domain/Reminders/ReminderService.php`, `app/Console/Commands/SendDailyReminders.php` (signature `beddy:reminders`), notifications `ProjectReadyReminder`, `DeadlineReminder`, `MaterialOverdueReminder`
- Modify: `routes/console.php` (schedule)
- Test: `tests/Feature/Reminders/ReminderServiceTest.php`

**Interfaces:**
- Consumes: `Workflow::ready()`, `deadlineDays()`, `deadlineMessage()`, `readyMessage()`.
- Produces:
  - `ReminderService::run(CarbonImmutable $today): int` returns the number of notifications sent. All three notifications use the `database` channel only, in Filament's database-notification format, with a link to the project detail page.
  - For each project with an owner, not in a final state:
    - status == `ready()` → `ProjectReadyReminder` (message `readyMessage()` with `:project`);
    - the status has a deadline and `$today >= due_date` → `DeadlineReminder` with `deadlineMessage()`;
    - one `MaterialOverdueReminder` per project when any material is `atteso` with `requested_at <= $today - 7 days`, listing the kinds («Materiale atteso da più di 7 giorni: foto, logo — Hotel Rossi»).
  - Each reminder is sent at most once per project, type and day. Before sending, check `notifications` for the same `type`, `data->project_id` and `created_at` date.
  - Schedule: `Schedule::command('beddy:reminders')->dailyAt('09:00')->timezone('Europe/Rome')`.

- [ ] **Step 1: Write the failing tests** (freeze time):
  - `it('reminds the owner every day while Pronto')`: run on 3 consecutive days → 3 notifications.
  - `it('sends the deadline reminder from day 7 and repeats daily')`: entered `in_sviluppo` on 2026-11-10; nothing on 11-16, one on 11-17, one on 11-18.
  - `it('stops after the status changes')`.
  - `it('warns about material waiting more than 7 days')`, and stops once the material is `ricevuto`.
  - `it('does not send twice on the same day')` (Review Focus 1): run twice on 11-17 → one notification.
  - `it('skips projects without owner')` (Review Focus 2): no exception, 0 sent.
  - `it('skips projects in a final state')`.
  - `it('schedules the command at 09:00')`: assert on the `Schedule` events list.
- [ ] **Step 2: Run them.** Expected: FAIL.
- [ ] **Step 3: Implement.**
- [ ] **Step 4: Run the tests.** Expected: PASS.
- [ ] **Step 5: Commit** `feat: daily reminders`.

---

### Task 11: Standard templates and demo data

The template values below are the reference for `TemplateSeeder`, which Task 7 creates and Task 8 extends. This task adds the idempotency test and the demo data.

**Files:**
- Create: `database/seeders/DemoSeeder.php`, model factories for every model still missing
- Modify: `database/seeders/DatabaseSeeder.php`, `database/seeders/TemplateSeeder.php` if needed
- Test: `tests/Feature/Seeders/SeederTest.php`

**Interfaces:**
- Produces:
  - `TemplateSeeder` is idempotent (`updateOrCreate` on label/title).
  - `DemoSeeder` runs only when `app()->environment('local')`.
  - Factories: `ProjectFactory` uses `ProjectCreator`, so requirements and materials exist.

Values from spec §4:

| flow | requirement | mandatory | material_kind |
|---|---|---|---|
| sito | Informazioni preliminari complete (contatti, acconto, dominio, hosting, assistenza) | yes | – |
| sito | Cliente contattato | yes | – |
| sito | Struttura sito inviata | yes | – |
| sito | Foto ricevute | yes | foto |
| sito | Testi ricevuti | yes | testi |
| sito | Logo ricevuto | yes | logo |
| modifica | Richiesta del cliente chiara e registrata | yes | – |
| modifica | Cliente contattato | yes | – |
| modifica | Materiale ricevuto | yes | altro |

Checklist "Messa online" (flow `sito`, trigger `in_pubblicazione`), in this order: Responsive, Traduzioni, Robots, Sitemap, Search Console, Analytics, Recaptcha, Wordfence, Cache, Disabilitare sitemap SEO Yoast, Cambio password, Rimozione plugin, Codice WPML, Contact form, 404, Google Business, Redirect vecchio sito.

Demo data (fictional):
- Users: "Utente Demo" (`demo@example.test`, password `password`, written in the README for local use only) and "Collega Demo".
- About 6 clients and 8 projects spread over both flows and all states. Include "Hotel Rossi" (nuovo, `in_preparazione`, 4/6 requirements done, foto `atteso` since 9 days ago).
- At least one project in `in_sviluppo` past its deadline, one with no owner, and one domain set with principale + secondario.

- [ ] **Step 1: Write the failing tests.** After `TemplateSeeder` runs twice, there are 6 + 3 requirement templates and 17 checklist items. `DemoSeeder` creates "Hotel Rossi" with 4 of 6 requirements done.
- [ ] **Step 2: Run them.** Expected: FAIL.
- [ ] **Step 3: Implement.**
- [ ] **Step 4: Run the tests**, then `php artisan migrate:fresh --seed` on MySQL. Expected: PASS, no errors.
- [ ] **Step 5: Commit** `feat: templates and demo data`.

---

### Task 12: Data-entry screens (Filament resources)

**Files:**
- Create:
  - `ClientResource` with relation managers Contatti, Domini, Progetti;
  - `ProjectResource` with relation managers Requisiti, Materiale (with file upload to `Attachment`), To-do, Domini, Commenti;
  - `RequirementTemplateResource`, `ChecklistTemplateResource` (with an items repeater, under the navigation group «Impostazioni»).
- Test: `tests/Feature/Filament/ClientResourceTest.php`, `tests/Feature/Filament/ProjectResourceTest.php`

**Interfaces:**
- Consumes: `ProjectCreator::create()` (the project create page must use it), `ProjectStatusService::change()` (status action), `DomainService::setPrimary()` (a «Rendi principale» action on the Domini relation), `InviteUser`.
- Produces: the resource route names Filament generates. `ProjectResource` has a header action «Cambia stato» that lists `workflow()->states()` minus `ready()` and the current status, and shows `InvalidTransition` messages as a danger notification.

- [ ] **Step 1: Write the failing Livewire tests:**
  - Create a client with a contact and a domain.
  - Create a `nuovo` project from the form → 6 requirements exist.
  - The «Cambia stato» action moves to `in_sviluppo`.
  - The `type` field is disabled on edit.
  - Marking material as `ricevuto` ticks its requirement.
- [ ] **Step 2: Run them.** Expected: FAIL.
- [ ] **Step 3: Implement.** Use Italian labels throughout. The project status column shows `statusLabel()`. The project form has a responsible user (`owner_id`), a members multi-select (`project_members`) and a due-date field that users can also edit by hand.
- [ ] **Step 4: Run the tests.** Expected: PASS.
- [ ] **Step 5: Commit** `feat: data-entry screens`.

---

### Task 13: "Progetti" page (home, faithful to the demo)

**Files:**
- Create: `app/Filament/Pages/ProjectsBoard.php` (slug `/`, navigation label «Progetti», sort 1), `resources/views/filament/pages/projects-board.blade.php`, `resources/views/filament/components/project-card.blade.php`, `resources/views/filament/components/requirement-ticks.blade.php`
- Test: `tests/Feature/Filament/ProjectsBoardTest.php`

**Interfaces:**
- Consumes: `Project::workflow()`, `statusLabel()`, the relations.
- Produces:
  - Livewire properties `string $view = 'grid'` (`grid` | `kanban` | `list`), `?string $status = null`, `?int $owner = null`, `?string $type = null`, `string $search = ''`. They are kept in the query string.
  - `requirement-ticks` component props: `Project $project`. It renders one tick per requirement (done or not), used by both card and detail.

Layout and look copied from section `#proj` of `docs/design/demo-grafica-v2.html`:
- Counters per state double as filters. States with the same label are grouped across flows; the two "Pronto da…" states are separate.
- Quick chips: «Assegnati a me», «In scadenza», «Materiale in ritardo».
- A Grid / Kanban / List toggle and an «Aggiungi progetto» button.
- Cards show client, project name, type, status pill, owner avatar (or «Nessun responsabile»), due date (red when past) and requirement ticks.
- Kanban has one column per state of the selected flow; it defaults to `sito` and has a flow switch.

- [ ] **Step 1: Write the failing tests:**
  - The page renders with demo-like data.
  - Filtering `status=in_sviluppo` shows only those projects.
  - The «Assegnati a me» chip filters by owner.
  - `view=kanban` renders the state columns of flow `sito`.
  - A project without owner renders «Nessun responsabile» (Review Focus 2).
  - Search matches the client name.
- [ ] **Step 2: Run them.** Expected: FAIL.
- [ ] **Step 3: Implement**, comparing against the demo in the browser side by side.
- [ ] **Step 4: Run the tests**, then open `http://beddy-web-agency.test` (Laragon) and compare visually with the demo. Expected: tests PASS, and the layout matches the demo.
- [ ] **Step 5: Commit** `feat: projects board`.

---

### Task 14: "Dettaglio progetto" page (faithful to the demo)

**Files:**
- Create: `app/Filament/Pages/ProjectDetail.php` (route `progetti/{project}`), `resources/views/filament/pages/project-detail.blade.php`, `resources/views/filament/components/status-stepper.blade.php`
- Modify: project cards and the resource «Visualizza» action link here
- Test: `tests/Feature/Filament/ProjectDetailTest.php`

**Interfaces:**
- Consumes: `ProjectStatusService::change()`, `Task::complete()`, `Project::timeline()`, `Project::comments()`, the `requirement-ticks` component.
- Produces: Livewire actions `changeStatus(string $to)`, `toggleRequirement(int $id)`, `setMaterialStatus(int $id, string $status)`, `toggleTask(int $id)`, `addTask(string $title, ?int $assigneeId, ?string $dueDate)`, `addComment(string $body)`.

Layout copied from section `#det` of the demo:
- Header with cover, client, type, owner and members, and due date.
- A status stepper over the project's flow states, with the current one highlighted; clicking a state calls `changeStatus` (the ready state is not clickable).
- Requisiti with ticks, and Materiale with status buttons and file count.
- To-do tabs (Aperti / Completati); in `in_pubblicazione`, the checklist tasks are grouped under «Messa online».
- Discussione (comments), client box with primary contact and primary domain, and the activity timeline.

- [ ] **Step 1: Write the failing tests:**
  - It renders the stepper with the 6 `modifica` states for a modifica project.
  - `changeStatus('in_sviluppo')` works.
  - `changeStatus('pronto_da_sviluppare')` shows the Italian error and keeps the state.
  - Ticking the last requirement shows «Pronto da sviluppare».
  - `addComment` appears in the discussion.
  - `addTask` assigns a user.
- [ ] **Step 2: Run them.** Expected: FAIL.
- [ ] **Step 3: Implement.**
- [ ] **Step 4: Run the tests** and compare with the demo in the browser. Expected: PASS, matching layout.
- [ ] **Step 5: Commit** `feat: project detail page`.

---

### Task 15: "Clienti" page (faithful to the demo)

**Files:**
- Create: `app/Filament/Pages/ClientsIndex.php` (slug `clienti`, navigation label «Clienti», sort 2), `resources/views/filament/pages/clients-index.blade.php`
- Test: `tests/Feature/Filament/ClientsIndexTest.php`

**Interfaces:**
- Consumes: `Client` relations.
- Produces: Livewire property `string $search = ''`. Each client row links to the `ClientResource` view page and to its projects on the board (`/?search=<client name>`).

Layout copied from section `#cli` of the demo. Each row shows the client, primary contact, number of projects by status, primary domains, assistance yes/no, hosting and the origin tag (Asana / Manuale), plus an «Aggiungi cliente» button.

- [ ] **Step 1: Write the failing tests:** it renders; search by name or by domain; it shows the count of active projects.
- [ ] **Step 2: Run them.** Expected: FAIL.
- [ ] **Step 3: Implement.**
- [ ] **Step 4: Run the tests** and do the visual check. Expected: PASS.
- [ ] **Step 5: Commit** `feat: clients page`.

---

### Task 16: Deploy guide and v1.0

**Files:**
- Create: `docs/DEPLOY.md`
- Modify: `README.md` (local setup with Laragon, demo login, how to run the tests)

**Interfaces:** none.

- [ ] **Step 1: Write `docs/DEPLOY.md`** for the company server. It covers:
  - PHP 8.4 extensions required;
  - `git clone` of the tagged version, `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build` (run locally or on the server);
  - `.env` keys, including the company mail server's SMTP settings;
  - `php artisan migrate --force` and `php artisan db:seed --class=TemplateSeeder --force`;
  - the storage link and permissions;
  - the cron line `* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1`;
  - creating the first user with `php artisan beddy:invite "Nome" email`, a small command wrapping `InviteUser`. Add it here, with a test that it creates the user.
- [ ] **Step 2: Run the full test suite.** `php artisan test`. Expected: all PASS.
- [ ] **Step 3: Commit** `docs: deploy guide`. Tag `v1.0` only after the user has tried the app locally and says it is ready. Tagging and the actual deploy are done with the user, never automatically.
