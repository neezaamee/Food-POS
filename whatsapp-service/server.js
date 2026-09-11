import express from 'express';
import cors from 'cors';
import QRCode from 'qrcode';
import pino from 'pino';
import path from 'path';
import fs from 'fs';
import { fileURLToPath } from 'url';
import makeWASocket, {
  DisconnectReason,
  useMultiFileAuthState,
  fetchLatestBaileysVersion,
} from '@whiskeysockets/baileys';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const PORT = process.env.PORT || 3333;
const AUTH_DIR = path.resolve(__dirname, '../storage/app/whatsapp-auth');

// Ensure auth directory exists
if (!fs.existsSync(AUTH_DIR)) {
  fs.mkdirSync(AUTH_DIR, { recursive: true });
}

const app = express();
app.use(cors());
app.use(express.json());

const logger = pino({ level: 'silent' });

let sock = null;
let qrCodeDataUrl = null;
let connectionState = 'disconnected'; // 'disconnected' | 'connecting' | 'qr_ready' | 'connected'
let pairedUser = null;
let isStarting = false;

async function connectToWhatsApp() {
  if (isStarting) return;
  isStarting = true;

  try {
    connectionState = 'connecting';
    const { state, saveCreds } = await useMultiFileAuthState(AUTH_DIR);
    const { version, isLatest } = await fetchLatestBaileysVersion().catch(() => ({ version: [2, 3000, 1015901307], isLatest: true }));

    sock = makeWASocket({
      version,
      logger,
      printQRInTerminal: false,
      auth: state,
      browser: ['Food Point POS', 'Chrome', '1.0.0'],
      connectTimeoutMs: 60000,
      defaultQueryTimeoutMs: 60000,
      keepAliveIntervalMs: 25000,
    });

    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('connection.update', async (update) => {
      const { connection, lastDisconnect, qr } = update;

      if (qr) {
        connectionState = 'qr_ready';
        try {
          qrCodeDataUrl = await QRCode.toDataURL(qr, {
            width: 320,
            margin: 2,
            color: {
              dark: '#111827',
              light: '#FFFFFF',
            },
          });
          console.log('[WhatsApp Bridge] Fresh QR Code generated.');
        } catch (err) {
          console.error('[WhatsApp Bridge] Error generating QR code image:', err);
        }
      }

      if (connection === 'close') {
        const statusCode = lastDisconnect?.error?.output?.statusCode;
        const shouldReconnect = statusCode !== DisconnectReason.loggedOut;
        console.log(`[WhatsApp Bridge] Connection closed (status: ${statusCode}). Reconnecting: ${shouldReconnect}`);

        qrCodeDataUrl = null;
        pairedUser = null;
        connectionState = 'disconnected';
        isStarting = false;

        if (shouldReconnect) {
          setTimeout(() => connectToWhatsApp(), 3000);
        } else {
          // Logged out: clean credentials
          cleanAuthDir();
        }
      } else if (connection === 'open') {
        console.log('[WhatsApp Bridge] WhatsApp Connected successfully!');
        connectionState = 'connected';
        qrCodeDataUrl = null;
        isStarting = false;

        const userJid = sock.user?.id || '';
        const rawPhone = userJid.split(':')[0] || userJid.split('@')[0];
        pairedUser = {
          jid: userJid,
          phone: rawPhone,
          name: sock.user?.name || 'Food Point POS Device',
        };
      }
    });
  } catch (error) {
    console.error('[WhatsApp Bridge] Startup error:', error);
    connectionState = 'disconnected';
    isStarting = false;
  }
}

function cleanAuthDir() {
  try {
    if (fs.existsSync(AUTH_DIR)) {
      const files = fs.readdirSync(AUTH_DIR);
      for (const file of files) {
        fs.unlinkSync(path.join(AUTH_DIR, file));
      }
    }
  } catch (err) {
    console.error('[WhatsApp Bridge] Error cleaning auth directory:', err);
  }
}

// API Routes
app.get('/api/ping', (req, res) => {
  res.json({ ok: true, timestamp: Date.now() });
});

app.get('/api/status', (req, res) => {
  res.json({
    ok: true,
    state: connectionState,
    connected: connectionState === 'connected',
    user: pairedUser,
    qr: qrCodeDataUrl,
  });
});

app.post('/api/connect', async (req, res) => {
  if (connectionState !== 'connected') {
    connectToWhatsApp();
  }
  res.json({
    ok: true,
    message: 'Pairing connection initiated',
    state: connectionState,
    qr: qrCodeDataUrl,
  });
});

app.post('/api/logout', async (req, res) => {
  try {
    if (sock) {
      await sock.logout().catch(() => {});
    }
  } catch (err) {
    console.error('[WhatsApp Bridge] Error during logout:', err);
  } finally {
    cleanAuthDir();
    sock = null;
    qrCodeDataUrl = null;
    pairedUser = null;
    connectionState = 'disconnected';
    isStarting = false;
  }

  res.json({ ok: true, message: 'Logged out successfully' });
});

app.post('/api/send', async (req, res) => {
  let { phone, message } = req.body;

  if (!phone || !message) {
    return res.status(400).json({ ok: false, error: 'Phone and message are required.' });
  }

  if (connectionState !== 'connected' || !sock) {
    return res.status(503).json({
      ok: false,
      error: 'WhatsApp is not connected. Please scan the QR code first.',
      state: connectionState,
    });
  }

  // Format destination JID: only digits
  const cleanNumber = String(phone).replace(/\D/g, '');
  if (cleanNumber.length < 9) {
    return res.status(400).json({ ok: false, error: 'Invalid phone number format.' });
  }

  const jid = `${cleanNumber}@s.whatsapp.net`;

  try {
    const result = await sock.sendMessage(jid, { text: message });
    return res.json({
      ok: true,
      messageId: result?.key?.id || null,
      recipient: cleanNumber,
    });
  } catch (error) {
    console.error('[WhatsApp Bridge] Send error:', error);
    return res.status(500).json({
      ok: false,
      error: error.message || 'Failed to send WhatsApp message.',
    });
  }
});

app.listen(PORT, '127.0.0.1', () => {
  console.log(`[WhatsApp Bridge] Running on http://127.0.0.1:${PORT}`);
  // Attempt auto-connect on start
  connectToWhatsApp();
});
