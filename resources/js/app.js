import './bootstrap';

import Alpine from 'alpinejs';
import flatpickr from 'flatpickr';

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

Alpine.data('dateRangePicker', ({
	startValue = '',
	endValue = '',
	emptySummary = 'Choose arrival and departure',
	disabledRangesByArea = {},
	areaInputName = 'living_area_ids[]',
} = {}) => {
	let pickerInstance = null;

	return {
		areaInputs: [],
		currentUnavailableRanges: [],
		disabledRangesByArea,
		invalidRangeMessage: '',
		hasSelection: false,
		summary: emptySummary,

		init() {
			const initialDates = [startValue, endValue].filter(Boolean);
			this.areaInputs = this.findAreaInputs(areaInputName);

			pickerInstance = flatpickr(this.$refs.display, {
				mode: 'range',
				dateFormat: 'Y-m-d',
				disableMobile: true,
				disable: this.currentUnavailableRanges,
				defaultDate: initialDates,
				showMonths: window.matchMedia('(min-width: 768px)').matches ? 2 : 1,
				monthSelectorType: 'static',
				position: 'auto center',
				prevArrow: '<span aria-hidden="true">&larr;</span>',
				nextArrow: '<span aria-hidden="true">&rarr;</span>',
				onReady: (_selectedDates, _dateString, instance) => {
					pickerInstance = instance;
					instance.calendarContainer.classList.add('tp-flatpickr');
					this.refreshUnavailableRanges(instance);
					this.syncSelection(instance.selectedDates);
				},
				onChange: (selectedDates) => {
					this.syncSelection(selectedDates);
				},
			});

			this.areaInputs.forEach((input) => {
				input.addEventListener('change', () => {
					this.refreshUnavailableRanges();
				});
			});

			this.$refs.display.addEventListener('blur', () => {
				this.updateValidity(pickerInstance?.selectedDates ?? []);
			});
		},

		findAreaInputs(areaInputName) {
			const form = this.$root.closest('form');

			if (! form) {
				return [];
			}

			return Array.from(form.querySelectorAll(`input[name="${areaInputName}"]`));
		},

		refreshUnavailableRanges(instance = pickerInstance) {
			if (! instance) {
				return;
			}

			const selectedAreaIds = this.areaInputs
				.filter((input) => input.checked)
				.map((input) => String(input.value));

			const seen = new Set();
			this.currentUnavailableRanges = selectedAreaIds.flatMap((areaId) => {
				return (this.disabledRangesByArea[areaId] ?? []).filter((range) => {
					const key = `${range.from}:${range.to}`;

					if (seen.has(key)) {
						return false;
					}

					seen.add(key);
					return true;
				});
			});

			instance.set('disable', this.currentUnavailableRanges);

			if (this.selectionConflictsWithUnavailableRange(instance.selectedDates)) {
				this.invalidRangeMessage = 'Those dates are unavailable for one or more selected living areas.';
				instance.clear();
				this.syncSelection([]);
				return;
			}

			this.invalidRangeMessage = '';
			this.updateValidity(instance.selectedDates);
		},

		toggle() {
			if (! pickerInstance) {
				return;
			}

			if (pickerInstance.isOpen) {
				pickerInstance.close();
				return;
			}

			pickerInstance.open();
			this.$refs.display.focus();
		},

		clear() {
			if (! pickerInstance) {
				return;
			}

			this.invalidRangeMessage = '';
			pickerInstance.clear();
			this.syncSelection([]);
		},

		syncSelection(selectedDates) {
			const startDate = selectedDates[0] ?? null;
			const endDate = selectedDates[1] ?? null;

			this.hasSelection = selectedDates.length > 0;
			this.$refs.start.value = startDate ? pickerInstance.formatDate(startDate, 'Y-m-d') : '';
			this.$refs.end.value = endDate ? pickerInstance.formatDate(endDate, 'Y-m-d') : '';
			this.$refs.display.value = this.buildDisplayValue(selectedDates);
			this.summary = this.buildSummary(selectedDates);
			this.updateValidity(selectedDates);
		},

		buildDisplayValue(selectedDates) {
			if (selectedDates.length === 0) {
				return '';
			}

			if (selectedDates.length === 1) {
				return `${this.formatFriendlyDate(selectedDates[0])} to ...`;
			}

			return `${this.formatFriendlyDate(selectedDates[0])} to ${this.formatFriendlyDate(selectedDates[1])}`;
		},

		buildSummary(selectedDates) {
			if (selectedDates.length === 0) {
				return emptySummary;
			}

			if (selectedDates.length === 1) {
				return 'Choose a departure date';
			}

			const durationInDays = Math.round((selectedDates[1] - selectedDates[0]) / 86400000) + 1;

			return `${durationInDays} ${durationInDays === 1 ? 'night' : 'nights'} selected`;
		},

		formatFriendlyDate(date) {
			return pickerInstance.formatDate(date, 'M j, Y');
		},

		selectionConflictsWithUnavailableRange(selectedDates) {
			if (selectedDates.length !== 2) {
				return false;
			}

			const selectedStart = selectedDates[0].getTime();
			const selectedEnd = selectedDates[1].getTime();

			return this.currentUnavailableRanges.some((range) => {
				const blockedStart = flatpickr.parseDate(range.from, 'Y-m-d')?.getTime();
				const blockedEnd = flatpickr.parseDate(range.to, 'Y-m-d')?.getTime();

				if (blockedStart === undefined || blockedEnd === undefined) {
					return false;
				}

				return blockedStart <= selectedEnd && blockedEnd >= selectedStart;
			});
		},

		updateValidity(selectedDates) {
			if (this.invalidRangeMessage !== '') {
				this.$refs.display.setCustomValidity(this.invalidRangeMessage);
				return;
			}

			if (selectedDates.length === 1) {
				this.$refs.display.setCustomValidity('Choose an end date to complete the range.');
				return;
			}

			this.$refs.display.setCustomValidity('');
		},
	};
});

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
