# Facciata (strangler fig)

Questo non è un API Gateway completo (quello è il tema dell'Appendice B), né il gestionale
legacy, né EventHub. È il livello di routing minimo che il pattern strangler fig richiede: un
unico punto di ingresso che, per ogni richiesta, decide quale sistema la deve servire davvero,
in base a una tabella di regole in `routes.php`.

Oggi la tabella è vuota di eccezioni: ogni richiesta finisce sul gestionale legacy, perché nessuna
funzionalità è stata ancora estratta. Migrare una funzionalità, nelle prossime sezioni del
Capitolo 11, significherà aggiungere una regola qui, non riscrivere `public/index.php`.

## Eseguire la facciata

Con il gestionale legacy attivo su `http://localhost:8001` (vedi `legacy/README.md`) ed
eventualmente EventHub attivo su `http://localhost:8000`:

```bash
php -S localhost:8080 -t public public/index.php
```

`public/index.php` è passato esplicitamente come script di routing, non solo come docroot:
è l'unico modo per far sì che il server di sviluppo di PHP passi da lì *ogni* richiesta, invece
di cercare un file corrispondente e restituire 404 per qualunque percorso che non sia
`index.php` stesso.
