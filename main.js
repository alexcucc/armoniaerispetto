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
});