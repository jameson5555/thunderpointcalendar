import { defineConfig, devices } from '@playwright/test';

Object.assign(process.env, {
    APP_ENV: 'testing',
    APP_URL: 'http://127.0.0.1:8087',
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: `/tmp/thunderpoint-a11y-${process.pid}.sqlite`,
    SESSION_DRIVER: 'file',
    CACHE_STORE: 'array',
    MAIL_MAILER: 'array',
    THUNDERPOINT_ADMIN_NAME: 'Accessibility Admin',
    THUNDERPOINT_ADMIN_EMAIL: 'admin@example.test',
    THUNDERPOINT_ADMIN_PASSWORD: 'Accessibility-Test-Password',
});

export default defineConfig({
    testDir: './tests/a11y',
    globalSetup: './tests/a11y/global-setup.js',
    globalTeardown: './tests/a11y/global-teardown.js',
    fullyParallel: false,
    workers: 1,
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? 'github' : 'list',
    use: {
        baseURL: process.env.APP_URL,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        ...devices['Desktop Chrome'],
    },
    webServer: [
        {
            command: 'php artisan serve --host=127.0.0.1 --port=8087',
            url: 'http://127.0.0.1:8087',
            reuseExistingServer: false,
            timeout: 120_000,
            env: process.env,
        },
        ...(process.env.CI ? [] : [{
            command: 'npm run dev -- --host 127.0.0.1 --port 5173',
            url: 'http://127.0.0.1:5173/@vite/client',
            reuseExistingServer: false,
            timeout: 120_000,
            env: process.env,
        }]),
    ],
});
