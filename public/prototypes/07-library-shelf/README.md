# 07 · Library Shelf

**Idea:** A bookcase. Each room is a book spine (own cloth colour, gilt title, varying height and width) on a shelf. Taking a book off the shelf opens it: the left page shows the room (title, character, description with a drop cap), the right page is the table of contents, where every link is a line with a dotted leader and a page number. Typography follows book design: EB Garamond for text, IM Fell English for titles, old-style numerals, small caps-style labels. Dark walnut room, cream paper.

**Animation technique:** CSS 3D transforms plus the Web Animations API. The spine slides forward (`translateZ`), then WAAPI flies the book from the spine's exact rectangle to the centre while it turns from its side to its front, then the cover swings open on a 3D hinge (`rotateY(-180deg)` with `backface-visibility`, the back of the cover is the left page). Closing plays it in reverse. Only `transform`, `translate` and `opacity` are animated. With `prefers-reduced-motion` the book opens instantly.

**Interaction:** Native `<dialog>` with `showModal()` (focus trap, Esc closes, backdrop click closes), URL hash per book so the browser back button closes it and a shared `#room-id` URL opens it.

**Strengths**
- The most distinctive and playful of the set, with a story that fits the Library room.
- The reading view is calm and very legible; the table of contents suits link lists.
- Works without JavaScript: spines are links to `#room-id`, CSS `:target` shows the open book.

**Weaknesses**
- Two steps to reach a link (open the book, then click), the only direction that costs an extra click.
- Spine text is vertical and small on phones.
- The ten spine looks are hand-set; more rooms repeat them.
- On phones the book shows one page; the left page content moves into the page header.

**Fonts:** EB Garamond, IM Fell English (SIL OFL, licenses in `fonts/`).
