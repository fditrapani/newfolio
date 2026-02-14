(function () {
	var TRANSITION_DURATION = 300;

	// Fade content in on page load
	// Body starts with 'newfolio-loading' (added via PHP) so content is hidden before first paint.
	// Double-rAF ensures the hidden state is painted before triggering the transition.

	// Stagger masonry cards on front page in random order
	var cards = document.querySelectorAll('.masonry__card');
	if (cards.length) {
		var order = Array.from(cards, function (_, i) { return i; });
		for (var i = order.length - 1; i > 0; i--) {
			var j = Math.floor(Math.random() * (i + 1));
			var tmp = order[i]; order[i] = order[j]; order[j] = tmp;
		}
		order.forEach(function (cardIndex, slot) {
			cards[cardIndex].style.transitionDelay = (Math.floor(slot / 2) * 50) + 'ms';
		});
	}

	// Stagger blog/archive/search list items
	var listItems = document.querySelectorAll('.link-card');
	listItems.forEach(function (item, i) {
		item.style.transitionDelay = (i * 100) + 'ms';
	});

	requestAnimationFrame(function () {
		requestAnimationFrame(function () {
			document.body.classList.remove('newfolio-loading');

			// Clean up inline delays after animations complete
			if (cards.length) {
				setTimeout(function () {
					cards.forEach(function (card) {
						card.style.transitionDelay = '';
					});
				}, 400 + (cards.length * 50));
			}

			if (listItems.length) {
				setTimeout(function () {
					listItems.forEach(function (item) {
						item.style.transitionDelay = '';
					});
				}, 400 + (listItems.length * 50));
			}
		});
	});

	function shouldTransition(anchor) {
		// Only same-origin links
		if (anchor.origin !== window.location.origin) return false;

		// Skip hash-only links, mailto, tel, javascript
		var href = anchor.getAttribute('href') || '';
		if (!href || href.charAt(0) === '#' || href.startsWith('mailto:') || href.startsWith('tel:')) return false;

		// Skip links that open in a new tab/window
		if (anchor.target && anchor.target !== '_self') return false;

		// Skip download links
		if (anchor.hasAttribute('download')) return false;

		// Skip wp-admin links
		if (href.indexOf('/wp-admin') !== -1 || href.indexOf('/wp-login') !== -1) return false;

		return true;
	}

	document.addEventListener('click', function (e) {
		// Find the closest anchor element
		var anchor = e.target.closest('a');
		if (!anchor || !shouldTransition(anchor)) return;

		// Don't intercept if modifier keys are held (open in new tab, etc.)
		if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;

		e.preventDefault();

		var destination = anchor.href;

		// If clicking a nav link, move the active state immediately
		var navItem = anchor.closest('.wp-block-navigation-item');
		if (navItem && navItem.closest('.newfolio__navigation')) {
			var currentActive = document.querySelector('.newfolio__navigation .current-menu-item');
			if (currentActive) {
				currentActive.classList.remove('current-menu-item');
			}
			navItem.classList.add('current-menu-item');
		}

		document.body.classList.add('newfolio-transitioning');

		setTimeout(function () {
			window.location.href = destination;
		}, TRANSITION_DURATION);
	});

	// When navigating back via browser back button, remove the class
	// so the page doesn't stay faded out from bfcache
	window.addEventListener('pageshow', function (e) {
		if (e.persisted) {
			document.body.classList.remove('newfolio-transitioning');
		}
	});
})();
