import { expect, test } from '@playwright/test';
import axe from 'axe-core';

async function assertNoAxeViolations(page, context) {
    await page.addScriptTag({ content: axe.source });
    const results = await page.evaluate(async () => window.axe.run(document, {
        runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'] },
    }));
    expect(results.violations, `${context}: ${results.violations.map(({ id, help }) => `${id} (${help})`).join(', ')}`).toEqual([]);
}

async function assertPageStructure(page) {
    await expect(page).toHaveTitle(/.+ · Thunderpoint/);
    await expect(page.locator('h1')).toHaveCount(1);
    await expect(page.locator('main#main-content')).toHaveCount(1);
    await expect(page.getByRole('link', { name: 'Skip to main content' })).toHaveCount(1);
}

async function login(page, email = 'admin@example.test') {
    await page.goto('/login');
    await page.locator('#home_login_email').fill(email);
    await page.locator('#home_login_password').fill('Accessibility-Test-Password');
    await page.locator('#login-panel').getByRole('button', { name: 'Log in' }).click();
    await expect(page).toHaveURL(/\/dashboard/);
}

test('public routes have clean automated scans and document structure', async ({ page }) => {
    for (const route of ['/', '/login', '/register', '/forgot-password', '/approval-pending']) {
        await page.goto(route);
        await assertPageStructure(page);
        await assertNoAxeViolations(page, route);
    }
});

test('home authentication selector is a keyboard-operable tablist', async ({ page }) => {
    await page.goto('/');
    const loginTab = page.getByRole('tab', { name: 'Sign in' });
    const registerTab = page.getByRole('tab', { name: 'Register' });
    await loginTab.focus();
    await page.keyboard.press('ArrowRight');
    await expect(registerTab).toBeFocused();
    await expect(registerTab).toHaveAttribute('aria-selected', 'true');
    await page.keyboard.press('Home');
    await expect(loginTab).toBeFocused();
});

test('failed sign-in focuses a summary and associates field guidance', async ({ page }) => {
    await page.goto('/login');
    await page.locator('#home_login_email').fill('admin@example.test');
    await page.locator('#home_login_password').fill('not-the-password');
    await page.locator('#login-panel').getByRole('button', { name: 'Log in' }).click();
    const summary = page.locator('#home-login-errors');
    await expect(summary).toBeFocused();
    await expect(page.locator('#home_login_email')).toHaveAttribute('aria-invalid', 'true');
    await expect(page.locator('#home_login_email')).toHaveAttribute('aria-describedby', 'home-login-errors');
    await assertNoAxeViolations(page, 'failed sign-in');
});

test('admin, dashboard, and profile pass scans for both user roles', async ({ page }) => {
    await login(page);
    for (const route of ['/dashboard', '/admin', '/profile']) {
        await page.goto(route);
        await assertPageStructure(page);
        await assertNoAxeViolations(page, route);
    }

    const roleSelects = page.locator('select[name="role"]');
    for (let index = 0; index < await roleSelects.count(); index += 1) {
        await expect(roleSelects.nth(index)).toHaveAccessibleName(/Role for .+/);
    }

    await page.context().clearCookies();
    await login(page, 'member@example.test');
    await assertPageStructure(page);
    await assertNoAxeViolations(page, 'standard member dashboard');
});

test('booking dialog and date picker contain focus and restore it on Escape', async ({ page }) => {
    await login(page);
    const trigger = page.locator('[data-calendar-day][aria-label^="Create booking starting"]').first();
    const clickedDate = await trigger.getAttribute('data-calendar-date');
    await trigger.click();

    const bookingDialog = page.locator('[data-calendar-modal]');
    await expect(bookingDialog).toHaveAttribute('aria-modal', 'true');
    await expect(page.locator('#app-shell')).toHaveAttribute('inert', '');
    await expect(page.locator('#calendar-modal-title')).toBeFocused();

    await page.getByLabel('Boathouse').check();
    const arrivalInput = page.getByRole('textbox', { name: 'Arrival date' });
    const departureInput = page.getByRole('textbox', { name: 'Departure date' });
    const [clickedYear, clickedMonth, clickedDay] = clickedDate.split('-');
    await expect(arrivalInput).toHaveValue(`${clickedMonth}/${clickedDay}/${clickedYear}`);
    await arrivalInput.fill('06/14/2030');
    await arrivalInput.press('Enter');
    await departureInput.fill('06/18/2030');
    await departureInput.press('Enter');
    await expect(page.locator('#calendar_booking_dates__client_error')).toContainText('unavailable');

    await arrivalInput.fill('06/10/2030');
    await arrivalInput.press('Enter');
    await expect(departureInput).toBeFocused();
    await departureInput.fill('06/12/2030');
    await departureInput.press('Enter');

    await page.getByRole('button', { name: 'Choose arrival date from calendar' }).click();

    const picker = page.locator('#calendar_booking_dates__picker');
    await expect(picker).toHaveRole('dialog');
    await expect(picker.locator('[role="grid"]')).toHaveCount(1);
    await expect(picker.locator('[role="grid"]')).toBeVisible();
    const desktopPickerBox = await picker.boundingBox();
    const arrivalInputBox = await arrivalInput.boundingBox();
    expect(desktopPickerBox.width).toBeLessThanOrEqual(352);
    expect(desktopPickerBox.height).toBeLessThanOrEqual(500);
    expect(Math.abs(desktopPickerBox.x - arrivalInputBox.x)).toBeLessThanOrEqual(2);
    expect(desktopPickerBox.y - (arrivalInputBox.y + arrivalInputBox.height)).toBeGreaterThanOrEqual(6);
    expect(desktopPickerBox.y - (arrivalInputBox.y + arrivalInputBox.height)).toBeLessThanOrEqual(12);
    await expect(page.locator(':focus')).toHaveAttribute('data-vc-date-btn', '');
    const disabledDate = page.getByRole('button', { name: 'June 15, 2030' });
    await expect(disabledDate).toBeDisabled();
    expect((await disabledDate.boundingBox()).height).toBeGreaterThanOrEqual(44);
    const bookingBarHeights = await page.locator('[data-calendar-booking-trigger]').evaluateAll((elements) => elements.map((element) => element.getBoundingClientRect().height));
    expect(bookingBarHeights.every((height) => height >= 24)).toBe(true);
    await assertNoAxeViolations(page, 'open booking and date picker dialogs');

    await picker.getByRole('button', { name: 'Close' }).focus();
    await page.keyboard.press('Tab');
    expect(await page.evaluate(() => document.querySelector('#calendar_booking_dates__picker')?.contains(document.activeElement))).toBe(true);

    await page.keyboard.press('Escape');
    await expect(picker).toBeHidden();
    await expect(page.getByRole('button', { name: 'Choose arrival date from calendar' })).toBeFocused();
    await page.keyboard.press('Escape');
    await expect(bookingDialog).toBeHidden();
    await expect(trigger).toBeFocused();
    await expect(page.locator('#app-shell')).not.toHaveAttribute('inert', '');
});

test('account deletion dialog is named, modal, escapable, and restores focus', async ({ page }) => {
    await login(page);
    await page.goto('/profile');
    const trigger = page.getByRole('button', { name: 'Delete Account' }).first();
    await trigger.click();
    const dialog = page.getByRole('dialog', { name: 'Are you sure you want to delete your account?' });
    await expect(dialog).toBeVisible();
    await expect(dialog).toHaveAttribute('aria-modal', 'true');
    await assertNoAxeViolations(page, 'account deletion dialog');
    await page.keyboard.press('Escape');
    await expect(dialog).toBeHidden();
    await expect(trigger).toBeFocused();
});

test('mobile navigation exposes state, closes with Escape, and does not overflow', async ({ page }) => {
    await page.setViewportSize({ width: 320, height: 800 });
    await login(page);
    const toggle = page.getByRole('button', { name: 'Toggle navigation' });
    await toggle.click();
    await expect(toggle).toHaveAttribute('aria-expanded', 'true');
    await page.keyboard.press('Escape');
    await expect(toggle).toHaveAttribute('aria-expanded', 'false');
    await expect(toggle).toBeFocused();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBe(true);
});

test('date picker stays within a narrow booking dialog', async ({ page }) => {
    await page.setViewportSize({ width: 320, height: 800 });
    await login(page);
    await page.locator('[data-calendar-day][aria-label^="Create booking starting"]').first().click();
    const bookingDialogPanel = page.locator('[data-calendar-modal] > [x-ref="dialog"]');
    const bookingDialogBox = await bookingDialogPanel.boundingBox();
    expect(bookingDialogBox.x).toBeGreaterThanOrEqual(16);
    expect(bookingDialogBox.x + bookingDialogBox.width).toBeLessThanOrEqual(304);
    const mobileArrivalInputBox = await page.getByRole('textbox', { name: 'Arrival date' }).boundingBox();
    const mobileDepartureInputBox = await page.getByRole('textbox', { name: 'Departure date' }).boundingBox();
    expect(Math.abs(mobileArrivalInputBox.y - mobileDepartureInputBox.y)).toBeLessThanOrEqual(2);
    await page.getByRole('button', { name: 'Choose arrival date from calendar' }).click();

    const picker = page.locator('#calendar_booking_dates__picker');
    await expect(picker.locator('[role="grid"]')).toHaveCount(1);
    await expect(picker).toBeVisible();
    const pickerBox = await picker.boundingBox();
    const arrivalInputBox = await page.getByRole('textbox', { name: 'Arrival date' }).boundingBox();
    expect(pickerBox.x).toBeGreaterThanOrEqual(0);
    expect(pickerBox.x + pickerBox.width).toBeLessThanOrEqual(320);
    expect(pickerBox.height).toBeLessThanOrEqual(500);
    expect(Math.abs(pickerBox.x - arrivalInputBox.x)).toBeLessThanOrEqual(2);
    expect(pickerBox.y - (arrivalInputBox.y + arrivalInputBox.height)).toBeGreaterThanOrEqual(6);
    expect(pickerBox.y - (arrivalInputBox.y + arrivalInputBox.height)).toBeLessThanOrEqual(12);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBe(true);

    await page.keyboard.press('Escape');
    await page.getByRole('button', { name: 'Choose departure date from calendar' }).click();
    await expect(picker).toBeVisible();
    const departurePickerBox = await picker.boundingBox();
    const departureInputBox = await page.getByRole('textbox', { name: 'Departure date' }).boundingBox();
    const departureControlBox = await page.locator('.tp-date-control').filter({ has: page.getByRole('textbox', { name: 'Departure date' }) }).boundingBox();
    expect(Math.abs((departurePickerBox.x + departurePickerBox.width) - (departureControlBox.x + departureControlBox.width))).toBeLessThanOrEqual(2);
    expect(departurePickerBox.y - (departureInputBox.y + departureInputBox.height)).toBeGreaterThanOrEqual(6);
    expect(departurePickerBox.y - (departureInputBox.y + departureInputBox.height)).toBeLessThanOrEqual(12);
});

test('approved text color pairs meet normal-text contrast', async () => {
    const pairs = [
        ['#ED7009', '#17120F'],
        ['#1A8C91', '#17120F'],
        ['#E7A30F', '#17120F'],
        ['#6F7429', '#FFFDF5'],
        ['#913C19', '#F7F0D7'],
        ['#147277', '#F7F0D7'],
        ['#5F4838', '#F7F0D7'],
    ];
    const luminance = (hex) => {
        const channels = hex.match(/[a-f\d]{2}/gi).map((value) => Number.parseInt(value, 16) / 255)
            .map((value) => value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4);
        return 0.2126 * channels[0] + 0.7152 * channels[1] + 0.0722 * channels[2];
    };
    for (const [background, foreground] of pairs) {
        const [lighter, darker] = [luminance(background), luminance(foreground)].sort((a, b) => b - a);
        expect((lighter + 0.05) / (darker + 0.05)).toBeGreaterThanOrEqual(4.5);
    }
});
