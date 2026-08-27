Migriere den Dienst hier von v3 zu v4. Beachte den Anhang.

/goal Am Ende soll iservmake run_tools grün sein.

(zusätzlich mit https://git.iserv.eu/iserv/git/ai/know Skill)

Hinweis: Dies ist ein Drittanbieter-Modul, es muss im Drittanbieter-Namespace leben (Modul-ID stsbl/mail-redirection). git.iserv.eu ist leider nicht verfügbar. Es können aber Tools manuell ausgeführt werden, um Metriken zu erhalten.

---

# Codex Guide: IServ v3 Module nach v4 migrieren

Diese Anleitung ist eine Arbeitsanweisung fuer Codex, wenn ein bestehendes IServ-v3-Modul in ein v4-Portal-Web-Modul migriert werden soll. Sie basiert auf der Migration von `iserv/eventregistration` und ergaenzt die offizielle v4-Journey-Dokumentation um praktische Pruefpunkte.

## Grundregeln

- Lies zuerst das bestehende Modul und das aktuelle `demo-v4`-Modul. Uebertrage Muster aus `demo-v4` bevorzugt gegenueber eigenen Loesungen.
- Arbeite in einem neuen Branch fuer stacked MRs.
- Erhalte technische IDs, Paketnamen, Datenbanknamen, Routenpraefixe und URLs in lowercase, wenn sie Teil bestehender APIs sind.
- Nutze fuer PHP-Namespace und Klassennamen korrektes PascalCase, zum Beispiel `Stsbl\IServ\EventRegistration`, nicht `Stsbl\IServ\Eventregistration`.
- Nutze Stacked MRs um Änderungen gut prüfbar.
- Lasse dich von https://git.iserv.eu/iserv/report/-/merge_requests/132/diffs inspirieren.

## Initialisierung

0. e2e Tests prüfen, ob diese vor der Migration funktionieren.
1. Ticket und Modulmetadaten klaeren:
   - Redmine-Ticket-ID
   - Modulname und Debian-Paketname
   - Beschreibung aus alter `debian/control`
   - gewuenschter Branch-Name
2. Projekt mit `igit init` als `portal-web-skeleton` initialisieren.
3. Danach sofort neuen Branch fuer die Migration anlegen.
4. Aktualisiere die README
5. Paket im Zielordner mit `iservmake iservinstall && iservchk`  installieren.
6. Prüfen, ob die Anwendunge nun Symfony 6 nutzt (ggf. erscheinen zu diesem Zeitpunkt fehler). Wenn Symfony 5 weiterhin genutzt wird, muss hier manuell eingegeriffen werden.
7. Lege den MR mit den Änderungen an und prüfe, ob der MR existert. Erst dann soll mit den eigentlichen Änderungen begonnen werden.

## Strukturmigration

- Loese alte Bundle-Struktur auf:
  - Produktivcode nach `app/src`
  - Tests nach `app/tests`
  - e2e Tests in `e2e`
  - Twig-Templates nach `app/templates` (installiere https://git.iserv.eu/iserv/lib/twig-extras / https://repoflow.iserv.eu/iserv/package/795c0402-884c-4da6-83f2-3f27af8c52d9)
  - Symfony-Konfiguration nach `app/config`
  - Assets nach `app/assets`
  - Uebersetzungsmarker nach `app/translations`
- Entferne alte Bundle-Klassen:
  - `*Bundle.php`
  - `DependencyInjection/*Extension.php`
- Passe Autoloading an:
  - `app/composer.json` PSR-4 Namespace: `Stsbl\IServ\<PascalCaseModule>\`
  - Test-Namespaces entsprechend unter `Stsbl\IServ\<PascalCaseModule>\Tests\...`

## Allgemein
  - `symfony` console Aufrufe sollen z.B. in cronjobs durch `iserv<modul>-console` ersetzt werden.
  - Der `/bundle` Ordner soll am Ende gelöscht sein. 

## CoreBundle abloesen

`Stsbl\IServ\CoreBundle` gibt es in v4 nicht mehr. Keine aktive Referenz darf bleiben.

Typische Ersetzungen:

- Controller:
  - Core-Controller durch `Symfony\Bundle\FrameworkBundle\Controller\AbstractController` ersetzen.
  - Falls gemeinsame Helfer noetig sind, lokalen `Abstract<Module>Controller` anlegen.
  - `#[Route('/event/{id}/register'` entspricht `/iserv/eventregistration/event/{id}/register`
- Flash:
  - `Stsbl\IServ\Bundle\Flash\Flash\FlashInterface` verwenden.
- Navigation:
  - Alte Menulistener entfernen.
  - Navigation ueber `iserv-module.json` abbilden.
- Domain/Portal-Konfiguration:
  - Nicht quer im Code `Stsbl\IServ\Library\Config\Config` verwenden.
  - Wie in `demo-v4` eine lokale Wrapper-Klasse anlegen, zum Beispiel `src/Config/IServConfig.php`.
  - `Stsbl\IServ\Library\Config\Config` in `app/config/packages/libraries.yaml` verdrahten.
Weitere:
  - Bevorzuge interne Projekte, findbar über https://repoflow.iserv.eu/iserv/search?q=in:composer-internal&type=npm,maven,pypi,docker,nuget,go,helm,rpm,universal,gems,debian,composer,cargo
    - z.B. iserv/uuid, iserv/mailer-bundle
- Tests:
  - Keine `IServWebTestCase`-, `FakeMailer`- oder Core-Testklassen referenzieren.
  - Stark gekoppelte funktionale v3-Tests entweder modernisieren oder explizit ueberspringen.

Suchpruefung:

```bash
rg -n 'CoreBundle|Stsbl\IServ\\CoreBundle|AbstractPageController|IServWebTestCase|FakeMailer' app bundle
```

## Konfiguration

- `app/config/bundles.php` auf benoetigte v4-Bundles pruefen, zum Beispiel:
  - CRUD
  - Asset
  - Bootstrap/Form
  - Flash
  - Mailer
  - Validator
  - Config, falls Modul IServ-Systemkonfiguration liest
- `app/config/packages/libraries.yaml` fuer IServ-Libraries nutzen
- `app/config/routes/attributes.yaml` fuer Controller-Attribute nutzen.
- CRUD-Routen ueber `app/config/routes/iserv_crud.yaml` mit `type: iserv_routing_definition` laden.

## Routing und Controller

- Controller-Routen auf `Symfony\Component\Routing\Attribute\Route` migrieren.
- Routen relativ zum Modul halten:
  - `/event`
  - `/manage/event`
  - `/type`
  - Public-Routen unter `/public`, müssen weiterhin unter der "alten" Adresse erreichbar bleiben.
    - `/iserv/public/<modul>/...` müssen umziehen zu `/iserv/<modul>/public`.
    ```location = /iserv/public/mdm/ios/dep_enroll {
      return 308 /iserv/mdm/public/mdm/ios/dep_enroll;
    }
    ```
- Route-Namen nach Moeglichkeit kompatibel halten, zum Beispiel `eventregistration_event_index`.
- Routen-Namen mit FelixGrep in anderen Modulen suchen, falls diese noch benutzt werden und auflisten, damit diese aufgelöst werden.
- Entity-Argumente in Controllern mit `#[MapEntity(...)]` absichern, wenn Route-Parameter nicht dem Entity-Primary-Key entsprechen.
- isGranted muss mit der UUID arbeiten und darf nicht den Namen verwenden. Überprüfe alle Rechte die im `priv/`-Ordner vorhanden sind bzw. im Code Suche nach `PRIV_`. Vergleiche mit `demo-v4`.

## Doctrine und Datenbank

- Doctrine-Entities auf PHP-Attribute migrieren, passend zu `type: attribute`.
- PHP-Doc entfernen, wenn diese durch PHP-Attribute bzw. Typen redundant sind.
- Tabellen- und Spaltennamen nicht unnoetig aendern.
- Privileges mit UUIDs statt Namen referenzieren. Vorhandene UUIDs im `priv/`-Ordner pruefen und `demo-v4` als Beispiel nehmen.
- Der `symfony` User braucht keine Postgres Rechte mehr, wenn keine anderes Modul das Repostiory nutzt. Wenn ja, muss im SQL Dokumentiert werden, welche das sind.
- Änderungen am Schema mit `chkdb -r` übernehmen (migriert automatisch).
- Führe nie manuell SQL Befehle aus!
- Keine weiteren .sql Dateien erstellen.
- Erstellung von Datenbank-Benutzern von `demo-v4` übernehmen.
- Bei Referenzen auf externe Datenbanktabelle, welche nicht aus diesem Modul kommen, muss ein neues Repository erstellt werden. Es dürfen keine fremden Repositories oder Entities verwendet werden.


## Assets

- Bestehende Vite-Konfiguration erhalten, wenn das v3-Modul bereits Vite nutzt.
- Webpack-Skeleton-Konfiguration entfernen, wenn Vite genutzt wird.
- `iserv-module.json` muss das richtige Manifest referenzieren, typischerweise:

```json
"assets": {
  "manifestFile": "app/public/static/.vite/manifest.json"
}
```

- Public-Pfade in `iserv-module.json` freigeben, wenn unauthentifizierte Routen noetig sind:

```json
"publicUrlPrefixes": ["public/"]
```

## Mail

- SwiftMailer entfernen und durch iserv/mailer-bundle ersetzen.
- `IServ\Bundle\Mailer\Mailer\MailerInterface` verwenden.
- Mail-Factory auf `Symfony\Component\Mime\Email` umstellen.
- Adressen mit aktueller Symfony-Mime-API erzeugen, zum Beispiel `Address::create(...)`.
- ICS-Anhaenge ueber Symfony Mime anhaengen.

## Build und Runtime-Pruefung

Du bist auf einer IServ VM, wenn /etc/iserv/config vorhanden ist.

In IServ-VM ausfuehren:

```bash
cd git/<modul-name>
iservmake iservinstall
iservchk
```

Wenn rsync auf die VM laeuft:

- Nach Case-only-Renames alte Dateien auf der VM suchen.
- Cache auf der VM leeren:

```bash
cd /root/git/<module>/app
composer dump-autoload
bin/console cache:clear
```

## Browser-Pruefung

Nutze die e2e Tests im `e2e` Ordner.

Teste spezifische Sachen mit einen Browser nutze den In-App Browser oder einen anderen.
```
https://<host>/iserv/<module>/<route>
```

## Tests ausführen

Mach die Anwendung zunächst lauffähig (e2e Tests) bevor du dich auf phpstan, psalm und phpunit konzentrierst. 

Führe alle Teste mit `iservmake lint tests` aus.

Einzelne so:
```
 iservmake php-fix-cs
 iservmake phpstan
 iservmake phpunit
 iservmake psalm
```

Hole dir die Baseline vom CI master build (Step PHPStan -> Browse). Z.B. https://git.iserv.eu/iserv/eventregistration/-/jobs/4893994/artifacts/browse

## CI- und MR-Pruefung

- Nach Commit und Push:

```bash
glab mr view
glab ci list
glab ci trace <job-id>
```

- Wenn GitLab-Container-Build fehlschlaegt:
  - Schlage Änderungen zur Behebung vor.
  - `package.json` und `package-lock.json` nur aendern, wenn die Asset-Migration es wirklich verlangt.

## Abschluss

Ein Migrationscommit ist erst fertig, wenn:

- keine aktiven CoreBundle-Referenzen mehr existieren,
- `composer dump-autoload` funktioniert,
- `iservmake iservinstall` funktioniert,
- die wichtigsten Webrouten im Browser getestet wurden,
- der MR/CI-Status geprueft wurde.


