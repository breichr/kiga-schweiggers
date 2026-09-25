# Website Kindergarten Schweiggers

Kleine, datenbankfreie PHP-Website mit eigener Verwaltung (`/admin`).

- Hosting auf Coolify: siehe [ANLEITUNG.md](ANLEITUNG.md), „Variante 3: Coolify“
- Inhalte bearbeiten: siehe [ANLEITUNG.md](ANLEITUNG.md), Teil A

Lokal testen:

```bash
docker build -t kiga .
docker run --rm -p 8080:80 -v kiga-data:/var/www/data -v kiga-uploads:/var/www/html/uploads kiga
# danach http://localhost:8080 und http://localhost:8080/admin
```
