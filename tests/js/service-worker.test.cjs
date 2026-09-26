// Exercises the real public/sw.js against a minimal service-worker stub so the
// cache-exclusion and click-target logic are verified, not just eyeballed.
// Uses only Node builtins, so it needs no test framework: run with
//   npm run test:sw
const fs = require('fs');
const path = require('path');
const vm = require('vm');

const SW_PATH = path.join(__dirname, '..', '..', 'public', 'sw.js');

const listeners = {};
const cached = new Map();

const sandbox = {
    self: {
        location: { origin: 'https://bmstest.marshacreatives.co.ke' },
        registration: { showNotification: async () => {} },
        clients: {
            matchAll: async () => [],
            openWindow: async (u) => { sandbox.__opened = u; },
        },
        skipWaiting: async () => {},
    },
    location: { origin: 'https://bmstest.marshacreatives.co.ke' },
    caches: {
        open: async () => ({ addAll: async () => {}, put: async (r, v) => cached.set(r.url, v) }),
        match: async () => undefined,
        keys: async () => [],
        delete: async () => true,
    },
    fetch: async () => { throw new Error('network'); },
    URL,
    Promise,
    console,
};
sandbox.self.addEventListener = (type, fn) => { listeners[type] = fn; };
sandbox.__listeners = listeners;
sandbox.__cached = cached;

vm.createContext(sandbox);
vm.runInContext(fs.readFileSync(SW_PATH, 'utf8'), sandbox);

let failures = 0;
function check(name, actual, expected) {
    const ok = actual === expected;
    if (!ok) { failures++; }
    console.log(`${ok ? 'PASS' : 'FAIL'}  ${name}  (got ${JSON.stringify(actual)}, want ${JSON.stringify(expected)})`);
}

// Re-run the fetch handler's guard for a path and report whether it handles it.
function isHandled(pathname) {
    let handled = false;
    const event = {
        request: { method: 'GET', url: 'https://bmstest.marshacreatives.co.ke' + pathname, mode: 'no-cors' },
        respondWith: () => { handled = true; },
    };
    listeners.fetch(event);
    return handled;
}

console.log('--- cache exclusion ---');
check('/notifications is never cached', isHandled('/notifications'), false);
check('/notifications/all is never cached', isHandled('/notifications/all'), false);
check('/notifications/{id}/read is never cached', isHandled('/notifications/abc-123/read'), false);
check('/push/key is never cached', isHandled('/push/key'), false);
check('/login is cacheable', isHandled('/login'), true);
check('/images/logo.png is cacheable', isHandled('/images/logo.png'), true);
check('/pushovers are still cacheable', isHandled('/pushovers'), true);
check('/notificationsfoo is still cacheable', isHandled('/notificationsfoo'), true);

console.log('--- non-GET and cross-origin are ignored ---');
let handled = false;
listeners.fetch({
    request: { method: 'POST', url: 'https://bmstest.marshacreatives.co.ke/notifications/read-all', mode: 'cors' },
    respondWith: () => { handled = true; },
});
check('POST is ignored', handled, false);

console.log('--- notification click target ---');
async function clickTarget(url) {
    let opened = null;
    sandbox.__opened = null;
    listeners.notificationclick({
        notification: { close() {}, data: { url } },
        waitUntil: (p) => p,
    });
    await new Promise((r) => setImmediate(r));
    return sandbox.__opened;
}

(async () => {
    check('absolute same-origin url is made relative',
        await clickTarget('https://bmstest.marshacreatives.co.ke/admin/jobs'), '/admin/jobs');
    check('query string is preserved',
        await clickTarget('https://bmstest.marshacreatives.co.ke/admin/jobs?page=2'), '/admin/jobs?page=2');
    check('relative url is kept',
        await clickTarget('/employee/dashboard'), '/employee/dashboard');
    check('stale foreign domain falls back to root',
        await clickTarget('https://old-domain.example/admin/jobs'), '/');
    check('missing url falls back to root',
        await clickTarget(undefined), '/');

    console.log(failures === 0 ? '\nALL SW CHECKS PASSED' : `\n${failures} SW CHECK(S) FAILED`);
    process.exit(failures === 0 ? 0 : 1);
})();
