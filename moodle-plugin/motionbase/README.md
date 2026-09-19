# MotionBase für Moodle (local_motionbase)

Ein Knopf für Lehrpersonen: **„Aus MotionBase hinzufügen“** – unten in der
Aktivitätsauswahl und im Kurs unter *Mehr*. Dort wählen sie aus, was die
Klasse bekommt:

- **Aufgaben** – Lektionen, die in MotionBase als Aufgabe markiert sind,
  werden zu normalen Moodle-Aufgaben: der Inhalt als Beschreibung, die Abgabe
  so, wie in MotionBase festgelegt (keine, Datei, Text, Datei oder Text).
  Termine und Bewertung legt die Lehrperson in Moodle fest.
- **Ganzer Kurs, Kapitel, einzelne Lektion, KI-Assistent** – als Aktivität des
  externen Tools MotionBase, schon auf den Inhalt eingestellt.

MotionBase bewertet nichts und bekommt keine Namen oder E-Mail-Adressen.

## Voraussetzungen

1. MotionBase ist in Moodle als externes Tool (LTI 1.3) eingerichtet.
2. Dieses Moodle ist in MotionBase unter *Moodle & LTI* als Plattform
   eingetragen.

Das Plugin braucht keine eigenen Schlüssel: Es signiert seine Anfragen mit dem
LTI-Schlüssel dieses Moodle, und MotionBase prüft sie gegen den Schlüsselsatz,
den es für LTI ohnehin schon kennt.

## Installieren

Das ZIP aus MotionBase (*Moodle & LTI › Plugin herunterladen*) unter
*Website-Administration › Plugins › Plugin installieren* hochladen. Die Adresse
von MotionBase ist darin schon eingetragen; ändern lässt sie sich unter
*Website-Administration › Plugins › Lokale Plugins › MotionBase*.

Die Aktivitätsauswahl hat unten Platz für ein Plugin. Das Plugin übernimmt
ihn nur, solange dort nichts oder Moodles Standard (der Marketplace-Link)
steht; einstellbar unter *Website-Administration › Kurse › Aktivitätsauswahl*.

Moodle 4.5 bis 5.2.
