import { execFileSync } from 'node:child_process';
import { closeSync, openSync, rmSync } from 'node:fs';

export default async function globalSetup() {
    const database = process.env.DB_DATABASE;

    if (! database || ! database.startsWith('/tmp/thunderpoint-a11y-')) {
        throw new Error('Accessibility tests require an isolated /tmp SQLite database.');
    }

    rmSync(database, { force: true });
    closeSync(openSync(database, 'w'));

    execFileSync('php', ['artisan', 'migrate:fresh', '--seed', '--force'], {
        env: process.env,
        stdio: 'inherit',
    });
    execFileSync('php', ['artisan', 'db:seed', '--class=Database\\Seeders\\AccessibilityTestSeeder', '--force'], {
        env: process.env,
        stdio: 'inherit',
    });
}
