# 09 · Mobile folders

**Idea.** Phone-first version of the Blueprint style, modelled on the iOS App Library. Ten rooms are tiles in a two-column grid. Each tile shows the first three links as 44 px icons (one tap to the target) and a yellow `+N` field that opens the whole room in a bottom sheet. Rooms with up to three links have no `+N`; the free cells are drawn as empty drawing slots and a big link count keeps the tile from looking empty. Rooms without links show a "No links yet." box.

**Use.** Tap an icon to leave for the link. Tap the room name or `+N` to open the sheet. Close with the Close button, `Esc`, swipe down on the sheet header, or the browser Back button (the sheet is a history entry, deep link `#room-id` works). Focus is trapped in the sheet and the page behind is `inert`.

**Look.** Cyanotype blue grid, white lines, one yellow marker colour, Barlow Condensed and IBM Plex Mono (OFL, in `fonts/`). Animation is transform/opacity only and off with `prefers-reduced-motion`.

**Strengths**
- Most visited links are one tap away, no overlay needed.
- Overview of all rooms and their size on one screen scroll.
- Without JavaScript the sheets are plain sections below the grid.

**Weaknesses**
- Only the first three links per room are promoted; the order in `data/` decides which.
- Icon-only targets have no visible title (title and `aria-label` only); unknown icons show an initial.
- Swipe-down works on the sheet header; on the list it only works if the browser hands over the gesture.
