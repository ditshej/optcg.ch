# 01 · Logbook

**Idea:** Documentation-site layout (in the spirit of statamic.dev): a sticky sidebar lists the rooms, one calm reading column holds the links as a ruled list. Strong typography does the work: a literary serif for headings (Newsreader), a plain sans for the links (IBM Plex Sans), mono for metadata. Paper-and-ink palette with one vermilion accent; follows the system light/dark setting.

**Animation technique:** `IntersectionObserver` twice: scroll-spy (the room under a band near the top of the viewport marks its sidebar entry) and a staggered fade-and-rise of the link rows. Only `opacity` and `transform` are animated. Disabled with `prefers-reduced-motion`.

**Strengths**
- Every link is one click away; the page is a plain scrollable list and works without JavaScript.
- Very scannable: title, description and host are visible without opening anything.
- Scales well to many links per room.

**Weaknesses**
- Least playful of the set; the One Piece / ship theme only shows in the copy and room names.
- All 10 rooms are on one long page, so the overview is the sidebar, not the page.
- On phones the sidebar becomes a chip row, which is less discoverable.

**Fonts:** Newsreader, IBM Plex Sans, IBM Plex Mono (SIL OFL, licenses in `fonts/`).
