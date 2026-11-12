# AI release review: project checklist

Points for this project, in addition to `docs/ai-review/base.md`.
A point marked "blocking" blocks the release like a blocking finding in the
base checklist.

- Blocking: the page makes no external requests and sets no cookies or tracking; fonts and icons are local.
- Blocking: no tracking parameters in links; every link is `https://` (see `src/Data/Validator.php`).
- Blocking: no official One Piece artwork.
- Blocking: the page works without JavaScript (callouts are plain links).
- Keyboard use, focus trap, touch targets of at least 44 px, WCAG AA and `prefers-reduced-motion` still hold.
- The page does not scroll; only the open link list does.
- Link icons are in `public/link-icons/`, never in `public/icons/`.
- Tests do not use the network (fakes and `tests/fixtures/`).
- No hosting details, internal tool or agent names, task numbers or secrets in files.
