import './bootstrap';

import Alpine from 'alpinejs';
import { Calendar } from 'vanilla-calendar-pro';

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

document.addEventListener('alpine:initialized', () => {
	window.setTimeout(() => {
		const errorSummary = Array.from(document.querySelectorAll('[data-error-summary]'))
			.find((element) => element.getClientRects().length > 0);
		errorSummary?.focus();
	}, 100);
});

Alpine.data('dateRangePicker', ({
	startValue = '',
	endValue = '',
	disabledRangesByArea = {},
	areaInputName = 'living_area_ids[]',
} = {}) => {
	let calendar = null;

	return {
		activeField: 'start',
		areaInputs: [],
		currentUnavailableRanges: [],
		disabledRangesByArea,
		endDate: endValue,
		invalidRangeMessage: '',
		invalidField: '',
		isOpen: false,
		pickerStyle: '',
		startDate: startValue,

		get pickerTitle() {
			return this.activeField === 'start' ? 'Choose arrival date' : 'Choose departure date';
		},

		init() {
			this.areaInputs = this.findAreaInputs(areaInputName);
			this.refreshUnavailableRanges(null, false);
			calendar = new Calendar(this.$refs.calendar, this.calendarOptions());
			calendar.init();
			this.syncFields();

			this.areaInputs.forEach((input) => {
				input.addEventListener('change', () => this.refreshUnavailableRanges());
			});

			this.$root.addEventListener('keydown', (event) => {
				if (event.key === 'Escape' && this.isOpen) {
					event.stopPropagation();
					this.close();
				}
			});

			window.addEventListener('resize', () => {
				if (this.isOpen) {
					this.positionPicker();
				}
			});
		},

		calendarOptions() {
			return {
				type: 'default',
				displayMonthsCount: 1,
				monthsToSwitch: 1,
				animation: ! window.matchMedia('(prefers-reduced-motion: reduce)').matches,
				displayDisabledDates: true,
				enableJumpToSelectedDate: true,
				selectionDatesMode: 'single',
				selectedDates: this.selectedDateForActiveField(),
				disableDates: this.currentUnavailableRanges.map((range) => this.rangeString(range.from, range.to)),
				onClickDate: (instance) => this.selectCalendarDate(instance.context.selectedDates[0] ?? ''),
				labels: {
					application: 'Choose a date',
					dates: 'Available dates',
				},
			};
		},

		findAreaInputs(areaInputName) {
			const form = this.$root.closest('form');

			if (! form) {
				return [];
			}

			return Array.from(form.querySelectorAll(`input[name="${areaInputName}"]`));
		},

		refreshUnavailableRanges(instance = calendar, updateCalendar = true) {
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

			if (this.rangeConflicts(this.startDate, this.endDate)) {
				this.invalidRangeMessage = 'Those dates are unavailable for one or more selected living areas.';
				this.invalidField = 'end';
			} else if (this.invalidRangeMessage.includes('unavailable')) {
				this.invalidRangeMessage = '';
				this.invalidField = '';
			}

			if (instance && updateCalendar) {
				this.updateCalendar();
			}
			this.updateValidity();
		},

		toggle(field) {
			if (this.isOpen && this.activeField === field) {
				this.close();
				return;
			}

			this.open(field);
		},

		open(field) {
			this.parseField(field);
			this.activeField = field;
			this.updateCalendar();
			this.isOpen = true;
			this.$nextTick(() => {
				this.positionPicker();
				window.setTimeout(() => this.focusCalendar(), 0);
			});
		},

		positionPicker() {
			const anchor = this.activeField === 'start' ? this.$refs.startDisplay : this.$refs.endDisplay;
			const rootRect = this.$refs.positioningRoot.getBoundingClientRect();
			const anchorRect = anchor.getBoundingClientRect();
			const pickerWidth = Math.min(352, rootRect.width);
			const preferredLeft = this.activeField === 'start'
				? anchorRect.left - rootRect.left
				: anchorRect.right - rootRect.left - pickerWidth;
			const left = Math.max(0, Math.min(preferredLeft, rootRect.width - pickerWidth));
			const top = anchorRect.bottom - rootRect.top + 8;

			this.pickerStyle = `left: ${left}px; top: ${top}px; margin-top: 0;`;
		},

		focusCalendar(attempt = 0) {
			const activeDate = this.$refs.calendar.querySelector('[data-vc-date-btn][tabindex="0"], [data-vc-date-selected] [data-vc-date-btn], [data-vc-date-btn]:not(:disabled)');
			activeDate?.focus({ preventScroll: true });
			if (activeDate && document.activeElement !== activeDate && attempt < 3) {
				window.setTimeout(() => this.focusCalendar(attempt + 1), 25);
			}
		},

		close(restoreFocus = true) {
			this.isOpen = false;
			this.updateValidity();
			if (restoreFocus) {
				const trigger = this.activeField === 'start' ? this.$refs.startTrigger : this.$refs.endTrigger;
				this.$nextTick(() => trigger.focus());
			}
		},

		setDates(startDate = '', endDate = '', nextDisabledRangesByArea = disabledRangesByArea) {
			if (! calendar) {
				return;
			}

			this.disabledRangesByArea = nextDisabledRangesByArea ?? {};
			this.startDate = startDate;
			this.endDate = endDate;
			this.invalidRangeMessage = '';
			this.invalidField = '';
			this.close(false);
			this.refreshUnavailableRanges(null, false);
			this.syncFields();
			this.updateCalendar();
		},

		syncFields() {
			this.$refs.start.value = this.startDate;
			this.$refs.end.value = this.endDate;
			this.$refs.startDisplay.value = this.formatDisplayDate(this.startDate);
			this.$refs.endDisplay.value = this.formatDisplayDate(this.endDate);
			this.updateValidity();
			this.$dispatch('date-range-changed', {
				startDate: this.startDate,
				endDate: this.endDate,
			});
		},

		parseField(field) {
			const input = field === 'start' ? this.$refs.startDisplay : this.$refs.endDisplay;
			const value = input.value.trim();
			if (value === '') {
				this[field === 'start' ? 'startDate' : 'endDate'] = '';
				this.invalidRangeMessage = '';
				this.invalidField = '';
				this.syncFields();
				return true;
			}

			const date = this.parseDisplayDate(value);
			if (! date) {
				this[field === 'start' ? 'startDate' : 'endDate'] = '';
				this.invalidRangeMessage = `Enter the ${field === 'start' ? 'arrival' : 'departure'} date as MM/DD/YYYY.`;
				this.invalidField = field;
				this.updateValidity();
				return false;
			}

			this[field === 'start' ? 'startDate' : 'endDate'] = date;
			if (field === 'start' && this.endDate && (this.endDate < date || this.rangeConflicts(date, this.endDate))) {
				this.endDate = '';
			}
			this.invalidRangeMessage = '';
			this.invalidField = '';
			this.validateRange();
			this.syncFields();
			this.updateCalendar();
			return this.invalidRangeMessage === '';
		},

		commitTypedField(field) {
			if (! this.parseField(field)) {
				return;
			}

			if (field === 'start') {
				this.$refs.endDisplay.focus();
			} else {
				this.$refs.endDisplay.blur();
			}
		},

		selectCalendarDate(date) {
			if (! date) {
				return;
			}

			if (this.activeField === 'start') {
				this.startDate = date;
				if (this.endDate && (this.endDate < date || this.rangeConflicts(date, this.endDate))) {
					this.endDate = '';
				}
			} else {
				this.endDate = date;
			}

			this.invalidRangeMessage = '';
			this.invalidField = '';
			this.validateRange();
			this.syncFields();

			if (this.invalidRangeMessage !== '') {
				return;
			}

			this.close(false);
			this.$nextTick(() => {
				(this.activeField === 'start' ? this.$refs.endDisplay : this.$refs.endDisplay).focus();
			});
		},

		validateRange() {
			if (this.startDate && this.endDate && this.endDate < this.startDate) {
				this.invalidRangeMessage = 'Departure date must be on or after the arrival date.';
				this.invalidField = 'end';
				return false;
			}

			if (this.rangeConflicts(this.startDate, this.endDate)) {
				this.invalidRangeMessage = 'Those dates are unavailable for one or more selected living areas.';
				this.invalidField = this.endDate ? 'end' : 'start';
				return false;
			}

			return true;
		},

		rangeConflicts(startDate, endDate = '') {
			if (! startDate) {
				return false;
			}

			const selectedStart = Date.parse(`${startDate}T00:00:00Z`);
			const selectedEnd = Date.parse(`${endDate || startDate}T00:00:00Z`);

			return this.currentUnavailableRanges.some((range) => {
				const blockedStart = Date.parse(`${range.from}T00:00:00Z`);
				const blockedEnd = Date.parse(`${range.to}T00:00:00Z`);

				if (Number.isNaN(blockedStart) || Number.isNaN(blockedEnd)) {
					return false;
				}

				return blockedStart <= selectedEnd && blockedEnd >= selectedStart;
			});
		},

		updateValidity() {
			const fields = { start: this.$refs.startDisplay, end: this.$refs.endDisplay };
			Object.values(fields).forEach((field) => {
				field.setCustomValidity('');
				field.removeAttribute('aria-invalid');
			});

			if (this.invalidRangeMessage && fields[this.invalidField]) {
				fields[this.invalidField].setCustomValidity(this.invalidRangeMessage);
				fields[this.invalidField].setAttribute('aria-invalid', 'true');
			}
		},

		selectedDateForActiveField() {
			const date = this.activeField === 'start' ? this.startDate : this.endDate;
			return date ? [date] : [];
		},

		rangeString(startDate, endDate) {
			return endDate ? `${startDate}:${endDate}` : startDate;
		},

		parseDisplayDate(value) {
			const match = value.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
			if (! match) {
				return '';
			}

			const isoDate = `${match[3]}-${match[1].padStart(2, '0')}-${match[2].padStart(2, '0')}`;
			return this.isIsoDate(isoDate) ? isoDate : '';
		},

		formatDisplayDate(value) {
			if (! this.isIsoDate(value)) {
				return '';
			}

			const [year, month, day] = value.split('-');
			return `${month}/${day}/${year}`;
		},

		isIsoDate(value) {
			const date = new Date(`${value}T00:00:00Z`);
			return ! Number.isNaN(date.getTime()) && date.toISOString().slice(0, 10) === value;
		},

		updateCalendar(overrides = {}) {
			if (! calendar) {
				return;
			}

			calendar.set({
				...overrides,
				selectedDates: this.selectedDateForActiveField(),
				disableDates: this.currentUnavailableRanges.map((range) => this.rangeString(range.from, range.to)),
			}, { year: true, month: true, dates: true });
		},

		trapPickerFocus(event) {
			const focusableElements = Array.from(this.$refs.picker.querySelectorAll(
				'button:not(:disabled), input:not(:disabled), [tabindex]:not([tabindex="-1"])',
			)).filter((element) => element.getClientRects().length > 0);
			if (focusableElements.length === 0) return;
			const firstElement = focusableElements[0];
			const lastElement = focusableElements.at(-1);
			if (event.shiftKey && document.activeElement === firstElement) {
				event.preventDefault();
				lastElement.focus();
			} else if (! event.shiftKey && document.activeElement === lastElement) {
				event.preventDefault();
				firstElement.focus();
			}
		},
	};
});

Alpine.data('calendarBookings', ({
	bookings = {},
	createAction = '/bookings',
	currentMonth = '',
	defaultGuestName = '',
	canCreateConfirmedBookings = false,
	createUnavailableRanges = {},
	initialForm = null,
} = {}) => ({
	mode: null,
	bookings,
	selectedDay: '',
	selectedDayBookings: [],
	selectedBooking: null,
	triggerElement: null,
	canGoBack: false,
	hasComposerDraft: false,
	form: {
		isEdit: false,
		group: '',
		action: createAction,
		areaIds: [],
		guestName: defaultGuestName,
		note: '',
		paymentMethod: 'pay_later',
		paymentReference: '',
		bookAsDraft: false,
		lockAreas: false,
		startDate: '',
		endDate: '',
	},

	get modalTitle() {
		if (this.mode === 'agenda') {
			return this.formatDate(this.selectedDay);
		}

		if (this.mode === 'view') {
			return this.selectedBooking?.guestName ?? 'Booking';
		}

		return this.form.isEdit ? this.form.guestName : 'Book your stay';
	},

	init() {
		if (! initialForm) {
			return;
		}

		this.$nextTick(() => {
			if (initialForm.context === 'calendar-edit' && initialForm.group && this.bookings[initialForm.group]?.canEdit) {
				this.openBooking(initialForm.group, null, false, initialForm);
				return;
			}

			this.openCreate(initialForm.startDate ?? '', null, false, initialForm);
		});
	},

	openDay(date, bookingGroups, triggerElement) {
		this.selectedDay = date;
		this.selectedDayBookings = bookingGroups
			.map((group) => this.bookings[group])
			.filter(Boolean);
		this.triggerElement = triggerElement;

		if (this.selectedDayBookings.length === 0) {
			this.openCreate(date, triggerElement);
			return;
		}

		this.canGoBack = false;
		this.mode = 'agenda';
		this.openModal();
	},

	openCreate(date = '', triggerElement = null, fromAgenda = false, restored = null) {
		if (triggerElement) {
			this.triggerElement = triggerElement;
		}

		const preserveDraft = ! restored && this.hasComposerDraft && ! this.form.isEdit;
		const preservedForm = preserveDraft ? this.form : null;
		this.selectedBooking = null;
		this.canGoBack = fromAgenda;
		this.form = {
			isEdit: false,
			group: '',
			action: createAction,
			areaIds: restored?.areaIds ?? preservedForm?.areaIds ?? [],
			guestName: restored?.guestName ?? preservedForm?.guestName ?? defaultGuestName,
			note: restored?.note ?? preservedForm?.note ?? '',
			paymentMethod: restored?.paymentMethod ?? preservedForm?.paymentMethod ?? 'pay_later',
			paymentReference: restored?.paymentReference ?? preservedForm?.paymentReference ?? '',
			bookAsDraft: restored?.bookAsDraft ?? preservedForm?.bookAsDraft ?? false,
			lockAreas: false,
			startDate: restored?.startDate ?? (date || preservedForm?.startDate || ''),
			endDate: restored?.endDate ?? (date ? '' : preservedForm?.endDate ?? ''),
		};
		this.hasComposerDraft = true;
		this.mode = 'form';
		this.openModal();
		this.setPickerDates(this.form.startDate, this.form.endDate, createUnavailableRanges);
	},

	openBooking(group, triggerElement = null, fromAgenda = false, restored = null) {
		const booking = this.bookings[group];

		if (! booking) {
			return;
		}

		if (triggerElement) {
			this.triggerElement = triggerElement;
		}

		this.selectedBooking = booking;
		this.canGoBack = fromAgenda;

		if (! booking.canEdit || ! booking.edit) {
			this.mode = 'view';
			this.openModal();
			return;
		}

		this.form = {
			isEdit: true,
			group: booking.group,
			action: booking.edit.action,
			areaIds: restored?.areaIds ?? booking.edit.areaIds,
			guestName: restored?.guestName ?? booking.edit.guestName,
			note: restored?.note ?? booking.edit.note,
			paymentMethod: restored?.paymentMethod ?? booking.edit.paymentMethod,
			paymentReference: restored?.paymentReference ?? booking.edit.paymentReference,
			bookAsDraft: false,
			lockAreas: booking.edit.lockAreas,
			startDate: restored?.startDate ?? booking.edit.startDate,
			endDate: restored?.endDate ?? booking.edit.endDate,
		};
		this.mode = 'form';
		this.openModal();
		this.setPickerDates(
			this.form.startDate,
			this.form.endDate,
			booking.edit.unavailableRangesByArea,
		);
	},

	captureDates({ startDate = '', endDate = '' } = {}) {
		if (this.mode !== 'form') {
			return;
		}

		this.form.startDate = startDate;
		this.form.endDate = endDate;
	},

	setPickerDates(startDate, endDate, disabledRangesByArea) {
		this.$nextTick(() => {
			window.dispatchEvent(new CustomEvent('calendar-booking-dates', {
				detail: { startDate, endDate, disabledRangesByArea },
			}));
		});
	},

	backToAgenda() {
		this.mode = 'agenda';
		this.selectedBooking = null;
		this.canGoBack = false;
		this.$nextTick(() => this.$refs.closeButton?.focus());
	},

	openModal() {
		document.body.classList.add('overflow-y-hidden');
		document.querySelector('#app-shell')?.setAttribute('inert', '');
		this.$nextTick(() => this.$refs.modalTitle?.focus());
	},

	closeModal() {
		if (this.mode === null) {
			return;
		}

		this.mode = null;
		this.canGoBack = false;
		document.body.classList.remove('overflow-y-hidden');
		document.querySelector('#app-shell')?.removeAttribute('inert');
		this.$nextTick(() => this.triggerElement?.focus());
	},

	handleEscape() {
		if (document.querySelector('.tp-date-picker-popover:not([style*="display: none"])')) {
			return;
		}

		this.closeModal();
	},

	formatDate(date) {
		if (! date) {
			return 'Bookings';
		}

		return new Intl.DateTimeFormat(undefined, {
			weekday: 'long',
			month: 'long',
			day: 'numeric',
			year: 'numeric',
			timeZone: 'UTC',
		}).format(new Date(`${date}T00:00:00Z`));
	},

	trapFocus(event) {
		const focusableElements = Array.from(this.$refs.dialog.querySelectorAll(
			'a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])',
		)).filter((element) => ! element.disabled && element.offsetParent !== null);

		if (focusableElements.length === 0) {
			return;
		}

		const firstElement = focusableElements[0];
		const lastElement = focusableElements[focusableElements.length - 1];

		if (event.shiftKey && document.activeElement === firstElement) {
			event.preventDefault();
			lastElement.focus();
		} else if (! event.shiftKey && document.activeElement === lastElement) {
			event.preventDefault();
			firstElement.focus();
		}
	},
}));

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
