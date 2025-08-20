const DEFAULT_ICON = 'sticky-note';
const { addFilter } = wp.hooks;
const { InspectorControls } = wp.blockEditor || wp.editor;
const { PanelBody, TextControl } = wp.components;
const { Fragment, createElement, useEffect } = wp.element;

/* ----------------- helpers ----------------- */

// find the block editor canvas document (iframe if present)
function getCanvasDocument() {
  // Common selectors across recent WP versions:
  // - iframe[name="editor-canvas"]
  // - .block-editor iframe (site editor)
  // - .edit-post-visual-editor__content-area iframe (post editor)
  const iframe =
    document.querySelector('iframe[name="editor-canvas"]') ||
    document.querySelector('.edit-post-visual-editor__content-area iframe') ||
    document.querySelector('.block-editor iframe');

  return iframe && iframe.contentDocument ? iframe.contentDocument : document;
}

// ensure the iframe document has lucide UMD loaded, then run callback
function ensureLucideInIframe(doc, then) {
  const w = doc && doc.defaultView ? doc.defaultView : null;

  // already present?
  if (w && w.lucide && typeof w.lucide.createIcons === 'function') {
    if (then) then();
    return;
  }

  // already loading?
  const existing = doc.getElementById('newfolio-lucide-iframe');
  if (existing) {
    existing.addEventListener('load', function () { if (then) then(); }, { once: true });
    return;
  }

  // inject <script> into the iframe <head>
  const src =
    (window.newfolioNavIcons && window.newfolioNavIcons.lucideSrc) ||
    'https://unpkg.com/lucide@latest/dist/umd/lucide.js';

  const s = doc.createElement('script');
  s.id = 'newfolio-lucide-iframe';
  s.src = src;
  s.onload = function () {
    if (then) then();
  };
  doc.head.appendChild(s);
}

function findNavClickable(wrapper) {
  // Try the most specific first, then fan out
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

function wrapEditorLabel(clickable, doc) {
  // If we already have a .nav-label, do nothing
  let label = clickable.querySelector('span.nav-label');
  if (label) return;

  label = doc.createElement('span');
  label.className = 'nav-label';

  // Move all non-icon nodes into the label wrapper
  const toMove = [];
  clickable.childNodes.forEach(function (node) {
    if (node.nodeType === 1) {
      // Skip existing icon nodes
      if (node.matches('i[data-lucide], svg.lucide, svg[data-lucide]')) return;
    }
    toMove.push(node);
  });
  toMove.forEach(function (n) { label.appendChild(n); });

  clickable.appendChild(label);
}


// idempotently ensure an <i data-lucide="..."> exists as the FIRST child of the anchor
// idempotently ensure an icon exists as the FIRST child of the anchor (editor iframe-safe)
// idempotently ensure an icon exists as the FIRST child of the anchor (editor iframe-safe)
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
    setTimeout(function(){ ensureEditorIcon(clientId, iconSlug); }, 150);
    return;
  }

  clickable.classList.add('has-lucide-icon');

  // Capture a stable label for tooltip (once)
  // Prefer an existing nav-label; otherwise use current textContent
  if (!clickable.hasAttribute('data-newfolio-label')) {
    const existingLabel = clickable.querySelector('.nav-label');
    const text = existingLabel
      ? existingLabel.textContent
      : (clickable.textContent || '').trim();
    if (text) {
      clickable.setAttribute('data-newfolio-label', text);
    }
  }

  // Idempotency: last applied slug
  const currentSlug = clickable.getAttribute('data-newfolio-icon') || '';

  // Detect existing lucide output
  const hasLucideSvg =
    clickable.querySelector('svg.lucide, svg[data-lucide]') !== null;

  // If slug unchanged and we already have a lucide SVG => do nothing
  if (iconSlug && iconSlug === currentSlug && hasLucideSvg) {
    return;
  }

  // If slug cleared: remove any prior icon and exit
  if (!iconSlug) {
    clickable.removeAttribute('data-newfolio-icon');
    clickable.querySelectorAll('svg.lucide, svg[data-lucide], i[data-lucide]').forEach(function(n){ n.remove(); });
    return;
  }

  // Slug changed (or we only had <i>): clean all old icons first
  clickable.querySelectorAll('svg.lucide, svg[data-lucide], i[data-lucide]').forEach(function(n){ n.remove(); });

  // Insert a fresh <i data-lucide="..."> as the first child
  const i = doc.createElement('i');
  i.setAttribute('data-lucide', iconSlug);
  clickable.insertBefore(i, clickable.firstChild);

  // Mark which slug we applied
  clickable.setAttribute('data-newfolio-icon', iconSlug);

  // Ensure lucide is loaded INSIDE the iframe and convert the <i> -> <svg>
  ensureLucideInIframe(doc, function () {
    try { doc.defaultView.lucide.createIcons(); } catch (e) {}
  });
}


/* -------------- attributes -------------- */

addFilter('blocks.registerBlockType', 'newfolio/nav-icon-attr', function (settings, name) {
  if (name !== 'core/navigation-link') return settings;

  settings.attributes = Object.assign({}, settings.attributes, {
    icon: { type: 'string', default: DEFAULT_ICON }, // was: default: ''
  });

  return settings;
});

/* ----------- editor-only enhancer ---------- */

function EditorIconEnhancer(props) {
  const icon = (props.attributes && props.attributes.icon) || '';

  useEffect(function () {
    ensureEditorIcon(props.clientId, icon);
  }, [props.clientId, icon]);

  // also try once after initial mount to catch iframe late-load
  useEffect(function () {
    const t = setTimeout(function () {
      ensureEditorIcon(props.clientId, icon);
    }, 300);
    return function () { clearTimeout(t); };
  }, []);

  return null;
}

/* -------------- inspector UI -------------- */

function NavIconControl(props) {
  return createElement(
    InspectorControls,
    {},
    createElement(
      PanelBody,
      { title: 'Navigation Icon (Lucide)', initialOpen: true },
      createElement(TextControl, {
        label: 'Icon slug (e.g. home, search, user)',
        help: 'Type any Lucide icon slug from lucide.dev. Leave blank for no icon.',
        value: props.attributes.icon || '',
        placeholder: 'home',
        onChange: function (val) {
          props.setAttributes({ icon: (val || '').trim() });
        },
      })
    )
  );
}

/* -------------- inject into editor --------- */

addFilter('editor.BlockEdit', 'newfolio/nav-icon-control', function (BlockEdit) {
  return function (props) {
    if (props.name !== 'core/navigation-link') {
      return createElement(BlockEdit, props);
    }

    return createElement(
      Fragment,
      {},
      createElement(BlockEdit, props),
      createElement(EditorIconEnhancer, props),
      createElement(NavIconControl, props)
    );
  };
});

/* -------------- save extra props ----------- */

addFilter(
  'blocks.getSaveContent.extraProps',
  'newfolio/nav-icon-save',
  function (extraProps, blockType, attributes) {
    if (blockType.name === 'core/navigation-link' && attributes.icon) {
      extraProps['data-icon'] = attributes.icon;
      extraProps.className = (extraProps.className || '') + ' has-lucide-icon';
    }
    return extraProps;
  }
);

window.newfolioEnhanceAll = function () {
  const doc = getCanvasDocument();
  const nodes = doc.querySelectorAll('[data-type="core/navigation-link"]');
  console.log('newfolio› enhanceAll found', nodes.length, 'navigation-link blocks');
  nodes.forEach(function (node) {
    const id = node.getAttribute('data-block');
    if (id) ensureEditorIcon(id, null); // runs once with current attribute (EditorIconEnhancer also handles updates)
  });
};
