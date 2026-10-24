# 11 · Tab Bar (mobile)

**Idea.** A fixed bar at the bottom, styled like the title block of a technical plan: ruled cells, mono sheet numbers, a sliding yellow marker. Each tab is one sheet of the plan.

**Grouping (prototype only, `groups.php`).** The 10 rooms are grouped into three areas plus "All":
- **Play**: Navigation Room, Crow's Nest, Soldier Dock, Usopp Factory
- **Learn**: Figurehead, Sick Bay, Library
- **Crew** (community and media): Helm, Aquarium Bar, Galley
- **All**: every room, each folded; tap a room to open its links

The grouping is not part of the data model. Change it in `groups.php`; every room id should appear once.

**How it works.** A tab shows the rooms of its area with all links. The panel slides in from the side of the tab you came from. Keyboard: arrow keys, `Home`, `End` on the bar. The active tab is written to the URL hash. Without JavaScript the tabs are anchors and all sheets stack on one page.

**Look.** Blueprint style of 04: cyanotype blue, grid, white lines, one yellow marker colour. Pictograms are own line drawings (inline SVG). Barlow Condensed and IBM Plex Mono (OFL, in `fonts/`).

**Strengths**
- Familiar mobile pattern; the bar is always in thumb reach.
- Links are visible at once, no extra tap for a sheet.
- Easy to extend: new rooms only need an entry in `groups.php`.

**Weaknesses**
- The grouping is an editorial decision and hides the room metaphor of the ship.
- Long panels (Play has 13 links) need scrolling.
- No overview picture; less of the "special something" than the drawing in 08.
