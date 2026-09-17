const { test, expect } = require('@playwright/test');
const AxeBuilder = require('@axe-core/playwright').default;

for (const layout of ['exception', 'bootstrap5', 'tailwind']) {
    for (const width of [375, 1200]) {
        for (const theme of ['light', 'dark', 'system']) {
            test(`${layout} ${theme} at ${width}px`, async ({ page }, testInfo) => {
                await page.setViewportSize({ width, height: 1000 });
                await page.emulateMedia({ colorScheme: 'dark' });
                const remoteRequests = [];
                page.on('request', request => {
                    if (!request.url().startsWith('http://127.0.0.1:8765/')) remoteRequests.push(request.url());
                });
                await page.goto(`/${layout}-${theme}.html`);
                await expect(page.getByRole('heading', { level: 1 })).toHaveText('Unable to complete the checkout request');
                await expect(page.locator('body')).toHaveCSS('background-color', theme === 'light'
                    ? (layout === 'exception' ? 'rgb(249, 249, 249)' : 'rgb(243, 245, 248)')
                    : 'rgb(16, 24, 39)');
                expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
                expect(remoteRequests).toEqual([]);
                if (layout !== 'exception') {
                    const results = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze();
                    expect(results.violations).toEqual([]);
                }
                await page.screenshot({ path: testInfo.outputPath('email.png'), fullPage: true });
            });
        }
    }

    test(`${layout} follows system theme changes`, async ({ page }) => {
        await page.emulateMedia({ colorScheme: 'light' });
        await page.goto(`/${layout}-system.html`);
        await expect(page.locator('body')).toHaveCSS('background-color', layout === 'exception' ? 'rgb(249, 249, 249)' : 'rgb(243, 245, 248)');
        await page.emulateMedia({ colorScheme: 'dark' });
        await expect(page.locator('body')).toHaveCSS('background-color', 'rgb(16, 24, 39)');
    });

    test(`${layout} wraps long exception data on mobile`, async ({ page }) => {
        await page.setViewportSize({ width: 375, height: 812 });
        await page.goto(`/${layout}-long.html`);
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
    });
}
