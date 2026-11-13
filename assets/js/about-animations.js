(function () {
	var COUNT_DURATION = 1200;
	var STAGGER_STEP = 80;
	var WORD_TRAVEL = 120;
	var GHOST_TRAVEL = 40;

	var highlights = document.querySelector('.about-highlights');
	if (!highlights) return;

	// Reduced motion: the markup already holds the final numbers and the CSS
	// default state is the final state, so the correct behaviour is to do nothing.
	if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

	if (!('IntersectionObserver' in window)) return;

	// Count the stat figures up from zero as each one enters the viewport
	var numbers = document.querySelectorAll('.about-stat__number');

	function countUp(el) {
		var target = parseInt(el.textContent, 10);
		if (isNaN(target)) return;

		var started = null;

		function step(now) {
			if (started === null) started = now;

			var progress = Math.min((now - started) / COUNT_DURATION, 1);
			var eased = 1 - Math.pow(1 - progress, 3);

			el.textContent = String(Math.round(target * eased));

			if (progress < 1) {
				requestAnimationFrame(step);
			}
		}

		el.textContent = '0';
		requestAnimationFrame(step);
	}

	if (numbers.length) {
		var countObserver = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (!entry.isIntersecting) return;
				countObserver.unobserve(entry.target);
				countUp(entry.target);
			});
		}, { threshold: 0.4 });

		numbers.forEach(function (number) {
			countObserver.observe(number);
		});
	}

	// Drift the decorative words as their sections scroll through the viewport.
	// Only the custom property is written — plans 08 and 09 own the transform.
	var word = highlights.querySelector('.about-highlights__word');
	var ghost = document.querySelector('.about-what-i-do__ghost');
	var wideScreen = window.matchMedia('(min-width: 960px)');
	var ticking = false;

	function viewportProgress(el) {
		var rect = el.getBoundingClientRect();
		var span = window.innerHeight + rect.height;
		if (span <= 0) return 0;

		// 0 as the element's top edge reaches the bottom of the viewport,
		// 1 once its bottom edge has cleared the top.
		var progress = (window.innerHeight - rect.top) / span;
		return Math.min(Math.max(progress, 0), 1);
	}

	function updateParallax() {
		ticking = false;

		// The word is display:none below 960px, so there is nothing to move.
		if (word && wideScreen.matches) {
			word.style.setProperty('--word-shift', (viewportProgress(highlights) * WORD_TRAVEL) + 'px');
		}

		if (ghost) {
			ghost.style.setProperty('--ghost-shift', ((viewportProgress(ghost) - 0.5) * GHOST_TRAVEL) + 'px');
		}
	}

	function onScroll() {
		if (ticking) return;
		ticking = true;
		requestAnimationFrame(updateParallax);
	}

	if (word || ghost) {
		window.addEventListener('scroll', onScroll, { passive: true });
		window.addEventListener('resize', onScroll, { passive: true });
		updateParallax();
	}

	// Rise the "What I do" items in sequence as the section enters
	var whatIDo = document.querySelector('.about-what-i-do');
	if (!whatIDo) return;

	var items = whatIDo.querySelectorAll('.about-item');
	if (!items.length) return;

	whatIDo.classList.add('js-stagger');

	var staggerObserver = new IntersectionObserver(function (entries) {
		entries.forEach(function (entry) {
			if (!entry.isIntersecting) return;

			staggerObserver.unobserve(entry.target);

			items.forEach(function (item, i) {
				item.style.transitionDelay = (i * STAGGER_STEP) + 'ms';
			});

			whatIDo.classList.add('is-visible');

			// Clean up inline delays once the sequence has played out
			setTimeout(function () {
				items.forEach(function (item) {
					item.style.transitionDelay = '';
				});
			}, 500 + (items.length * STAGGER_STEP));
		});
	}, { threshold: 0.2 });

	staggerObserver.observe(whatIDo);
})();
