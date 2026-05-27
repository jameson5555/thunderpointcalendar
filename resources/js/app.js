import './bootstrap';

import Alpine from 'alpinejs';

const flashScrollPositionKey = 'tp:flash-scroll-y';

document.addEventListener('submit', (event) => {
	const form = event.target;

	if (! (form instanceof HTMLFormElement)) {
		return;
	}

	const formMethod = (form.getAttribute('method') ?? 'GET').toUpperCase();
	const formData = new FormData(form);
	const spoofedMethod = formData.get('_method');
	const requestMethod = typeof spoofedMethod === 'string' && spoofedMethod !== ''
		? spoofedMethod.toUpperCase()
		: formMethod;

	if (requestMethod === 'GET') {
		return;
	}

	window.sessionStorage.setItem(flashScrollPositionKey, String(window.scrollY));
});

window.Alpine = Alpine;

Alpine.data('flashToasts', (initialToasts = []) => ({
	toasts: initialToasts.map((toast) => ({
		...toast,
		visible: false,
		closing: false,
	})),

	start() {
		this.restoreScrollPosition();

		this.toasts.forEach((toast) => {
			window.requestAnimationFrame(() => {
				toast.visible = true;
			});
		});
	},

	restoreScrollPosition() {
		const storedTop = window.sessionStorage.getItem(flashScrollPositionKey);

		if (storedTop === null) {
			return;
		}

		window.sessionStorage.removeItem(flashScrollPositionKey);

		const top = Number.parseInt(storedTop, 10);

		if (Number.isNaN(top)) {
			return;
		}

		window.requestAnimationFrame(() => {
			window.scrollTo({ top, left: 0, behavior: 'auto' });
		});
	},

	dismiss(id) {
		const toast = this.toasts.find((item) => item.id === id);

		if (! toast || toast.closing) {
			return;
		}

		toast.closing = true;
		toast.visible = false;

		window.setTimeout(() => {
			this.toasts = this.toasts.filter((item) => item.id !== id);
		}, 200);
	},
}));

Alpine.start();
