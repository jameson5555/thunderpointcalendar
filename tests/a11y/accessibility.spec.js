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

async function assertMobileSurfaces(page, selector, expectedColor) {
    const metrics = await page.locator(selector).evaluateAll((elements) => elements.map((element) => {
        const styles = window.getComputedStyle(element);
        const bounds = element.getBoundingClientRect();

        return {
            borderColor: styles.borderTopColor,
            borderWidth: styles.borderTopWidth,
            radius: Number.parseFloat(styles.borderTopLeftRadius),
            x: bounds.x,
            right: bounds.right,
        };
    }));

    expect(metrics.length).toBeGreaterThan(0);
    for (const metric of metrics) {
        expect(metric.borderColor).toBe(expectedColor);
        expect(metric.borderWidth).toBe('2px');
        expect(metric.radius).toBeGreaterThanOrEqual(24);
        expect(metric.x).toBeGreaterThanOrEqual(12);
        expect(metric.right).toBeLessThanOrEqual((await page.viewportSize()).width - 12);
    }
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

test('living-area legend uses prominent identifying bullets without mobile overflow', async ({ page }) => {
    await login(page);
    const legendItems = page.locator('.tp-part-legend-item');
    const legendMarkers = page.locator('.tp-part-legend-marker');
    const expectedColors = [
        'rgb(237, 112, 9)',
        'rgb(26, 140, 145)',
        'rgb(231, 163, 15)',
        'rgb(111, 116, 41)',
    ];

    await expect(legendItems).toHaveCount(expectedColors.length);
    await expect(legendMarkers).toHaveCount(expectedColors.length);
    for (let index = 0; index < expectedColors.length; index += 1) {
        await expect(legendMarkers.nth(index)).toHaveCSS('background-color', expectedColors[index]);
        expect((await legendMarkers.nth(index).boundingBox()).width).toBeGreaterThanOrEqual(12);
    }

    await page.setViewportSize({ width: 320, height: 800 });
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBe(true);
});

test('mobile task surfaces stay inset, rounded, and color-coded', async ({ page }) => {
    await login(page);

    for (const width of [320, 390]) {
        await page.setViewportSize({ width, height: 844 });
        await page.goto('/dashboard');

        await assertMobileSurfaces(page, '.tp-calendar-surface.tp-surface--action', 'rgb(237, 112, 9)');
        await assertMobileSurfaces(page, '[data-your-bookings].tp-surface--booking', 'rgb(231, 163, 15)');

        const dashboardAlignment = await page.locator('[data-calendar-overview], [data-your-bookings]').evaluateAll((elements) => elements.map((element) => {
            const bounds = element.getBoundingClientRect();
            return { x: bounds.x, width: bounds.width };
        }));
        expect(dashboardAlignment).toHaveLength(2);
        expect(dashboardAlignment[0].x).toBe(dashboardAlignment[1].x);
        expect(dashboardAlignment[0].width).toBe(dashboardAlignment[1].width);
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBe(true);

        await page.goto('/admin');
        await assertMobileSurfaces(page, 'section.tp-surface--guidance', 'rgb(111, 116, 41)');
        await assertMobileSurfaces(page, 'article.tp-surface--booking', 'rgb(231, 163, 15)');
        await assertMobileSurfaces(page, 'section.tp-surface--settings', 'rgb(26, 140, 145)');
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBe(true);

        await page.goto('/profile');
        await assertMobileSurfaces(page, '.tp-surface--settings', 'rgb(26, 140, 145)');
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBe(true);
    }

    await page.context().clearCookies();
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/login');
    await assertMobileSurfaces(page, '.tp-surface--action', 'rgb(237, 112, 9)');
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBe(true);
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

    await page.getByRole('button', { name: 'June 20, 2030' }).click();
    await expect(arrivalInput).toHaveValue('06/20/2030');
    await expect(picker).toBeHidden();

    await page.getByRole('button', { name: 'Choose arrival date from calendar' }).click();
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
    const toggle = page.locator('button[aria-controls="mobile-navigation"]');
    const pageContent = page.locator('header').filter({ has: page.locator('.tp-part-legend') });
    const contentTopBeforeOpen = (await pageContent.boundingBox()).y;
    await toggle.click();
    await expect(toggle).toHaveAttribute('aria-expanded', 'true');
    await expect(toggle).toHaveAccessibleName('Close');
    const scrim = page.locator('[data-mobile-menu-scrim]');
    await expect(scrim).toBeVisible();
    await expect(page.locator('[data-mobile-menu-panel]')).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Go to' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Your account' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Add Thunderpoint to your phone' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Calendar', exact: true })).toHaveAttribute('aria-current', 'page');
    await expect(page.locator('#mobile-navigation')).not.toContainText('admin@example.test');
    expect((await toggle.boundingBox()).height).toBeGreaterThanOrEqual(44);
    expect(Math.abs((await pageContent.boundingBox()).y - contentTopBeforeOpen)).toBeLessThanOrEqual(1);
    await assertNoAxeViolations(page, 'open mobile navigation');
    const scrimBox = await scrim.boundingBox();
    await scrim.click({ position: { x: 8, y: scrimBox.height - 8 } });
    await expect(toggle).toHaveAttribute('aria-expanded', 'false');
    await expect(toggle).toHaveAccessibleName('Menu');
    await toggle.click();
    await page.keyboard.press('Escape');
    await expect(toggle).toHaveAttribute('aria-expanded', 'false');
    await expect(toggle).toBeFocused();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBe(true);
});

test('desktop account menu exposes state and restores focus on Escape', async ({ page }) => {
    await login(page);
    const accountToggle = page.locator('button[aria-controls="account-menu"]');
    await expect(accountToggle).toContainText('Account');
    await accountToggle.click();
    await expect(accountToggle).toHaveAttribute('aria-expanded', 'true');
    await expect(page.locator('#account-menu')).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(accountToggle).toHaveAttribute('aria-expanded', 'false');
    await expect(accountToggle).toBeFocused();
});

test('mobile install guidance is accessible and restores focus to the menu control', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await login(page);
    await page.getByRole('button', { name: 'Menu' }).click();
    await page.getByRole('button', { name: 'Add Thunderpoint to your phone' }).click();

    const dialog = page.getByRole('dialog', { name: 'Add Thunderpoint to your phone' });
    await expect(dialog).toBeVisible();
    await expect(page.locator('#install-guidance-title')).toBeFocused();
    await expect(dialog).toContainText('Install app or Add to Home Screen');
    await assertNoAxeViolations(page, 'install guidance dialog');

    await page.keyboard.press('Escape');
    await expect(dialog).toBeHidden();
    await expect(page.getByRole('button', { name: 'Menu' })).toBeFocused();
});

test('mobile install action uses a native prompt and hides after acceptance', async ({ page }) => {
    await page.addInitScript(() => {
        window.__installPromptCalls = 0;
        window.addEventListener('DOMContentLoaded', () => {
            const event = new Event('beforeinstallprompt');
            Object.defineProperty(event, 'prompt', {
                value: async () => {
                    window.__installPromptCalls += 1;
                    return { outcome: 'accepted', platform: 'web' };
                },
            });
            window.dispatchEvent(event);
        });
    });
    await page.setViewportSize({ width: 390, height: 844 });
    await login(page);
    await page.getByRole('button', { name: 'Menu' }).click();
    await page.getByRole('button', { name: 'Add Thunderpoint to your phone' }).click();

    await expect.poll(() => page.evaluate(() => window.__installPromptCalls)).toBe(1);
    await expect(page.locator('[data-install-section]')).toBeHidden();
});

test('dismissed native install remains available with guidance on the next attempt', async ({ page }) => {
    await page.addInitScript(() => {
        window.addEventListener('DOMContentLoaded', () => {
            const event = new Event('beforeinstallprompt');
            Object.defineProperty(event, 'prompt', {
                value: async () => ({ outcome: 'dismissed', platform: 'web' }),
            });
            window.dispatchEvent(event);
        });
    });
    await page.setViewportSize({ width: 390, height: 844 });
    await login(page);
    await page.getByRole('button', { name: 'Menu' }).click();
    const installButton = page.getByRole('button', { name: 'Add Thunderpoint to your phone' });
    await installButton.click();
    await expect(installButton).toBeVisible();
    await installButton.click();

    await expect(page.getByRole('dialog', { name: 'Add Thunderpoint to your phone' })).toBeVisible();
});

test('mobile install guidance gives iPhone-specific steps', async ({ page }) => {
    await page.addInitScript(() => {
        Object.defineProperty(window.navigator, 'userAgent', { value: 'Mozilla/5.0 (iPhone)' });
        Object.defineProperty(window.navigator, 'platform', { value: 'iPhone' });
    });
    await page.setViewportSize({ width: 390, height: 844 });
    await login(page);
    await page.getByRole('button', { name: 'Menu' }).click();
    await page.getByRole('button', { name: 'Add Thunderpoint to your phone' }).click();

    const dialog = page.getByRole('dialog', { name: 'Add Thunderpoint to your phone' });
    await expect(dialog).toContainText('Tap the Share button.');
    await expect(dialog).toContainText('Choose Add to Home Screen.');
});

test('mobile install action is hidden in standalone display mode', async ({ page }) => {
    await page.addInitScript(() => {
        Object.defineProperty(window.navigator, 'standalone', { value: true });
    });
    await page.setViewportSize({ width: 390, height: 844 });
    await login(page);
    await page.getByRole('button', { name: 'Menu' }).click();

    await expect(page.locator('[data-install-section]')).toBeHidden();
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
        ['#913C19', '#F4E8CD'],
        ['#147277', '#F4E8CD'],
        ['#5F4838', '#F4E8CD'],
        ['#4E3B2E', '#D9C89E'],
        ['#4E3B2E', '#E4D6B3'],
        ['#4E3B2E', '#D5C08D'],
        ['#6F5540', '#E2D2AB'],
        ['#6F5540', '#F4E8CD'],
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
