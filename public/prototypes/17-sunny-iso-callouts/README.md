# 17 · Sunny Iso Callouts

**Idea.** Prototype 15 (isometric cutaway) combined with the callouts of 12: the topics stand outside the ship in two columns, like the labels of a technical plan, and a leader line runs from each label to its room. The lines are drawn from the live projection, so they follow the room when the ship is turned.

**How it works.**
- Each callout shows the number, the topic in large letters and the link count. Room names appear only in the panel and in the topic list.
- Turn the ship by dragging (mouse: turn and tilt; touch: sideways, vertical drags still scroll the page). Turning is limited to about 26° either way and a tilt range of 26–54°, so the ship never goes upside down. Buttons (left, right, up, down, reset) and the arrow keys on the focused stage do the same for keyboard users.
- The order and height of the callouts are fixed at the rest view; only the leader lines move. Callouts never jump from one side to the other while turning.
- Hover or focus on a callout lights up the room and the other way round.
- Tap a callout, a room or a tile: the camera moves into the room and the links open (bottom sheet on portrait screens, side panel on wide landscape screens). `Esc`, a swipe down on the handle, a tap on the dimmed area and the browser Back button close it.
- Portrait phone and tablet: the ship stands up (bow with the lion at the bottom, stern at the top) so it fills the stage. The callouts become numbered badges on the ship, spread apart where rooms are close, and the topic tiles with the same numbers sit below.
- Phone in landscape keeps the callouts, with smaller labels and the controls at the bottom centre.
- Below the first screen all topics appear again as a plain list (fallback for screen readers, keyboard, reduced motion). Without JavaScript the callouts and this list still reach every link; the ship drawing needs JavaScript.

**What is new compared to 12 and 15.**
- Callouts and isometric ship in one view; leader lines follow the room while turning (12 had fixed lines, 15 had tags that sat on the ship).
- Much more detailed drawing, shared in `ship.js`: curved hull with planking and frames, rails, gun ports, rudder, paddle wheel with paddles, lion head with two manes, face and ears, lawn with grass and two mikan trees, mast with crow's nest barrel, yards, billowing sails, shrouds with ratlines, stays, flag, windows on the cabins, aquarium glass with fish, gear in the workshop, helm wheel.
- Three line weights (outline, structure, detail) and drafting dimension lines (length, transom width, mast height) with arrow heads and extension lines.
- Limited turning plus keyboard controls; a tall view for portrait screens.

**Prototype-only data.** The box of each room lives in `ROOMS` in `ship.js`; the callout slots are computed in `app.js`. Names, topics and links come from `data/` through `Optcg\Data\Loader`.

**Look.** Blueprint style of 04, 08 and 12. Barlow Condensed and IBM Plex Mono (OFL, in `fonts/`). The ship is an own drawing; no official artwork is traced.

**Strengths**
- The topic names are readable at a glance, and the ship explains where they belong.
- Turning the ship on desktop makes the plan feel alive.
- The same drawing works from phone to desktop.

**Weaknesses**
- On a portrait phone the callouts degrade to badges plus tiles; the ship is about 330 px tall.
- Leader lines of the stern rooms cross the ship and each other at some turning angles.
- Depth sorting is per object, enough for this scene but not for arbitrary geometry.
- Re-drawing the SVG while turning costs a few milliseconds per frame; fine on a mid-range phone, but it is the heaviest part.
