/* Theme dropdown for Site Editor */
(function () {
    if (!window.wp || !wp.data) return;
  
    const selectCore = () => wp.data.select('core');
    const selectEditSite = () => wp.data.select('core/edit-site');
    const dispatchCore = () => wp.data.dispatch('core');
    
    let userIsInteracting = false;
  
    function toggleDarkModeClass(theme) {
      // Find the iframe body (Site Editor content)
      const iframe = document.querySelector('iframe[name="editor-canvas"]');
      const iframeBody = iframe ? iframe.contentDocument?.body : null;
      
      if (iframeBody) {
        if (theme === 'dark') {
          iframeBody.classList.add('newfolio-darkmode');
        } else {
          iframeBody.classList.remove('newfolio-darkmode');
        }
      }
    }
  
    function findTemplateSidebar() {
      let el = document.querySelector('[aria-label="Template"]');
      if (el) return el;
      el = document.querySelector('.edit-site-sidebar__panel') || document.querySelector('.interface-complementary-area');
      return el || null;
    }
  
    function currentTemplateRecord() {
      const postType = selectEditSite()?.getEditedPostType?.();
      const postId = selectEditSite()?.getEditedPostId?.();
      if ((postType !== 'wp_template' && postType !== 'wp_template_part') || !postId) return null;
      
      // Try multiple ways to get the record
      let rec = selectCore()?.getEditedEntityRecord?.('postType', postType, postId);
      
      // If that's empty, try getting the current post
      if (!rec || Object.keys(rec).length === 0) {
        rec = selectCore()?.getCurrentPost?.();
      }
      
      // If still empty, try getting the entity record directly
      if (!rec || Object.keys(rec).length === 0) {
        rec = selectCore()?.getEntityRecord?.('postType', postType, postId);
      }
      
      // Make a deep copy to prevent the record from being mutated
      const recordCopy = rec ? JSON.parse(JSON.stringify(rec)) : {};
      
      // Get the current value from database via AJAX
      const templateSlug = postId.split('//').pop();
      
      const formData = new FormData();
      formData.append('action', 'get_newfolio_theme');
      formData.append('post_type', postType);
      formData.append('template_slug', templateSlug);
      formData.append('nonce', wpApiSettings.nonce || '');
      
      fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success && data.data) {
          // Update the dropdown if it exists and value is different
          const select = document.querySelector('#newfolio-theme-select');
          if (select && select.value !== data.data) {
            select.value = data.data;
            toggleDarkModeClass(data.data);
          }
        }
      })
      .catch(err => {
        // AJAX failed, use default
        const select = document.querySelector('#newfolio-theme-select');
        if (select) {
          select.value = 'light';
          toggleDarkModeClass('light');
        }
      });
      
      return rec ? { postId, record: recordCopy, postType } : null;
    }
  
    function updateDropdownValue(forceUpdate = false) {
      const select = document.querySelector('#newfolio-theme-select');
      if (!select) return;
      
      // Only update if this is the initial load (forceUpdate = true) or if user isn't interacting
      if (!forceUpdate && userIsInteracting) return;
      
      const ctx = currentTemplateRecord();
      if (!ctx) return;
      
      const current = (ctx.record.meta && ctx.record.meta.newfolio_theme) || 'light';
      
      if (select.value !== current) {
        select.value = current;
      }
    }

    function renderDropdown() {
      const host = findTemplateSidebar();
      const ctx = currentTemplateRecord();
      
      
      if (!host || !ctx) return;
  
      // Prevent duplicates
      if (host.querySelector('#newfolio-theme-select')) {
        updateDropdownValue(true); // Force update on initial load
        return;
      }
  
      const wrap = document.createElement('div');
      wrap.style.padding = '12px 16px';
      wrap.style.borderTop = '1px solid var(--wp-admin-border-color, #ddd)';
  
      const label = document.createElement('label');
      label.textContent = 'Theme';
      label.setAttribute('for', 'newfolio-theme-select');
      label.style.display = 'block';
      label.style.fontWeight = '600';
      label.style.marginBottom = '8px';
  
      const select = document.createElement('select');
      select.id = 'newfolio-theme-select';
      select.style.width = '100%';
      select.style.padding = '6px 8px';
      select.style.borderRadius = '4px';
  
      select.innerHTML = `
        <option value="light">Light mode</option>
        <option value="dark">Dark mode</option>
      `;
      
      // Set initial value based on template name to avoid flicker
      const templateSlug = ctx.postId.split('//').pop();
      const expectedDefaults = {
        'home': 'dark',
        'search': 'dark', 
        'archive': 'dark',
        'singular': 'dark',
        'single-snap': 'light',
        '404': 'light',
        'front-page': 'light',
        'index': 'light',
        'page': 'light',
        'blog-post-alt': 'dark'
      };
      const initialValue = expectedDefaults[templateSlug] || 'light';
      select.value = initialValue;
      toggleDarkModeClass(initialValue);
  
      select.addEventListener('focus', () => {
        userIsInteracting = true;
      });
      
      select.addEventListener('blur', () => {
        // Reset after a short delay to allow for change events
        setTimeout(() => {
          userIsInteracting = false;
        }, 100);
      });
  
      select.addEventListener('change', () => {
        // Update WordPress data store to activate save button
        dispatchCore().editEntityRecord('postType', ctx.postType, ctx.postId, {
          meta: {
            newfolio_theme: select.value
          }
        });
        
        // Toggle dark mode class on body
        toggleDarkModeClass(select.value);
      });
  
      wrap.appendChild(label);
      wrap.appendChild(select);
      host.appendChild(wrap);
    }
  
    // Try to render immediately
    setTimeout(renderDropdown, 100);
  
    // Re-try when the editor UI changes (but DON'T reset dropdown values)
    let lastCheck = 0;
    const mo = new MutationObserver(() => {
      const now = Date.now();
      if (now - lastCheck < 1000) return; // Throttle to max once per 1000ms
      lastCheck = now;
      
      // Add a delay to let the data store update
      setTimeout(() => {
        const ctx = currentTemplateRecord();
        if (ctx) {
          // Only render if dropdown doesn't exist - don't update existing dropdown
          const host = findTemplateSidebar();
          if (host && !host.querySelector('#newfolio-theme-select')) {
            renderDropdown();
          }
        }
      }, 200); // Wait 200ms for data store to update
    });
    mo.observe(document.body, { childList: true, subtree: true });
  
    // Also try on DOM ready
    document.addEventListener('DOMContentLoaded', renderDropdown);
  })();
  