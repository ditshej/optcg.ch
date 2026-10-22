# Rooms of the Thousand Sunny

`optcg.ch` groups all links into 10 rooms of the Thousand Sunny, one per crew
member. Every room is a folder on the overview page. The data lives in
[`data/rooms.yaml`](../data/rooms.yaml); this page explains the rooms in plain
language. See `docs/plan.md` (section 4) for the original decision and its
later revision to 10 rooms.

## The 10 rooms

| Order | `id` | Room | Character | Topic (label under folder) |
|---|---|---|---|---|
| 1 | `figurehead` | Figurehead | Luffy | Official |
| 2 | `lawn-deck` | Lawn Deck | Nami | Events |
| 3 | `crows-nest` | Crow's Nest | Zoro | Training |
| 4 | `library` | Library | Robin | Rules & Cards |
| 5 | `usopp-factory` | Usopp Factory | Usopp | Tools |
| 6 | `galley` | Galley | Sanji | Shops |
| 7 | `sick-bay` | Sick Bay | Chopper | New Players |
| 8 | `soldier-dock` | Soldier Dock | Franky | Deckbuilding |
| 9 | `aquarium-bar` | Aquarium Bar | Brook | Media |
| 10 | `helm` | Helm | Jinbe | Community |

The callout on the ship shows only the **topic** (for example "Shops").
The sheet (phone) or panel (desktop) for a room shows the character and room
name as a subtitle, for example "Galley · Sanji". Events sits on the lawn deck.

Each room has a place on the ship drawing: `ShipDrawing` draws it, `ShipLayout`
holds its hit area and callout position. A new room needs an entry in both
`ShipLayout::ZONES` and `ShipLayout::CALLOUTS`; the page refuses to render
without it.

## Rule: one link, one room

Every link belongs to exactly **one** room. If a link could fit more than one
room, its **main purpose** wins.

**Example:** a YouTube channel that mostly publishes deck guides belongs to
*Media* (`aquarium-bar`), not to *Training* (`crows-nest`), because publishing
videos is its main purpose, not building decks.

**Example:** an online deck builder belongs to *Deckbuilding* (`soldier-dock`),
not to *Training* (`crows-nest`); *Training* keeps simulators and meta lists.

## Notes

- *Shops* and *Community* start without contributed links; the maintainer adds
  the first links there later (see `docs/plan.md` section 5a).
