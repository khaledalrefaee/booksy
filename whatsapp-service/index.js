const express = require('express');
let baileys;
try { baileys = require('baileys'); } catch(e) { baileys = require('@whiskeysockets/baileys'); }
const { makeWASocket, useMultiFileAuthState, DisconnectReason } = baileys;
const pino = require('pino');
const QRCode = require('qrcode');
const path = require('path');
const fs = require('fs');

const app = express();
app.use(express.json());

const PORT = process.env.WA_PORT || 3001;
const API_KEY = process.env.WA_API_KEY || 'booksy-wa-secret-2026';
const AUTH_DIR = path.join(__dirname, 'auth_info');

let sock = null;
let qrCode = null;
let qrDataUrl = null;
let connectionStatus = 'disconnected';
let connectedPhone = null;
let messageQueue = [];
let isProcessingQueue = false;
let sentCount = 0;
let failedCount = 0;

function authMiddleware(req, res, next) {
    const key = req.headers['x-api-key'] || req.query.api_key;
    if (key !== API_KEY) return res.status(401).json({ error: 'Unauthorized' });
    next();
}

async function connectWhatsApp() {
    try {
        if (!fs.existsSync(AUTH_DIR)) fs.mkdirSync(AUTH_DIR, { recursive: true });

        console.log('🔌 Connecting to WhatsApp...');
        const { state, saveCreds } = await useMultiFileAuthState(AUTH_DIR);

        sock = makeWASocket({
            auth: state,
            printQRInTerminal: false,
            logger: pino({ level: 'silent' }),
            browser: ['Booksy', 'Safari', '3.0'],
            connectTimeoutMs: 120000,
            keepAliveIntervalMs: 30000,
            retryRequestDelayMs: 5000,
            markOnlineOnConnect: false,
        });

        sock.ev.on('creds.update', saveCreds);

        sock.ev.on('connection.update', async (update) => {
            const { connection, lastDisconnect, qr } = update;

            if (qr) {
                qrCode = qr;
                qrDataUrl = await QRCode.toDataURL(qr, { width: 280, margin: 2 });
                connectionStatus = 'qr_ready';
                console.log('📱 QR Code ready — open http://localhost:' + PORT + ' to scan');
            }

            if (connection === 'close') {
                connectionStatus = 'disconnected';
                qrCode = null;
                qrDataUrl = null;
                connectedPhone = null;
                const statusCode = lastDisconnect?.error?.output?.statusCode;

                if (statusCode === DisconnectReason.loggedOut || statusCode === 401) {
                    console.log('❌ Logged out — clearing session...');
                    try { fs.rmSync(AUTH_DIR, { recursive: true, force: true }); } catch(e) {}
                    setTimeout(connectWhatsApp, 3000);
                } else if (statusCode === DisconnectReason.restartRequired) {
                    setTimeout(connectWhatsApp, 2000);
                } else {
                    console.log(`⚠️  Disconnected (code: ${statusCode}). Retrying in 10s...`);
                    setTimeout(connectWhatsApp, 10000);
                }
            }

            if (connection === 'open') {
                connectionStatus = 'connected';
                qrCode = null;
                qrDataUrl = null;
                connectedPhone = sock.user?.id?.split(':')[0] || sock.user?.id || 'unknown';
                console.log(`✅ Connected: ${connectedPhone}`);
                processQueue();
            }
        });

        sock.ev.on('messages.upsert', () => {});

    } catch (err) {
        console.error('❌ Error:', err.message);
        setTimeout(connectWhatsApp, 15000);
    }
}

function formatPhone(phone) {
    let cleaned = String(phone).replace(/[\s\-\(\)\+]/g, '');
    if (cleaned.startsWith('00')) cleaned = cleaned.substring(2);
    if (cleaned.startsWith('0')) cleaned = '963' + cleaned.substring(1);
    if (!cleaned.includes('@')) cleaned = cleaned + '@s.whatsapp.net';
    return cleaned;
}

async function processQueue() {
    if (isProcessingQueue || messageQueue.length === 0) return;
    if (connectionStatus !== 'connected' || !sock) return;

    isProcessingQueue = true;
    while (messageQueue.length > 0) {
        const msg = messageQueue.shift();
        try {
            await sock.sendMessage(formatPhone(msg.phone), { text: msg.text });
            sentCount++;
            msg.resolve({ success: true });
        } catch (err) {
            failedCount++;
            msg.reject(err);
        }
        await new Promise(r => setTimeout(r, 2000 + Math.random() * 3000));
    }
    isProcessingQueue = false;
}

// ── Web Dashboard ───────────────────────────────────────────────────────
app.get('/', (req, res) => {
    const statusColors = { connected: '#22c55e', qr_ready: '#f59e0b', disconnected: '#ef4444' };
    const statusLabels = { connected: '✅ متصل', qr_ready: '📱 بانتظار المسح', disconnected: '❌ غير متصل' };

    res.send(`<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booksy WhatsApp</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Poppins',sans-serif; background:#0f0f17; color:#fff; min-height:100vh; padding:24px; }
        .container { max-width:520px; margin:0 auto; }
        .card { background:#1a1f2e; border-radius:20px; padding:28px; margin-bottom:16px; border:1px solid rgba(255,255,255,.06); }
        h1 { font-size:22px; font-weight:800; margin-bottom:4px; }
        .subtitle { font-size:12px; opacity:.4; margin-bottom:24px; }
        .status-bar { display:flex; align-items:center; gap:10px; padding:14px 18px; border-radius:14px; margin-bottom:20px; }
        .status-dot { width:12px; height:12px; border-radius:50%; flex-shrink:0; }
        .status-dot.pulse { animation: pulse 2s ease infinite; }
        @keyframes pulse { 0%,100%{opacity:1;} 50%{opacity:.3;} }
        .status-text { font-size:14px; font-weight:700; }
        .status-sub { font-size:11px; opacity:.5; }
        .qr-box { text-align:center; padding:20px; background:rgba(255,255,255,.03); border-radius:16px; margin-bottom:16px; }
        .qr-box img { border-radius:12px; background:#fff; padding:8px; }
        .qr-label { font-size:12px; opacity:.5; margin-top:10px; }
        .stats { display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; margin-bottom:20px; }
        .stat { background:rgba(255,255,255,.04); border-radius:12px; padding:14px; text-align:center; }
        .stat-num { font-size:22px; font-weight:800; }
        .stat-label { font-size:10px; opacity:.4; text-transform:uppercase; letter-spacing:.5px; margin-top:2px; }
        .form-group { margin-bottom:12px; }
        label { font-size:12px; font-weight:600; opacity:.6; display:block; margin-bottom:4px; }
        input, textarea { width:100%; background:rgba(255,255,255,.06); border:1.5px solid rgba(255,255,255,.1); border-radius:10px; padding:10px 14px; color:#fff; font-family:inherit; font-size:13px; }
        input:focus, textarea:focus { outline:none; border-color:#C9A227; }
        textarea { resize:vertical; min-height:70px; }
        .btn { width:100%; padding:12px; border:none; border-radius:12px; font-family:inherit; font-size:14px; font-weight:700; cursor:pointer; transition:transform .1s; }
        .btn:active { transform:scale(.98); }
        .btn-primary { background:linear-gradient(135deg,#C9A227,#d4af37); color:#000; }
        .btn-danger { background:rgba(239,68,68,.15); color:#ef4444; border:1px solid rgba(239,68,68,.2); }
        .btn-outline { background:rgba(255,255,255,.06); color:#fff; border:1px solid rgba(255,255,255,.1); }
        .btn + .btn { margin-top:8px; }
        .result { padding:12px 16px; border-radius:10px; font-size:12px; font-weight:600; margin-top:12px; display:none; }
        .result.success { background:rgba(34,197,94,.1); color:#22c55e; }
        .result.error { background:rgba(239,68,68,.1); color:#ef4444; }
        .actions { display:flex; gap:8px; margin-top:16px; }
        .actions .btn { flex:1; padding:10px; font-size:12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>💬 Booksy WhatsApp</h1>
            <div class="subtitle">خدمة إشعارات الواتساب</div>

            <div class="status-bar" id="statusBar" style="background:rgba(${statusColors[connectionStatus] === '#22c55e' ? '34,197,94' : statusColors[connectionStatus] === '#f59e0b' ? '245,158,11' : '239,68,68'},.1);">
                <div class="status-dot ${connectionStatus === 'connected' ? 'pulse' : ''}" id="statusDot"
                     style="background:${statusColors[connectionStatus] || '#ef4444'};${connectionStatus === 'connected' ? 'box-shadow:0 0 8px '+statusColors[connectionStatus] : ''};"></div>
                <div>
                    <div class="status-text" id="statusText">${statusLabels[connectionStatus] || connectionStatus}</div>
                    <div class="status-sub" id="statusSub">${connectedPhone ? 'الرقم: ' + connectedPhone : 'في انتظار الاتصال...'}</div>
                </div>
            </div>

            ${qrDataUrl ? `
            <div class="qr-box" id="qrBox">
                <img src="${qrDataUrl}" alt="QR" width="260">
                <div class="qr-label">امسح الكود بتطبيق WhatsApp</div>
            </div>` : '<div id="qrBox"></div>'}

            <div class="stats">
                <div class="stat">
                    <div class="stat-num" style="color:#22c55e;" id="sentCount">${sentCount}</div>
                    <div class="stat-label">مرسلة</div>
                </div>
                <div class="stat">
                    <div class="stat-num" style="color:#ef4444;" id="failedCount">${failedCount}</div>
                    <div class="stat-label">فاشلة</div>
                </div>
                <div class="stat">
                    <div class="stat-num" style="color:#f59e0b;" id="queueCount">${messageQueue.length}</div>
                    <div class="stat-label">بالانتظار</div>
                </div>
            </div>
        </div>

        <div class="card">
            <h2 style="font-size:16px;font-weight:700;margin-bottom:16px;">📤 إرسال رسالة تجريبية</h2>
            <form id="sendForm" onsubmit="sendMsg(event)">
                <div class="form-group">
                    <label>رقم الهاتف</label>
                    <input type="text" id="phone" placeholder="09XXXXXXXX" dir="ltr" required>
                </div>
                <div class="form-group">
                    <label>الرسالة</label>
                    <textarea id="message" placeholder="مرحباً! هذه رسالة تجريبية من Booksy ✅">مرحباً! هذه رسالة تجريبية من Booksy ✅</textarea>
                </div>
                <button type="submit" class="btn btn-primary" id="sendBtn">إرسال ✉️</button>
            </form>
            <div class="result" id="result"></div>

            <div class="actions">
                <button class="btn btn-outline" onclick="restartWA()">🔄 إعادة اتصال</button>
                <button class="btn btn-danger" onclick="resetWA()">🗑️ حذف الجلسة</button>
            </div>
        </div>
    </div>

    <script>
        const API_KEY = '${API_KEY}';
        const headers = { 'Content-Type': 'application/json', 'X-Api-Key': API_KEY };

        async function sendMsg(e) {
            e.preventDefault();
            const btn = document.getElementById('sendBtn');
            const result = document.getElementById('result');
            btn.textContent = '⏳ جاري الإرسال...';
            btn.disabled = true;
            result.style.display = 'none';

            try {
                const res = await fetch('/send', {
                    method: 'POST', headers,
                    body: JSON.stringify({
                        phone: document.getElementById('phone').value,
                        message: document.getElementById('message').value,
                    })
                });
                const data = await res.json();
                if (res.ok) {
                    result.className = 'result success';
                    result.textContent = '✅ تم الإرسال بنجاح!';
                } else {
                    result.className = 'result error';
                    result.textContent = '❌ ' + (data.error || 'فشل الإرسال');
                }
            } catch (err) {
                result.className = 'result error';
                result.textContent = '❌ خطأ: ' + err.message;
            }
            result.style.display = 'block';
            btn.textContent = 'إرسال ✉️';
            btn.disabled = false;
            refreshStatus();
        }

        async function restartWA() {
            await fetch('/restart', { method: 'POST', headers });
            setTimeout(refreshStatus, 2000);
        }

        async function resetWA() {
            if (!confirm('هل تريد حذف الجلسة؟ ستحتاج مسح QR من جديد.')) return;
            await fetch('/reset', { method: 'POST', headers });
            setTimeout(() => location.reload(), 2000);
        }

        async function refreshStatus() {
            try {
                const res = await fetch('/status?api_key=' + API_KEY);
                const data = await res.json();

                const colors = { connected: '#22c55e', qr_ready: '#f59e0b', disconnected: '#ef4444' };
                const labels = { connected: '✅ متصل', qr_ready: '📱 بانتظار المسح', disconnected: '❌ غير متصل' };
                const c = colors[data.status] || '#ef4444';
                const rgb = c === '#22c55e' ? '34,197,94' : c === '#f59e0b' ? '245,158,11' : '239,68,68';

                document.getElementById('statusBar').style.background = 'rgba('+rgb+',.1)';
                document.getElementById('statusDot').style.background = c;
                document.getElementById('statusDot').className = 'status-dot' + (data.status === 'connected' ? ' pulse' : '');
                document.getElementById('statusText').textContent = labels[data.status] || data.status;

                if (data.status === 'qr_ready' && data.qr) {
                    location.reload();
                }
                if (data.status === 'connected') {
                    document.getElementById('qrBox').innerHTML = '';
                }
            } catch(e) {}
        }

        setInterval(refreshStatus, 5000);
    </script>
</body>
</html>`);
});

// ── API ─────────────────────────────────────────────────────────────────
app.get('/status', authMiddleware, (req, res) => {
    res.json({ status: connectionStatus, qr: qrCode, queue: messageQueue.length, phone: connectedPhone, sent: sentCount, failed: failedCount });
});

app.post('/restart', authMiddleware, (req, res) => {
    connectionStatus = 'disconnected';
    if (sock) { try { sock.end(); } catch(e) {} }
    sock = null;
    setTimeout(connectWhatsApp, 1000);
    res.json({ ok: true });
});

app.post('/reset', authMiddleware, (req, res) => {
    connectionStatus = 'disconnected';
    if (sock) { try { sock.end(); } catch(e) {} }
    sock = null;
    try { fs.rmSync(AUTH_DIR, { recursive: true, force: true }); } catch(e) {}
    setTimeout(connectWhatsApp, 1000);
    res.json({ ok: true });
});

app.post('/send', authMiddleware, async (req, res) => {
    const { phone, message } = req.body;
    if (!phone || !message) return res.status(400).json({ error: 'phone and message required' });
    if (connectionStatus !== 'connected') return res.status(503).json({ error: 'Not connected', status: connectionStatus });

    try {
        const result = await new Promise((resolve, reject) => {
            messageQueue.push({ phone, text: message, resolve, reject });
            processQueue();
        });
        res.json(result);
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
});

app.post('/send-bulk', authMiddleware, async (req, res) => {
    const { messages } = req.body;
    if (!Array.isArray(messages) || !messages.length) return res.status(400).json({ error: 'messages array required' });
    if (connectionStatus !== 'connected') return res.status(503).json({ error: 'Not connected' });

    let queued = 0;
    for (const msg of messages) {
        if (msg.phone && msg.message) {
            messageQueue.push({ phone: msg.phone, text: msg.message, resolve: () => {}, reject: () => {} });
            queued++;
        }
    }
    processQueue();
    res.json({ queued });
});

app.listen(PORT, () => {
    console.log(`\n🚀 Booksy WhatsApp — http://localhost:${PORT}\n`);
    connectWhatsApp();
});
