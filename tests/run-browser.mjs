import { createServer } from 'vite'
import { execFileSync, spawn } from 'node:child_process'
import { fileURLToPath } from 'node:url'

const root = fileURLToPath(new URL('../', import.meta.url))
const apiRoot = fileURLToPath(new URL('../api/', import.meta.url))
const base = 'http://127.0.0.1:5198'
const env = {
  ...process.env,
  APP_ENV: 'testing', APP_DEBUG: 'false', APP_URL: base,
  APP_KEY: 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
  DB_CONNECTION: 'mysql', DB_DATABASE: 'rjph_test',
  DB_USERNAME: process.env.DB_USERNAME || 'rjph',
  DB_PASSWORD: process.env.DB_PASSWORD || 'rjph_local',
  DB_URL: '', CACHE_STORE: 'array', SESSION_DRIVER: 'file',
  MAIL_MAILER: 'log', LOG_CHANNEL: 'single', SANCTUM_STATEFUL_DOMAINS: '127.0.0.1:5198',
}
let php, vite
try {
  // Fixed disposable database shared with the API and weekly suites; run these suites sequentially.
  execFileSync('php', ['artisan', 'migrate:fresh', '--seed', '--seeder=Database\\Seeders\\DevelopmentSeeder', '--force', '--no-interaction'], { cwd: apiRoot, env, stdio: 'inherit' })
  php = spawn('php', ['artisan', 'serve', '--no-reload', '--host=127.0.0.1', '--port=8018'], { cwd: apiRoot, env, stdio: 'ignore', detached: true })
  for (let attempt = 0; attempt < 100; attempt++) {
    try { if ((await fetch('http://127.0.0.1:8018/api/up')).ok) break } catch {}
    if (attempt === 99) throw new Error('Test API failed to start')
    await new Promise(resolve => setTimeout(resolve, 100))
  }
  vite = await createServer({ root, server: { host: '127.0.0.1', port: 5198, strictPort: true, open: false, proxy: { '/api': 'http://127.0.0.1:8018' } } })
  await vite.listen()
  for (const script of ['browser.mjs', 'admin-browser.mjs', 'riders-browser.mjs', 'auth-browser.mjs', 'fresh-setup-browser.mjs']) {
    await new Promise((resolve, reject) => {
      const child = spawn(process.execPath, [`tests/${script}`], { cwd: root, env, stdio: 'inherit' })
      child.on('error', reject)
      child.on('exit', code => code === 0 ? resolve() : reject(new Error(`${script} failed (${code})`)))
    })
  }
} finally {
  await vite?.close()
  if (php?.pid) { try { process.kill(-php.pid, 'SIGTERM') } catch {} }
}
