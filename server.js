const express = require('express');
const axios = require('axios');
const path = require('path');
const querystring = require('querystring');

const app = express();
app.use(express.json());
app.use(express.static(path.join(__dirname, 'public')));

const TERMINAL = 144187;
const API_NAME = 'YHCYRjtzNgoyG5cdaUGi';
const API_PASSWORD = 'kP6A9MOfihPCqpAJd3vN';
const CARDCOM_URL = 'https://secure.cardcom.solutions/Interface/CreateInvoice.aspx';

app.post('/create-quote', async (req, res) => {
  const { customerName, customerPhone, description, price, notes } = req.body;

  const lines = [{ description, price: parseFloat(price), qty: 1 }];
  if (notes && notes.trim()) {
    lines.push({ description: notes.trim(), price: 0, qty: 1 });
  }

  const params = {
    TerminalNumber: TERMINAL,
    UserName: API_NAME,
    UserPassword: API_PASSWORD,
    codepage: 65001,
    InvoiceType: 400,
    'InvoiceHead.CustName': customerName,
    'InvoiceHead.Phone': customerPhone || '',
    'InvoiceHead.Language': 'he',
    'InvoiceHead.SendByEmail': 'false',
  };

  lines.forEach((line, i) => {
    const n = i + 1;
    params[`InvoiceLines${n}.Description`] = line.description;
    params[`InvoiceLines${n}.Price`] = line.price;
    params[`InvoiceLines${n}.Quantity`] = line.qty;
    params[`InvoiceLines${n}.IsPriceIncludeVAT`] = 'true';
  });

  try {
    const response = await axios.post(CARDCOM_URL, querystring.stringify(params), {
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      timeout: 15000,
    });

    const raw = response.data;
    let parsed = {};

    if (typeof raw === 'string') {
      raw.split('&').forEach(pair => {
        const [k, v] = pair.split('=');
        if (k) parsed[decodeURIComponent(k)] = decodeURIComponent(v || '');
      });
    } else {
      parsed = raw;
    }

    const code = String(parsed.ResponseCode ?? parsed.responseCode ?? '');
    const link = parsed.Link || parsed.link || '';

    if (code === '0' && link) {
      res.json({ success: true, link });
    } else {
      const msg = parsed.Description || parsed.description || `קוד שגיאה: ${code}`;
      console.error('CardCom error:', parsed);
      res.json({ success: false, error: msg });
    }
  } catch (err) {
    console.error('Request error:', err.message);
    res.status(500).json({ success: false, error: 'שגיאת חיבור לCardCom' });
  }
});

const PORT = 3333;
app.listen(PORT, () => {
  console.log('\n========================================');
  console.log('  קרקס הקסמים - כלי הצעות מחיר');
  console.log('========================================');
  console.log(`\n  פתח בדפדפן: http://localhost:${PORT}`);
  console.log('  (לחץ Ctrl+C כדי לסגור)\n');
});
