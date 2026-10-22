# 18 · Sunny Tilt

**Idea.** Start like prototype 12 and 16: the Thousand Sunny in side elevation with ten callouts. Tap a topic and the flat elevation tips over into an isometric view in which the decks lift apart as stacked layers (water and dimensions, hull, cabins, rigging, room outlines). The view then flies into the chosen room and only then the links open. Closing tips the ship back. It joins the clarity of 12 with the feeling of space of 15.

**How it works.**
- Start: flat section drawing with callouts, as in 16 (same drawing, same load animation).
- Tap or click a room, a callout or a topic:
  1. the scene rotates (`rotateX` and `rotateZ`, CSS 3D) and the layers move apart on the z axis, about 0.7 s;
  2. the camera flies to the room in the tilted view, scaled so that the room sits in the part of the screen the panel leaves free;
  3. the side panel (landscape) or bottom sheet (portrait) opens with the links.
- Switching to another topic while the panel is open keeps the tilt and only moves the camera.
- Close with the Close button, `Esc`, a tap on the dimmed area, the browser Back button (`#room-id` deep links work) or a swipe (down for the sheet, right for the panel). The ship tips back flat.
- Taps during the 0.8 s run-in are ignored so that history entries and the sheet cannot get out of step.
- With `prefers-reduced-motion` nothing tips or flies: the panel opens at once on the flat drawing.
- Portrait phone: the ship across the full width with numbered rooms, the topic list below. Under the first screen all topics are a plain `<details>` list (screen readers, keyboard, no JavaScript).

**Prototype-only data.** `ship.php` holds the drawing (as `ship_layers()`), the hit areas (`ZONES`) and the callout positions (`CALLOUTS`). `index.php` stacks the layers as separate `<svg>` elements with one shared `viewBox`; the 3D transform is plain CSS (`transform-style: preserve-3d`, `perspective`). Link data comes from `data/` through `Optcg\Data\Loader`.

**Look.** Blueprint style as in 04, 08, 12 and 16. Barlow Condensed and IBM Plex Mono (OFL, in `fonts/`). In the tilted view the callouts fade back so that the room and its outline stay in front.

**New compared with 12 and 15.**
- 12 had a flat zoom only; 15 had a fixed isometric cutaway. Here the viewer moves between both, and the way in is the tap on the topic.
- The drawing is split into layers, so the tilt shows depth instead of a skewed picture.
- Only `transform`, `opacity` and the stroke trace of the load animation are animated.

**Strengths**
- Clear start (topics in large letters), spatial reward on tap.
- One drawing serves both views; the layer split costs no extra assets.

**Weaknesses**
- The tilted view shows a flat drawing lifted into layers, not a true 3D model, so it reads as an exploded plan rather than a solid ship.
- The run-in takes about 0.8 s before the links appear; the reduced-motion path skips it.
- Many large layers (11 SVG layers in the DOM, 5 or 6 of them shown on a given screen): a very old phone could stutter during the tilt.
