document.addEventListener('DOMContentLoaded', () => {
  const header = document.querySelector('header');
  const navigationMenu = document.querySelector('.navigation-menu');

  function updateHeaderHeight() {
    const headerHeight = header.offsetHeight;
    document.documentElement.style.setProperty('--header-height', `${headerHeight}px`);
  }

  // Update header height on page load and window resize
  updateHeaderHeight();
  window.addEventListener('resize', updateHeaderHeight);

  // Example: Toggle navigation menu active state
  const hamburger = document.querySelector('.hamburger');
  hamburger.addEventListener('click', () => {
    navigationMenu.classList.toggle('active');
  });

  document.querySelectorAll('.manage-toggle').forEach((toggle) => {
    toggle.addEventListener('click', () => {
      const navItem = toggle.closest('.nav-item.dropdown');
      const submenu = navItem.querySelector('.submenu');
      navItem.classList.toggle('open');
      if (navItem.classList.contains('open')) {
        submenu.style.maxHeight = `${submenu.scrollHeight}px`;
      } else {
        submenu.style.maxHeight = '0';
      }
    });
  });
});