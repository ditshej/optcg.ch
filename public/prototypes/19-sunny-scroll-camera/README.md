# 19 · Sunny Scroll Camera

**Idea.** A guided tour on scroll. The page opens with the isometric cutaway of the whole ship. Scrolling down moves a camera from the bow through the midship to the stern; at each topic the camera rests and the topic with its links fades in beside (landscape) or below (portrait) the ship. At the end the camera pulls back and the normal list of all topics follows.

**How it works.**
- A tall scroll rail holds a sticky stage. Scroll position drives the camera: for each stop the camera holds, then moves to the next. Only `transform` and `opacity` change while scrolling; the ship is drawn once.
- Route: overview, Official, Community (bow); Deckbuilding, Media, Training (midship); Tools, Shops, Rules & Cards, New Players, Events (stern); overview again. The three route tags in the header show where the camera is, a thin bar shows overall progress.
- The numbered badges sit inside the camera and are scaled back, so they keep their size while it zooms.
- Tap a badge or a room: the scroll camera pauses and the view zooms into the room, with the links in a bottom sheet (portrait) or side panel (wide landscape), as in 15. `Esc`, a swipe down on the handle, a tap on the dimmed area and the browser Back button close it; the camera returns to the scroll position. The "Zoom into the room" button in the panel does the same for the current stop.
- "Skip to all topics" link at the top of the page for keyboard users.
- With reduced motion: no pinned story and no camera movement, only the ship picture (badges still open the sheet) and the plain list. Without JavaScript only the list is shown.

**What is new compared to 12 and 15.** The whole drawing of `ship.js` (see prototype 17), and the scroll-driven camera that turns the overview into a tour. 12 and 15 show everything at once; this one tells it in order.

**Prototype-only data.** The box of each room lives in `ROOMS` in `ship.js` (with its area: bow, mid, stern); the route order is derived in `app.js`. Names, topics and links come from `data/` through `Optcg\Data\Loader`.

**Look.** Blueprint style of 04, 08 and 12. Barlow Condensed and IBM Plex Mono (OFL, in `fonts/`).

**Strengths**
- Gives the page a story and a reason to scroll; every topic gets a full-size view of its room.
- Works without a mouse: scrolling is all that is needed, links in the panel are real links.
- Cheap to run: one transform per frame.

**Weaknesses**
- Reaching a topic far down the route costs scrolling (about 9 screens in total); the badges and the list are the shortcuts.
- Pinned scroll stories can feel hijacked on some devices; the skip link and the reduced-motion mode are the mitigation.
- The camera cannot turn the ship while it scrolls, because that would mean redrawing the SVG each frame; zoom and pan only.
- Rooms with no links (Shops, Community) still get a stop, with an empty-state message.
