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

Die Website ist für einen ganz normalen Webspace gebaut (z. B. bei World4You, easyname, all-inkl, Hetzner Webhosting). Es braucht keine Datenbank, keinen Server und kein Docker – nur FTP-Zugang.

### Voraussetzungen

- Webspace mit **PHP 8.1 oder neuer** (im Kundenmenü des Anbieters einstellbar)
- Apache-Webserver, der `.htaccess`-Dateien liest (Standard bei fast allen Anbietern; für nginx siehe unten)
- Empfohlen: PHP-Erweiterung **GD** (für das automatische Verkleinern von Fotos). Ohne GD werden Fotos unverändert gespeichert.
- **HTTPS** für die Domain (bei den meisten Anbietern ein Klick, z. B. „Let's Encrypt aktivieren“)
- Ein FTP-Programm, z. B. [FileZilla](https://filezilla-project.org/)

### Dateien besorgen

Auf GitHub im Repository auf **Code → Download ZIP** klicken und das ZIP auf dem eigenen Rechner entpacken.
Die Dateien `README.md`, `ANLEITUNG.md` und `.gitignore` werden auf dem Webspace nicht gebraucht, stören aber auch nicht.

### Erstes Einrichten

1. **Versteckte Dateien sichtbar machen.** In FileZilla: *Server → Anzeigen versteckter Dateien erzwingen*. Sonst fehlen die `.htaccess`-Dateien und `.user.ini`, und die Schutzregeln wirken nicht.
2. **Hochladen.** Den gesamten Inhalt des entpackten Ordners in das Hauptverzeichnis der Domain hochladen (oft `html`, `httpdocs` oder `public_html`). Eine Unterseite wie `https://…/kindergarten/` funktioniert genauso.
3. **Schreibrechte vergeben.** In FileZilla mit Rechtsklick auf die Ordner `data` und `uploads` → *Dateiberechtigungen* → `755` eintragen, Haken bei *In Unterverzeichnisse einsteigen*. Falls die Verwaltung danach meldet, dass sie nicht schreiben darf: `775` probieren, notfalls `777` (nur für diese beiden Ordner).
4. **Passwort festlegen.** `https://<Ihre Domain>/admin` aufrufen und ein Passwort festlegen (mindestens 10 Zeichen). Beim ersten Aufruf übernimmt die Website die Startinhalte automatisch.
5. **Schutz prüfen.** Diese Adressen müssen eine Fehlermeldung (403 „Forbidden“) zeigen, nicht den Inhalt:
   - `https://<Ihre Domain>/data/content.json`
   - `https://<Ihre Domain>/inc/functions.php`

   Wird der Inhalt angezeigt, fehlen die `.htaccess`-Dateien (Schritt 1) oder der Anbieter verwendet nginx (siehe unten).
6. **Fotos testen.** In der Verwaltung ein Foto hochladen. Klappt das bei großen Handyfotos nicht, im Kundenmenü des Anbieters `upload_max_filesize` auf mindestens 16 MB und `memory_limit` auf 256 MB setzen. Die mitgelieferten `.htaccess` und `.user.ini` versuchen das bereits automatisch, nicht jeder Anbieter erlaubt es aber.

Die mitgelieferten `.htaccess`-Dateien sperren den Zugriff auf `data/` und `inc/` und verhindern, dass im Ordner `uploads/` Programme ausgeführt werden.

### Was wo liegt

Alle Inhalte, die über die Verwaltung entstehen, stecken in zwei Ordnern:
- `data/` – Texte (`content.json`), Passwort (`config.php`), automatische Sicherungen (`backups/`), Anmeldungen (`sessions/`)
- `uploads/` – Fotos

Der restliche Code enthält keine Inhalte. Die Vorlage für den allerersten Start liegt getrennt in `inc/startinhalt.json`.

### Neue Version einspielen (z. B. nach Änderungen am Design)

1. Neues ZIP herunterladen und entpacken.
2. Alles per FTP hochladen und vorhandene Dateien **überschreiben**.

Inhalte, Fotos und Passwort bleiben dabei erhalten: In den Ordnern `data/` und `uploads/` liegen im ZIP nur Schutzdateien, die eigentlichen Inhalte entstehen erst auf dem Webspace.
Vorsichtshalber trotzdem vorher eine Datensicherung machen (siehe unten). Und **nie** die Ordner `data` oder `uploads` auf dem Webspace löschen.

### Datensicherung

Regelmäßig (z. B. einmal im Monat und vor jedem Update) die Ordner `data/` und `uploads/` per FTP auf den eigenen Rechner herunterladen.

Zusätzlich legt die Website bei jedem Speichern automatisch eine Sicherung der Texte an (die letzten 30). Diese lassen sich in der Verwaltung wiederherstellen.

Wiederherstellen einer FTP-Sicherung: die beiden Ordner wieder hochladen und vorhandene Dateien überschreiben.

### Passwort zurücksetzen

Die Datei `data/config.php` per FTP löschen.
Beim nächsten Aufruf von `/admin` kann ein neues Passwort festgelegt werden. Die Inhalte bleiben erhalten.

### Sonderfall nginx

nginx liest keine `.htaccess`-Dateien. Läuft der Webspace mit nginx, muss der Anbieter (oder wer den Server betreut) diese Regeln in die Serverkonfiguration übernehmen:

```nginx
location ~ ^/(data|inc)/ { deny all; }
location ~ ^/uploads/.*\.(php|phtml|phar)$ { deny all; }
location ~ /\. { deny all; }
client_max_body_size 20m;
```

### Umzug von der alten Joomla-Seite

1. Fotos von der alten Seite abspeichern, **bevor** sie abgeschaltet wird (Teamfoto, Waldtage, historische Aufnahmen, Diashow).
2. Neue Seite einrichten und die Fotos über die Verwaltung an den passenden Stellen hochladen.
3. **Impressum und Datenschutz** mit der Gemeinde vervollständigen. Die Platzhalter sind mit `[bitte ergänzen]` markiert.
4. Erst dann die Domain auf die neue Seite umstellen.

### Aufbau der Dateien

```
index.php            Öffentliche Website
admin/               Verwaltung (Anmeldung, Bearbeiten, Foto-Upload)
inc/functions.php    Gemeinsame Funktionen und Aufbau der Bereiche
inc/startinhalt.json Startinhalte für den allerersten Aufruf
assets/              Gestaltung, Schriften, Skripte
data/                Inhalte, Passwort, Sicherungen (entsteht auf dem Webspace)
uploads/             Hochgeladene Fotos
.htaccess, .user.ini Schutzregeln und PHP-Einstellungen
```

Neue Felder oder Bereiche werden in `inc/functions.php` in der Funktion `schema()` ergänzt. Die Verwaltung baut ihre Formulare daraus automatisch.

### Schriften

Grandstander und Atkinson Hyperlegible, beide unter der SIL Open Font License (siehe `assets/fonts/`). Sie werden vom eigenen Server geladen, es besteht keine Verbindung zu Google.
