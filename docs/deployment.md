# Deployment

## Target

| | |
| --- | --- |
| Server | Plesk shared hosting; connection values: ask the maintainer |
| Domain | `https://optcg.ch` |
| Checkout | `/optcg.ch` (Plesk chroot, `~` is `/`) |
| Document root | `optcg.ch/public` |
| PHP | domain 8.5 (FPM, Apache); no PHP runs over SSH |
| Database | none |
| Git remote on the server | `git@github-optcg:ditshej/optcg.ch.git` |
| Deploy key | `~/.ssh/optcg-deploy` on the server, read-only on GitHub ("plesk metanet") |

## Deploy

```bash
cp .env.deploy.example .env.deploy   # once, values: ask the maintainer
./deploy.sh
```

Requirements on the Mac: PHP 8.5, Composer, rsync, and the SSH key for the
server.

What happens:

1. `deploy.sh` fetches the deploy branch from GitHub and runs
   `composer install --no-dev --optimize-autoloader` in a temporary
   directory, with `composer.json`, `composer.lock` and `src/` of that branch.
   The local `vendor/` (with dev packages) is not touched.
2. It uploads that `vendor/` with `rsync --delete` to the server.
3. It runs `_deploy.sh` on the server over SSH: branch check, clean working
   tree check, `git pull --ff-only`.

Composer runs on the Mac because the SSH shell on the server has no PHP 8.5
(the newest there is 8.3). The web server runs PHP 8.5, set per domain in
Plesk. `vendor/` is in `.gitignore`, so the upload does not make the working
tree dirty.

**Nobody runs a deploy against the live server without an explicit go from
the repository owner.**

## Rollback

`git revert` on `main`, then `./deploy.sh` again. Never change files on the
server: the next deploy aborts on a dirty working tree.

## When the deploy aborts

- "server is on branch …": on the server `git checkout main`.
- "uncommitted changes …": on the server `git status`, find out where the
  change came from, then `git checkout -- <file>`.
- `git pull` not a fast-forward: someone committed on the server. Do not
  merge there; ask.
- Permission denied (publickey) towards GitHub: `ssh -T git@github-optcg`
  on the server; is the deploy key still on GitHub?
- Composer fails on the Mac: check `php -v` (must be 8.5) and the extensions
  `curl`, `dom` and `gd`.
- The page shows "Composer detected issues in your platform": the domain PHP
  in Plesk is not 8.5, or an extension is missing there.

## First setup

Started on 2026-10-23, guided by an agent with the skill `rig-plesk-deploy`.
Finished on 2026-10-24; first deploy was `faf3182`.

- [x] Domain in Plesk and served
- [x] Document root, PHP version, SSL
- [x] Backup of `~/.ssh/config`, key created, `Host` block added
- [x] Deploy key on GitHub, read-only; `ssh -T` names the right repository
- [x] Scripts merged, checkout on the server, placeholders removed
- [x] First `./deploy.sh` ran through
- [x] Site loads; `.git/config`, `composer.json` and `vendor/autoload.php`
  answer 404

## Specific to this project

- `vendor/` comes from the Mac, not from the server (see above). If the
  server's SSH shell gets PHP 8.5 later, Composer can move back into
  `_deploy.sh`.
- `bin/fetch-icons.php` and `bin/check-links.php` never run on the server.
  They run locally or in GitHub Actions.
