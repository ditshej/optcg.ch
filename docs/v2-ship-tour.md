# Version 2 idea: animated ship tour

> **Status: idea, not planned.** This is a sketch for a possible later version.
> It is not scheduled, not scoped, and not approved for build. See `docs/plan.md`,
> section 10 ("Version 2 (idea only)") and section 3 (non-goals of v1).

## The idea

Instead of (or in addition to) the folder grid of v1, the overview page shows a
side-on cross-section drawing of the Thousand Sunny, similar to the official
blueprint-style cutaways but as an own SVG drawing, not an official image. Each
room (figurehead, navigation room, crow's nest, library, workshop, galley, lawn
deck, aquarium bar) is a clickable hotspot on the drawing.

Clicking a room opens the **same full-screen folder overlay** v1 already has,
with the same tiles for that room. The ship tour only changes how a room is
*opened*; it reads the same data (`data/rooms.yaml`, `data/links/<room-id>.yaml`)
and reuses the same overlay markup, CSS and JavaScript as v1. No new data model,
no new link format.

A possible animation: hovering or focusing a room gently highlights it on the
hull (for example a soft glow or outline); clicking it zooms/pans into the room
before the overlay opens, instead of the v1 grid-to-overlay transition. Exact
motion is undecided; `prefers-reduced-motion` would disable it as in v1.

The v1 folder grid would likely stay as the no-JS and small-screen fallback, or
as an alternate view the user can switch to — not decided.

## Why later, not now

- v1 ships a working, accessible page first. The ship tour is a visual
  enhancement on top of data and an overlay that do not exist yet.
- It needs an own SVG drawing of the Sunny, which is a design effort on its own.
- It is higher risk for mobile performance and accessibility than a simple grid.

## Open questions

- **Design:** how detailed should the SVG cutaway be? Static drawing or a
  layered/animated SVG? Who draws it (the maintainer, a commissioned artist, or an
  existing free asset redrawn)?
- **Copyright:** the drawing must be an own interpretation of the ship's layout,
  not a traced or copied official blueprint. How different does the room
  layout need to be from the official ship to stay clearly an own work?
- **Mobile performance:** a detailed SVG with hover/zoom interaction may be
  heavy or fiddly on small touch screens. Does the tour even make sense on a
  phone, or is the v1 grid the permanent mobile view?
- **Accessibility:** hotspots on a drawing are harder to reach by keyboard and
  harder to announce to screen readers than a grid of labelled folder tiles.
  Would each hotspot need a parallel text list of rooms for non-visual use?
- **Touch targets:** rooms on a ship drawing are different sizes and shapes;
  meeting the 44 px minimum touch target everywhere may reshape the drawing.
- **Maintenance:** who updates the drawing if a room's purpose changes?

## Non-goals (still true for any v2)

- No official One Piece artwork; any ship drawing is an own SVG.
- No tracking, no analytics, no external requests — same privacy rules as v1.
