import { test, expect, Page, Route } from '@playwright/test';

// Helper to mock availability API with a mode lock on the 22nd of current month
function buildAvailabilityPayload(mode: 'online' | 'onsite') {
    const now = new Date();
    const yyyy = now.getFullYear();
    const mm = now.toLocaleString('en-US', { month: 'short' });
    const dow = new Date(yyyy, now.getMonth(), 22).toLocaleString('en-US', { weekday: 'short' });
    const key = `${dow}, ${mm} 22 ${yyyy}`;
    return {
        success: true,
        capacity: 5,
        dates: [{ date: key, booked: 1, remaining: 4, mode }],
    };
}

async function openFirstProfessorModal(page: Page) {
    await page.waitForLoadState('domcontentloaded');
    // Show modal overlay directly and prep the environment similar to openModal
    await page.evaluate(() => {
        const modal = document.getElementById('consultationModal');
        if (modal) modal.style.display = 'flex';
        // Set a prof id so availability call uses it
        const hidden = document.getElementById('modalProfId') as HTMLInputElement | null;
        if (hidden) hidden.value = '123';
        // Allow M-F
        // @ts-ignore
        if (typeof window.__updateAllowedWeekdays === 'function') {
            // @ts-ignore
            window.__updateAllowedWeekdays(
                'Monday: 8-5\nTuesday: 8-5\nWednesday: 8-5\nThursday: 8-5\nFriday: 8-5'
            );
        }
    });
    await page.waitForSelector('#consultationModal', { state: 'visible' });
    // Trigger availability fetch to populate dataset.mode per day
    await page.evaluate(() => {
        // @ts-ignore
        if (typeof window.__fetchAvailability === 'function') {
            // @ts-ignore
            window.__fetchAvailability('123');
        }
    });
}

async function clickDay22(page: Page) {
    // Ensure the 22nd button exists on current month view
    const btn = page.locator('.pika-table .pika-button', { hasText: '22' }).first();
    await btn.waitFor({ state: 'visible' });
    await btn.click();
}

for (const route of ['/comsci']) {
    test.describe(`Mode lock auto-select on ${route}`, () => {
        test('clicking day 22 auto-selects radio to online', async ({ page }) => {
            // Intercept availability and return a lock for day 22
            await page.route('**/api/professor/availability**', (routeFn) => {
                return routeFn.fulfill({ json: buildAvailabilityPayload('online') });
            });

            await page.goto(route);
            await openFirstProfessorModal(page);
            await clickDay22(page);

            const online = page.locator('input[name="mode"][value="online"]');
            const onsite = page.locator('input[name="mode"][value="onsite"]');
            await expect(online).toBeChecked();
            await expect(onsite).toBeDisabled();
        });

        test('clicking day 22 auto-selects radio to onsite', async ({ page }) => {
            await page.route('**/api/professor/availability**', (routeFn) => {
                return routeFn.fulfill({ json: buildAvailabilityPayload('onsite') });
            });

            await page.goto(route);
            await openFirstProfessorModal(page);
            await clickDay22(page);

            const online = page.locator('input[name="mode"][value="online"]');
            const onsite = page.locator('input[name="mode"][value="onsite"]');
            await expect(onsite).toBeChecked();
            await expect(online).toBeDisabled();
        });
    });
}
