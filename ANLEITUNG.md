# Website Kindergarten Schweiggers – Anleitung

Teil A ist für alle, die Inhalte bearbeiten.
Teil B ist für die Person, die die Website einmalig einrichtet.

---

## Teil A: Inhalte bearbeiten

### Anmelden

1. Öffnen Sie die Website und scrollen Sie ganz nach unten.
2. Klicken Sie rechts unten auf **„Anmelden“** (oder hängen Sie `/admin` an die Adresse an).
3. Geben Sie das Passwort ein.

Nach vier Stunden ohne Aktivität werden Sie automatisch abgemeldet.
Nach fünf falschen Passwörtern ist die Anmeldung für 10 Minuten gesperrt.

### So funktioniert es immer

1. Wählen Sie eine Kachel, z. B. **„Aktuelles“**.
2. Ändern Sie, was Sie möchten.
3. Klicken Sie unten rechts auf **„Änderungen speichern“**.

Erst nach dem Speichern ist die Änderung auf der Website sichtbar.
Solange nicht gespeichert ist, steht unten „Sie haben ungespeicherte Änderungen“.

### Häufige Aufgaben

**Eine Neuigkeit schreiben**
Aktuelles → „Neuen Beitrag hinzufügen“ → Überschrift und Text eintragen → optional Foto auswählen → speichern.
Das Datum ist automatisch das heutige.

**Einen Termin eintragen**
Termine → „Neuen Termin hinzufügen“ → Datum, „Was?“ und z. B. die Uhrzeit eintragen → speichern.
Vergangene Termine verschwinden von selbst von der Website. Sie müssen sie nicht löschen.

**Kurzfristiger Hinweis (z. B. „Morgen geschlossen“)**
Startseite & Kontakt → Feld „Wichtiger Hinweis“ ausfüllen → speichern.
Der Text erscheint als gelber Balken ganz oben.
**Wichtig:** Danach wieder leeren und speichern, sonst bleibt der Balken stehen.

**Fotos in die Galerie stellen**
Bilder → „Foto hinzufügen“ → „Foto auswählen“ → warten bis „Fertig“ erscheint → speichern.
Große Handyfotos sind kein Problem, sie werden automatisch verkleinert.
Bitte nur Fotos verwenden, für die die Eltern zugestimmt haben.

**Team ändern**
Gruppen & Team → Gruppe aufklappen → im Feld „Personen“ steht jede Person in einer eigenen Zeile:

```
Eva Beck – Elementarpädagogin
Barbara Schweitzer – Kinderbetreuerin
```

**Tarife ändern**
Öffnungszeiten & Beiträge → Tarif aufklappen → Preis ändern → speichern.

### Einträge aufklappen, verschieben, löschen

- Einen Eintrag öffnen Sie durch Klick auf seine Überschrift.
- **„Nach oben“ / „Nach unten“** ändert die Reihenfolge (z. B. bei Gruppen oder der Geschichte).
- **„… löschen“** entfernt den Eintrag. Endgültig weg ist er erst nach dem Speichern.

### Text gestalten

| Sie schreiben | Auf der Website |
|---|---|
| Eine leere Zeile | Neuer Absatz |
| `**Wichtig**` | **Wichtig** (fett) |
| Zeilen, die mit `- ` beginnen | Aufzählung mit Punkten |
| `https://…` oder eine E-Mail-Adresse | wird automatisch anklickbar |

### Etwas ist schiefgegangen?

Kein Problem. Bei jedem Speichern wird automatisch eine Sicherung angelegt.
Kachel **„Sicherungen & Passwort“** → beim gewünschten Zeitpunkt auf **„Wiederherstellen“** klicken.
Die letzten 30 Stände werden aufbewahrt.

### Passwort ändern

Kachel „Sicherungen & Passwort“ → unten „Passwort ändern“.
Wenn das Passwort vergessen wurde: siehe Teil B, „Passwort zurücksetzen“.

---

## Teil B: Einrichtung (einmalig)

### Voraussetzungen

- Webspace mit **PHP 8.1 oder neuer** (bei fast allen Anbietern vorhanden)
- Empfohlen: PHP-Erweiterung **GD** (für das automatische Verkleinern von Fotos). Ohne GD werden Fotos unverändert gespeichert.
- Keine Datenbank nötig.

### Variante 1: Normaler Webspace (Apache)

1. Den Inhalt dieses Ordners per FTP in das Hauptverzeichnis der Domain hochladen (inklusive der versteckten `.htaccess`-Dateien).
2. Schreibrechte für den Webserver auf die Ordner `data/` und `uploads/` vergeben (meist `775` oder `755`, je nach Anbieter).
3. `https://…/admin` aufrufen und ein Passwort festlegen (mindestens 10 Zeichen).
4. Unbedingt **HTTPS** verwenden, damit das Passwort verschlüsselt übertragen wird.

Die mitgelieferten `.htaccess`-Dateien sperren den Zugriff auf `data/` und `inc/` und verhindern, dass im Ordner `uploads/` Programme ausgeführt werden.

**Prüfen Sie nach der Einrichtung:** `https://…/data/content.json` muss eine Fehlermeldung (403) liefern, nicht den Inhalt.

### Variante 2: nginx

nginx liest keine `.htaccess`-Dateien. Diese Regeln in die Serverkonfiguration übernehmen:

```nginx
location ~ ^/(data|inc)/ { deny all; }
location ~ ^/uploads/.*\.(php|phtml|phar)$ { deny all; }
location ~ /\. { deny all; }
client_max_body_size 20m;
```

### Variante 3: Eigener Server (VPS) mit Docker und Caddy (empfohlen)

Das Projekt bringt alles für den Betrieb in Docker mit: `Dockerfile`, `docker-compose.yaml`, einen Healthcheck (`/health.php`) und ein Startskript, das leere Volumes beim ersten Start automatisch mit den Startinhalten befüllt. Caddy läuft davor als Reverse Proxy und kümmert sich um HTTPS.

Im Container liegen Inhalte, Passwort, Sicherungen und Anmeldungen in `/var/www/data`, also **außerhalb** des Web-Verzeichnisses. Sie sind damit grundsätzlich nicht über den Browser abrufbar. Die Sperren für `inc/` und `uploads/` stehen fest in der Apache-Konfiguration des Images (`docker/apache.conf`).

**Voraussetzungen auf dem Server**
- Docker mit dem Compose-Plugin (`docker compose version` muss funktionieren)
- Caddy (z. B. als Systemdienst aus dem offiziellen Paket)
- Ports 80 und 443 in der Firewall offen
- Die Domain zeigt per DNS (A-Eintrag) auf die IP-Adresse des Servers. Bei einer Gemeinde-Domain (`.gv.at`) muss das die Stelle einrichten, die die Domain der Gemeinde verwaltet.

**1. Code auf den Server holen**

```bash
git clone https://github.com/breichr/kiga-schweiggers.git /opt/kiga
cd /opt/kiga
```

Bei einem privaten Repository vorher einen Deploy-Key (nur Lesezugriff) in GitHub hinterlegen und über die SSH-Adresse klonen.
Die mitgelieferte `.gitignore` sorgt dafür, dass Passwort, Sicherungen und Fotos nie ins Repository gelangen.

**2. Port nur für Caddy freigeben**

Die `docker-compose.yaml` öffnet keinen Port nach außen. Daneben die Datei `/opt/kiga/docker-compose.override.yml` anlegen (sie bleibt nur auf dem Server und wird von Docker Compose automatisch mitgelesen):

```yaml
services:
  web:
    ports:
      - "127.0.0.1:8080:80"   # nur lokal erreichbar, nicht aus dem Internet
```

**3. Starten**

```bash
docker compose up -d --build
docker compose ps                          # Status sollte „healthy“ zeigen
curl -I http://127.0.0.1:8080/health.php   # sollte „200 OK“ liefern
```

Docker legt dabei die beiden Volumes `kiga_kiga-data` und `kiga_kiga-uploads` an (der Teil vor dem Unterstrich ist der Ordnername `kiga`).

**4. Caddy einrichten**

In `/etc/caddy/Caddyfile` (Domain anpassen):

```caddy
kindergarten.schweiggers.gv.at {
    encode zstd gzip
    reverse_proxy 127.0.0.1:8080
}
```

```bash
sudo systemctl reload caddy
```

Caddy holt das HTTPS-Zertifikat automatisch und leitet `http://` auf `https://` um. Die Website erkennt HTTPS hinter dem Proxy (über den Header `X-Forwarded-Proto`) und setzt das Anmelde-Cookie entsprechend sicher. Die echte Besucher-IP kommt ebenfalls an.

*Falls Caddy selbst in einem Docker-Container läuft:* Schritt 2 weglassen, beide Container in ein gemeinsames Docker-Netzwerk hängen und im Caddyfile `reverse_proxy web:80` statt `127.0.0.1:8080` eintragen.

**5. Einrichten**

- `https://<Ihre Domain>/admin` aufrufen und das Passwort festlegen (mindestens 10 Zeichen).
- Kontrolle: `https://<Ihre Domain>/inc/functions.php` muss „Forbidden“ zeigen.

**Updates einspielen**

Nach Änderungen am Code (z. B. am Design):

```bash
cd /opt/kiga
git pull
docker compose up -d --build
```

**Wichtig zu den Volumes**
- Inhalte, Fotos, Passwort und Anmeldungen liegen ausschließlich in den Volumes. Ein Update lässt sie unangetastet.
- **Niemals** `docker compose down -v` ausführen: Das `-v` löscht die Volumes und damit alle Inhalte.
- Die Datei `data/content.json` im Repository ist nur die Vorlage für den allerersten Start.

**Sicherung der Volumes**
Die Inhalte regelmäßig sichern, z. B. täglich per Cronjob auf dem Server (`crontab -e`):

```bash
30 3 * * * docker run --rm -v kiga_kiga-data:/d -v kiga_kiga-uploads:/u -v /root/kiga-backup:/b alpine tar czf /b/kiga-$(date +\%F).tar.gz -C / d u
```

Die Sicherungsdateien zusätzlich vom Server wegkopieren (z. B. auf einen anderen Rechner oder Speicherplatz).

Wiederherstellen einer Sicherung:

```bash
cd /opt/kiga && docker compose stop
docker run --rm -v kiga_kiga-data:/d -v kiga_kiga-uploads:/u -v /root/kiga-backup:/b alpine \
  tar xzf /b/kiga-JJJJ-MM-TT.tar.gz -C /
docker compose start
```

**Passwort zurücksetzen**

```bash
cd /opt/kiga
docker compose exec web rm -f /var/www/data/config.php /var/www/data/.login-versuche
```

Danach `/admin` aufrufen und ein neues Passwort festlegen. Die Inhalte bleiben erhalten.

*Hinweis:* Das Image läuft genauso in Coolify oder anderen Docker-Plattformen (Build Pack „Docker Compose“, Domain beim Dienst `web` auf Port 80).

### Umzug von der alten Joomla-Seite

1. Fotos von der alten Seite abspeichern, **bevor** sie abgeschaltet wird (Teamfoto, Waldtage, historische Aufnahmen, Diashow).
2. Neue Seite einrichten und die Fotos über die Verwaltung an den passenden Stellen hochladen.
3. **Impressum und Datenschutz** mit der Gemeinde vervollständigen. Die Platzhalter sind mit `[bitte ergänzen]` markiert.
4. Erst dann die Domain auf die neue Seite umstellen.

### Passwort zurücksetzen

Die Datei `data/config.php` per FTP löschen (beim eigenen Server: siehe Variante 3).
Beim nächsten Aufruf von `/admin` kann ein neues Passwort festgelegt werden. Die Inhalte bleiben erhalten.

### Datensicherung

Alle Inhalte stecken in zwei Ordnern:
- `data/` – Texte, Einstellungen, automatische Sicherungen (bei Docker: Volume unter `/var/www/data`)
- `uploads/` – Fotos

Diese beiden Ordner regelmäßig zusätzlich extern sichern.

### Aufbau der Dateien

```
index.php          Öffentliche Website
health.php         Statusprüfung für Docker
Dockerfile         Image-Bauplan
docker-compose.yaml  Startet den Container (docker compose)
docker/            Startskript, Apache- und PHP-Einstellungen für den Container
admin/             Verwaltung (Anmeldung, Bearbeiten, Foto-Upload)
inc/functions.php  Gemeinsame Funktionen und Aufbau der Bereiche
assets/            Gestaltung, Schriften, Skripte
data/content.json  Alle Inhalte
uploads/           Hochgeladene Fotos
```

Neue Felder oder Bereiche werden in `inc/functions.php` in der Funktion `schema()` ergänzt. Die Verwaltung baut ihre Formulare daraus automatisch.

### Schriften

Grandstander und Atkinson Hyperlegible, beide unter der SIL Open Font License (siehe `assets/fonts/`). Sie werden vom eigenen Server geladen, es besteht keine Verbindung zu Google.
