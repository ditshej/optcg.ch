# CLAUDE.md

Project knowledge for working on `optcg.ch`. General workflow rules live elsewhere; this file
only covers what is specific to this repository.

## Purpose

- Public landing page on `https://optcg.ch` with links about the One Piece Card Game, grouped by
  the 10 rooms of the Thousand Sunny.
- The page is a blueprint of the ship (own SVG) with one callout per room; a callout opens the
  room's links in a bottom sheet (portrait) or side panel (landscape).
- Link data is YAML, one file per room, so anyone can propose links by pull request on GitHub.
- Plans: `docs/plan.md` (base plan), `docs/plan-sunny-callouts.md` (current layout, replaces the
  App Library layout of `docs/plan.md` section 9). Room list: `docs/rooms.md`. Version 2 idea
  (animated ship tour, not in scope): `docs/v2-ship-tour.md`.

## Stack

- PHP 8.5 (`composer.json` pins platform `8.5.0`), extensions `curl`, `dom`, `gd`.
- Twig 3 templates in `templates/`, rendered by `src/View/PageRenderer.php`.
- `symfony/yaml` + `justinrainbow/json-schema` for data loading and validation.
- Vanilla JS in `public/js/sunny.js`, plain CSS in `public/css/style.css`. No framework, no
  bundler, no build step, no Node.
- No database, no backend, no CMS, no login.
- PHPUnit 11, twig-cs-fixer 4.

## Commands

- Setup: `composer install`
- Run locally: `php -S 127.0.0.1:8000 -t public`
- Tests: `composer test` (PHPUnit, no network access; icon tests use local fixtures)
- Data check: `composer validate-data` (also runs in CI on every push and PR)
- Twig lint / fix: `composer lint:twig` / `composer fix:twig`
- Syntax check of single files: `php -l <file>`
- Fetch icons: `composer fetch-icons` (outbound HTTP, local or CI only, see traps)
- Dead link check: `composer check-links` (outbound HTTP, runs weekly in CI)
- Browser checks: no Playwright setup in the repo yet. Check UI changes in a headless browser
  against the local PHP server at 360×640, 390×844, tablet and desktop sizes.
- CI (`.github/workflows/ci.yml`): `composer validate --strict`, data validator, PHPUnit.

## Structure

- `data/rooms.yaml`: the 10 rooms (`id`, `name`, `topic`, `description`, `order`).
- `data/links/<room-id>.yaml`: links of one room. Example: Events live in `lawn-deck.yaml`.
- `data/schema/*.schema.json`: JSON Schemas; `src/Data/Validator.php` adds cross-file rules
  (`https://` only, no tracking params, unique ids/URLs, icon file exists and is square).
- `src/Data`: loader and validator. `src/Icons`: icon fetching and selection.
  `src/LinkCheck`: dead link check. `src/View`: rendering helpers.
- `src/View/ShipDrawing.php` draws the ship; `src/View/ShipLayout.php` holds room zones and
  callout positions.
- `public/` is the document root: `index.php`, `css/`, `js/`, `fonts/` (local), `link-icons/`.
- `bin/`: CLI entry points for the composer scripts.
- `tests/` mirrors `src/` (`Data`, `Icons`, `LinkCheck`, `View`); fixtures in `tests/fixtures/`.
  Network is faked (`FakeHttpFetcher`, `FakeHttpProbe`).

## Conventions

- Namespace `Optcg\` (PSR-4 on `src/`), tests `Optcg\Tests\`. `declare(strict_types=1);` and
  `final` classes.
- Room ids and link ids: lower-case, hyphen-separated, stable. A room `id` is used in the file
  name `data/links/<id>.yaml`, in URLs (`#<id>`) and in `RoomColors` / `ShipLayout`.
- One link belongs to exactly one room; its main purpose decides (`docs/rooms.md`).
- The callout label shows the topic (for example "Shops"), not the room name.
- Link fields: see `CONTRIBUTING.md` and `data/schema/links.schema.json`. `icon_source: fetched` is the
  normal case; `manual` means the icon script never touches that icon.
- Validation errors name the file, the link id and the fix.
- Progressive enhancement: without JavaScript every callout is a plain link to its room section.
- Accessibility: keyboard (Tab, Enter/Space, Esc, Back button), focus trap in the open sheet,
  focus returns to the callout, touch targets ≥ 44 px, WCAG AA contrast,
  `prefers-reduced-motion` turns off all animation.
- The page never scrolls; only the link list inside the open sheet/panel scrolls.
- The intro animation runs only on the first visit (flag in `localStorage`, no cookie).
- Commit messages: Conventional Commits style (`feat:`, `fix:`, `docs:`, `refactor:`, `chore:`).
- Branches: `task/<short-topic>`. One topic per PR.

## Deploy and hosting

- Shared PHP hosting, document root `public/`. Details: `docs/deployment.md`.
- `./deploy.sh` runs on the maintainer's machine: builds production `vendor/` from
  `origin/main`, uploads it with rsync, then `_deploy.sh` on the server does
  `git pull --ff-only`. Composer does not run on the server.
- Server values go in the local, untracked `.env.deploy` (template: `.env.deploy.example`).
- Only the repository owner triggers a deploy. Never deploy without an explicit go.
- Never edit files on the server; the next deploy aborts on a dirty working tree.
  Rollback = `git revert` on `main` + deploy again.

## Privacy and test data

- No tracking, no analytics, no cookies, no requests to external servers from the page.
  Fonts and icons are served locally.
- Outbound links: `https://` only, no tracking or affiliate parameters, `rel="noopener"`.
- Tests use fixtures and fakes only; no real network calls in PHPUnit.

## Traps

- **The repo is public.** Everything you write is public: files, comments, commit messages,
  branch names, PR texts. Never write hosting details (provider, panel, server name, paths,
  users, ports, SSH hosts), internal tool or agent names, internal task numbers, references to
  other private repos, or secrets. Write "repository owner" for the person.
- Never commit screenshots or other review evidence (`docs/review/` is ignored). Attach them to
  the task instead; test output as text in the PR description is fine.
- Never commit files under `.github/workflows/`. Put the full workflow file in the PR
  description; the owner adds it by hand.
- Link icons live in `public/link-icons/`, never `public/icons/`: the server's Apache has a
  global `/icons/` alias, so files there are not served.
- `bin/fetch-icons.php` and `bin/check-links.php` make outbound HTTP calls and must never run on
  the live server. The "Fetch icons" workflow opens a PR, it never pushes to `main`.
- A new room needs entries in `data/rooms.yaml`, a `data/links/<id>.yaml`, `RoomColors`,
  `ShipLayout::ZONES` and `ShipLayout::CALLOUTS`, and a place in `ShipDrawing`. The page refuses
  to render a room without a layout entry.
- `featured` exists in the data but the page does not use it yet.
- The "Suggest a link" tile and the "Add your shop" tile (Shops room only) are added at render
  time (`SuggestLinkEntry`, `AddShopLink`), not stored in the YAML.
- No official One Piece artwork or character images. Only own SVG drawings and the fetched link
  icons.
- The tournament draw (lottery) tool must never appear on the page.
- The design prototypes on branch `task/design-prototypes-a` (draft PR #20) are parked; do not
  merge them and do not use them on the live page.
- Only the repository owner merges PRs.
