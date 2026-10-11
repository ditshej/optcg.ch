# Rooms of the Thousand Sunny

`optcg.ch` groups all links into 8 rooms of the Thousand Sunny. Every room is a
folder on the overview page. The data lives in [`data/rooms.yaml`](../data/rooms.yaml);
this page explains the rooms in plain language. See `docs/plan.md` (section 4)
for the original decision.

## The 8 rooms

| Order | `id` | Room | Character | Topic (label under folder) |
|---|---|---|---|---|
| 1 | `figurehead` | Figurehead | Luffy | Official |
| 2 | `navigation-room` | Navigation Room | Nami | Events |
| 3 | `crows-nest` | Crow's Nest | Zoro | Training |
| 4 | `library` | Library | Robin | Rules & Cards |
| 5 | `workshop` | Workshop | Franky / Usopp | Tools |
| 6 | `galley` | Galley | Sanji | Shops |
| 7 | `lawn-deck` | Lawn Deck | Crew | Community |
| 8 | `aquarium-bar` | Aquarium Bar | Brook | Media |

The folder on the overview page shows only the **topic** (for example "Shops").
The overlay for a room shows the room name and the character as a subtitle,
for example "Galley · Sanji".

## Rule: one link, one room

Every link belongs to exactly **one** room. If a link could fit more than one
room, its **main purpose** wins.

**Example:** a YouTube channel that mostly publishes deck guides belongs to
*Media* (`aquarium-bar`), not to *Training* (`crows-nest`), because publishing
videos is its main purpose, not building decks.

## Notes

- *Shops* and *Community* start without contributed links; ditshej adds the
  first links there later (see `docs/plan.md` section 5a).
- The lottery/draw tool never appears on the page, in any room.
