import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
export const repoRoot = path.resolve(__dirname, '../../..');
export const appUrl = 'http://127.0.0.1:8026';
export const e2eDatabasePath = path.join(repoRoot, 'storage', 'framework', 'testing', 'eurocham-e2e.sqlite');

export function e2eEnv() {
    return {
        ...process.env,
        APP_ENV: 'e2e',
        APP_DEBUG: 'false',
        APP_URL: appUrl,
        ADMIN_EMAIL: 'admin@eurocham.sn',
        ADMIN_PASSWORD: 'eurocham2026',
        DB_CONNECTION: 'sqlite',
        DB_DATABASE: e2eDatabasePath,
        DB_URL: '',
        CACHE_STORE: 'file',
        VOTE_LOCK_STORE: 'file',
        SESSION_DRIVER: 'file',
        SESSION_SECURE_COOKIE: 'false',
        QUEUE_CONNECTION: 'sync',
        MAIL_MAILER: 'array',
        BCRYPT_ROUNDS: '4',
    };
}
