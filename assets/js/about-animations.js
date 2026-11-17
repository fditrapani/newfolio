(function () {
	var COUNT_DURATION = 1200;
	var STAGGER_STEP = 120;
	var WORD_TRAVEL = 420;
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

	// 0 while the element's top edge is at the bottom of the viewport, 1 once
	// that top edge has reached the top of it. Deliberately unclamped: it keeps
	// counting past 1 as you carry on scrolling and below 0 on the way back up,
	// so whatever it drives stays tied to the scroll position instead of
	// parking once the element is in place.
	function approachProgress(el) {
		var rect = el.getBoundingClientRect();
		if (window.innerHeight <= 0) return 1;

		return (window.innerHeight - rect.top) / window.innerHeight;
	}

	function updateParallax() {
		ticking = false;

		// Below 960px the word is horizontal and in flow, and the stylesheet
		// gives it no transform, so there is nothing to move.
		if (word && wideScreen.matches) {
			// Descends with the scroll: WORD_TRAVEL above centre as the band
			// enters, 0 — the whole word legible — as the band's top edge
			// reaches the top of the viewport, and on down and out of the
			// band's clipped bottom as you keep going.
			word.style.setProperty('--word-shift', (-(1 - approachProgress(highlights)) * WORD_TRAVEL) + 'px');
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

	// Rise a section's items in sequence as each one reaches the viewport.
	// Observing the section instead fired the whole sequence while it was
	// still off screen. Each item's delay is read from its staggerDelay data
	// attribute, so the caller decides what cascades against what.
	function staggerIn(section, items) {
		section.classList.add('js-stagger');

		var observer = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (!entry.isIntersecting) return;

				var item = entry.target;
				observer.unobserve(item);

				item.style.transitionDelay = item.dataset.staggerDelay + 'ms';
				item.classList.add('is-visible');

				// Clean up the inline delay once the entrance has played out
				setTimeout(function () {
					item.style.transitionDelay = '';
				}, 700 + (2 * STAGGER_STEP));
			});
		}, { threshold: 0.25, rootMargin: '0px 0px -10% 0px' });

		items.forEach(function (item) {
			observer.observe(item);
		});
	}

	// Reveal every item together as soon as one trigger element arrives. Used
	// where the items cannot be observed individually because they are not all
	// in the viewport's path.
	function revealTogether(section, trigger, items) {
		section.classList.add('js-stagger');

		var observer = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (!entry.isIntersecting) return;

				observer.unobserve(entry.target);

				items.forEach(function (item) {
					item.classList.add('is-visible');
				});
			});
		}, { threshold: 0.15 });

		observer.observe(trigger);
	}

	// "What I do": the items, then the button that closes the section.
	var whatIDo = document.querySelector('.about-what-i-do');

	if (whatIDo) {
		var items = [];

		whatIDo.querySelectorAll('.about-item').forEach(function (item, i) {
			// The delay cascades the two items that share a row.
			item.dataset.staggerDelay = (i % 2) * STAGGER_STEP;
			items.push(item);
		});

		// The button sits alone under the grid, so it has nothing to cascade
		// against and arrives on its own as it reaches the viewport.
		var cta = whatIDo.querySelector('.wp-block-buttons');

		if (cta) {
			cta.dataset.staggerDelay = 0;
			items.push(cta);
		}

		if (items.length) staggerIn(whatIDo, items);
	}

	// Highlights cards. From 800px up they are a static two-column grid, so
	// each one is observed on its own and rises as it arrives, the left column
	// leading the right. Below 800px they are a horizontal scroll row: the
	// cards parked off to the right never intersect the viewport, so the whole
	// row fades in together when the band itself arrives.
	var columns = highlights.querySelectorAll('.about-highlights__column');
	var cards = [];

	columns.forEach(function (column, columnIndex) {
		column.querySelectorAll(':scope > *').forEach(function (card) {
			card.dataset.staggerDelay = columnIndex * STAGGER_STEP;
			cards.push(card);
		});
	});

	// The CTA closes the band below the cards, so it has nothing to cascade
	// against and arrives on its own.
	var highlightsCta = highlights.querySelector('.wp-block-buttons');

	if (highlightsCta) {
		highlightsCta.dataset.staggerDelay = 0;
		cards.push(highlightsCta);
	}

	if (cards.length) {
		if (window.matchMedia('(min-width: 800px)').matches) {
			staggerIn(highlights, cards);
		} else {
			revealTogether(highlights, highlights.querySelector('.about-highlights__grid'), cards);
		}
	}
})();
