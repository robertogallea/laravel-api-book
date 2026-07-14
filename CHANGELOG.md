# Changelog

Mappa tra i tag di questo repository (EventHub, l'applicazione di esempio del libro *Laravel
API: dalla struttura alla produzione*) e i capitoli/fasi del libro. Solo `code/` e' versionato
qui: il testo del libro e la relativa documentazione vivono nel repository del libro.

| Tag | Capitolo/Fase | Descrizione |
|---|---|---|
| `chapter-00` | 0 - Introduzione | Nessun codice (capitolo editoriale). |
| `chapter-01-step-02` | 1, Fase 2 | Rotte REST per events/bookings, controller di base, ForceJsonResponse. |
| `chapter-01-step-03` | 1, Fase 3 | Form Request per validare la creazione/aggiornamento di eventi e prenotazioni. |
| `chapter-01-step-04` | 1, Fase 4 | Modelli Event/Booking, migrazioni, prime API Resources. |
| `chapter-01-step-05` | 1, Fase 5 | Formato di errore Problem Details e catalogo ErrorCode. |
| `chapter-01-step-06` | 1, Fase 6 | Upload dell'immagine di copertina di un evento. |
| `chapter-01` | 1 - Fondamenta di un progetto API-only | Configurazione Pint; capitolo completo. |
| `chapter-02-step-02` | 2, Fase 2 | CreateBookingAction, DTO/VO di dominio, notifier via DI; BookingController delega. |
| `chapter-02-step-06` | 2, Fase 6 | CreateBookingCommand riusa la stessa Action da un secondo entry point. |
| `chapter-02` | 2 - Clean code e architettura del dominio | Capitolo completo. |
| `chapter-03-step-03` | 3, Fase 3 | Rotte v1/v2, nuova policy di cancellazione via CancelBookingAction. |
| `chapter-03-step-04` | 3, Fase 4 | Middleware di deprecazione ed errore per versioni rimosse. |
| `chapter-03` | 3 - Versionamento delle API | Capitolo completo. |
| `chapter-04-step-02` | 4, Fase 2-4 | Sanctum (utenti finali) e Passport (partner), rate limiting. |
| `chapter-04-step-05` | 4, Fase 5 | Ruoli e Policy per l'autorizzazione granulare. |
| `chapter-04` | 4 - Sicurezza API | Test di routing/errori/versionamento/sicurezza; capitolo completo. |
| `chapter-05` | 5 - Documentazione API | Scramble (OpenAPI code-first), ProblemDetailsResponses, campi visibili per ruolo. |
| `chapter-06` | 6 - Webhooks e integrazioni esterne | Notifica webhook al partner, retry via coda, firma HMAC, doc OpenAPI del webhook. |
| `chapter-07` | 7 - Idempotenza e affidabilita | Idempotency-Key su creazione prenotazione: dedup, conflitto su payload diverso. |
| `chapter-08-step-02` | 8, Fase 2-5 | Eager loading, ricerca/filtri via scope, indici. |
| `chapter-08-step-06` | 8, Fase 6 | EventCache con invalidazione via model event. |
| `chapter-08-step-07` | 8, Fase 7 | Notifica di conferma prenotazione inviata su coda. |
| `chapter-08` | 8 - Eloquent, performance e scalabilita | Capitolo completo. |
| `chapter-09` | 9 - Testing delle API | Stati factory, rimozione scaffold ExampleTest, completamento copertura, fix del vincolo capacity su UpdateEventRequest. |
| `chapter-10-step-01` | 10, Fase 1 | Dockerfile/docker-compose con MySQL 8.4. |
| `chapter-10-step-02` | 10, Fase 2 | PaymentGatewayCredentials fallisce esplicitamente se non configurato. |
| `chapter-10-step-03` | 10, Fase 3-4 | composer quality (Pint+Pest+scramble:analyze) e pipeline CI. |
| `chapter-10` | 10 - Verso la produzione | Capitolo completo. |
| `chapter-11` | 11 - Da monolite a API-first | legacy/ e gateway/ (strangler fig), LegacyEnrollmentAdapter, MigrateLegacyEnrollmentsCommand (percorso di default del database legacy corretto: `legacy/` e' sibling di `app/`). |
| `chapter-12` | 12 - Conclusioni | Nessun codice (capitolo editoriale). |
| `chapter-13` | Appendice A - Osservabilita e monitoraggio | LogFailedRequests, HealthController. |
| `chapter-14` | Appendice B - API Gateway e architetture distribuite | Nessun codice (capitolo concettuale). |
