# 04 · Blueprint

**Idea.** A technical drawing of an abstract ship, cut across the beam: hull, mast, rigging, centre line, waterline, dimension lines and a title block. Each room is a compartment in the section. A parts list next to the drawing mirrors it (hover or focus highlights both). A compartment opens as a full-screen detail sheet with the links.

**Animation technique.** View Transitions API. The compartment grows into the detail sheet (shared `view-transition-name: room`) and shrinks back on close. SVG shapes cannot carry a transition name, so a ghost `div` with the compartment's geometry stands in. Without the API, or with `prefers-reduced-motion`, the sheet simply switches.

**Look.** Cyanotype blue with a fine and a coarse grid, white lines, one yellow marker colour. Barlow Condensed and IBM Plex Mono (OFL, in `fonts/`).

**Strengths**
- Strong, unusual layout that still reads as an index (the parts list is the accessible twin of the drawing).
- Back button, `Esc` and the Back control all close the sheet; deep link `#room-id` works; the page behind is `inert` while a sheet is open.
- Without JavaScript the sheets are plain sections below the drawing.

**Weaknesses**
- Compartment slots are fixed per room id (`SLOTS` in `index.php`); a room without a slot is only in the parts list.
- The view transition needs a current Chromium or Safari; other browsers get an instant switch.
- Labels in the drawing are small on a 360 px screen; the parts list carries the load there.
