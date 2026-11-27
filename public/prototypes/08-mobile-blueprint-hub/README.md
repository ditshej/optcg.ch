# 08 · Blueprint Hub (mobile)

**Idea.** The blueprint ship of prototype 04, turned upright so all 10 rooms fit on one 390 x 844 screen without scrolling. A compact top bar holds four top links and an instant search. Every room is a compartment with its name and link count.

**How it works.**
- Tap a compartment: the drawing zooms into it and a bottom sheet slides up with the links. The other rooms fade back.
- Close the sheet with the Close button, a swipe down on the handle area, a tap on the dimmed area, `Esc` or the browser Back button (the open room is a history entry; `#room-id` deep links work).
- Search filters title, description, room name, topic and character as you type. Matches replace the drawing as a list; the drawing steps back.
- Under the first screen the same rooms appear as a plain parts list (`<details>`), for screen readers, keyboard and reduced motion. Without JavaScript this list and all links still work.

**Prototype-only data.** `top-links.php` lists the link ids and short labels of the top bar.

**Look.** Blueprint style of 04: cyanotype blue, grid, white lines, one yellow marker colour, chamfered top-link buttons, dashed outline for empty rooms. Barlow Condensed and IBM Plex Mono (OFL, in `fonts/`).

**Strengths**
- Everything is one tap away from the first screen; the drawing is the navigation.
- Sheet and search sit in the lower half of the screen, reachable with one thumb.
- Same markup serves the sheet, the search and the fallback list.

**Weaknesses**
- Compartment slots are fixed per room id (`SLOTS` in `index.php`); a new room needs a slot.
- Names in the drawing are small on 360 px wide phones; the parts list carries the load there.
- Swipe-to-close works on the handle and title area, not while dragging the link list.
