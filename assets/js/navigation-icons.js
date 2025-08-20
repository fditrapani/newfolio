// Minimal editor-side code: attribute + sidebar + iframe Lucide conversion.
// No DOM surgery beyond inserting <i> and running Lucide in the iframe.

const DEFAULT_ICON = 'sticky-note';

const { addFilter } = wp.hooks;
const { InspectorControls } = wp.blockEditor || wp.editor;
const { PanelBody, TextControl } = wp.components;
const { Fragment, createElement, useEffect } = wp.element;

/* ---------- helpers ---------- */

// Editor canvas document (iframe if present)
function getCanvasDocument() {
  const iframe =
    document.querySelector('iframe[name="editor-canvas"]') ||
    document.querySelector('.edit-post-visual-editor__content-area iframe') ||
    document.querySelector('.block-editor iframe');

  return iframe && iframe.contentDocument ? iframe.contentDocument : document;
}

// Ensure Lucide UMD is loaded inside the iframe, then callback
function ensureLucideInIframe(doc, cb) {
  const w = doc && doc.defaultView ? doc.defaultView : null;

  if (w && w.lucide && typeof w.lucide.createIcons === 'function') {
    cb && cb();
    return;
  }
  const existing = doc.getElementById('newfolio-lucide-iframe');
  if (existing) {
    existing.addEventListener('load', function () { cb && cb(); }, { once: true });
    return;
  }
  const src =
    (window.newfolioNavIcons && window.newfolioNavIcons.lucideSrc) ||
    'https://unpkg.com/lucide@latest/dist/umd/lucide.js';

  const s = doc.createElement('script');
  s.id = 'newfolio-lucide-iframe';
  s.src = src;
  s.onload = function () { cb && cb(); };
  doc.head.appendChild(s);
}

// Find the clickable element within a nav-link block (WP varies)
function findNavClickable(wrapper) {
  return (
    wrapper.querySelector('a.wp-block-navigation-item__content') ||
    wrapper.querySelector('a.wp-block-navigation-link__content') ||
    wrapper.querySelector('button.wp-block-navigation-item__content') ||
    wrapper.querySelector('[role="link"].wp-block-navigation-item__content') ||
    wrapper.querySelector('[data-wp-component="Link"]') ||
    wrapper.querySelector('a[aria-label]') ||
    wrapper.querySelector('button[aria-label]') ||
    wrapper.querySelector('[role="link"]') ||
    wrapper.querySelector('a[href]') ||
    wrapper.querySelector('button') ||
    wrapper.querySelector('.wp-block-navigation-item__content') ||
    wrapper.querySelector('.wp-block-navigation-link__content') ||
    wrapper.querySelector('a') ||
    wrapper.querySelector('button')
  );
}

/* ---------- editor icon preview (idempotent) ---------- */
function ensureEditorIcon(clientId, iconSlug) {
  const doc = getCanvasDocument();
  if (!doc) return;

  const wrapper = doc.querySelector('[data-block="' + clientId + '"]');
  if (!wrapper) return;

  const clickable =
    findNavClickable(wrapper) ||
    wrapper.querySelector('a') ||
    wrapper.querySelector('button');

  if (!clickable) {
    setTimeout(function(){ ensureEditorIcon(clientId, iconSlug); }, 120);
    return;
  }

  clickable.classList.add('has-lucide-icon');

  // Idempotency: last applied slug
  const currentSlug = clickable.getAttribute('data-newfolio-icon') || '';

  // Existing lucide SVG?
  const hasLucideSvg = clickable.querySelector('svg.lucide, svg[data-lucide]') !== null;

  // If slug unchanged and already converted => no-op
  if (iconSlug && iconSlug === currentSlug && hasLucideSvg) {
    return;
  }

  // If cleared, remove icon nodes and exit
  if (!iconSlug) {
    clickable.removeAttribute('data-newfolio-icon');
    clickable.querySelectorAll('svg.lucide, svg[data-lucide], i[data-lucide]').forEach(n => n.remove());
    return;
  }

  // Clean any previous icon nodes
  clickable.querySelectorAll('svg.lucide, svg[data-lucide], i[data-lucide]').forEach(n => n.remove());

  // Insert fresh <i data-lucide="..."> as first child
  const i = doc.createElement('i');
  i.setAttribute('data-lucide', iconSlug);
  clickable.insertBefore(i, clickable.firstChild);

  clickable.setAttribute('data-newfolio-icon', iconSlug);

  // Convert inside the iframe
  ensureLucideInIframe(doc, function () {
    try { doc.defaultView.lucide.createIcons(); } catch (e) {}
  });
}

/* ---------- attribute (with default) ---------- */
addFilter('blocks.registerBlockType', 'newfolio/nav-icon-attr', function (settings, name) {
  if (name !== 'core/navigation-link') return settings;

  settings.attributes = Object.assign({}, settings.attributes, {
    icon: { type: 'string', default: DEFAULT_ICON },
  });

  return settings;
});

/* ---------- inspector control ---------- */
function NavIconControl(props) {
  return createElement(
    InspectorControls,
    {},
    createElement(
      PanelBody,
      { title: 'Navigation Icon (Lucide)', initialOpen: true },
      createElement(TextControl, {
        label: 'Icon slug (e.g. home, search, user)',
        help: 'lucide.dev slugs. Clear to remove the icon.',
        value: props.attributes.icon || '',
        placeholder: DEFAULT_ICON,
        onChange: function (val) {
          props.setAttributes({ icon: (val || '').trim() });
        },
      })
    )
  );
}

/* ---------- inject control + preview enhancer ---------- */
addFilter('editor.BlockEdit', 'newfolio/nav-icon-control', function (BlockEdit) {
  return function (props) {
    if (props.name !== 'core/navigation-link') {
      return createElement(BlockEdit, props);
    }

    // Keep preview in sync when icon changes
    useEffect(function () {
      ensureEditorIcon(props.clientId, props.attributes.icon || '');
    }, [props.clientId, props.attributes.icon]);

    // Also run once a bit later for iframe races
    useEffect(function () {
      const t = setTimeout(function () {
        ensureEditorIcon(props.clientId, props.attributes.icon || '');
      }, 300);
      return function () { clearTimeout(t); };
    }, []);

    return createElement(
      Fragment,
      {},
      createElement(BlockEdit, props),
      createElement(NavIconControl, props)
    );
  };
});
