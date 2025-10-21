(() => {
  const root = document.documentElement;
  const body = document.body;
  const THEME_KEY = 'scytaledroid-theme';
  const THEMES = ['tron', 'classic', 'dark'];

  const themeToggle = document.querySelector('[data-theme-toggle]');

  const normalizeTheme = (value) => (THEMES.includes(value) ? value : 'tron');

  const setTheme = (theme) => {
    const next = normalizeTheme(theme);
    root.setAttribute('data-theme', next);
    body.setAttribute('data-theme', next);
    if (themeToggle) {
      themeToggle.dataset.themeCurrent = next;
      const label = themeToggle.querySelector('[aria-hidden="true"]');
      if (label) {
        label.textContent = `Theme: ${next.charAt(0).toUpperCase()}${next.slice(1)}`;
      }
    }
  };

  const storedTheme = window.localStorage.getItem(THEME_KEY) || 'tron';
  setTheme(storedTheme);

  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      const current = themeToggle.dataset.themeCurrent || storedTheme;
      const nextIndex = (THEMES.indexOf(normalizeTheme(current)) + 1) % THEMES.length;
      const nextTheme = THEMES[nextIndex];
      window.localStorage.setItem(THEME_KEY, nextTheme);
      setTheme(nextTheme);
    });
  }

  // Sidebar navigation helper
  const shell = document.querySelector('[data-shell]');
  const sidebar = document.querySelector('[data-sidebar]');
  const sidebarStateKey = 'scytaledroid-sidebar-collapsed';
  const sidebarToggles = document.querySelectorAll('[data-sidebar-toggle]');
  const sidebarStateChip = document.querySelector('[data-sidebar-state]');
  const sidebarLinks = document.querySelectorAll('[data-sidebar-link]');
  const sidebarQuery = window.matchMedia('(max-width: 960px)');

  const setSidebarChip = (state) => {
    if (!sidebarStateChip) return;
    const label = state.charAt(0).toUpperCase() + state.slice(1);
    sidebarStateChip.textContent = `Sidebar: ${label}`;
  };

  const setToggleMetadata = (label, expanded) => {
    sidebarToggles.forEach((btn) => {
      if (!btn) return;
      btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
      btn.setAttribute('aria-label', label);
      btn.setAttribute('title', label);
      const labelTarget = btn.querySelector('[data-sidebar-toggle-label]');
      if (labelTarget) {
        labelTarget.textContent = label;
      }
    });
  };

  const syncSidebarState = () => {
    if (!shell || !sidebar) return;
    if (sidebarQuery.matches) {
      shell.classList.remove('is-sidebar-collapsed');
      const open = shell.classList.contains('is-sidebar-open');
      sidebar.setAttribute('aria-hidden', open ? 'false' : 'true');
      setToggleMetadata(open ? 'Close navigation' : 'Open navigation', open);
      setSidebarChip(open ? 'open' : 'closed');
    } else {
      shell.classList.remove('is-sidebar-open');
      const collapsed = window.localStorage.getItem(sidebarStateKey) === '1';
      shell.classList.toggle('is-sidebar-collapsed', collapsed);
      sidebar.setAttribute('aria-hidden', collapsed ? 'true' : 'false');
      setToggleMetadata(collapsed ? 'Expand navigation' : 'Collapse navigation', !collapsed);
      setSidebarChip(collapsed ? 'collapsed' : 'expanded');
    }
  };

  const toggleSidebar = () => {
    if (!shell || !sidebar) return;
    if (sidebarQuery.matches) {
      const open = shell.classList.toggle('is-sidebar-open');
      sidebar.setAttribute('aria-hidden', open ? 'false' : 'true');
      setToggleMetadata(open ? 'Close navigation' : 'Open navigation', open);
      setSidebarChip(open ? 'open' : 'closed');
      return;
    }
    const collapsed = shell.classList.toggle('is-sidebar-collapsed');
    window.localStorage.setItem(sidebarStateKey, collapsed ? '1' : '0');
    sidebar.setAttribute('aria-hidden', collapsed ? 'true' : 'false');
    setToggleMetadata(collapsed ? 'Expand navigation' : 'Collapse navigation', !collapsed);
    setSidebarChip(collapsed ? 'collapsed' : 'expanded');
  };

  syncSidebarState();

  sidebarToggles.forEach((btn) => {
    btn.addEventListener('click', (event) => {
      event.preventDefault();
      toggleSidebar();
    });
  });

  const handleViewportChange = () => {
    syncSidebarState();
  };

  if (typeof sidebarQuery.addEventListener === 'function') {
    sidebarQuery.addEventListener('change', handleViewportChange);
  } else if (typeof sidebarQuery.addListener === 'function') {
    sidebarQuery.addListener(handleViewportChange);
  }

  document.addEventListener('click', (event) => {
    if (!sidebarQuery.matches) return;
    if (!shell || !sidebar) return;
    if (!shell.classList.contains('is-sidebar-open')) return;
    if (sidebar.contains(event.target)) return;
    if (event.target.closest('[data-sidebar-toggle]')) return;
    shell.classList.remove('is-sidebar-open');
    sidebar.setAttribute('aria-hidden', 'true');
    setToggleMetadata('Open navigation', false);
    setSidebarChip('closed');
  });

  sidebarLinks.forEach((link) => {
    link.addEventListener('click', () => {
      if (!sidebarQuery.matches) return;
      if (!shell || !sidebar) return;
      shell.classList.remove('is-sidebar-open');
      sidebar.setAttribute('aria-hidden', 'true');
      setToggleMetadata('Open navigation', false);
      setSidebarChip('closed');
    });
  });

  window.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    if (!sidebarQuery.matches) return;
    if (!shell || !sidebar) return;
    if (!shell.classList.contains('is-sidebar-open')) return;
    shell.classList.remove('is-sidebar-open');
    sidebar.setAttribute('aria-hidden', 'true');
    setToggleMetadata('Open navigation', false);
    setSidebarChip('closed');
  });

  // Panel collapse/expand
  document.querySelectorAll('[data-action="toggle-panel"]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const panel = btn.closest('[data-panel]');
      if (!panel) return;
      const collapsed = panel.classList.toggle('is-collapsed');
      btn.textContent = collapsed ? 'Expand' : 'Collapse';
      btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    });
  });

  // Clear filter inputs
  document.querySelectorAll('[data-action="clear-filters"]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const form = btn.closest('form');
      if (!form) return;
      form.querySelectorAll('input[type="search"], input[type="text"]').forEach((input) => {
        input.value = '';
      });
      const sizeSelect = form.querySelector('select[name="size"]');
      if (sizeSelect) {
        sizeSelect.selectedIndex = 0;
      }
      form.submit();
    });
  });

  // Quick keyboard shortcut Ctrl/Cmd + K to focus search
  const searchInput = document.querySelector('#filter-q');
  window.addEventListener('keydown', (event) => {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
      event.preventDefault();
      if (searchInput) {
        searchInput.focus();
        searchInput.select();
      }
    }
  });

  // Table density toggling
  const densityBtn = document.querySelector('[data-action="toggle-density"]');
  const densityIndicator = document.querySelector('[data-density-indicator]');
  const densityKey = 'scytaledroid-density';
  const table = document.querySelector('[data-table="apps"]');
  const densityModes = ['', 'table-compact', 'table-dense'];

  const applyDensity = (mode) => {
    if (!table) return;
    table.classList.remove('table-compact', 'table-dense');
    const normalized = densityModes.includes(mode) ? mode : '';
    if (normalized) {
      table.classList.add(normalized);
    }
    if (densityIndicator) {
      let label = 'Standard';
      if (normalized === 'table-compact') label = 'Compact';
      else if (normalized === 'table-dense') label = 'Dense';
      densityIndicator.textContent = `Density: ${label}`;
    }
  };

  applyDensity(window.localStorage.getItem(densityKey) || '');

  if (densityBtn && table) {
    densityBtn.addEventListener('click', () => {
      const current = table.classList.contains('table-dense')
        ? 'table-dense'
        : table.classList.contains('table-compact')
          ? 'table-compact'
          : '';
      const next = densityModes[(densityModes.indexOf(current) + 1) % densityModes.length];
      window.localStorage.setItem(densityKey, next);
      applyDensity(next);
    });
  }

  // Copy to clipboard for package names
  document.querySelectorAll('[data-copy]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const value = btn.getAttribute('data-copy');
      if (!value) return;
      try {
        if (navigator.clipboard?.writeText) {
          await navigator.clipboard.writeText(value);
        } else {
          const textarea = document.createElement('textarea');
          textarea.value = value;
          textarea.style.position = 'fixed';
          textarea.style.opacity = '0';
          document.body.appendChild(textarea);
          textarea.focus();
          textarea.select();
          document.execCommand('copy');
          textarea.remove();
        }
        btn.classList.add('is-copied');
        const original = btn.textContent;
        btn.textContent = 'Copied';
        window.setTimeout(() => {
          btn.classList.remove('is-copied');
          btn.textContent = original || 'Copy';
        }, 1800);
      } catch (error) {
        btn.textContent = 'Error';
        window.setTimeout(() => {
          btn.textContent = 'Copy';
        }, 1600);
      }
    });
  });

  // Align collapse button text if default state is collapsed
  document.querySelectorAll('[data-panel]').forEach((panel) => {
    if (panel.classList.contains('is-collapsed')) {
      const control = panel.querySelector('[data-action="toggle-panel"]');
      if (control) {
        control.textContent = 'Expand';
        control.setAttribute('aria-expanded', 'false');
      }
    }
  });
})();
