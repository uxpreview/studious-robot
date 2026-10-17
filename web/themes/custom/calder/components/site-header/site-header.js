/**
 * @file
 * Mobile menu toggle for the site header.
 */
((Drupal, once) => {
  Drupal.behaviors.calderHeader = {
    attach(context) {
      once('calder-header', '[data-calder-header]', context).forEach((header) => {
        const button = header.querySelector('[data-calder-menu-toggle]');
        const panel = header.querySelector('[data-calder-menu]');
        const label = header.querySelector('[data-calder-menu-label]');
        if (!button || !panel) {
          return;
        }
        const setOpen = (open) => {
          button.setAttribute('aria-expanded', String(open));
          panel.classList.toggle('hidden', !open);
          label.textContent = open ? 'Close' : 'Menu';
        };
        button.addEventListener('click', () => setOpen(button.getAttribute('aria-expanded') !== 'true'));
        header.addEventListener('keydown', (event) => {
          if (event.key === 'Escape' && button.getAttribute('aria-expanded') === 'true') {
            setOpen(false);
            button.focus();
          }
        });
      });
    },
  };
})(Drupal, once);
