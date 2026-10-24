# optcg.ch

Landing page with links about the One Piece Card Game, grouped by room (see `docs/plan.md`).

## How the page works

The page is the Thousand Sunny drawn as a blueprint (own SVG, no official images). Each of the ten
rooms has a callout on the ship (a numbered topic list under the ship on portrait phones). Tapping
a callout zooms into the room and opens its links in a bottom sheet (portrait) or a side panel
(landscape). The page itself never scrolls; only the link list in the sheet does.

- **Without JavaScript** every callout is a plain link to the room's section further down the page,
  so all links stay reachable.
- **Keyboard:** `Tab` moves through the callouts, `Enter` or `Space` opens one, `Esc` or the Back
  button closes it. Focus stays inside the open sheet and returns to the callout afterwards.
  Callouts are announced with topic, room and number of links.
- **Animation:** on the first visit the ship and its callout lines draw themselves in (about 1.4 s).
  A flag in `localStorage` (no cookie) skips it on later visits. With `prefers-reduced-motion` there
  is no animation at all.
- **Code:** `src/View/ShipDrawing.php` (drawing), `src/View/ShipLayout.php` (room zones and callout
  positions), `templates/` and `public/js/sunny.js`. Room list: [docs/rooms.md](docs/rooms.md).
  Link data: `data/links/<room-id>.yaml`, for example `lawn-deck.yaml` for events.

## Setup

```sh
composer install
```

## Validate data

```sh
composer validate-data
```

Checks `data/rooms.yaml` and `data/links/*.yaml` against the JSON Schemas in `data/schema/` and
the cross-file rules from the plan (required fields, `https://` only, no tracking parameters, no
duplicate ids/URLs, icon file exists and is square). Runs automatically in CI on every pull request.

## Fetch icons

```sh
composer fetch-icons
```

Runs `bin/fetch-icons.php`: for every link in `data/links/*.yaml` that has no icon file yet, it
looks at the target website for the best icon (`apple-touch-icon`, then the web app manifest, then
`<link rel="icon">`, last `/favicon.ico`; the largest square image wins) and stores it as
`public/icons/<link-id>.png`, scaled to 180x180 px. Links with `icon_source: manual` are never
touched. If no usable icon is found (or the best one is smaller than 64x64 px), the link gets no
icon file and the page falls back to its coloured initial tile.

This script makes outbound HTTP requests to the linked websites and writes files under
`public/icons/`. It is a local/CI tool only:

- **it must never run on the live server** — the deploy script never calls it, and it is not part
  of the deployed file set;
- run it locally before opening a pull request, or trigger the "Fetch icons" GitHub Actions
  workflow (manually, or automatically after a merge to `main`) — it runs the script and opens a
  pull request with any new icon files, it never pushes to `main` directly;
- replacing an icon: add a better image under the same file name by pull request and set
  `icon_source: manual` on the link so the script leaves it alone from then on.

## Tests

```sh
composer test
```

PHPUnit tests for the data loader/validator and the icon selection logic. The icon selection tests
run against local HTML/manifest fixtures only, no network access is used or required.

## Deploy

```sh
./deploy.sh
```

Builds the production `vendor/` locally, uploads it and makes the server pull `main` over SSH.
Details, setup and troubleshooting: [docs/deployment.md](docs/deployment.md).

## Contributing

Want to suggest a link or your shop? See [CONTRIBUTING.md](CONTRIBUTING.md).
