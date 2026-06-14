import { spawn, spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { e2eDatabasePath, e2eEnv, repoRoot } from './e2e-env.mjs';

const env = e2eEnv();
fs.mkdirSync(path.dirname(e2eDatabasePath), { recursive: true });

function executable(command) {
    return command;
}

function commandArgs(command, args) {
    if (process.platform === 'win32' && command === 'npm') {
        return {
            command: 'cmd.exe',
            args: ['/d', '/s', '/c', ['npm', ...args].join(' ')],
        };
    }

    return { command: executable(command), args };
}

function run(command, args) {
    const resolved = commandArgs(command, args);
    const result = spawnSync(resolved.command, resolved.args, {
        cwd: repoRoot,
        env,
        stdio: 'inherit',
    });

    if (result.error) {
        console.error(result.error);
    }

    if (result.status !== 0) {
        process.exit(result.status ?? 1);
    }
}

run('php', ['artisan', 'e2e:prepare']);
run('npm', ['run', 'build']);

const server = spawn('php', ['artisan', 'serve', '--host=127.0.0.1', '--port=8026'], {
    cwd: repoRoot,
    env,
    stdio: 'inherit',
});

function stop() {
    if (! server.killed) {
        server.kill();
    }
}

process.on('SIGINT', () => {
    stop();
    process.exit(130);
});

process.on('SIGTERM', () => {
    stop();
    process.exit(143);
});

server.on('exit', (code) => {
    process.exit(code ?? 0);
});
