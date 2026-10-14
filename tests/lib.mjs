// Общие помощники тестов: вход в админку и вызовы API. Нужен запущенный сервер:
//   php -S 127.0.0.1:8080 app/dev-router.php   (и вход: php app/bin/admin-setup.php testpass123)
export const SITE = process.env.SITE || 'http://127.0.0.1:8080';
const B = SITE + '/admin/';
let cookie = '';
export async function req(path, opts = {}) {
  const r = await fetch(path.startsWith('http') ? path : B + path, { redirect: 'manual', ...opts, headers: { cookie, ...(opts.headers || {}) } });
  for (const c of r.headers.getSetCookie?.() || []) { const kv = c.split(';')[0]; const [k] = kv.split('='); cookie = cookie.split('; ').filter(x => x && !x.startsWith(k + '=')).concat(kv).join('; '); }
  return r;
}
let csrf = '';
export async function login(password = process.env.ADMIN_PASS || 'testpass123') {
  await req('login', { method: 'POST', body: new URLSearchParams({ password }) });
  const html = await (await req('')).text();
  csrf = (html.match(/"csrf":"([a-f0-9]+)"/) || [])[1];
  if (!csrf) throw new Error('не удалось войти в админку');
}
export async function api(p, body, form) {
  const opts = form ? { method: 'POST', body: form, headers: { 'X-CSRF': csrf } }
    : body ? { method: 'POST', body: JSON.stringify(body), headers: { 'Content-Type': 'application/json', 'X-CSRF': csrf } } : {};
  return (await req('api/' + p, opts)).json();
}
export function check(ok, label, extra = '') { console.log((ok ? 'OK  ' : 'FAIL') + ' ' + label + (extra ? ' -> ' + extra : '')); if (!ok) process.exitCode = 1; return ok; }
