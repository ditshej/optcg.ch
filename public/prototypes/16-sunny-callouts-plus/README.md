# 16 · Sunny Callouts Plus

**Idea.** Prototype 12 grown up: the Thousand Sunny as a real section drawing, bow on the left. The decks are levels (D1 to D5, keel and waterline on a level scale at the left edge), the length has a dimension line with station marks 0 to 10, and the rooms show their furniture inside the hull and the cabins: bookshelves, chart table, stove, sick bay bed, aquarium with fish, boats in the dock, workbench with gear, a barbell in the crow's nest. The 10 topics stand around the ship as callouts with leader lines, as on a technical plan.

**How it works.**
- On load the drawing traces itself: hull, mast, decks and cabins first, then the callout lines one after the other. The whole run takes about 1.4 s and only animates stroke offset and opacity. With `prefers-reduced-motion` the sheet is complete at once.
- Hover or focus on a callout lights up its room and line, and the other way round. The topic list on phones joins in.
- Tap or click a room, a callout or a topic: the drawing zooms into the room and the links open in a side panel (landscape) or bottom sheet (portrait).
- Close with the Close button, `Esc`, a tap on the dimmed area, a swipe (down for the sheet, right for the panel) or the browser Back button. `#room-id` deep links work.
- Portrait phone: the ship runs across the full width with the same numbers in its rooms, the topics follow below as a two-column list with number, topic and link count (three columns from 600 px).
- Under the first screen all topics are a plain `<details>` list: fallback for screen readers, keyboard, no JavaScript.

**Prototype-only data.** `ship.php` holds the drawing (split into `layer_*` functions), the hit area and badge of each room (`ZONES`) and the callout positions (`CALLOUTS`). Link data comes from `data/` through `Optcg\Data\Loader`.

**New compared with 12.**
- A section drawing instead of a plain elevation: deck levels, frames, inner shell, deck planking, level scale, station scale, title block with "SECTION A-A".
- Rooms are furnished, so each one is recognisable when the view zooms in.
- Finer drawing: 12-blade paddle wheel with housing, lion with inner mane and teeth, rigging (shrouds, forestay, backstay), crew mark on the sail, gun ports, chimney smoke.
- Load animation (stroke trace) and a staggered entry of the callouts.
- Number badges now sit in the top-left corner of each room, so they never cover the furniture. Portrait ship is cropped to include the level scale and its badges are larger.

**Strengths**
- The most detailed drawing of the series; zooming in rewards the viewer.
- Topic names stay large and the position on the ship still carries meaning.

**Weaknesses**
- More drawing means more SVG (about 30 KB of markup per view).
- On a portrait phone the ship is still about 190 px high; the topic list below is the comfortable way in.
- Callout positions are fixed per room id.
