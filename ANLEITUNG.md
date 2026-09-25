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

### Variante 3: Coolify (empfohlen für eigenen Server)

Das Projekt bringt alles mit, was Coolify braucht: `Dockerfile`, `docker-compose.yaml`, einen Healthcheck (`/health.php`) und ein Startskript, das leere Volumes beim ersten Start automatisch mit den Startinhalten befüllt.

Im Container liegen Inhalte, Passwort, Sicherungen und Anmeldungen in `/var/www/data`, also **außerhalb** des Web-Verzeichnisses. Sie sind damit grundsätzlich nicht über den Browser abrufbar. Die Sperren für `inc/` und `uploads/` stehen fest in der Apache-Konfiguration des Images (`docker/apache.conf`).

**1. Code in ein Git-Repository legen**

```bash
cd kindergarten-schweiggers
git init && git add . && git commit -m "Website Kindergarten Schweiggers"
git remote add origin <URL Ihres Repositorys>
git push -u origin main
```

Die mitgelieferte `.gitignore` sorgt dafür, dass Passwort, Sicherungen und Fotos nie ins Repository gelangen.
Für ein privates Repository in Coolify vorher die GitHub-App oder einen Deploy-Key einrichten.

**2. In Coolify anlegen**

Projekt → *New Resource* → *Private/Public Repository* → Repository wählen.
Danach **eine** der beiden Varianten wählen:

*A) Build Pack „Docker Compose“ (am einfachsten)*
- Coolify liest `docker-compose.yaml` und legt die beiden Volumes `kiga-data` und `kiga-uploads` automatisch an.
- Coolify schlägt über `SERVICE_FQDN_WEB_80` automatisch eine Domain vor. Beim Dienst **web** die eigene Domain eintragen, z. B. `https://kindergarten.schweiggers.gv.at`.

*B) Build Pack „Dockerfile“*
- *Ports Exposes*: `80`
- Unter *Persistent Storage* zwei Volumes anlegen:
  - Ziel `/var/www/data`
  - Ziel `/var/www/html/uploads`
- Domain eintragen.

**3. Deployen und einrichten**

- *Deploy* klicken. Nach dem Start sollte der Status „healthy“ sein.
- `https://<Ihre Domain>/admin` aufrufen und das Passwort festlegen.
- Kontrolle: `https://<Ihre Domain>/inc/functions.php` muss „Forbidden“ zeigen.

**Wichtig zu den Volumes**
- Ohne Volumes gehen bei jedem neuen Deploy alle Änderungen und Fotos verloren.
- Neue Deploys (z. B. nach Änderungen am Design) lassen Inhalte, Fotos, Passwort und Anmeldungen unangetastet. Diese liegen ausschließlich in den Volumes.
- Die Datei `data/content.json` im Repository ist nur die Vorlage für den allerersten Start.

**HTTPS**
Coolify stellt das Zertifikat über seinen Proxy automatisch aus. Die Website erkennt HTTPS hinter dem Proxy und setzt das Anmelde-Cookie entsprechend sicher.
Die Domain muss per DNS (A-Eintrag) auf die IP-Adresse des Servers zeigen. Bei einer Gemeinde-Domain (`.gv.at`) muss das die Stelle einrichten, die die Domain der Gemeinde verwaltet.

**Sicherung der Volumes**
Coolify sichert automatisch nur Datenbanken, keine Volumes. Die Inhalte deshalb zusätzlich regelmäßig sichern, z. B. per Cronjob auf dem Server:

```bash
docker run --rm -v <volume-name-data>:/d -v <volume-name-uploads>:/u -v /root/kiga-backup:/b alpine \
  tar czf /b/kiga-$(date +%F).tar.gz -C / d u
```

Die genauen Volume-Namen zeigt `docker volume ls` (Coolify stellt eine Kennung voran).

**Passwort zurücksetzen in Coolify**
1. Unter *Environment Variables* `KIGA_RESET_PASSWORD` auf `1` setzen und neu starten (*Restart*).
2. `/admin` aufrufen und ein neues Passwort festlegen.
3. `KIGA_RESET_PASSWORD` wieder auf `0` setzen, sonst wird das Passwort bei jedem Neustart erneut gelöscht.

Alternativ im Terminal des Containers: `rm /var/www/data/config.php`

### Umzug von der alten Joomla-Seite

1. Fotos von der alten Seite abspeichern, **bevor** sie abgeschaltet wird (Teamfoto, Waldtage, historische Aufnahmen, Diashow).
2. Neue Seite einrichten und die Fotos über die Verwaltung an den passenden Stellen hochladen.
3. **Impressum und Datenschutz** mit der Gemeinde vervollständigen. Die Platzhalter sind mit `[bitte ergänzen]` markiert.
4. Erst dann die Domain auf die neue Seite umstellen.

### Passwort zurücksetzen

Die Datei `data/config.php` per FTP löschen (bei Coolify: siehe oben).
Beim nächsten Aufruf von `/admin` kann ein neues Passwort festgelegt werden. Die Inhalte bleiben erhalten.

### Datensicherung

Alle Inhalte stecken in zwei Ordnern:
- `data/` – Texte, Einstellungen, automatische Sicherungen (bei Coolify: Volume unter `/var/www/data`)
- `uploads/` – Fotos

Diese beiden Ordner regelmäßig zusätzlich extern sichern.

### Aufbau der Dateien

```
index.php          Öffentliche Website
health.php         Statusprüfung für Coolify/Docker
Dockerfile         Image-Bauplan
docker-compose.yaml  Für Coolify (Build Pack „Docker Compose“)
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
