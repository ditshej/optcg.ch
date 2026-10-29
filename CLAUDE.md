# CLAUDE.md

- Public landing page on `https://optcg.ch`: One Piece Card Game links grouped by the 10 rooms of
  the Thousand Sunny. A ship blueprint (own SVG) has one callout per room; it opens the room's
  links in a sheet/panel.
- Plans: `docs/plan.md`, `docs/plan-sunny-callouts.md` (current layout, replaces plan.md §9).
  Rooms: `docs/rooms.md`. Deploy: `docs/deployment.md`.
- PHP 8.5, Twig 3, YAML data + JSON Schema, vanilla JS/CSS. No build step, no Node, no database.

## Commands

- `composer install` · `php -S 127.0.0.1:8000 -t public`
- `composer test` · `composer validate-data` · `composer lint:twig` (fix: `fix:twig`)
- `composer fetch-icons`, `composer check-links`: outbound HTTP, local or CI only, never on the
  server.
- No Playwright in the repo. Check UI changes at 360×640, 390×844, tablet and desktop.

## Rules

- Data: `data/rooms.yaml`, one `data/links/<room-id>.yaml` per room. Each link has exactly one
  room. `src/Data/Validator.php` adds cross-file rules (`https://` only, no tracking params,
  unique ids/URLs, square icon exists).
- Link icons go in `public/link-icons/`, never `public/icons/` (server Apache alias).
- A new room needs: `rooms.yaml`, `data/links/<id>.yaml`, `RoomColors`, `ShipLayout::ZONES` and
  `::CALLOUTS`, and a place in `ShipDrawing`. Without a layout entry the page does not render.
- The "Suggest a link" and "Add your shop" tiles are added at render time, not stored in YAML.
- Works without JS (callouts are plain links). Keyboard, focus trap, ≥ 44 px targets, WCAG AA,
  `prefers-reduced-motion`. The page never scrolls; only the open link list does.
- No tracking, cookies or external requests from the page; fonts and icons are local.
- Tests never use the network (fakes and `tests/fixtures/`).
- No official One Piece artwork. The tournament draw (lottery) tool never appears on the page.
- Branch `task/design-prototypes-a` (draft PR #20) is parked: do not merge or reuse it.
- Public repo: no hosting details, internal tool/agent names, task numbers or secrets in files,
  commits or PRs. No screenshots in the repo (`docs/review/` is ignored).
- Deploy only on an explicit go from the owner (`./deploy.sh`, see `docs/deployment.md`). Never
  edit files on the server.
