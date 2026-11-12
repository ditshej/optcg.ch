# 12 · Sunny Callouts

**Idea.** The Thousand Sunny in side elevation, drawn as a blueprint, bow on the left. Each of the 10 topics sits at the place on the ship where its room really is. On wide screens the topics stand outside the ship like the callouts of a technical plan, each with a leader line to its room. On a portrait phone the ship runs across the full width and the topics follow in two columns; the same numbers sit in the ship.

**How it works.**
- Landscape (desktop, tablet, phone turned): the ship with 10 callouts. Each callout shows the number, the topic in large letters and the link count. The room name appears only as a small addition in the panel and in the topic list.
- Hover or focus on a callout lights up the room and the other way round (also for the topic list on phones).
- Tap or click a room or a callout: the drawing zooms into the room and the links appear. Landscape: side panel on the right; portrait: bottom sheet. The zoom centres the room in the part of the screen the panel leaves free.
- Close with the Close button, `Esc`, a tap on the dimmed area, the browser Back button (the open room is a history entry; `#room-id` deep links work) or a swipe (down for the sheet, right for the side panel).
- Turning the phone switches between the two layouts without a reload; an open room stays open.
- Under the first screen all topics appear again as a plain list of `<details>`: fallback for screen readers, keyboard, reduced motion and no JavaScript.

**Prototype-only data.** `ship.php` holds the drawing, the hit area of each room (`ZONES`) and the callout positions (`CALLOUTS`). Link data comes from `data/` through `Optcg\Data\Loader`.

**Look.** Blueprint style of 04 and 08: cyanotype blue, grid, white lines, one yellow marker colour, chamfered label boxes, dashed outline for topics without links. Barlow Condensed and IBM Plex Mono (OFL, in `fonts/`). The ship is an own simplified drawing: lion head, lawn deck with mast, sail, tree, fore cabins with the helm, stern hut on two storeys, paddle wheel.

**Strengths**
- The callouts name the topic in large letters, so the ship needs no explaining.
- Position on the ship carries meaning: 2 topics forward, 3 amidships, 5 aft.
- Hover linking makes the plan feel alive on desktop.

**Weaknesses**
- On a portrait phone the ship is small (about 170 px high). Its rooms are tappable, but the numbered topic list below is the comfortable way in.
- Callout positions are fixed per room id (`ZONES` and `CALLOUTS` in `ship.php`); a new room needs a place.
- The callouts' leader lines cross each other with the forestay at some places; accepted for the plan look.
