# 14 · Sunny Landscape

**Idea.** Landscape first. The main view is the Thousand Sunny in side elevation, drawn as a blueprint, filling the screen, with the topic written straight into each room. In portrait, a phone shows a calm hint to turn the device, and right below it the full list of topics, so the page is fully usable upright. Nothing is locked.

**How it works.**
- Landscape (any size) and any screen at least 700 px wide: the ship, with topic and link count in each room. Bow on the left.
- Portrait on a phone: a hint with a small animated phone ("Turn your phone for the ship view") and the topic list as the full alternative. Tapping a topic opens a bottom sheet with its links.
- Turning the phone switches to the ship view without a reload. An open sheet becomes the side panel and the drawing zooms to the room.
- Tap or click a room: the drawing zooms into it and the links appear (side panel in landscape, bottom sheet in portrait). Close with the Close button, `Esc`, a tap on the dimmed area, the browser Back button (history entry; `#room-id` deep links work) or a swipe (down / right).
- Hover or focus lights up the room. Reduced motion: no zoom, no animation, the panel opens at once.
- Under the first screen all topics appear again as a plain list of `<details>`: fallback for screen readers, keyboard and no JavaScript.

**Prototype-only data.** `ship.php` holds the drawing, the hit area of each room (`ZONES`) and where each label sits (`LABELS`). Link data comes from `data/` through `Optcg\Data\Loader`.

**Look.** Blueprint style of 04 and 08: cyanotype blue, grid, white lines, one yellow marker colour, dashed room outlines, labels with a dark halo over the drawing. Barlow Condensed and IBM Plex Mono (OFL, in `fonts/`). Own simplified ship drawing.

**Strengths**
- The ship is big and the labels sit in the rooms: nothing to decode, no legend.
- Phones in portrait are never locked out; the topic list is complete.
- Rotating is a smooth change of layout, not a page load.

**Weaknesses**
- The big ship is only a phone-landscape experience; most people open the page upright and first see a hint and a list.
- Labels sit in fixed places per room id (`LABELS` in `ship.php`); a new room needs a place and may need a shorter title.
- Small rooms (Training, Community) give the label little space; long topics wrap onto two lines.
