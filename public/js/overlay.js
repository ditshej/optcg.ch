// Progressive enhancement for the folder overlay (plan.md sections 6 and 9).
// Without this script every folder link still works as a plain anchor to the
// matching <section> further down the page. With this script,
// the matching .room-section-inner is moved into the <dialog> instead, so the
// room's content lives in exactly one place at a time.
(() => {
  const dialog = document.getElementById('folder-overlay');
  const mount = document.getElementById('overlay-mount');
  const closeButton = document.getElementById('overlay-close');

  if (!dialog || !mount || !closeButton || typeof dialog.showModal !== 'function') {
    return;
  }

  const TRANSITION_NAME = 'folder-active';

  let openRoomId = null;
  let homeFor = null;
  let returnFocusTo = null;

  const canAnimate = () =>
    typeof document.startViewTransition === 'function' &&
    !window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  const sectionFor = (roomId) => {
    const section = roomId ? document.getElementById(roomId) : null;
    return section && section.classList.contains('room-section') ? section : null;
  };

  const folderTileFor = (roomId) => document.querySelector(`.folder[href="#${roomId}"]`);

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

  const openOverlay = (roomId) => {
    if (openRoomId === roomId) {
      return;
    }
    const section = sectionFor(roomId);
    if (!section) {
      return;
    }

    const wasOpen = dialog.open;
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

    const tile = returnFocusTo ? returnFocusTo.querySelector('.folder-icon') : null;
    const show = () => {
      dialog.showModal();
      if (tile) {
        tile.style.viewTransitionName = '';
      }
    };

    if (tile && canAnimate()) {
      tile.style.viewTransitionName = TRANSITION_NAME;
      dialog.style.viewTransitionName = TRANSITION_NAME;
      document.startViewTransition(show).finished.finally(() => {
        dialog.style.viewTransitionName = '';
      });
    } else {
      show();
    }
  };

  const closeOverlay = () => {
    if (!dialog.open) {
      return;
    }

    const tile = folderTileFor(openRoomId);
    const hide = () => {
      dialog.close();
      if (tile) {
        tile.style.viewTransitionName = TRANSITION_NAME;
      }
    };

    if (tile && canAnimate()) {
      dialog.style.viewTransitionName = TRANSITION_NAME;
      document.startViewTransition(hide).finished.finally(() => {
        dialog.style.viewTransitionName = '';
        tile.style.viewTransitionName = '';
      });
    } else {
      hide();
    }
  };

  dialog.addEventListener('close', () => {
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
  });

  closeButton.addEventListener('click', closeOverlay);

  // Native <dialog> does not close on a backdrop tap by itself: a click that
  // lands on the dialog element itself (not a descendant) is the backdrop.
  dialog.addEventListener('click', (event) => {
    if (event.target === dialog) {
      closeOverlay();
    }
  });

  const syncToHash = () => {
    const id = location.hash.slice(1);
    if (sectionFor(id)) {
      openOverlay(id);
    } else {
      closeOverlay();
    }
  };

  window.addEventListener('hashchange', syncToHash);
  syncToHash();
})();
