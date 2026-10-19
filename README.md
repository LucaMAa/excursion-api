# excursion-api

REST API per escursioni e prenotazioni, basata su Symfony, PostgreSQL e JWT.

## Avvio locale con Docker

Prerequisiti: Docker con Docker Compose e Git.

```sh
git clone <repository-url>
cd excursion-api
docker compose up -d --build
docker compose exec php composer install
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
docker compose exec php php bin/console lexik:jwt:generate-keypair --skip-if-exists
```

La configurazione locale di JWT è in `.env` con una passphrase di sviluppo.
Per un ambiente condiviso o di produzione, imposta `JWT_PASSPHRASE` in
`.env.local` o nelle variabili d'ambiente del container con un valore forte e
segreto **prima** di generare le chiavi; non condividere né committare la
chiave privata o la passphrase. Le chiavi RSA vengono salvate in
`config/jwt/` e sono escluse da Git.

Crea un amministratore. Il comando chiede la password senza mostrarla; se
l'email esiste già, assegna `ROLE_ADMIN` e aggiorna la password:

```sh
docker compose exec php php bin/console app:create-admin admin@example.com
```

Controlla che i container siano avviati:

```sh
docker compose ps
```

Base URL dell'API: `http://localhost:8080`.

## OpenAPI e Swagger UI

Con i container avviati, apri:

- Swagger UI: <http://localhost:8080/api/doc>
- Spec OpenAPI JSON: <http://localhost:8080/api/doc.json>

La UI descrive request body, risposte e autenticazione JWT. Per provare le
rotte protette, usa il pulsante **Authorize** e incolla il token JWT (senza
aggiungere manualmente `Bearer `: Swagger applica lo schema Bearer).

## Test con Postman

Importa in Postman la collection
[`postman/Excursion API.postman_collection.json`](./postman/Excursion%20API.postman_collection.json).
Imposta le variabili della collection:

| Variabile | Valore |
| --- | --- |
| `baseUrl` | `http://localhost:8080` |
| `userEmail` | email nuova per la registrazione |
| `userPassword` | password di almeno 12 caratteri |
| `adminEmail` | email usata nel comando `app:create-admin` |
| `adminPassword` | password inserita nel comando |

Esegui le richieste in questo ordine:

1. **Register user** — `POST /api/register`; crea un utente con ruolo
   `ROLE_USER` (HTTP `201`).
2. **Login user** — `POST /api/login_check`; autentica l'utente e salva
   automaticamente il JWT nella variabile `token`.
3. **List excursions** — `GET /api/excursions`; richiede `ROLE_USER` e accetta
   `limit` e `offset` opzionali.
4. **Create excursion (admin)** — prima modifica `userEmail`/`userPassword`
   con le credenziali admin oppure esegui **Login admin**; poi esegui questa
   richiesta autenticata. La risposta salva `excursionId`.
5. **Login user** — ripristina il token dell'utente normale.
6. **Book seats** — `POST /api/excursions/{{excursionId}}/booking`; invia un
   array non vuoto di persone. Ogni email può prenotare una sola volta per
   escursione.

La collection include anche **Ping** e **Login admin**. Il login admin salva
il token nella stessa variabile `token`, che viene usata dalle richieste
autenticate successive.

Esempio di body per creare un'escursione:

```json
{
  "title": "Tour del lago",
  "description": "Escursione guidata sul lago.",
  "city": "Como",
  "country": "Italia",
  "date": "2026-11-01",
  "capacity": 20
}
```

Esempio di body per prenotare più persone:

```json
[
  {
    "customerName": "Mario Rossi",
    "customerEmail": "mario@example.com",
    "seats": 1
  },
  {
    "customerName": "Anna Bianchi",
    "customerEmail": "anna@example.com",
    "seats": 2
  }
]
```

La prenotazione restituisce HTTP `201`; `404` se l'escursione non esiste e
`409` se non ci sono posti sufficienti o un'email ha già prenotato
quell'escursione. Gli endpoint `/admin` richiedono un JWT con `ROLE_ADMIN`.

## Endpoint

| Metodo | Endpoint | Accesso |
| --- | --- | --- |
| `GET` | `/api/ping` | Pubblico |
| `POST` | `/api/register` | Pubblico |
| `POST` | `/api/login_check` | Pubblico |
| `GET` | `/api/excursions` | JWT |
| `POST` | `/api/excursions/{id}/booking` | JWT |
| `POST` | `/admin/excursions` | JWT con `ROLE_ADMIN` |

## Code quality

Esegui tutti i controlli dal container PHP:

```sh
docker compose exec php composer check
```

Sono disponibili anche gli script individuali, ad esempio
`composer phpstan`, `composer phpcs`, `composer test` e
`composer security-audit`.
