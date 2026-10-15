// Progressive enhancement for the folder overlay (plan.md sections 6 and 9).
// Without this script every folder link still works as a plain anchor to the
// matching <section> further down the page. With this script,
// the matching .room-section-inner is moved into the <dialog> instead, so the
// room's content lives in exactly one place at a time.
//
// The open/close motion is a manual FLIP (First/Last/Invert/Play) driven by
// the Web Animations API, not the native View Transitions API. Native view
// transitions morph the old/new snapshots independently (each fitted into a
// growing box with the UA's default object-fit), which looks like two
// separate movements when the source (a small square folder icon) and the
// target (the full-screen dialog) have very different aspect ratios: the box
// grows first, then the differently-shaped content snaps/slides into place.
// Manual FLIP avoids that by animating one real transform on the dialog and
// fading the real content in on its own schedule, so the result is identical
// across Chrome, Firefox and Safari instead of depending on each engine's
// view-transition implementation.
(() => {
  const dialog = document.getElementById('folder-overlay');
  const mount = document.getElementById('overlay-mount');
  const closeButton = document.getElementById('overlay-close');

  if (
    !dialog ||
    !mount ||
    !closeButton ||
    typeof dialog.showModal !== 'function' ||
    typeof dialog.animate !== 'function'
  ) {
    return;
  }

  const GROW_DURATION = 320;
  const GROW_EASING = 'cubic-bezier(0.22, 1, 0.36, 1)';
  const FADE_DURATION = 150;

  let openRoomId = null;
  let homeFor = null;
  let returnFocusTo = null;
  let isAnimating = false;

  // Every Animation created by animateOpen/animateClose is tracked here so it
  // can be cancelled as a group. animateClose uses fill: 'forwards' so the
  // shrunk/faded end state holds until dialog.close() runs; those finished
  // animations stay in the effect stack afterwards and, left alone, keep
  // overriding the next animateOpen's keyframes on the same properties (the
  // dialog would then open frozen at the old shrink transform and opacity 0).
  // Cancelling them once the dialog has actually closed clears that state.
  let activeAnimations = [];

  const trackAnimation = (animation) => {
    activeAnimations.push(animation);
    return animation;
  };

  const cancelActiveAnimations = () => {
    activeAnimations.forEach((animation) => animation.cancel());
    activeAnimations = [];
  };

  // Resets the animation lock and re-applies whatever the hash says right
  // now. A hashchange that arrives while isAnimating is true is ignored by
  // openOverlay/closeOverlay (they'd otherwise fight the animation in
  // flight); calling this once the animation settles replays that latest
  // hash so a fast back-button tap during the 320 ms motion still ends up
  // in the correct open/closed state instead of being lost.
  const onAnimationSettled = () => {
    isAnimating = false;
    syncToHash();
  };

  const prefersReducedMotion = () =>
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  const sectionFor = (roomId) => {
    const section = roomId ? document.getElementById(roomId) : null;
    return section && section.classList.contains('room-section') ? section : null;
  };

  const folderTileFor = (roomId) => document.querySelector(`.folder[href="#${roomId}"]`);

  const iconRectFor = (roomId) => {
    const tile = folderTileFor(roomId);
    const icon = tile ? tile.querySelector('.folder-icon') : null;
    return icon ? icon.getBoundingClientRect() : null;
  };

  // Transform that makes toRect's box (the dialog, already full-screen) look
  // and sit exactly like fromRect (the folder icon). Playing this backwards
  // to 'none' is the "grow"; playing it forwards from 'none' is the "shrink".
  const invertTransform = (fromRect, toRect) => {
    const scaleX = fromRect.width / toRect.width;
    const scaleY = fromRect.height / toRect.height;
    const x = fromRect.left - toRect.left;
    const y = fromRect.top - toRect.top;
    return `translate(${x}px, ${y}px) scale(${scaleX}, ${scaleY})`;
  };

  const moveContentIn = (section) => {
    const inner = section.querySelector('.room-section-inner');
    if (!inner) {
      return null;
    }
    homeFor = { parent: section, nextSibling: inner.nextSibling };
    mount.appendChild(inner);
    return inner;
  };

  const moveContentHome = () => {
    if (!homeFor) {
      return;
    }
    const inner = mount.firstElementChild;
    if (inner) {
      homeFor.parent.insertBefore(inner, homeFor.nextSibling);
    }
    homeFor = null;
  };

  const animateOpen = (tileRect) => {
    const toRect = dialog.getBoundingClientRect();
    const reduced = prefersReducedMotion();

    if (!tileRect || reduced) {
      trackAnimation(dialog.animate([{ opacity: 0 }, { opacity: 1 }], {
        duration: FADE_DURATION,
        easing: 'ease-out',
        pseudoElement: '::backdrop',
      }));
      const finished = trackAnimation(mount.animate([{ opacity: 0 }, { opacity: 1 }], {
        duration: FADE_DURATION,
        easing: 'ease-out',
      })).finished;
      finished.then(onAnimationSettled, onAnimationSettled);
      return;
    }

    const grow = trackAnimation(dialog.animate(
      [{ transform: invertTransform(tileRect, toRect) }, { transform: 'none' }],
      { duration: GROW_DURATION, easing: GROW_EASING },
    ));
    trackAnimation(dialog.animate([{ opacity: 0 }, { opacity: 1 }], {
      duration: GROW_DURATION,
      easing: 'ease-out',
      pseudoElement: '::backdrop',
    }));

    const contentDelay = Math.round(GROW_DURATION * 0.55);
    trackAnimation(mount.animate([{ opacity: 0 }, { opacity: 1 }], {
      duration: GROW_DURATION - contentDelay,
      delay: contentDelay,
      easing: 'ease-out',
      fill: 'backwards',
    }));

    grow.finished.then(onAnimationSettled, onAnimationSettled);
  };

  const animateClose = (tileRect, onDone) => {
    const fromRect = dialog.getBoundingClientRect();
    const reduced = prefersReducedMotion();

    if (!tileRect || reduced) {
      trackAnimation(dialog.animate([{ opacity: 1 }, { opacity: 0 }], {
        duration: FADE_DURATION,
        easing: 'ease-out',
        pseudoElement: '::backdrop',
        fill: 'forwards',
      }));
      const finished = trackAnimation(mount.animate([{ opacity: 1 }, { opacity: 0 }], {
        duration: FADE_DURATION,
        easing: 'ease-out',
        fill: 'forwards',
      })).finished;
      finished.then(onDone, onDone);
      return;
    }

    const shrink = trackAnimation(dialog.animate(
      [{ transform: 'none' }, { transform: invertTransform(tileRect, fromRect) }],
      { duration: GROW_DURATION, easing: GROW_EASING, fill: 'forwards' },
    ));
    trackAnimation(dialog.animate([{ opacity: 1 }, { opacity: 0 }], {
      duration: GROW_DURATION,
      easing: 'ease-out',
      pseudoElement: '::backdrop',
      fill: 'forwards',
    }));
    trackAnimation(mount.animate([{ opacity: 1 }, { opacity: 0 }], {
      duration: Math.round(GROW_DURATION * 0.35),
      easing: 'ease-out',
      fill: 'forwards',
    }));

    shrink.finished.then(onDone, onDone);
  };

  const openOverlay = (roomId, { skipAnimation = false } = {}) => {
    if (openRoomId === roomId || isAnimating) {
      return;
    }
    const section = sectionFor(roomId);
    if (!section) {
      return;
    }

    const wasOpen = dialog.open;
    const tileRect = wasOpen ? null : iconRectFor(roomId);

    if (wasOpen) {
      moveContentHome();
    }

    const inner = moveContentIn(section);
    if (!inner) {
      return;
    }

    const heading = inner.querySelector('h2[id]');
    if (heading) {
      dialog.setAttribute('aria-labelledby', heading.id);
    }

    openRoomId = roomId;
    returnFocusTo = folderTileFor(roomId);

    if (wasOpen) {
      closeButton.focus();
      return;
    }

    dialog.showModal();

    if (!skipAnimation) {
      isAnimating = true;
      animateOpen(tileRect);
    }
  };

  const closeOverlay = () => {
    if (!dialog.open || isAnimating) {
      return;
    }

    const tileRect = iconRectFor(openRoomId);
    isAnimating = true;
    animateClose(tileRect, () => {
      dialog.close();
    });
  };

  // Fires for every close path alike (the shrink animation finishing,
  // a backdrop tap, and a native Esc-key close that bypasses animateClose
  // entirely), so it is the one place that reliably runs after dialog.close()
  // has actually happened and can clean up what that close leaves behind.
  dialog.addEventListener('close', () => {
    cancelActiveAnimations();
    moveContentHome();
    dialog.removeAttribute('aria-labelledby');

    // Replace (not push) the current entry: opening a room already added or
    // reused a history entry for it (a folder click pushes one; a deep link
    // is the page's own entry). Replacing that entry here, instead of
    // pushing a new one on top, avoids leaving the closed room's entry
    // reachable by the back button — pushing it is what previously made
    // Back reopen the folder right after closing it.
    if (location.hash.slice(1) === openRoomId) {
      history.replaceState(null, '', '#overview');
    }
    openRoomId = null;

    if (returnFocusTo) {
      returnFocusTo.focus();
      returnFocusTo = null;
    }

    onAnimationSettled();
  });

  closeButton.addEventListener('click', closeOverlay);

  // Native <dialog> does not close on a backdrop tap by itself: a click that
  // lands on the dialog element itself (not a descendant) is the backdrop.
  dialog.addEventListener('click', (event) => {
    if (event.target === dialog) {
      closeOverlay();
    }
  });

  let hasSyncedOnce = false;
  const syncToHash = () => {
    const id = location.hash.slice(1);
    const skipAnimation = !hasSyncedOnce;
    hasSyncedOnce = true;
    if (sectionFor(id)) {
      openOverlay(id, { skipAnimation });
    } else {
      closeOverlay();
    }
  };

  window.addEventListener('hashchange', syncToHash);
  syncToHash();
})();
