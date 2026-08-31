# Interaktive Grafiken

Quellen der interaktiven Grafiken, die über den **Interaktiv**-Block in Kurse
eingebunden werden. Jede Grafik ist eine eigenständige HTML-Datei – alles CSS
und JS inline, keine Build-Schritte, keine relativen Asset-Pfade.

## Einbinden

1. Im Editor `/` drücken → **Interaktiv**
2. HTML-Datei hochladen (oder eine externe URL einfügen)
3. Fertig – die Grafik erscheint in Kursansicht, Public Embed und LTI

Der Upload landet auf dem **privaten** Disk und wird ausschließlich über
`/interactive/{id}` ausgeliefert. Diese Route setzt
`Content-Security-Policy: sandbox allow-scripts`: die Grafik läuft auf einer
Opaque Origin und kommt weder an Session-Cookie noch an localStorage der App –
auch dann nicht, wenn jemand die URL direkt aufruft.

Konsequenz für die Grafik: **kein** `localStorage`, `sessionStorage`, `cookie`
oder `fetch` auf App-Endpunkte. Canvas, SVG, CSS-Animationen, Inline-JS: alles ok.

## Eingebettet keine zweite Card

Der Interaktiv-Block zeichnet bereits Rahmen und Radius. Zeichnet die Grafik
ihren eigenen Rahmen dazu, sieht das aus wie eine Card in einer Card. Deshalb
im `<head>`, **vor** dem `<style>`, den Modus markieren:

```html
<script>
  if (window.parent !== window) {
    document.documentElement.classList.add('is-embedded');
  }
</script>
```

und im CSS die eigene Rahmung zurücknehmen:

```css
.is-embedded,
.is-embedded body { overflow: hidden; }

.is-embedded body { padding: 0; background: transparent; }

.is-embedded .app-card { width: 100%; border: 0; border-radius: 0; }
```

`overflow: hidden` verhindert den Scrollbalken, der sonst so lange sichtbar ist,
bis die erste Höhenmeldung angekommen ist. Standalone – etwa beim direkten
Aufruf von `/interactive/{id}` – bleibt die Card erhalten.

## Höhe

Die Grafik meldet ihre Höhe selbst, dann wächst der Iframe automatisch mit.
Dieses Snippet ans Ende des Scripts hängen:

```js
let lastReportedHeight = 0;

function reportHeight() {
  if (window.parent === window) return;

  // Muss die Body-Box sein, NICHT documentElement.scrollHeight: letzteres
  // unterschreitet nie den Viewport des Iframes. Sobald das Parent den Rahmen
  // vergrößert hat, könnte die Höhe dann nur noch wachsen, nie zurück – die
  // Grafik bliebe beim Verbreitern (Sidebar zu, Fenster größer) zu hoch.
  const height = Math.ceil(document.body.getBoundingClientRect().height);

  // Nur echte Änderungen melden – verhindert eine Resize-Endlosschleife
  if (height === lastReportedHeight) return;

  lastReportedHeight = height;
  window.parent.postMessage({ type: 'motionbase:resize', height }, '*');
}

window.addEventListener('resize', reportHeight);
if (typeof ResizeObserver !== 'undefined') {
  new ResizeObserver(reportHeight).observe(document.body);
}
reportHeight();
```

Wichtig: `body` darf **keine** `min-height: 100vh` haben – sonst wächst die
Grafik bei jeder Meldung weiter. Die Höhe ist auf 120–5000 px begrenzt; ohne
Meldung gilt der im Block eingestellte Wert (Default 480 px).

## Stil

Design-Tokens aus `resources/css/app.css` übernehmen, damit die Grafik nicht
aus dem Kurs herausfällt:

| Rolle | Wert |
|---|---|
| Akzent | `#ff0055` |
| Text | `#18181b` / `#3f3f46` / `#71717a` |
| Ränder | `#e4e4e7`, `#f4f4f5` |
| Flächen | `#ffffff`, `#fafafa` |
| Radius | `1rem` (Karte), `0.75rem` (Panel/Button) |
| Schrift | Inter, `font-feature-settings: 'ss01','ss02','cv01','cv02'` |

Karten über Ränder abgrenzen, nicht über Schatten. Aktive Zustände sind
`#18181b` mit weißer Schrift.
