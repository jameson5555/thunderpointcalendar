import { rmSync } from 'node:fs';

export default async function globalTeardown() {
    const database = process.env.DB_DATABASE;

    if (database?.startsWith('/tmp/thunderpoint-a11y-')) {
        rmSync(database, { force: true });
    }
}
