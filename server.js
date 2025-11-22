const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');
const express = require('express');

const app = express();
app.use(express.json());

const client = new Client({
    authStrategy: new LocalAuth(),
    puppeteer: {
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    }
});

client.on('qr', (qr) => {
    console.log('Scan this QR with your WhatsApp:');
    qrcode.generate(qr, { small: true });
});

client.on('ready', () => {
    console.log('WhatsApp Connected! Ready to send OTPs');
});

client.on('authenticated', () => {
    console.log('Logged in successfully');
});

client.initialize();

// SEND OTP ENDPOINT
app.post('/send-otp', async (req, res) => {
    let { number, otp } = req.body;

    // Generate OTP if Laravel sent empty or missing
    if (!otp || otp === '' || otp.length !== 6) {
        otp = Math.floor(100000 + Math.random() * 900000).toString();
    }

    if (!number) {
        return res.status(400).json({ success: false, message: 'Missing number' });
    }

    const chatId = number + "@c.us";
    const message = `Phone Book Sri Lanka

Your verification code is *${otp}*

Valid for 5 minutes.
Thank you!`;

    try {
        await client.sendMessage(chatId, message);
        console.log(`OTP ${otp} sent to ${number}`);

        // RETURN THE REAL OTP TO LARAVEL
        res.json({ success: true, otp: otp });
    } catch (error) {
        console.error('Send failed:', error.message);
        res.status(500).json({ success: false, message: error.message });
    }
});

app.listen(3000, '0.0.0.0', () => {
    console.log('WhatsApp OTP Server (whatsapp-web.js) Running on http://YOUR_IP:3000');
});