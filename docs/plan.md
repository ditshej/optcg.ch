# Plan: optcg.ch landing page

**Status:** Accepted on 2026-10-11.
This file is the English copy of the accepted plan. It is the reference for all build tasks.

## 1. Goal

`optcg.ch` hosts a public overview page with links about the One Piece Card Game.
The links are grouped by the "rooms" of the Thousand Sunny.
The data lives in files in the public repo `ditshej/optcg.ch`.
The maintainer and other people add links by pull request, directly on GitHub, without local tooling.

## 2. Scope of version 1

- A well-designed, fast page that behaves like the iPhone **App Library**:
  every room is a folder, a click opens the folder as a full-screen overlay with all links as tiles. Details in section 9.
- Every link belongs to exactly one room.
- Link data in YAML, one file per room. Easy to edit on GitHub.
- Every link has an icon. Icons are stored locally in the repo (no external requests).
  A script fetches the website's icon; anyone can replace it by pull request. Details in section 5.
- Data validation on every pull request (CI) and a simple contribution rule
  (`CONTRIBUTING.md`, pull request and issue templates).
- Regular check for dead links.
- Deployment to plain PHP hosting.
- The idea for version 2 as a document in the repo.

## 3. Non-goals (version 1)

- No interactive, animated ship tour (that is version 2, documented as an idea only).
- No backend, no CMS, no login, no database on the server.
- The tournament draw tool (project 4) never appears on the page.
- No tracking, no analytics, no cookies.
- No official One Piece artwork (copyright).
- No search field at the top (as in the App Library) in v1. It can come later.

## 4. Rooms of the Thousand Sunny

8 rooms = 8 folders. The label under a folder shows **only the topic**, for example "Shops".
Room name and character appear in the overlay as title and subtitle, for example "Galley · Sanji".

| Order | `id` | Room | Character | Topic (label under folder) | Content |
|---|---|---|---|---|---|
| 1 | `figurehead` | Figurehead | Luffy | Official | official website, Bandai TCG+, official announcements |
| 2 | `navigation-room` | Navigation Room | Nami | Events | egmanevents, tournament calendars, locals |
| 3 | `crows-nest` | Crow's Nest | Zoro | Training | simulators, meta lists, deck builders |
| 4 | `library` | Library | Robin | Rules & Cards | rules, card databases, info sites |
| 5 | `workshop` | Workshop | Franky / Usopp | Tools | prizing.optcg.ch, 3v3, Two Moons |
| 6 | `galley` | Galley | Sanji | Shops | recommended shops, "Add your shop" |
| 7 | `lawn-deck` | Lawn Deck | Crew | Community | social media profiles, Discord, groups |
| 8 | `aquarium-bar` | Aquarium Bar | Brook | Media | YouTube, Twitch, podcasts |

Rule: every link has exactly one room. If a link fits two rooms, its main purpose wins.
Example: a YouTube channel with deck guides belongs to *Media*, not *Training*.
Task 1 reviews the exact English texts (labels, descriptions).

## 5. Data model (YAML)

- `data/rooms.yaml`: rooms with `id`, `name`, `topic` (label under the folder), `character`, `description`, `order`. All texts in English.
- `data/links/<room-id>.yaml`: one file per room. This reduces conflicts between pull requests.
- Fields per link:
  - `id` (short, unique, for example `bandai-tcg-plus`)
  - `title` (short, fits in 1–2 lines under the tile)
  - `url`
  - `description`
  - `language` (for example `de`, `en`)
  - optional `icon` (file name in `public/icons/`, default `<id>.png`)
  - `icon_source` (`fetched` or `manual`)
  - `tags`
  - `featured`
- Order in the file = order in the folder. The first four links (or the ones marked `featured`) are the folder preview.
- Without an icon, the page shows a fallback tile (first letter on a coloured area, colour from the room).
- CI validation: required fields, `https://` only, room exists, no duplicate URLs, maximum text length,
  icon file exists (square, PNG/SVG/WebP, maximum size), no tracking parameters (`utm_*` and similar).
- Why YAML: easiest to read and edit, supports comments. PHP reads YAML with the Composer package `symfony/yaml`,
  so the deployment runs `composer install` (path in section 7).
- CI validates the YAML files against a JSON Schema (`data/schema/*.json`) and with custom rules in PHP.

### Icons

- `bin/fetch-icons.php` looks for the best icon of the target website for every link without an icon file:
  `apple-touch-icon`, then the web app manifest, then `<link rel="icon">`, last `/favicon.ico`.
  The largest square image wins.
- The script stores the icon once as `public/icons/<link-id>.png` (or `.svg`), scaled to 180×180 px.
  The page never loads anything from third-party servers.
- Flow: the maintainer or a contributor runs the script locally, or a GitHub Actions workflow
  (manual or after merge) opens a pull request with the new icons. No script runs on the live server.
- Replacing: anyone with a better icon adds the file with the same name by pull request.
  The YAML field `icon_source: manual` prevents the script from overwriting it.
- If the script finds no usable icon (too small, < 64 px), the fallback tile stays.

## 5a. Initial link list

- **The research is reviewed by the maintainer in the pull request.**
- Given by the briefing: `prizing.optcg.ch`, 3v3, Two Moons (all *Tools*), official website (*Official*),
  egmanevents (*Events*), deck builders and meta sites (*Training*).
- 3–8 links per room, each with source and a short reason in the pull request.
  Criteria: actively maintained, related to the One Piece Card Game, `https`, no tracking or affiliate links,
  no paywall as the only content.
- **Shops (*Shops*) and social media profiles (*Community*) are delivered later by the maintainer.**
  No proposals for these. Until then, *Shops* only shows the "Add your shop" tile,
  *Community* starts with the profiles from the briefing or empty with "Suggest a link".
- Folders with fewer than 4 links show empty slots in the preview. This is allowed.
- The maintainer removes or adds links in the pull request. The icon script runs only after the merge (task 3a).

## 6. Stack

- PHP 8.5: `public/index.php` reads the data files and renders the HTML. No build step.
- HTML and CSS, little vanilla JS (one small script for the overlay, no framework).
- Overlay with the native `<dialog>` element: focus stays in the overlay, `Esc` closes,
  the phone's back button closes (history entry). Every folder has its own URL (`optcg.ch/#galley`) that can be shared.
- Transition: the folder "grows" from its position to full screen (View Transitions API, CSS animation as fallback).
  With `prefers-reduced-motion`, only a fade.
- Without JavaScript: the folder is a link to a section further down with all links. Everything stays reachable.
- Fonts and icons are local, no external requests.
- Tests: PHPUnit for loading and validating the data. GitHub Actions: data validation and tests on every pull request,
  link check once a week (opens an issue for dead links). Phone and desktop screenshots in UI pull requests.

## 7. Hosting and deployment

Plain PHP hosting, document root `public/`. `deploy.sh` (local, SSH) runs `_deploy.sh` on the server:
branch and clean-tree check, `git pull --ff-only`, `composer install --no-dev -o`. Connection data and
binary paths live in `.env.deploy` (untracked). Nobody deploys without an explicit go from the maintainer.

## 8. Contributions from outside

- `CONTRIBUTING.md`: add the link to the right room file, one topic per pull request, `https` only,
  no tracking or affiliate parameters, related to the One Piece Card Game.
  Icon rules: normally the script; a custom icon must be square, PNG/SVG/WebP, at least 180×180 px, with the right to use it.
- Pull request template with a checklist. Issue template "Suggest a link" for people without Git knowledge.
- CI checks every pull request. The maintainer decides and merges (`CODEOWNERS`).
- **Shops:** shops are added like all links: by pull request or with the issue template "Add your shop".
  The maintainer decides. No custom form, no e-mail. The "Add your shop" tile in the *Shops* folder links to the issue template.
- Shop rules in `CONTRIBUTING.md`: the shop sells One Piece Card Game products, has its own `https` website,
  the link points to the home page or the OPTCG category, no affiliate links. No right to be listed.

## 9. Design

### Layout: App Library behaviour

The behaviour of the iPhone App Library (folders with preview, full-screen overlay), not its look.

- Overview: grid of folders, two per row on phones, more on larger screens. Under each folder only the topic.
  The overlay header shows room name and character, for example "Galley · Sanji".
- Folder preview: up to 4 links as large icons in a square. If the room has more than 4 links,
  the fourth slot shows 4 small icons (as on the iPhone).
- Click or tap on a folder: full-screen overlay with a smooth transition. It shows all links of the room as tiles
  (icon + title with 1–2 lines). Close with ×, `Esc`, back button, or a tap next to the grid; the user is back on the overview.
- Mobile first, responsive. Tile size leaves room for a 1–2 line title and is easy to hit
  (target ≥ 64 px icon, touch target ≥ 44 px per WCAG).
- Links open in a new tab with `rel="noopener"`.

### Language

- **English only.** All page texts (room names, descriptions, footer, hints, disclaimer) are English.
  No language switch, `<html lang="en">`.
- Link titles stay as on the target website. The `language` field shows the target website's language
  (for example a small `DE` badge for German sites).

### Own look, not iOS

The page must not look like an iPhone. It leans on One Piece with own drawings and shapes, no official artwork.

**Chosen direction: design B – sea chart.**

- Overview background: parchment with a fine grid and a compass rose, as in the design draft.
- Folders and tiles: chamfered corners (octagon shape), dark brown border.
  Folders have a dashed inner frame like a map cartouche.
- Overlay: an unfolded sea chart on parchment, the overview dimmed behind it.
- Overlay background (revised): no large crosshair. Instead a very fine grid, fine rhumb lines as on old sea charts,
  a small detailed compass rose and a hinted coastline. All faint, so tiles and text stay clearly readable (WCAG AA contrast).
- Colours: parchment (`#f5e4be`), ink dark brown (`#4a3015`), accent red (`#8b2a1f`), plus green and blue for details.
  Task 4 fixes the exact values.
- Fonts: a serif font for titles (map character), a sans-serif font for tile titles. Both local and freely licensed.
- **Logo:** the word mark "optcg.ch" plus a small own symbol (for example compass rose or ship's wheel, own SVG drawing).
  The symbol is also the favicon.
- Mobile first, keyboard, contrast per WCAG AA.

### Footer

- Disclaimer: fan site, not affiliated with Bandai, Toei or Shueisha.
- Contact: GitHub repo (issues for questions and proposals) and Discord. No e-mail address, no name, no postal address.
  Reason: private fan site without ads, sales or forms; in Switzerland a full imprint is usually not required for this.
- Short privacy note: no cookies, no tracking, no external requests; the host stores technical server logs.
- The maintainer provides the Discord link (server invite or profile) in the pull request of the page (task 4).
- If ads, affiliate links or sales are added later, the maintainer reviews the imprint question again.

## 10. Version 2 (idea only)

`docs/v2-ship-tour.md`: an animated, interactive tour through the Thousand Sunny.
Cross-section of the ship, rooms are clickable. Uses the same data files and the same folder overlay as v1.
Planned later.

## 11. Risks

- Copyright on One Piece names and images: own design, no official artwork, disclaimer.
- Third-party logos as icons: only to identify the link, stored locally, removed on request.
- The icon script finds only a small or blurry favicon for some sites: fallback tile or manual icon.
- Spam or advertising pull requests: CI validation and review by the maintainer.
- Dead links: weekly link check.
- View Transitions API not in all browsers: CSS animation as fallback, same function.
- The CLI PHP on the server can differ from the web PHP: binary paths are configurable in `.env.deploy`.
- No full imprint: fine for a private fan site without ads and sales. If that changes, an imprint is needed (section 9).

## 12. Tasks

| No. | Task |
|---|---|
| 0 | `docs/plan.md` (this file) as a pull request |
| 1 | Rooms: `data/rooms.yaml` and `docs/rooms.md` (8 rooms from section 4, English texts) |
| 2 | Initial link list: research 3–8 links per room (without shops and social media), with source and reason |
| 3 | Data schema, validation, CI |
| 3a | Icon script `bin/fetch-icons.php` and a workflow that adds icons by pull request; fetch icons for the initial list |
| 4 | Page v1, overview: folder grid with preview, responsive, usable without JS |
| 5 | Page v1, overlay: `<dialog>`, transition, keyboard, back button, deep links, reduced motion |
| 6 | Contribution rules: `CONTRIBUTING.md`, templates, `CODEOWNERS` |
| 7 | Weekly link check |
| 8 | Deploy scripts and setup checklist (go from the maintainer before the first deployment) |
| 9 | Document the v2 idea |

Every task has acceptance criteria.

## 13. Workflow

One branch and one pull request per task. The pull request contains a description, test output
(PHPUnit, data validation) and, for UI changes, phone and desktop screenshots. CI checks every
pull request; a pull request with red CI does not go to review. A review checks the pull request
against the acceptance criteria and this plan: read the code, check tests and CI, open the page
locally, check the screenshots (design B, contrast, keyboard, phone). Defects go back for fixes,
then a new review. Only the maintainer merges.
