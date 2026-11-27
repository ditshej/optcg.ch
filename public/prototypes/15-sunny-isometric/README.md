# 15 · Sunny Isometric

**Idea.** The Thousand Sunny as an isometric cutaway: seen from above at an angle, the near side of the hull cut away so the three levels (hold, between decks, main deck with lawn and stern tower) are visible. It is more compact than the side elevation and fits portrait and landscape. The ship is built from simple solids and projected in the browser (`scene.js`, plain SVG, no WebGL).

**How it works.**
- Each topic is a tag with a leader line to its room: Official (lion head), Community (helm), Training (crow's nest), Media and Deckbuilding (below deck, midship), Shops, New Players, Rules & Cards, Events (stern tower), Tools (below deck, stern).
- Desktop: drag with the mouse to turn and tilt the ship. Touch: drag sideways to turn; vertical drags still scroll the page. Buttons turn the ship left or right and reset the view (also for keyboard use).
- Tap a tag, a room or a tile: the camera moves into the room and the links open (bottom sheet on portrait screens, side panel on wide landscape screens). `Esc`, a swipe down on the handle, a tap on the dimmed area and the browser Back button close it and the camera moves back.
- Phones: the tags shrink to numbered badges on the ship and topic tiles with the names sit in the thumb zone. Phone landscape puts the tiles in two columns beside the ship.
- Under the first screen all topics appear as a plain list with their links (screen reader, keyboard and reduced-motion fallback). Without JavaScript the tags and this list still reach every link.

**Prototype-only data.** The solid of each room, its anchor and the offset of its tag live in `ROOMS` in `scene.js`. Names, topics and links come from `data/` through `Optcg\Data\Loader`.

**Look.** Blueprint style of 04 and 08. Drafting grid under the ship, white outlines, yellow marker colour for the active room, chamfered tags. Barlow Condensed and IBM Plex Mono (OFL, in `fonts/`).

**Strengths**
- Compact: the whole ship fits any screen at a useful size, so no panning is needed.
- Turning the ship on desktop shows the levels and makes the page feel alive.
- The levels explain "below deck" topics without a second view.

**Weaknesses**
- The solids are abstract; the ship reads as the Sunny through the lion head, mast, stern tower and wheel, not through detail.
- Tag placement is hand-tuned per room; turning far from the default view can make tags cross leader lines.
- Depth sorting is per solid, which is enough for this scene but not for arbitrary geometry.
