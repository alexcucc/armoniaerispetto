document.addEventListener('DOMContentLoaded', () => {
  const header = document.querySelector('header');
  const navigationMenu = document.querySelector('.navigation-menu');
  const hamburger = document.querySelector('.hamburger');

  if (!header || !navigationMenu || !hamburger) {
    return;
  }

  function updateHeaderHeight() {
    const headerHeight = header.offsetHeight;
    document.documentElement.style.setProperty('--header-height', `${headerHeight}px`);
  }

  // Update header height on page load and window resize
  updateHeaderHeight();
  window.addEventListener('resize', updateHeaderHeight);

  // Example: Toggle navigation menu active state
  hamburger.addEventListener('click', () => {
    navigationMenu.classList.toggle('active');
  });

  document.querySelectorAll('.manage-toggle').forEach((toggle) => {
    const navItem = toggle.closest('.nav-item.dropdown');
    const submenu = navItem?.querySelector('.submenu');
    const navLinks = navItem?.closest('.nav-links');

    if (!navItem || !submenu || !navLinks) {
      return;
    }

    const defaultText = toggle.textContent.trim();
    toggle.dataset.defaultText = defaultText;

    const showSubmenu = () => {
      navLinks.classList.add('manage-focused');
      navItem.classList.add('manage-expanded', 'open');
      submenu.style.display = 'block';
      toggle.textContent = '← Torna al menu';
    };

    const hideSubmenu = () => {
      navLinks.classList.remove('manage-focused');
      navItem.classList.remove('manage-expanded', 'open');
      submenu.style.display = 'none';
      toggle.textContent = toggle.dataset.defaultText;
    };

    toggle.addEventListener('click', (event) => {
      event.preventDefault();

      const isExpanded = navItem.classList.contains('manage-expanded');

      if (isExpanded) {
        hideSubmenu();
      } else {
        showSubmenu();
      }
    });

    toggle.manageMenuControls = {
      show: showSubmenu,
      hide: hideSubmenu,
      isExpanded: () => navItem.classList.contains('manage-expanded'),
    };

    // Ensure the submenu is hidden when the page loads.
    hideSubmenu();
  });

  const openGestioneMenu = () => {
    const manageToggle = document.querySelector('.manage-toggle');
    const controls = manageToggle?.manageMenuControls;

    if (!manageToggle || !controls) {
      return false;
    }

    navigationMenu?.classList.add('active');
    controls.show();
    manageToggle.focus();

    return true;
  };

  document.querySelectorAll('.page-button.back-button').forEach((button) => {
    button.addEventListener('click', (event) => {
      const explicitTarget = button.dataset.backTarget;
      const href = button.getAttribute('href');
      const target = explicitTarget || href;

      if (target && target !== '#') {
        event.preventDefault();
        window.location.href = target;
        return;
      }

      event.preventDefault();
      window.location.href = 'index.php?open_gestione=1';
    });
  });

  const shouldOpenGestione = new URLSearchParams(window.location.search).get('open_gestione') === '1';

  if (shouldOpenGestione && openGestioneMenu()) {
    const url = new URL(window.location.href);
    url.searchParams.delete('open_gestione');
    window.history.replaceState(null, '', `${url.pathname}${url.search}${url.hash}`);
  }

  document.querySelectorAll('.tab-container').forEach((container) => {
    const buttons = Array.from(container.querySelectorAll('.tab-button'));
    const panels = Array.from(container.querySelectorAll('.tab-panel'));

    if (!buttons.length || !panels.length) {
      return;
    }

    const activateTab = (targetId) => {
      buttons.forEach((button) => {
        const isActive = button.getAttribute('aria-controls') === targetId;
        button.classList.toggle('active', isActive);
        button.setAttribute('aria-selected', isActive ? 'true' : 'false');
        button.tabIndex = isActive ? 0 : -1;
      });

      panels.forEach((panel) => {
        const shouldShow = panel.id === targetId;
        panel.classList.toggle('active', shouldShow);
        panel.hidden = !shouldShow;
      });
    };

    const defaultButton = buttons.find((button) => button.classList.contains('active')) ?? buttons[0];
    activateTab(defaultButton.getAttribute('aria-controls'));

    buttons.forEach((button) => {
      button.addEventListener('click', () => {
        const targetId = button.getAttribute('aria-controls');

        if (!targetId) {
          return;
        }

        activateTab(targetId);
      });

      button.addEventListener('keydown', (event) => {
        if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') {
          return;
        }

        event.preventDefault();

        const currentIndex = buttons.indexOf(button);
        const offset = event.key === 'ArrowRight' ? 1 : -1;
        const nextIndex = (currentIndex + offset + buttons.length) % buttons.length;
        const nextButton = buttons[nextIndex];

        nextButton.focus();
        activateTab(nextButton.getAttribute('aria-controls'));
      });
    });
  });

  document.querySelectorAll('.users-table th.sortable[data-sort-url]').forEach((header) => {
    const sortUrl = header.dataset.sortUrl;

    if (!sortUrl) {
      return;
    }

    header.setAttribute('role', 'button');
    header.tabIndex = header.tabIndex >= 0 ? header.tabIndex : 0;

    const navigateToSort = () => {
      window.location.href = sortUrl;
    };

    header.addEventListener('click', navigateToSort);

    header.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        navigateToSort();
      }
    });
  });

  const motivationButtons = Array.from(document.querySelectorAll('.motivation-viewer[data-reason]'));

  if (motivationButtons.length) {
    const modal = document.createElement('div');
    modal.className = 'motivation-modal';
    modal.innerHTML = `
      <div class="motivation-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="motivation-modal-title">
        <div class="motivation-modal__header">
          <h2 id="motivation-modal-title" class="motivation-modal__title">Motivazione del respingimento</h2>
          <button type="button" class="motivation-modal__close" aria-label="Chiudi finestra">&times;</button>
        </div>
        <div class="motivation-modal__body"></div>
      </div>
    `;

    document.body.appendChild(modal);

    const body = modal.querySelector('.motivation-modal__body');
    const closeButton = modal.querySelector('.motivation-modal__close');

    const closeModal = () => {
      modal.classList.remove('open');
      modal.setAttribute('aria-hidden', 'true');
    };

    const openModal = (reasonText) => {
      if (!body) {
        return;
      }

      body.textContent = reasonText;
      modal.classList.add('open');
      modal.removeAttribute('aria-hidden');
      closeButton?.focus();
    };

    closeButton?.addEventListener('click', closeModal);

    modal.addEventListener('click', (event) => {
      if (event.target === modal) {
        closeModal();
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && modal.classList.contains('open')) {
        closeModal();
      }
    });

    motivationButtons.forEach((button) => {
      button.addEventListener('click', () => {
        const reasonText = button.dataset.reason ?? '';

        if (!reasonText) {
          return;
        }

        openModal(reasonText);
      });
    });
  }
});
