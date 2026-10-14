// Скриншоты админки в headless Chrome: вход, обход разделов, ошибки JS в консоли. node tests/shots.mjs [маршруты] [ширина] [высота] [full]
import { spawn } from 'child_process';
import fs from 'fs';
const OUT = process.env.SHOTS || 'shots';
fs.mkdirSync(OUT, { recursive: true });
const routes = (process.argv[2] || '#/,#/section/hero,#/section/programs,#/section/documents,#/section/menu,#/photos,#/pdfs,#/leads,#/settings').split(',');
const width = Number(process.argv[3] || 1360), height = Number(process.argv[4] || 900);
const chrome = spawn('C:/Program Files/Google/Chrome/Application/chrome.exe', ['--headless=new', '--remote-debugging-port=9333', '--user-data-dir=' + OUT + '/prof', '--no-first-run', `--window-size=${width},${height}`, 'about:blank'], { stdio: 'ignore' });
const sleep = ms => new Promise(r => setTimeout(r, ms));
let ws;
for (let i = 0; i < 40; i++) {
  try { const l = await (await fetch('http://127.0.0.1:9333/json/list')).json(); const p = l.find(x => x.type === 'page'); if (p) { ws = new WebSocket(p.webSocketDebuggerUrl); break; } } catch { }
  await sleep(250);
}
await new Promise(r => ws.addEventListener('open', r, { once: true }));
let id = 0; const pending = new Map(); const errors = [];
ws.addEventListener('message', ev => {
  const m = JSON.parse(ev.data);
  if (m.id && pending.has(m.id)) { pending.get(m.id)(m.result || m); pending.delete(m.id); }
  if (m.method === 'Runtime.exceptionThrown') errors.push(m.params.exceptionDetails.exception?.description || m.params.exceptionDetails.text);
  if (m.method === 'Runtime.consoleAPICalled' && m.params.type === 'error') errors.push(m.params.args.map(a => a.value || a.description).join(' '));
});
const send = (method, params = {}) => new Promise(r => { const i = ++id; pending.set(i, r); ws.send(JSON.stringify({ id: i, method, params })); });
const ev = async expr => (await send('Runtime.evaluate', { expression: expr, awaitPromise: true, returnByValue: true })).result?.value;
await send('Runtime.enable'); await send('Page.enable');
await send('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile: width < 600 });
await send('Page.navigate', { url: 'http://127.0.0.1:8080/admin/login' }); await sleep(800);
await ev(`document.querySelector('input[name=password]').value='testpass123'; document.querySelector('form').submit(); 1`); await sleep(1200);
for (const r of routes) {
  await ev(`location.hash='${r}'; 1`); await sleep(1500);
  if (process.env.EVAL) { await ev(process.env.EVAL); await sleep(500); } // действие перед снимком, например раскрыть карточку
  const full = process.argv[5] === 'full';
  const h = full ? await ev('document.documentElement.scrollHeight') : height;
  if (full) await send('Emulation.setDeviceMetricsOverride', { width, height: Math.min(h, 6000), deviceScaleFactor: 1, mobile: width < 600 });
  const shot = await send('Page.captureScreenshot', { format: 'png' });
  const name = (r.replace(/[#/]+/g, '_').replace(/^_|_$/g, '') || 'dash') + '-' + width + '.png';
  fs.writeFileSync(OUT + '/' + name, Buffer.from(shot.data, 'base64'));
  if (full) await send('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile: width < 600 });
  console.log('shot', name, 'overflowX:', await ev('document.documentElement.scrollWidth > innerWidth'));
}
console.log('JS errors:', errors.length ? errors : 'none');
chrome.kill();
process.exit(0);
