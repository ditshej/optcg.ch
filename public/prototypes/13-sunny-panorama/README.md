# 13 · Sunny Panorama

**Idea.** A side elevation of the Thousand Sunny in blueprint style, bow on the left. Each of the 10 rooms sits where it lies on the ship, and each is labelled with its topic in large type, with the link count and the small room name. On a phone the ship fills the height of the upper area and you swipe along it in three snap stops (bow, midship, stern). On tablet and desktop the whole ship is visible.

**How it works.**
- Phone portrait: swipe sideways (CSS scroll snap). Under the ship, large topic tiles show the topics of the current station in the thumb zone. A mini ship strip shows where you are; tap a third of it to jump to that station.
- Tablet and desktop: the whole ship is visible. Tablet portrait adds all topics as tiles below the ship.
- Tap a room or a tile: the camera moves into the room (transform on the whole drawing) and the links open. Phone and tablet portrait: bottom sheet. Wide landscape screens (tablet, desktop, phone turned): side panel.
- Close with the Close button, a swipe down on the sheet handle, a tap on the dimmed area, `Esc` or the browser Back button. The open room is a history entry; `#room-id` deep links work.
- Under the first screen all topics appear as a plain list with their links, for screen readers, keyboard and reduced motion. Without JavaScript this list and all links still work.

**Prototype-only data.** The position of each room on the ship and its swipe station live in `SLOTS` in `index.php`. Room names, topics and links come from `data/` through `Optcg\Data\Loader`.

**Look.** Blueprint style of 04 and 08: cyanotype blue, grid, white lines, one yellow marker colour, chamfered tags. The ship is an own SVG drawing: lion head, lawn deck with swing, tree and mast, furled sail, stern tower with three storeys, paddle wheel, dimension lines. Barlow Condensed and IBM Plex Mono (OFL, in `fonts/`).

**Strengths**
- The topic is large and sits where the room lies on the ship; the picture explains the grouping.
- Phone: one thumb reaches the tiles; the ship stays large because you pan instead of shrinking it.
- Desktop: the whole ship is a poster; every room is one click away.

**Weaknesses**
- On a phone you see only a third of the ship at a time; the strip and the tiles carry the overview.
- Small text in the drawing (room name, number) hides on narrow phones.
- Room positions are fixed per room id; a new room needs a slot.
