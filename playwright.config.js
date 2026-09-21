const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
    testDir: './tests/browser',
    outputDir: 'build/browser-results',
    fullyParallel: true,
    forbidOnly: Boolean(process.env.CI),
    retries: 0,
    workers: process.env.CI ? 2 : undefined,
    reporter: [['list'], ['html', { outputFolder: 'build/browser-report', open: 'never' }]],
    use: { baseURL: 'http://127.0.0.1:8765', browserName: 'chromium' },
    webServer: {
        command: 'php scripts/render-previews.php && php -S 127.0.0.1:8765 -t build/previews',
        url: 'http://127.0.0.1:8765/modern-light.html',
        reuseExistingServer: !process.env.CI,
    },
});
