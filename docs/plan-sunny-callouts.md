# Plan: rebuild the live page as Sunny callouts

**Status:** Accepted on 2026-10-22 (plan revision 5).
This file is the English copy of the accepted plan. It extends `docs/plan.md`
and replaces its App Library layout (section 9). Everything else in
`docs/plan.md` stays valid.

## 1. Goal

The live page `optcg.ch` shows the **Thousand Sunny as a blueprint in side view**
instead of the folders (App Library). Every topic is a callout with a line to its room.
Tapping zooms into the room and opens the links. The page never scrolls.

## 2. Decided

- **Template:** prototype **16 Callouts plus** (cutaway drawing, dimension lines,
  ship and lines draw themselves on load).
- **Blueprint after Oda's drawings** (volume 46, SBS): two masts, four levels
  (below deck, 1st, 2nd, 3rd floor/upper deck), no paddle wheels.
  Own drawing, no official images.
- **Room positions** according to the room check on the planning issue
  ("Prüfung: Räume der Sunny"): crow's nest on the front mast,
  aquarium bar on the 1st floor below the galley.
- **Events moves to the lawn deck** (decided on 2026-10-22). In `data/rooms.yaml`
  the room `navigation-room` becomes `lawn-deck` (name "Lawn Deck"),
  the file `data/links/navigation-room.yaml` becomes `data/links/lawn-deck.yaml`.
  The event links stay the same, only the room ID changes.
  Nami's chart table is no longer drawn as a room.
- **Layout** according to the guidelines for the final page ("Vorgaben für die finale Seite"):
  no topic list, no page scrolling, landscape = whole ship,
  portrait = ship on top + callouts below, scrolling only inside the open callout.
- **Clear topics** as callout text (for example "Shops"), not room names.
- **Very small phones** (for example 360 × 640, portrait; decided on 2026-10-22):
  ship on top slightly smaller, below it the 10 callouts in **2 columns with 5 rows**.
  Short lines to the room stay. No scrolling.
- **Content of the open callout** (decided on 2026-10-22): per link **icon, title and
  short description** from the data (`description` in `data/links/*.yaml`).
  The list scrolls inside the sheet (phone) or panel (desktop).
- **Animations** (decided on 2026-10-22): all 4 from 16 stay:
  (1) ship and lines draw themselves on load (about 1.4 s),
  (2) zoom into the room on tap,
  (3) sheet/panel slides in,
  (4) links appear one after the other.
  The load animation runs **only on the first visit** (marker in the browser,
  `localStorage`, no cookie). With "reduce motion" (`prefers-reduced-motion`)
  there is no animation, everything is there at once.
- **No top links, no search.**
- **Prototypes 01–19** stay parked on the branch `task/design-prototypes-a`
  (PR #20 stays a draft and is not merged), as the maintainer asked on 2026-10-22.
  The rebuild starts on a new branch from `main` and takes over only the code of 16.
  No prototype goes onto the live page.
- **Stays from the existing plan** (`docs/plan.md`): YAML data, contribution rule,
  CI check, link check, local icons, PHP hosting, no external requests,
  no tracking, no lottery tool.

## 3. Non-goals

- No 3D/isometric view, no scroll camera (17–19 stay prototypes).
- No new links and no new topics. Only the events room changes.
- No deploy without the maintainer's explicit go.

## 4. Tasks and who builds

1. **ARM – data and ship:** room `navigation-room` → `lawn-deck` (data, schema check,
   `docs/rooms.md`, tests). New ship blueprint after Oda as SVG (two masts, four levels,
   10 rooms in the right place). Callout layout for landscape, portrait and very small
   phones, without page scrolling. Sheet (phone) and panel (desktop) with icon, title,
   description. Replaces the App Library in `public/index.php`.
2. **NEEDLE – animation, interaction, evidence** (starts after 1): the 4 animations with
   "first visit only" and "reduce motion". Keyboard (Tab through callouts, Enter opens,
   Esc closes, focus stays in the sheet), screen reader texts. Screenshots in 4 sizes
   (360 × 640, 390 × 844, tablet, desktop) in the PR. Align the docs in `README.md`
   and `CONTRIBUTING.md`.

Both PRs target `main`. DECK checks them (CI green, no external requests,
no lottery tool, no page scrolling) and hands them to the maintainer for review.

## 5. Done when

- `optcg.ch` locally shows the Sunny with 10 callouts on phone, tablet and desktop,
  without page scrolling.
- Every topic opens its links with icon, title and description.
- CI (data check, tests, link check) is green.
- The maintainer has merged both PRs. Deploy only after that and only with the maintainer's go.

## 6. Risks

- **Space in portrait:** 10 callouts plus ship without scrolling is tight.
  At very small heights the ship gets smaller, not the text.
- **Ship drawing:** we know Oda's blueprints only from the wiki description.
  The drawing is a simplified own version.
