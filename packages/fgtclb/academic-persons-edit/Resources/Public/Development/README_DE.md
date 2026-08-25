# Frontend-Tests für `academic-persons-edit`

Dieser Ordner enthält die isolierte Jest-/jsdom-Testumgebung für die produktiven JavaScript-Module unter `../JavaScript/frontend/`.

Die Tests laden direkt den produktiven Quellcode. Es existieren keine separaten Kopien der getesteten Funktionen. Dadurch prüfen die Tests genau die Module, die später im Browser durch TYPO3 verwendet werden.

## Schnellstart

Vom Repository aus:

```bash
cd packages/fgtclb/academic-persons-edit/Resources/Public/Development
npm install
npm test
```

Ein erfolgreicher Lauf beginnt mit:

```text
Production JavaScript ES-module scope: OK
```

Danach müssen alle Test-Suites und Tests erfolgreich sein. Zum Zeitpunkt der Erstellung sind dies:

```text
Test Suites: 9 passed, 9 total
Tests:       63 passed, 63 total
```

Die Anzahl kann steigen, wenn weitere Testfälle ergänzt werden. Entscheidend ist, dass keine Suite und kein Test fehlschlägt.

## Voraussetzungen

- Node.js `>= 18.14`
- eine zur Node.js-Installation passende, aktuelle npm-Version
- die Verzeichnisse `Development` und `JavaScript` als direkte Geschwister unter `Resources/Public`
- `../JavaScript/package.json` mit `"type": "module"`

Node.js und npm sollten aus derselben Installation stammen. Die verwendeten Programme und Versionen lassen sich so prüfen:

```bash
which node
which npm
node -v
npm -v
```

Auf einem Apple-Silicon-Mac mit Homebrew beginnen beide Pfade normalerweise mit `/opt/homebrew/`. Für `npm install` und die Testbefehle ist kein `sudo` erforderlich.

## Verfügbare Befehle

| Befehl | Zweck |
| --- | --- |
| `npm install` | Installiert beziehungsweise aktualisiert die lokalen Testabhängigkeiten. |
| `npm test` | Prüft den ESM-Modul-Scope und führt anschließend alle Tests einmal aus. |
| `npm run test:watch` | Startet Jest im Watch-Modus und führt betroffene Tests bei Änderungen erneut aus. |
| `npm run test:coverage` | Führt alle Tests mit Coverage-Auswertung aus. |
| `npm test -- tests/image.test.js` | Führt nur eine bestimmte Testdatei aus. |
| `npm test -- --testNamePattern="uploads"` | Führt nur Tests aus, deren Name zum angegebenen Muster passt. |

Der HTML-Coverage-Bericht wird unter `coverage/index.html` erzeugt. Unter macOS kann er anschließend mit folgendem Befehl geöffnet werden:

```bash
open coverage/index.html
```

`node_modules/` und `coverage/` werden nicht versioniert.

## Ablauf eines Testlaufs

`npm test` führt nacheinander folgende Schritte aus:

1. `scripts/verify-esm-environment.js` liest `../JavaScript/package.json` und prüft den Eintrag `"type": "module"`.
2. Der Vorcheck importiert ein produktives Frontend-Modul nativ mit Node.js.
3. Jest wird mit `jest.config.cjs` und `--runInBand` gestartet.
4. jsdom stellt für die Tests eine Browser-ähnliche DOM-Umgebung bereit.
5. `babel-jest-transformer.cjs` übersetzt ES-Module ausschließlich innerhalb des Testprozesses nach CommonJS.
6. Die Testdateien importieren und prüfen den unveränderten produktiven Quellcode.

Die Babel-Transformation verändert keine Dateien unter `Resources/Public/JavaScript`. Der Code bleibt dort ein natives Browser-ES-Modul.

Die frühere Option `--experimental-vm-modules` wird nicht mehr verwendet. Eine entsprechende `ExperimentalWarning` darf beim aktuellen Testbefehl daher nicht erscheinen.

## Test-Suites

| Testdatei | Getesteter Bereich |
| --- | --- |
| `tests/ckeditor.test.js` | CKEditor-Konfiguration, Auflösung der globalen Editor-Instanz, Initialisierung und Polling |
| `tests/common.test.js` | gemeinsame Selektoren und Hilfsfunktionen, Profil-IDs, Statusmeldungen und JSON-Requests |
| `tests/documents.test.js` | Modale Formulare mit Datensatztitel, fünf Zeilenaktionen, CRUD-Requests, Sortierung und DOM-Aktualisierung der strukturierten Sections |
| `tests/fields.test.js` | Feldtypen, Vorschauen, Validierung, Autosave, Abbrechen, Speichern, Gruppenaktionen, „Alle bearbeiten“ und Rich-Text-Initialisierung |
| `tests/image.test.js` | Bildvorschau, Upload, Löschen, Validierung, Modalzustände und Fehlerbehandlung |
| `tests/profile.test.js` | Initialisierung der Profil-Komponenten über das Einstiegsmodul |
| `tests/rich-text.test.js` | Rich-Text-Erkennung, sichere Vorschau, Editor-Lebenszyklus, Ausgangswerte und Fehlerbehandlung |
| `tests/sticky-image.test.js` | Sticky-Abstände, `ResizeObserver`-Verhalten, Resize-Fallback und Aufräumen bei `pagehide` |
| `tests/sync.test.js` | Synchronisations-Checkbox, Speicherung, Übernahme des Serverwerts und Wiederherstellung bei Fehlern |

Die Tests verwenden keine echten Netzwerkzugriffe und benötigen kein laufendes TYPO3. Externe Browser-Abhängigkeiten wie `fetch`, Bootstrap, `ResizeObserver` und CKEditor werden kontrolliert simuliert.

## Ordnerstruktur

```text
Development/
├── README.md
├── package.json
├── jest.config.cjs
├── babel-jest-transformer.cjs
├── scripts/
│   └── verify-esm-environment.js
└── tests/
    ├── setup.js
    ├── mocks/
    │   └── ckeditor-modules.js
    └── *.test.js
```

Die wichtigsten Dateien haben folgende Aufgaben:

- `package.json` definiert Node-Version, Abhängigkeiten und npm-Befehle.
- `jest.config.cjs` legt jsdom, Testpfade, Coverage, Mocks und Transformation fest.
- `babel-jest-transformer.cjs` transformiert die ES-Module nur für Jest.
- `scripts/verify-esm-environment.js` erkennt einen falschen Node-Modul-Scope vor dem eigentlichen Testlauf.
- `tests/setup.js` ergänzt fehlende Browserfunktionen und setzt DOM sowie globale Mocks nach jedem Test zurück.
- `tests/mocks/ckeditor-modules.js` ersetzt die durch TYPO3s Browser-Import-Map bereitgestellten CKEditor-Pakete.

## Tests erweitern

Für ein neues oder geändertes Frontend-Modul gilt:

1. Die zugehörige Testdatei unter `tests/` anlegen oder erweitern.
2. Den produktiven Code direkt aus `../../JavaScript/frontend/` importieren.
3. Nur das für den Test benötigte HTML in jsdom aufbauen.
4. Netzwerk- und Browser-APIs mit Jest-Mocks kontrolliert simulieren.
5. Erfolgsfälle, ungültige Eingaben und Fehlerpfade abdecken.
6. Asynchrone APIs realistisch simulieren; Promise-basierte Methoden müssen auch im Mock ein Promise liefern.
7. Abschließend `npm test` und bei größeren Änderungen `npm run test:coverage` ausführen.

Der PHP-Architekturtest `Tests/Unit/Architecture/FrontendJavaScriptTestEnvironmentTest.php` prüft zusätzlich, dass jedes Frontend-Modul eine eigene Jest-Suite besitzt und exportierte Funktionen in der zugehörigen Testdatei berücksichtigt werden.

## Fehlerbehebung

### `cb.apply is not a function`

Dieser Fehler entsteht typischerweise durch eine sehr alte npm-Version, die zusammen mit einer neuen Node.js-Version ausgeführt wird. Zuerst die tatsächlich verwendeten Programme prüfen:

```bash
which node
which npm
node -v
npm -v
```

Node.js und npm müssen aus derselben Installation stammen. Anschließend `npm install` erneut ausführen.

### `Unexpected export statement in CJS module`

Zuerst prüfen, ob der ESM-Marker vorhanden ist und die aktuelle Jest-Konfiguration verwendet wird:

```bash
grep -n '"type": "module"' ../JavaScript/package.json
grep -n 'verify-esm-environment' package.json
grep -n 'babel-jest-transformer.cjs' jest.config.cjs
```

Danach:

```bash
npm install
npm test
```

Der Lauf muss `Production JavaScript ES-module scope: OK` ausgeben. Fehlt diese Zeile, wird eine veraltete Version des `Development`-Ordners verwendet.

### Stacktraces zeigen einen Pfad unter `.Trash`

Dann befindet sich das Terminal noch in einer gelöschten oder verschobenen Projektkopie. Den aktuellen Pfad prüfen:

```bash
pwd
```

Anschließend ausdrücklich in den aktiven Projektordner wechseln und den Test dort erneut ausführen.

### Einzelne Tests schlagen nach dem ESM-Vorcheck fehl

Wenn der ESM-Vorcheck erfolgreich ist, funktioniert der Modul-Lader. Die erste fehlgeschlagene Assertion oder der erste Stacktrace verweist dann auf einen echten Test-, Mock- oder Produktionsfehler. Bei asynchronen Methoden ist besonders zu prüfen, ob der Mock den realen Rückgabetyp nachbildet, beispielsweise ein Promise statt `undefined`.

## Erfolgskriterium

Eine Änderung ist aus Sicht dieser Testumgebung erfolgreich, wenn:

- der ESM-Vorcheck erfolgreich ist,
- alle Jest-Suites grün sind,
- keine unbehandelten Fehler in der Konsolenausgabe erscheinen und
- bei relevanten Änderungen die Coverage keine unbeabsichtigten Lücken zeigt.
