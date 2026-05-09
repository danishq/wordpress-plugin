/**
 * Adflipr checkout/cart sync — Works with Checkout Blocks (React) + Store API.
 *
 * Collects cart totals via GET /wc/store/v1/cart, billing fields from the DOM,
 * and POSTs debounced payloads to /wp-json/adflipr/v1/checkout-sync.
 *
 * @package AdfliprCartTracker
 */
(function () {
	'use strict';

	var cfg = window.adfliprTracker || {};
	var REST_URL = cfg.restUrl || '';
	var CART_URL = cfg.storeCartUrl || '';
	var NONCE = cfg.nonce || '';
	var DEBOUNCE_MS = Math.max(1000, parseInt(cfg.debounceMs, 10) || 1000);
	var SESSION_KEY = cfg.sessionKey || 'adflipr_tracker_sid';
	var SOURCE_CART = cfg.sourceCart || 'cart_page_frontend';
	var SOURCE_CHECKOUT = cfg.sourceCheckout || 'checkout_block_frontend';

	if (!REST_URL || !NONCE) {
		return;
	}

	function uuid() {
		if (window.crypto && typeof window.crypto.randomUUID === 'function') {
			return window.crypto.randomUUID();
		}
		return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
			var r = (Math.random() * 16) | 0;
			var v = c === 'x' ? r : (r & 0x3) | 0x8;
			return v.toString(16);
		});
	}

	function getSessionId() {
		try {
			var existing = window.localStorage.getItem(SESSION_KEY);
			if (existing && existing.length >= 8) {
				return existing;
			}
			var created = uuid();
			window.localStorage.setItem(SESSION_KEY, created);
			return created;
		} catch (e) {
			return uuid();
		}
	}

	function getDraftIdFromStorage() {
		try {
			var id = window.sessionStorage.getItem('adflipr_server_draft');
			return id ? parseInt(id, 10) : 0;
		} catch (e) {
			return 0;
		}
	}

	function setDraftIdInStorage(id) {
		if (!id) {
			return;
		}
		try {
			window.sessionStorage.setItem('adflipr_server_draft', String(id));
		} catch (e) {}
	}

	function debounce(fn, wait) {
		var t;
		return function () {
			var ctx = this;
			var args = arguments;
			clearTimeout(t);
			t = setTimeout(function () {
				fn.apply(ctx, args);
			}, wait);
		};
	}

	function mapCartItems(cart) {
		if (!cart || !Array.isArray(cart.items)) {
			return [];
		}
		return cart.items.map(function (line) {
			var wcPid = line.id != null ? parseInt(line.id, 10) : 0;
			var totals = line.totals || {};
			var sub = totals.line_subtotal != null ? parseFloat(totals.line_subtotal) : 0;
			var tot = totals.line_total != null ? parseFloat(totals.line_total) : 0;
			return {
				product_id: wcPid,
				variation_id: line.type === 'variation' ? wcPid : 0,
				quantity: line.quantity != null ? parseFloat(line.quantity) : 0,
				subtotal: sub,
				total: tot,
				key: line.key || '',
			};
		});
	}

	function scrapeBillingFromDom() {
		var root =
			document.querySelector('.wc-block-checkout') ||
			document.querySelector('form.checkout') ||
			document.body;

		var email =
			pickInputValue(root, [
				'#email',
				'#billing-email',
				'#contact',
				'input[id*="billing-email"]',
				'input[id*="BillingEmail"]',
				'input[id*="email"]',
				'.wc-block-checkout-contact-phone-email input[type="email"]',
				'.wc-block-components-text-input input[type="email"]',
				'.wc-block-checkout input[type="email"]',
				'input[name="email"]',
				'input[name="billing_email"]',
				'input[autocomplete="email"]',
				'input[type="email"]',
			]) || '';

		if (!email) {
			var emailInputs = root.querySelectorAll('input[type="email"]');
			for (var ei = 0; ei < emailInputs.length; ei++) {
				var val = emailInputs[ei].value && String(emailInputs[ei].value).trim();
				if (val) {
					email = val;
					break;
				}
			}
		}

		var firstName = pickInputValue(root, [
			'#billing-first_name',
			'input[name="billing_first_name"]',
			'input[autocomplete="given-name"]',
		]);

		var lastName = pickInputValue(root, [
			'#billing-last_name',
			'input[name="billing_last_name"]',
			'input[autocomplete="family-name"]',
		]);

		var billingAddress = {
			first_name: firstName,
			last_name: lastName,
			company: pickInputValue(root, ['#billing-company', 'input[name="billing_company"]']),
			address_1: pickInputValue(root, ['#billing-address_1', 'input[name="billing_address_1"]']),
			address_2: pickInputValue(root, ['#billing-address_2', 'input[name="billing_address_2"]']),
			city: pickInputValue(root, ['#billing-city', 'input[name="billing_city"]']),
			state: pickInputValue(root, ['#billing-state', 'select[name="billing_state"]', 'input[name="billing_state"]']),
			postcode: pickInputValue(root, ['#billing-postcode', 'input[name="billing_postcode"]']),
			country: pickInputValue(root, ['#billing-country', 'select[name="billing_country"]']),
			phone: pickInputValue(root, ['#billing-phone', 'input[name="billing_phone"]']),
			email: email,
		};

		return {
			email: email,
			first_name: firstName,
			last_name: lastName,
			billing_address: billingAddress,
			billing_city: billingAddress.city || '',
		};
	}

	function pickInputValue(root, selectors) {
		for (var i = 0; i < selectors.length; i++) {
			var el = root.querySelector(selectors[i]);
			if (el && 'value' in el && el.value) {
				return String(el.value).trim();
			}
		}
		return '';
	}

	function isCheckoutContext() {
		return document.body.classList.contains('woocommerce-checkout') ||
			!!document.querySelector('.wc-block-checkout');
	}

	function isCartContext() {
		return document.body.classList.contains('woocommerce-cart') ||
			!!document.querySelector('.wc-block-cart');
	}

	function fetchStoreCart() {
		if (!CART_URL) {
			return Promise.resolve(null);
		}
		return fetch(CART_URL, {
			method: 'GET',
			credentials: 'same-origin',
			headers: {
				'X-WP-Nonce': NONCE,
				Accept: 'application/json',
			},
		})
			.then(function (r) {
				return r.ok ? r.json() : null;
			})
			.catch(function () {
				return null;
			});
	}

	function postSync(payload) {
		return fetch(REST_URL, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': NONCE,
			},
			body: JSON.stringify(payload),
		})
			.then(function (r) {
				return r.json();
			})
			.then(function (data) {
				if (data && data.draft_order_id) {
					setDraftIdInStorage(data.draft_order_id);
				} else if (data && data.server_draft_id) {
					setDraftIdInStorage(data.server_draft_id);
				}
				return data;
			})
			.catch(function () {
				return null;
			});
	}

	var syncInFlight = false;

	function buildAndSend() {
		if (syncInFlight) {
			return;
		}
		syncInFlight = true;

		var fields = scrapeBillingFromDom();
		var sessionId = getSessionId();
		var source = isCheckoutContext() ? SOURCE_CHECKOUT : SOURCE_CART;

		fetchStoreCart().then(function (cart) {
			var items = mapCartItems(cart);
			var subtotal = cart && cart.totals && cart.totals.total_items
				? parseFloat(cart.totals.total_items)
				: 0;
			var total = cart && cart.totals && cart.totals.total_price
				? parseFloat(cart.totals.total_price)
				: 0;

			var payload = {
				session_id: sessionId,
				email: fields.email,
				first_name: fields.first_name,
				last_name: fields.last_name,
				billing_city: fields.billing_city,
				billing_address: fields.billing_address,
				cart_items: items,
				subtotal: subtotal,
				cart_subtotal: subtotal,
				total: total,
				source: source,
				draft_order_id: getDraftIdFromStorage(),
				event: 'checkout_updated',
			};

			return postSync(payload);
		}).finally(function () {
			syncInFlight = false;
		});
	}

	var debouncedSync = debounce(buildAndSend, DEBOUNCE_MS);

	function bindListeners() {
		document.addEventListener('input', debouncedSync, true);
		document.addEventListener('change', debouncedSync, true);
		document.addEventListener(
			'blur',
			function (e) {
				if (e.target && e.target.matches && e.target.matches('input, select, textarea')) {
					debouncedSync();
				}
			},
			true
		);

		// Classic fragments refresh.
		if (window.jQuery) {
			window.jQuery(document.body).on('updated_cart_totals updated_checkout', debouncedSync);
		}

		// Backup heartbeat so refresh / failed payment still reconciles (~2 min).
		window.setInterval(debouncedSync, 120000);

		// Initial pass after paint (blocks hydrate asynchronously).
		window.setTimeout(debouncedSync, 1500);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', bindListeners);
	} else {
		bindListeners();
	}

	// Optional: WooCommerce Blocks / wp.hooks lifecycle when present.
	if (window.wp && wp.hooks && typeof wp.hooks.addAction === 'function') {
		try {
			wp.hooks.addAction('experimental__woocommerce_blocks-checkout-render-checkout-form', 'adflipr-tracker', debouncedSync);
		} catch (e) {}
	}
})();
