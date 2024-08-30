# GuidedTour ILIAS Plugin
### University of Cologne | Competence Center E-Learning
#### Nadimo Staszak

## Installation
### Download via ZIP
Start at your ILIAS root directory. It is assumed the generated downloaded plugin `gtour.zip` is in your download folder `~/Downloads`. 
Otherwise please adjust the commands below.

Run the follow commands:
```bash
mkdir -p Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/GuidedTour
cd Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/GuidedTour
mv ~/Downloads/gtour.zip gtour.zip
unzip gtour.zip
unlink gtour.zip

Update and activate the plugin in the ILIAS Plugin Administration
```

### Download via GIT
Start at your ILIAS root directory.

Run the follow commands:
```bash
mkdir -p Customizing/global/plugins/Services/UIComponent/UserInterfaceHook
cd Customizing/global/plugins/Services/UIComponent/UserInterfaceHook
git clone https://github.com/cce-uzk/GuidedTourPlugin.git GuidedTour

Update and activate the plugin in the ILIAS Plugin Administration
```

## Update
Start at your ILIAS root directory.
```bash
cd Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/GuidedTour
git pull
Update and activate the plugin in the ILIAS Plugin Administration
```

## Requirements

* ILIAS 8.0 - 8.999
* PHP >=7.4

## Hintergrundinformationen
Die GuidedTour basiert auf dem Framework `Bootstrap Tour` (https://bootstraptour.com/). Eingebunden ist jedoch dessen Fork von `Bootstrap Tourist` 
(https://github.com/IGreatlyDislikeJavascript/bootstrap-tourist).
Da `Bootstrap Tour` auf dem veralteten `coffeescript` aufbaut und eine Reihe offener Feature Requests und Bugfix Requests hat, soll `Bootstrap Tourist` 
dies (bis zum offiziellen Upgrade von `Bootstrap Tour`) als Fork mit nativen JavaScript ES6 mit Bugfixes und neuen Features kompensieren und wird hoffentlich 
Grundlage für eine Code-Rewrite der `Bootstrap Tour` sein.

## Touren erstellen
### Plugin Konfiguration
Unter "Administration" > "ILIAS erweitern" > "Plugins" kann das Plugin GuidedTour konfiguriert werden.
Hier können Touren erstellt, (de-)aktiviert, gelöscht und bearbeitet werden. 
Inaktive Touren werden den Benutzer:innen nicht angeboten und auch nicht in das Frontend geladen.

### Touren anlegen / bearbeiten
Zum Anlegen einer neuen Tour muss unter `Konfiguration` der Button `Tour erstellen` gedrückt werden.
Soll eine bestehende Tour bearbeitet werden, so kann im jeweiligen Kontextmenü zur Tour `Bearbeiten` 
gewählt werden.
* Über den `Titel` wird der Anzeigename der Tour im Hauptmenü gesetzt.
* Über das Feld `Objekttyp` wird gesteuert, bei welcher Objekt-Anzeige die Tour angeboten werden soll. 
  Wählt man hier `Global (Standard)`, so wird die Tour Instanzweit angeboten, wird z.B. `Kurs` gewählt, 
  so wird die Tour nur bei Anzeige eines Kurses angeboten.
* Über das Feld `Aktiv` kann das Objekt in der Anzeige im Frontend aktiv bzw. inaktiv geschaltet werden.
* Über die Auswahl `Globale Rollen` werden die Rollen ausgewählt, bei denen die Tour im Frontend angeboten wird.
* Über das `Icon` können `.svg` Icons für die Tour-Anzeige im Hauptmenü eingebunden werden.
* Im Feld `Skript` wird die Tour in ihren Schritten definiert, die Eingabe muss als gültiges `json` erfolgen. 
  Der Inhalt des Feldes wird direkt als Tour-Schritte in die Applikation geladen.
  Hierzu mehr im Abschnitt Tour-Skript erstellen.
  
### Tour-Skript erstellen
Möglichkeiten zur Gestaltung der Touren sind grundlegend der Dokumentation des Frameworks `Bootstrap Tour` zu entnehmen
(http://bootstraptour.com/api/). Relevant ist hierbei vor allem der Abschnitt `Step Options`. 
Da, wie unter `Hintergrundinformationen` beschrieben, der Fork `Bootstrap Tourist` verwendet wird, ist auch folgende Dokumentation interessant:
https://github.com/IGreatlyDislikeJavascript/bootstrap-tourist.

Die Eingabe der Tour Schritte muss als gültiges `json` erfolgen.

#### Grundaufbau
Jeder `Tour Schritt` ist durch geschweifte Klammern zu umrahmen.
```yaml
{
  "attribute": "value"
}
  ```
Folgen mehrere Schritte aufeinander, so sind diese durch ein Komma zu trennen.
```yaml
{
  "attribute": "value"
},
{
  "attribute": "value2"
}
```

Achtung geboten ist bei der Verwendung von `Anführungszeichen` in Text. 
Da Anführungszeichen ebenfalls der Strukturierung der Attribute dienen, müssen 
`Anführungszeichen` innerhalb von Text durch einen vorgestellten Backslash `\"Text\"` kenntlich gemacht werden.
```yaml
{
  "attribute": "Ein Text mit \"Anführungszeichen\" im Text"
}
```


Der generelle Aufbau `eines` Tour-Schritts bietet hierbei bereits eine Vielzahl an Attributen, hier zur Übersicht, 
eine gekürzte Übersicht der wichtigsten Attribute folgt.

```yaml
{
  "path": "",
  "host": "",
  "element": "",
  "placement": "right",
  "smartPlacement": true,
  "title": "",
  "content": "",
  "next": 0,
  "prev": 0,
  "animation": true,
  "container": "body",
  "backdrop": false,
  "backdropContainer": "body",
  "backdropPadding": false,
  "redirect": true,
  "reflex": false,
  "orphan": false,
  "template": "",
  "onShow": "",
  "onShown": "",
  "onHide": "",
  "onHidden": "",
  "onNext": "",
  "onPrev": "",
  "onPause": "",
  "onResume": "",
  "onRedirectError": ""
}
```
Für komplexere Anwendungsfälle sind die Funktionen "onShow", "onShown", "onHide", "onHidden": "",
"onNext", "onPrev", "onPause", "onResume", "onRedirectError" mit dem `tour` Objekt überladen (tour) => { coding }, eine Übersicht kann sich z.B. mit "onNext": "console.log(tour);" verschaffen werden. 

In den meisten Fällen wird aber nur eine Auswahl der nachfolgenden Attribute benötigt:
```yaml
{
  "path": "",
  "element": "#element-id", // Auswahl via Id oder
  "element": ".element-class", // Auswahl via Klasse oder
  "element": "func:function () { return $(document).find('.il-mainmenu');  }", // Auswahl via Funktion
  "orphan": false,
  "title": "",
  "content": "",
  "onNext": "",
  "onPrev": ""
}
```

Die Werte der Attribute `text` und `content` können sowohl `Text`, als auch `HTML` enthalten:
```yaml
{
  "element": ".il-mainbar",
  "title": "<b>Willkommen</b> <small>Hauptmenü</small>",
  "content": "Hier kann auch ein <a href='www.ilias.de' target='_blank'>Link</a> erscheinen."
}
```
### ILIAS GuidedTour Funktionen
Die GuidedTour erweitert die JavaScript Bibliothek um nachfolgende Funktionen, 
die bei der Ausgestaltung der Tour-Steps im `json` nützlich sein können:
```yaml
/**
 *   Funktionen zur Element-Auswahl
 */

// Liefert das n-te (index) Hauptmenue-Element
il.Plugins.GuidedTour.getMainbarElementByIndex(index);

// Liefert das n-te (index) Sub-Hauptmenue-Element
il.Plugins.GuidedTour.getSlateElementByIndex(index);

// Liefert das n-te (index) Tab-Element
il.Plugins.GuidedTour.getTabElementByIndex(index);

// Liefert das n-te (index) Sub-Tab-Element
il.Plugins.GuidedTour.getSubTabElementByIndex(index);


/**
 *    Funktionen fuer Aktionen
 */

// Simulierter "Klick" auf das n-te (index) Hauptmenue-Element
il.Plugins.GuidedTour.clickMainbarElementByIndex(index);

// Simulierter "Klick" auf das n-te (index) Sub-Hauptmenue-Element
il.Plugins.GuidedTour.clickSlateElementByIndex(index);

// Simulierter "Klick" auf das n-te (index) Tab-Element
il.Plugins.GuidedTour.clickTabElementByIndex(index);

// Simulierter "Klick" auf das n-te (index) Sub-Tab-Element
il.Plugins.GuidedTour.clickSubTabElementByIndex(index);

// Simuliert den Aufruf einer anderen Seite
il.Plugins.GuidedTour.goTo('url');

/**
 *    Weitere Funktionen
 */

// Ueberpruefe, ob ein MainBar-Element ausgeklappt ist (Slate angezeigt => true) oder nicht (=>false)
il.Plugins.GuidedTour.isMainBarElementCollapsed(index);

```
### ILIAS spezifische Anwendungsbeispiele
* `Ungebundendes Element`: Anzeige eines Tour-Steps ohne Bindung an ein Element (Anzeige in der Mitte der Webseite):
    ```yaml
    {
      "orphan": true,
      "title": "titel",
      "content": "text"
    }
    ```
* `Element binden:` Anzeige eines Tour-Steps gebunden an ein Element (mehrere Beispiele für die Element-Verknüpfung):
    ```yaml
    {
      "element": ".il-mainbar", // Bsp. Bindung an ein HTML Element
      "element": "func:il.Plugins.GuidedTour.getMainbarElementByIndex(1)", // Bindung an das oberste Hauptmenue-Element
      "element": "func:il.Plugins.GuidedTour.getSlateElementByIndex(1)", // Bindung an das oberste Sub-Hauptmenue-Element
      "element": "func:il.Plugins.GuidedTour.getTabElementByIndex(1)", // Bindung an das erste Tab-Element
      "element": "func:il.Plugins.GuidedTour.getSubTabElementByIndex(1)", // Bindung an das erste Sub-Tab-Element  
      "title": "titel",
      "content": "text"
    }
    ```

* `Aktionen bei "weiter" / "zurück":` Ausführung einer Aktion beim Klick auf "Weiter" oder "Zurück". 
  Die unten benannten Beispiel-Aktionen stehen sowohl in ILIAS Global zur Verfügung und können somit 
  beliebig in `onNext` bzw. `onPrev` verwendet werden.
    ```yaml 
    {
      "element": ".il-mainbar", // Beispiel Bindung an ein Element
      "onNext": "
          // Hier kann eine Aktion eingefügt werden, die beim Klick auf 'Weiter' ausgeführt wird.
          il.Plugins.GuidedTour.clickMainbarElementByIndex(1); // Simuliert einen 'Klick' auf das oberste Hauptmenue-Element
          il.Plugins.GuidedTour.clickSlateElementByIndex(1); // Simuliert einen 'Klick' auf das oberste Sub-Hauptmenue-Element
          il.Plugins.GuidedTour.goTo('url'); // Aufruf einer bestimmten Url
          let element = document.getElementById('element-id'); if (element) { element.click(); } // Simuliert einen 'Klick' auf ein html element
      ",
      "onPrev": "
          // Hier kann eine Aktion eingefügt werden, die beim Klick auf 'Zurück' ausgeführt wird.
          il.Plugins.GuidedTour.clickTabElementByIndex(1); // Simuliert einen 'Klick' auf das erste Tab-Element
          il.Plugins.GuidedTour.clickSubTabElementByIndex(1); // Simuliert einen 'Klick' auf das erste Sub-Tab-Element
          il.Plugins.GuidedTour.goTo('url'); // Aufruf einer bestimmten Url
      ", 
      "title": "titel",
      "content": "text"
    }
    ```
  
