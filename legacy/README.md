# Gestionale corsi (sistema legacy)

Questo non è EventHub. È lo scheletro minimo del gestionale monolitico preesistente per un
centro corsi, usato come punto di partenza del caso di studio di migrazione del Capitolo 11
("Da monolite a API-first"). Esiste solo per rendere concreti gli esempi del capitolo: non è
codice di produzione, non ha test, non ha autenticazione, non ha alcuna API.

Gestisce due concetti accoppiati nello stesso modulo: corsi (`courses`) e iscrizioni
(`enrollments`). Ogni pagina è un singolo file PHP che mescola accesso ai dati, regola di
business (posti disponibili) e rendering HTML, senza alcuno strato intermedio: è esattamente
il tipo di accoppiamento che il capitolo userà come punto di partenza da smontare.

## Eseguire il sistema

```bash
php seed.php                       # popola alcuni corsi di esempio (una sola volta)
php -S localhost:8001 -t public    # avvia il sistema su http://localhost:8001
```

Poi visita `http://localhost:8001/courses.php`.
