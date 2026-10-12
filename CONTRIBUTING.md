# Contributing to optcg.ch

Thank you for helping grow this list of links around the One Piece Card Game.
There are two ways to suggest something:

- **Open a pull request** if you are comfortable with Git and YAML (fastest, see below).
- **Open an issue** if you are not: use the
  [Suggest a link](https://github.com/ditshej/optcg.ch/issues/new?template=suggest-a-link.yml) or
  [Add your shop](https://github.com/ditshej/optcg.ch/issues/new?template=add-your-shop.yml) template.

ditshej decides what gets merged. There is no right to be listed.

## Ground rules

- **One topic per pull request.** One new link, or one small fix. Do not bundle unrelated changes.
- **`https://` only.** No `http://` links.
- **No tracking or affiliate parameters** in the URL (for example `utm_*`, `fbclid`, `gclid`, any affiliate/referral id).
- **Related to the One Piece Card Game.** Official sources, tournaments, deck tools, card databases, shops,
  community spaces, video/audio content, or other sites clearly useful to OPTCG players.
- English in the pull request description and commit messages; the link data itself can be in any `language`.

## Adding a link

1. Open `data/links/<room-id>.yaml` for the room the link belongs to (see `docs/rooms.md` for the room list).
2. Copy the commented example at the top of the file and fill in the fields:

   ```yaml
   - id: example-link
     title: Example Link
     url: "https://example.com"
     description: "One sentence describing what this link is for."
     language: en
     icon_source: manual
     tags: [example]
     featured: false
   ```

   | Field | Rules |
   | --- | --- |
   | `id` | Short, unique, lower-case, hyphen-separated (for example `bandai-tcg-plus`). Must be unique across all `data/links/*.yaml` files. |
   | `title` | Short, fits in 1–2 lines under the tile (max 80 characters). |
   | `url` | `https://` only, no tracking or affiliate parameters. |
   | `description` | One sentence (max 200 characters). |
   | `language` | Two-letter code of the target website, for example `en` or `de`. |
   | `icon_source` | `fetched` for the normal case (see below), or `manual` for your own icon. |
   | `icon` (optional) | File name in `public/icons/`. Defaults to `<id>.png` if omitted. |
   | `tags`, `featured` (optional) | Free-form tags; `featured: true` counts the link towards the folder preview. |

3. Save the file and open a pull request. GitHub Actions runs the data validator and the test suite on every
   pull request; fix anything it reports.
4. To check locally before pushing:

   ```bash
   composer install
   php bin/validate-data.php
   ```

See `docs/plan.md` (section 5) and `data/schema/links.schema.json` for the full data model.

### Icons

- **Normal case:** leave `icon` empty and set `icon_source: fetched`. A script fetches the site's own icon
  after merge; no action needed from you.
- **Your own icon:** if you provide an icon file yourself, it must be:
  - square, PNG, SVG or WebP,
  - at least 180×180 px,
  - something you have the right to use (your own artwork, or the target site's own icon/logo with permission).
  Set `icon_source: manual` so the automatic fetch script never overwrites it, and add the file under
  `public/icons/<id>.png` (or `.svg`/`.webp`) in the same pull request.

## Suggesting a shop

Shops go through the same process as any other link (pull request to `data/links/galley.yaml`, or the
[Add your shop](https://github.com/ditshej/optcg.ch/issues/new?template=add-your-shop.yml) issue template
if you prefer not to use Git). In addition to the rules above, a shop must:

- sell One Piece Card Game products,
- have its own `https://` website (no marketplace-only listings),
- link to the shop's home page or its OPTCG category page,
- not use affiliate links.

There is no automatic form and no e-mail intake for shops; ditshej reviews and decides on every submission,
the same way as for any other link.

## Review and merge

Every pull request runs through CI (data validation and tests) and is reviewed by the code owner
(`CODEOWNERS`). Only ditshej merges.
