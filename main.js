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
});
