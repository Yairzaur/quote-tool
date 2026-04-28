<?php
define('TERMINAL', 144187);
define('API_NAME', 'YHCYRjtzNgoyG5cdaUGi');
define('API_PASSWORD', 'kP6A9MOfihPCqpAJd3vN');
define('CARDCOM_URL', 'https://secure.cardcom.solutions/Interface/CreateInvoice.aspx');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    header('Content-Type: application/json; charset=utf-8');

    $customerName = trim($_POST['customerName'] ?? '');
    $customerPhone = trim($_POST['customerPhone'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');

    $params = [
        'TerminalNumber'             => TERMINAL,
        'UserName'                   => API_NAME,
        'UserPassword'               => API_PASSWORD,
        'codepage'                   => 65001,
        'InvoiceType'                => 400,
        'InvoiceHead.CustName'       => $customerName,
        'InvoiceHead.Phone'          => $customerPhone,
        'InvoiceHead.Language'       => 'he',
        'InvoiceHead.SendByEmail'    => 'false',
        'InvoiceLines1.Description'  => $description,
        'InvoiceLines1.Price'        => $price,
        'InvoiceLines1.Quantity'     => 1,
        'InvoiceLines1.IsPriceIncludeVAT' => 'true',
    ];

    if ($notes) {
        $params['InvoiceLines2.Description'] = $notes;
        $params['InvoiceLines2.Price']       = 0;
        $params['InvoiceLines2.Quantity']    = 1;
    }

    $ch = curl_init(CARDCOM_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $raw = curl_exec($ch);
    curl_close($ch);

    parse_str($raw, $result);

    $code = $result['ResponseCode'] ?? '-1';
    $link = $result['Link'] ?? '';

    if ($code === '0' && $link) {
        echo json_encode(['success' => true, 'link' => $link]);
    } else {
        $msg = $result['Description'] ?? "שגיאה (קוד $code)";
        echo json_encode(['success' => false, 'error' => $msg]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
  <title>הצעת מחיר | קרקס הקסמים</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      background: #0e0e0e;
      color: #f0e6c8;
      font-family: 'Segoe UI', Arial, sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: flex-start;
      justify-content: center;
      padding: 24px 16px 40px;
    }
    .container { width: 100%; max-width: 480px; }
    .logo { text-align: center; margin-bottom: 28px; }
    .logo h1 { font-size: 22px; font-weight: 700; color: #c9a84c; letter-spacing: 1px; }
    .logo p { font-size: 13px; color: #888; margin-top: 4px; }
    .card { background: #1a1a1a; border: 1px solid #2a2a2a; border-radius: 16px; padding: 28px 24px; }
    .field { margin-bottom: 18px; }
    label { display: block; font-size: 13px; color: #c9a84c; margin-bottom: 7px; font-weight: 600; }
    input, select, textarea {
      width: 100%; background: #111; border: 1px solid #333;
      color: #f0e6c8; padding: 13px 14px; border-radius: 10px;
      font-size: 15px; font-family: inherit; direction: rtl;
      transition: border-color 0.2s; -webkit-appearance: none;
    }
    input:focus, select:focus, textarea:focus { outline: none; border-color: #c9a84c; }
    select option { background: #1a1a1a; }
    textarea { height: 80px; resize: none; }
    .price-symbol { position: relative; }
    .price-symbol::after {
      content: '₪'; position: absolute; left: 14px; top: 50%;
      transform: translateY(-50%); color: #c9a84c; font-size: 16px; pointer-events: none;
    }
    .price-symbol input { padding-left: 34px; }
    .btn {
      width: 100%; padding: 16px; border-radius: 12px; border: none;
      font-size: 17px; font-weight: 700; cursor: pointer; margin-top: 8px;
      transition: all 0.2s; font-family: inherit;
    }
    .btn-primary { background: linear-gradient(135deg, #c9a84c, #a07830); color: #0e0e0e; }
    .btn-primary:hover { filter: brightness(1.1); }
    .btn-primary:active { transform: scale(0.98); }
    .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
    .spinner {
      display: inline-block; width: 18px; height: 18px;
      border: 2px solid rgba(0,0,0,0.2); border-top-color: #0e0e0e;
      border-radius: 50%; animation: spin 0.7s linear infinite;
      vertical-align: middle; margin-left: 8px;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    .result-box { margin-top: 20px; padding: 20px; border-radius: 12px; display: none; }
    .result-box.success { background: #0f1f0f; border: 1px solid #2a5a2a; }
    .result-box.error { background: #1f0f0f; border: 1px solid #5a2a2a; }
    .result-box .result-title { font-size: 15px; font-weight: 700; margin-bottom: 12px; }
    .result-box.success .result-title { color: #6adf6a; }
    .result-box.error .result-title { color: #df6a6a; }
    .link-display {
      background: #111; border: 1px solid #333; border-radius: 8px;
      padding: 10px 12px; font-size: 12px; color: #888;
      word-break: break-all; margin-bottom: 14px;
    }
    .btn-wa {
      background: #25D366; color: #fff; display: flex; align-items: center;
      justify-content: center; gap: 8px; text-decoration: none;
      padding: 14px; border-radius: 12px; font-size: 16px; font-weight: 700; margin-bottom: 10px;
    }
    .btn-copy { background: #2a2a2a; color: #c9a84c; border: 1px solid #3a3a3a; }
    .btn-new { background: transparent; color: #888; border: 1px solid #333; margin-top: 4px; }
    .error-msg { color: #df6a6a; font-size: 14px; }
  </style>
</head>
<body>
  <div class="container">
    <div class="logo">
      <h1>✦ קרקס הקסמים</h1>
      <p>יצירת הצעת מחיר מהירה</p>
    </div>
    <div class="card">
      <form id="quoteForm">
        <div class="field">
          <label>שם הלקוח / שם האירוע</label>
          <input type="text" id="customerName" placeholder="לדוגמה: יעל כהן - יום הולדת 7" required />
        </div>
        <div class="field">
          <label>טלפון לקוח (אופציונלי)</label>
          <input type="tel" id="customerPhone" placeholder="050-0000000" />
        </div>
        <div class="field">
          <label>סוג השירות</label>
          <select id="serviceType" onchange="handleServiceChange()">
            <option value="">— בחר סוג שירות —</option>
            <option value="הופעת קרקס הקסמים - חבילה בסיסית">הופעה בסיסית</option>
            <option value="הופעת קרקס הקסמים - חבילה פרימיום">הופעה פרימיום</option>
            <option value="הופעת קרקס הקסמים - חבילת VIP">הופעת VIP</option>
            <option value="הופעת קרקס הקסמים - אירוע חברות">אירוע חברות</option>
            <option value="קורס אקדמיה דיגיטלית למנטליזם">אקדמיה דיגיטלית</option>
            <option value="custom">תיאור חופשי ↓</option>
          </select>
        </div>
        <div class="field" id="customDescField" style="display:none">
          <label>תיאור השירות</label>
          <input type="text" id="customDesc" placeholder="כתוב את שם השירות" />
        </div>
        <div class="field">
          <label>הערות / פרטים נוספים (אופציונלי)</label>
          <textarea id="notes" placeholder="לדוגמה: 60 דקות הופעה, 20 ילדים..."></textarea>
        </div>
        <div class="field">
          <label>מחיר</label>
          <div class="price-symbol">
            <input type="number" id="price" placeholder="0" min="0" step="1" required />
          </div>
        </div>
        <button type="submit" class="btn btn-primary" id="submitBtn">צור הצעת מחיר</button>
      </form>
      <div class="result-box" id="resultBox">
        <div class="result-title" id="resultTitle"></div>
        <div id="resultContent"></div>
      </div>
    </div>
  </div>
  <script>
    function handleServiceChange() {
      const sel = document.getElementById('serviceType');
      document.getElementById('customDescField').style.display = sel.value === 'custom' ? 'block' : 'none';
    }
    function getDescription() {
      const sel = document.getElementById('serviceType');
      return sel.value === 'custom' ? document.getElementById('customDesc').value.trim() : sel.value;
    }
    document.getElementById('quoteForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const description = getDescription();
      if (!description) { alert('נא לבחור סוג שירות'); return; }
      const btn = document.getElementById('submitBtn');
      btn.disabled = true;
      btn.innerHTML = 'יוצר הצעה... <span class="spinner"></span>';
      const fd = new FormData();
      fd.append('action', 'create');
      fd.append('customerName', document.getElementById('customerName').value.trim());
      fd.append('customerPhone', document.getElementById('customerPhone').value.trim());
      fd.append('description', description);
      fd.append('price', document.getElementById('price').value);
      fd.append('notes', document.getElementById('notes').value.trim());
      try {
        const res = await fetch('', { method: 'POST', body: fd });
        const data = await res.json();
        const box = document.getElementById('resultBox');
        box.style.display = 'block';
        box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        if (data.success) {
          const waText = encodeURIComponent(`שלום ${document.getElementById('customerName').value.trim()},\n\nמצורפת הצעת המחיר שלנו:\n${data.link}\n\nבברכה,\nיאיר זאורוב - קרקס הקסמים`);
          document.getElementById('resultTitle').textContent = '✓ הצעת המחיר מוכנה!';
          box.className = 'result-box success';
          document.getElementById('resultContent').innerHTML = `
            <div class="link-display">${data.link}</div>
            <a href="https://wa.me/?text=${waText}" target="_blank" class="btn-wa btn">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="white"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.917 1.04 5.59 2.757 7.67L.946 23.434l3.907-1.749A11.923 11.923 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 01-5.006-1.37l-.359-.214-3.724 1.667 1.698-3.62-.234-.372A9.818 9.818 0 1112 21.818z"/></svg>
              שלח בוואטסאפ
            </a>
            <button class="btn btn-copy" onclick="copyLink('${data.link}')">העתק לינק</button>
            <button class="btn btn-new" onclick="newQuote()">הצעה חדשה</button>`;
        } else {
          document.getElementById('resultTitle').textContent = '✗ שגיאה';
          box.className = 'result-box error';
          document.getElementById('resultContent').innerHTML = `<p class="error-msg">${data.error}</p><button class="btn btn-new" style="margin-top:12px" onclick="newQuote()">נסה שוב</button>`;
        }
      } catch {
        const box = document.getElementById('resultBox');
        box.style.display = 'block';
        box.className = 'result-box error';
        document.getElementById('resultTitle').textContent = '✗ שגיאת חיבור';
        document.getElementById('resultContent').innerHTML = '<p class="error-msg">שגיאת חיבור.</p>';
      } finally {
        btn.disabled = false;
        btn.innerHTML = 'צור הצעת מחיר';
      }
    });
    function copyLink(link) {
      navigator.clipboard.writeText(link).then(() => {
        event.target.textContent = '✓ הועתק!';
        setTimeout(() => event.target.textContent = 'העתק לינק', 2000);
      });
    }
    function newQuote() {
      document.getElementById('quoteForm').reset();
      document.getElementById('customDescField').style.display = 'none';
      document.getElementById('resultBox').style.display = 'none';
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  </script>
</body>
</html>
