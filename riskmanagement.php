<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Expires" content="0" />
<title>Risk Management Tool - The Trader's Escape</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
<style>
  :root{
    --bg:#0f172a; --panel:#1e293b; --panel-soft:#334155; --border:#475569;
    --text:#f8fafc; --muted:#94a3b8; --accent:#3b82f6; --accent-2:#1d4ed8; --danger:#ef4444; --warn:#f59e0b;
    --success:#10b981; --info:#06b6d4; --gradient:linear-gradient(135deg, var(--accent), var(--accent-2));
  }
  *{box-sizing:border-box}
  html,body{height:100%}
  body{
    margin:0; font-family:'Inter',system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;
    background: radial-gradient(1200px 600px at 10% -10%, rgba(59,130,246,.12), transparent 40%),
                radial-gradient(900px 500px at 90% -20%, rgba(29,78,216,.08), transparent 42%),
                var(--bg);
    color:var(--text); padding:24px; display:flex; justify-content:center; align-items:flex-start;
    min-height:100vh;
  }
  .wrap{width:100%; max-width:1400px; gap:20px; display:grid}
  .top{display:flex; align-items:center; gap:12px; color:var(--muted); font-weight:600; flex-wrap:wrap}
  .brand{display:flex; align-items:center; gap:10px; color:var(--text); font-weight:800}
  .brand img{height:34px; width:auto; filter:drop-shadow(0 0 6px rgba(59,130,246,.7)); border-radius:6px}
  .chip{margin-left:auto; display:flex; gap:8px}
  .pill{font-size:12px; padding:4px 10px; border-radius:999px; border:1px solid var(--border); color:var(--accent); background:rgba(59,130,246,.08)}
  .grid{display:grid; gap:16px}
  @media(min-width:1000px){.grid.cols-4{grid-template-columns:1fr 1fr 1fr 1fr}.grid.cols-3{grid-template-columns:1fr 1fr 1fr}.grid.cols-2{grid-template-columns:1fr 1fr}}
  .card{background:linear-gradient(180deg, rgba(59,130,246,.08), rgba(30,41,59,.8)); border:1px solid var(--border); border-radius:20px; padding:24px; backdrop-filter:blur(10px); box-shadow:0 8px 32px rgba(0,0,0,.3)}
  .section-title{font-weight:800; margin-bottom:10px}
  label{font-size:13px; color:var(--muted)}
  input, select, textarea{width:100%; background:var(--panel-soft); border:1px solid var(--border); border-radius:12px; padding:12px 16px; color:var(--text); transition:all 0.3s ease; font-size:14px}
  input:focus, select:focus, textarea:focus{outline:none; border-color:var(--accent); box-shadow:0 0 0 3px rgba(59,130,246,.1)}
  input[type=range]{accent-color:var(--accent)}
  textarea{min-height:64px; resize:vertical}
  .row{display:flex; align-items:center; justify-content:space-between; gap:12px}
  .hint{font-size:12px; color:var(--muted)}
  .button{cursor:pointer; border:none; border-radius:12px; padding:12px 20px; font-weight:600; background:var(--gradient); color:#ffffff; box-shadow:0 4px 16px rgba(59,130,246,.3); transition:all 0.3s ease; font-size:14px}
  .button:hover{transform:translateY(-2px); box-shadow:0 8px 24px rgba(59,130,246,.4)}
  .button.secondary{background:linear-gradient(90deg,var(--panel),var(--panel-soft)); color:var(--text); border:1px solid var(--border); box-shadow:none}
  .button.danger{background:linear-gradient(90deg,#5b1313,#2a0909); color:#ffdada; border:1px solid rgba(239,68,68,.5)}
  .kpi{display:grid; gap:8px; padding:16px; background:rgba(59,130,246,.05); border-radius:12px; border:1px solid rgba(59,130,246,.1)}
  .kpi .label{font-size:13px; color:var(--muted); font-weight:500}
  .kpi .value{font-size:28px; font-weight:800; background:var(--gradient); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text}
  .divider{height:1px; background:linear-gradient(90deg, rgba(59,130,246,.25), rgba(29,78,216,.25)); margin:10px 0}
  table{width:100%; border-collapse:collapse; font-size:13px; border-radius:12px; overflow:hidden}
  th, td{padding:12px 16px; border:1px solid var(--border); text-align:left}
  th{color:var(--accent); background:rgba(59,130,246,.08); font-weight:600}
  tr:hover{background:rgba(59,130,246,.02)}
  .badge{font-size:11px; padding:4px 8px; border-radius:999px; border:1px solid var(--border); color:var(--accent); background:rgba(59,130,246,.06)}
  .chart-container{width:100%; height:200px; max-height:200px; overflow:hidden; position:relative}
  canvas{width:100% !important; height:180px !important; max-height:180px !important; min-height:180px !important; background:var(--panel-soft); border:1px solid var(--border); border-radius:16px; display:block; box-shadow:inset 0 2px 8px rgba(0,0,0,.1)}
  .toolbar{display:flex; gap:8px; align-items:center; flex-wrap:wrap}
  .progress{height:12px; background:var(--panel-soft); border:1px solid var(--border); border-radius:999px; overflow:hidden}
  .progress > div{height:100%; background:var(--gradient); width:0%; transition:width 0.5s ease}
  @media print{ .top,.button,.pill,.chip,.toolbar { display:none !important } body{background:#fff;color:#000;padding:0} .card{border:none;box-shadow:none} }
</style>
</head>
<body>
  <div class="wrap">
    <div class="top">
      <div class="brand">
        <img src="/assets/logo.png" alt="The Trader's Escape">
        <span>Risk Management Tool</span>
      </div>
      <div class="chip">
        <span class="pill">Educational</span>
        <span class="pill">Demo Data</span>
      </div>
    </div>

    <!-- Position Size Calculator -->
    <div class="card">
      <div class="section-title">Advanced Position Size Calculator</div>
      <div class="grid cols-4">
        <div>
          <label>Account Equity ($)</label>
          <input type="number" id="equity" value="100000" placeholder="100000">
        </div>
        <div>
          <label>Risk Percentage (%)</label>
          <input type="number" id="riskPct" value="1" placeholder="1" step="0.1">
        </div>
        <div>
          <label>Fixed Risk Amount ($)</label>
          <input type="number" id="riskFixed" value="" placeholder="Leave empty for %">
        </div>
        <div>
          <label>Daily Loss Limit (%)</label>
          <input type="number" id="dailyLossPct" value="3" placeholder="3" step="0.1">
        </div>
      </div>
      <div class="divider"></div>
      <div class="grid cols-4">
        <div>
          <label>Segment</label>
          <select id="segment">
            <option value="Equity Cash">Equity Cash</option>
            <option value="Futures">Futures</option>
            <option value="Options">Options</option>
          </select>
        </div>
        <div>
          <label>Side</label>
          <select id="side">
            <option value="Long">Long</option>
            <option value="Short">Short</option>
          </select>
        </div>
        <div>
          <label>Symbol</label>
          <input type="text" id="symbol" placeholder="AAPL">
        </div>
        <div>
          <label>Preset</label>
          <select id="preset">
            <option value="">Custom</option>
            <option value="SPY">SPY</option>
            <option value="QQQ">QQQ</option>
            <option value="IWM">IWM</option>
            <option value="ES">ES Futures</option>
            <option value="NQ">NQ Futures</option>
          </select>
        </div>
      </div>
      <div class="divider"></div>
      <div class="grid cols-4">
        <div>
          <label>Entry Price</label>
          <input type="number" id="entry" value="100" placeholder="100" step="0.01">
        </div>
        <div>
          <label>Stop Loss</label>
          <input type="number" id="stop" value="95" placeholder="95" step="0.01">
        </div>
        <div>
          <label>Target Price</label>
          <input type="number" id="target" value="" placeholder="Optional" step="0.01">
        </div>
        <div>
          <label>R Multiple</label>
          <input type="number" id="rMultiple" value="" placeholder="Optional" step="0.1">
        </div>
      </div>
      <div class="divider"></div>
      <div class="grid cols-4">
        <div>
          <label>Lot Size</label>
          <input type="number" id="lotSize" value="100" placeholder="100">
        </div>
        <div>
          <label>Point Value</label>
          <input type="number" id="pointValue" value="1" placeholder="1">
        </div>
        <div>
          <label>Fees ($)</label>
          <input type="number" id="fees" value="0" placeholder="0" step="0.01">
        </div>
        <div>
          <label>Rounding Rule</label>
          <select id="roundRule">
            <option value="down">Down</option>
            <option value="up">Up</option>
            <option value="nearest">Nearest</option>
          </select>
        </div>
      </div>
      <div class="divider"></div>
      <div class="grid cols-4">
        <div>
          <label>
            <input type="checkbox" id="useAtr"> Use ATR for Stop Loss
          </label>
        </div>
        <div>
          <label>ATR</label>
          <input type="number" id="atr" value="" placeholder="ATR value" step="0.01">
        </div>
        <div>
          <label>ATR Multiplier</label>
          <input type="number" id="atrMult" value="2" placeholder="2" step="0.1">
        </div>
        <div>
          <label>Max Open Risk (%)</label>
          <input type="number" id="maxOpenRiskPct" value="2" placeholder="2" step="0.1">
        </div>
      </div>
      <div class="divider"></div>
      <div class="grid cols-2">
        <div>
          <label>Tags</label>
          <input type="text" id="sizerTags" placeholder="swing, momentum">
        </div>
        <div>
          <label>Notes</label>
          <input type="text" id="sizerNotes" placeholder="Trade notes">
        </div>
      </div>
      <div class="divider"></div>
      <div class="grid cols-4">
        <div class="kpi">
          <div class="label">Stop Distance</div>
          <div class="value" id="kStop">–</div>
        </div>
        <div class="kpi">
          <div class="label">Position Size</div>
          <div class="value" id="kQty">–</div>
        </div>
        <div class="kpi">
          <div class="label">Risk Amount</div>
          <div class="value" id="kRisk">–</div>
        </div>
        <div class="kpi">
          <div class="label">Potential Reward</div>
          <div class="value" id="kReward">–</div>
        </div>
      </div>
      <div class="row">
        <div class="kpi">
          <div class="label">Risk Budget</div>
          <div class="value" id="budgetBadge">–</div>
        </div>
      </div>
      <div class="hint" id="guardMsg"></div>
      <div class="toolbar">
        <button class="button" onclick="addJournal()">Add to Journal</button>
        <button class="button secondary" onclick="clearSizer()">Clear</button>
      </div>
    </div>

    <!-- Journal + Stats -->
    <div class="card">
      <div class="row" style="justify-content:space-between">
        <div class="section-title">Trade Journal & Analytics</div>
        <div class="toolbar">
          <label>Filter month</label><select id="monthFilter"></select>
          <label>Symbol</label><input type="text" id="symFilter" placeholder="e.g., AAPL" style="max-width:140px" />
          <label>Strategy/Tags</label><input type="text" id="tagFilter" placeholder="e.g., breakout" style="max-width:160px" />
          <input type="text" id="searchBox" placeholder="Search notes…" style="min-width:220px" />
          <label>Monthly Target ($)</label><input type="number" id="monthlyTarget" value="10000" style="max-width:160px" />
          <button class="button secondary" id="printReportBtn">Print report</button>
          <button class="button secondary" id="exportCsvBtn">Export CSV</button>
          <button class="button secondary" id="quickBackupBtn" title="Download ALL trades as CSV with today's date">Quick Backup</button>
          <label class="button secondary" style="cursor:pointer">Import CSV<input type="file" id="importCsv" accept=".csv" style="display:none"></label>
          <button class="button danger" id="resetJournalBtn">Reset Journal</button>
        </div>
      </div>

      <div class="grid cols-4">
        <div class="kpi"><div class="label">Net P&L ($)</div><div class="value" id="statPnl">–</div></div>
        <div class="kpi"><div class="label">Cumulative R</div><div class="value" id="statR">–</div></div>
        <div class="kpi"><div class="label">Win rate</div><div class="value" id="statWin">–</div></div>
        <div class="kpi"><div class="label">Expectancy / trade (R)</div><div class="value" id="statExp">–</div></div>
      </div>

      <div class="grid cols-2">
        <div class="card" style="padding:12px">
          <div class="section-title" style="margin:0 0 6px 0">Equity Curve</div>
          <div class="chart-container">
            <canvas id="equityCanvas" width="600" height="220"></canvas>
          </div>
        </div>
        <div class="card" style="padding:12px">
          <div class="section-title" style="margin:0 0 6px 0">Distribution</div>
          <div class="chart-container">
            <canvas id="histCanvas" width="600" height="220"></canvas>
          </div>
        </div>
      </div>

      <div class="grid cols-2">
        <div>
          <label>Progress vs monthly target</label>
          <div class="progress"><div id="progressBar"></div></div>
          <div class="hint" id="progressHint">0% of $ 0 target</div>
        </div>
        <div class="row" style="gap:10px; flex-wrap:wrap">
          <div>Entries: <b id="statCount">0</b></div>
          <div>Average P&L / trade: <b id="statAvg">$ 0</b></div>
        </div>
      </div>

      <!-- Per-strategy stats -->
      <div class="card" style="padding:12px">
        <div class="section-title" style="margin:0 0 6px 0">Per‑Strategy Stats</div>
        <div style="overflow:auto">
          <table id="stratTable">
            <thead>
              <tr>
                <th>Tag / Strategy</th><th>Trades</th><th>Win %</th><th>Avg R</th><th>Net P&L ($)</th><th>Expectancy (R)</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
        <div class="hint">Tip: use comma‑separated tags (e.g., "breakout, ORB").</div>
      </div>

             <div style="overflow:auto; margin-top:10px">
         <table id="journalTable">
           <thead>
             <tr>
               <th>Date</th><th>Segment</th><th>Symbol</th><th>Side</th><th>Entry</th><th>Stop</th><th>Exit</th><th>Qty/Lots</th><th>R</th><th>P&L ($)</th><th>Tags</th><th>Notes</th><th>Actions</th>
             </tr>
           </thead>
           <tbody></tbody>
         </table>
       </div>

      <div class="hint">Disclaimer: Taxes/fees/lot sizes can change. This tool is educational and not financial advice.</div>
    </div>

    <div style="color:var(--muted); font-size:12px">© <span id="year"></span> thetradersescape. Risk Management Edition.</div>
  </div>

  <script>
    // Constants and utilities
    const F2 = new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 });
    const $ = id => document.getElementById(id);

    // Storage keys
    const KEY_SETTINGS = 'tte_risk_settings', KEY_JOURNAL = 'tte_trades_journal', KEY_TARGET = 'tte_monthly_target';

    // Tick rounding for precision
    const TICK = 0.01;
    const roundTick = (x) => Math.round(x / TICK) * TICK;

    // Presets for different instruments
    const PRESETS = {
      "SPY": { lot: 100, point: 1 },
      "QQQ": { lot: 100, point: 1 },
      "IWM": { lot: 100, point: 1 },
      "ES": { lot: 1, point: 50 },
      "NQ": { lot: 1, point: 20 }
    };

    // Settings management
    function saveSettings() {
      const payload = {
        equity: +$('equity').value || 0,
        riskPct: +$('riskPct').value || 0,
        riskFixed: $('riskFixed').value || '',
        dailyLossPct: +$('dailyLossPct').value || 0,
        maxOpenRiskPct: +$('maxOpenRiskPct').value || 0
      };
      localStorage.setItem(KEY_SETTINGS, JSON.stringify(payload));
    }

    function loadSettings() {
      try {
        const s = JSON.parse(localStorage.getItem(KEY_SETTINGS) || '{}');
        $('equity').value = s.equity ?? 100000;
        $('riskPct').value = s.riskPct ?? 1;
        $('riskFixed').value = s.riskFixed ?? '';
        $('dailyLossPct').value = s.dailyLossPct ?? 3;
        $('maxOpenRiskPct').value = s.maxOpenRiskPct ?? 2;
      } catch (e) {}
    }

    // Journal management
    function saveJournal(t) {
      localStorage.setItem(KEY_JOURNAL, JSON.stringify(t));
    }

    function loadJournal() {
      try {
        return JSON.parse(localStorage.getItem(KEY_JOURNAL) || '[]');
      } catch (e) {
        return [];
      }
    }

    function saveTarget(v) {
      localStorage.setItem(KEY_TARGET, String(v || 0));
    }

    function loadTarget() {
      return +(localStorage.getItem(KEY_TARGET) || 0);
    }

    // Risk budget calculation
    function riskBudget() {
      const eq = +$('equity').value || 0;
      const fixed = +$('riskFixed').value || 0;
      const pct = +$('riskPct').value || 0;
      const b = fixed > 0 ? fixed : eq * (pct / 100);
      $('budgetBadge').textContent = 'Risk budget: $' + F2.format(Math.round(b));
      return Math.max(b, 0);
    }

    // Rounding rules
    function roundByRule(x, rule) {
      if (rule === 'up') return Math.ceil(x);
      if (rule === 'nearest') return Math.round(x);
      return Math.floor(x);
    }

    // Preset handling
    function onPreset() {
      const p = PRESETS[$('preset').value];
      if (p) {
        $('symbol').value = $('preset').value;
        $('lotSize').value = p.lot;
        $('pointValue').value = p.point;
      }
    }

    // ATR-based stop loss
    function maybeApplyATR(entry, side) {
      if (!$('useAtr').checked) return +$('stop').value || 0;
      const atr = +$('atr').value || 0;
      const mult = +$('atrMult').value || 0;
      if (!(atr > 0 && mult > 0)) return +$('stop').value || 0;
      const sd = atr * mult;
      let stop = side === 'Long' ? (entry - sd) : (entry + sd);
      stop = roundTick(stop);
      $('stop').value = stop.toFixed(2);
      return stop;
    }

    // Position size calculator
    function computeSizer() {
      const segment = $('segment').value;
      const side = $('side').value;
      let entry = +$('entry').value || 0;
      let stop = +$('stop').value || 0;
      
      entry = roundTick(entry);
      $('entry').value = entry.toFixed(2);
      stop = maybeApplyATR(entry, side) || roundTick(stop);
      $('stop').value = stop.toFixed(2);
      
      let target = $('target').value ? (+$('target').value || 0) : null;
      if (target) {
        target = roundTick(target);
        $('target').value = target.toFixed(2);
      }
      
      const rMultiple = $('rMultiple').value ? (+$('rMultiple').value || 0) : null;
      const lotSize = +$('lotSize').value || 1;
      const pointValue = +$('pointValue').value || 1;
      const feesExtra = +$('fees').value || 0;
      const roundRule = $('roundRule').value;

      if (!target && rMultiple) {
        if (side === 'Long') {
          target = entry + rMultiple * (entry - stop);
        } else {
          target = entry - rMultiple * (stop - entry);
        }
        target = roundTick(target);
        $('target').value = target.toFixed(2);
      }

      if (entry <= 0 || stop <= 0) {
        $('kStop').textContent = '–';
        $('kQty').textContent = '–';
        $('kRisk').textContent = '–';
        $('kReward').textContent = '–';
        return null;
      }

      const sd = Math.abs(entry - stop);
      if (sd === 0) {
        $('kStop').textContent = '0';
        $('kQty').textContent = '–';
        $('kRisk').textContent = '–';
        $('kReward').textContent = '–';
        return null;
      }

      const budget = riskBudget();
      let qty = 0, riskUsed = 0, exposure = 0, reward = 0;

      if (segment === 'Equity Cash') {
        const riskPerUnit = sd;
        qty = roundByRule((budget) / riskPerUnit, roundRule);
        qty = Math.max(qty, 0);
        riskUsed = qty * riskPerUnit;
        exposure = qty * entry;
        if (target) {
          reward = qty * Math.abs(target - entry);
        }
        $('kQty').textContent = qty + ' sh';
      } else {
        const riskPerLot = sd * lotSize * pointValue;
        qty = roundByRule((budget) / riskPerLot, roundRule);
        qty = Math.max(qty, 0);
        riskUsed = qty * riskPerLot;
        exposure = qty * lotSize * entry * pointValue;
        if (target) {
          reward = qty * Math.abs(target - entry) * lotSize * pointValue;
        }
        $('kQty').textContent = qty + ' lot(s)';
      }

      riskUsed += feesExtra;
      if (target) {
        reward = Math.max(0, reward - feesExtra);
      }

      $('kStop').textContent = sd.toFixed(2) + ' pts';
      $('kRisk').textContent = '$' + F2.format(Math.round(riskUsed));
      $('kReward').textContent = target ? ('$' + F2.format(Math.round(reward))) : '–';

      const guard = [];
      if (qty <= 0) guard.push('Position size rounds to 0 under current budget/fees.');
      $('guardMsg').textContent = guard.join(' ');

      return {
        segment, side, entry, stop, target, rMultiple, lotSize, pointValue,
        qty, sd, riskUsed, exposure, feesExtra,
        tags: $('sizerTags').value.trim(),
        notes: $('sizerNotes').value.trim()
      };
    }

    // Chart drawing functions
    function drawEquityCurve(id, rows) {
      const c = $(id);
      const ctx = c.getContext('2d');
      ctx.clearRect(0, 0, c.width, c.height);
      
      const cum = [];
      let s = 0;
      rows.forEach(r => {
        s += (+r.pnl || 0);
        cum.push(s);
      });
      
      if (cum.length === 0) {
        ctx.fillStyle = '#94a3b8';
        ctx.fillText('No data', 10, 20);
        return;
      }
      
      const minV = Math.min(...cum);
      const maxV = Math.max(...cum);
      const pad = 30;
      const W = c.width;
      const H = c.height;
      
      // Grid
      ctx.strokeStyle = 'rgba(255,255,255,0.1)';
      ctx.beginPath();
      ctx.moveTo(pad, pad);
      ctx.lineTo(pad, H - pad);
      ctx.lineTo(W - pad, H - pad);
      ctx.stroke();
      
      // Scale functions
      const toX = i => pad + (W - 2 * pad) * (i / (cum.length - 1 || 1));
      const toY = v => H - pad - (H - 2 * pad) * ((v - minV) / ((maxV - minV) || 1));
      
      // Equity curve
      ctx.strokeStyle = '#3b82f6';
      ctx.lineWidth = 2;
      ctx.beginPath();
      ctx.moveTo(toX(0), toY(cum[0]));
      for (let i = 1; i < cum.length; i++) {
        ctx.lineTo(toX(i), toY(cum[i]));
      }
      ctx.stroke();
    }

    function drawHistogram(id, arr) {
      const c = $(id);
      const ctx = c.getContext('2d');
      ctx.clearRect(0, 0, c.width, c.height);
      
      if (arr.length === 0) {
        ctx.fillStyle = '#94a3b8';
        ctx.fillText('No data', 10, 20);
        return;
      }
      
      const bins = 11;
      const minB = -5;
      const maxB = 5;
      const step = (maxB - minB) / bins;
      const counts = new Array(bins).fill(0);
      
      arr.forEach(v => {
        let idx = Math.floor((v - minB) / step);
        idx = Math.max(0, Math.min(bins - 1, idx));
        counts[idx]++;
      });
      
      const pad = 30;
      const W = c.width;
      const H = c.height;
      const barW = (W - 2 * pad) / bins;
      const maxC = Math.max(...counts) || 1;
      
      // Grid
      ctx.strokeStyle = 'rgba(255,255,255,0.1)';
      ctx.beginPath();
      ctx.moveTo(pad, pad);
      ctx.lineTo(pad, H - pad);
      ctx.lineTo(W - pad, H - pad);
      ctx.stroke();
      
      // Bars
      for (let i = 0; i < bins; i++) {
        const h = (H - 2 * pad) * (counts[i] / maxC);
        const x = pad + i * barW;
        const y = H - pad - h;
        ctx.fillStyle = '#1d4ed8';
        ctx.fillRect(x + 2, y, barW - 4, h);
      }
    }

         // Journal rendering
     function renderJournal() {
       const rows = loadJournal();
       const tbody = $('journalTable').querySelector('tbody');
       tbody.innerHTML = '';
       
       rows.forEach((t, i) => {
         const tr = document.createElement('tr');
         tr.innerHTML = `
           <td>${t.date || ''}</td>
           <td>${t.segment || ''}</td>
           <td>${t.symbol || ''}</td>
           <td>${t.side || ''}</td>
           <td>${(+t.entry || 0).toFixed(2)}</td>
           <td>${(+t.stop || 0).toFixed(2)}</td>
           <td>${(+t.exit || 0).toFixed(2)}</td>
           <td>${t.qty || 0} ${(t.segment === 'Equity Cash') ? 'sh' : 'lot(s)'}</td>
           <td>${(t.r ?? 0).toFixed(2)}</td>
           <td>$${F2.format(t.pnl ?? 0)}</td>
           <td>${t.tags || ''}</td>
           <td>${t.notes || ''}</td>
           <td>
             <button class="button secondary" onclick="editJournal(${i})">Edit</button>
             <button class="button danger" onclick="delJournal(${i})">Delete</button>
           </td>`;
         tbody.appendChild(tr);
       });

       const pnl = rows.reduce((a, b) => a + (+b.pnl || 0), 0);
       const rsum = rows.reduce((a, b) => a + (+b.r || 0), 0);
       const wins = rows.filter(t => (+t.pnl || 0) > 0).length;
       const trades = rows.length;
       const winRate = trades ? (wins / trades * 100) : 0;
       const expectancyR = trades ? (rsum / trades) : 0;
       const avgPnL = trades ? Math.round(pnl / trades) : 0;

       $('statPnl').textContent = '$' + F2.format(pnl);
       $('statR').textContent = F2.format(rsum);
       $('statWin').textContent = F2.format(winRate) + ' %';
       $('statExp').textContent = F2.format(expectancyR);
       $('statCount').textContent = trades;
       $('statAvg').textContent = '$' + F2.format(avgPnL);

       drawEquityCurve('equityCanvas', rows);
       drawHistogram('histCanvas', rows.map(t => +t.r || 0));
       updateProgress();
     }

    // Journal management functions
    function addJournal() {
      const s = computeSizer();
      if (!s) return;
      
      const symbol = $('symbol').value.trim() || '—';
      const exit = prompt('Exit price?');
      if (exit === null) return;
      
      let exitPx = +exit;
      if (!(exitPx > 0)) return alert('Invalid exit price');
      
      exitPx = roundTick(exitPx);
      const dir = s.side === 'Long' ? 1 : -1;
      const riskPerUnit = Math.abs(s.entry - s.stop);
      const pnlPerUnit = (exitPx - s.entry) * dir;
      
      let qtyUnits = s.qty;
      let factor = 1;
      if (s.segment !== 'Equity Cash') {
        qtyUnits = s.qty * s.lotSize;
        factor = s.pointValue;
      }
      
      let pnl = qtyUnits * pnlPerUnit * factor;
      pnl -= s.feesExtra;
      
      const R = riskPerUnit > 0 ? (pnlPerUnit / riskPerUnit) : 0;
      
      const entry = {
        date: new Date().toISOString().slice(0, 10),
        segment: s.segment,
        symbol: symbol,
        side: s.side,
        entry: s.entry,
        stop: s.stop,
        exit: exitPx,
        qty: s.qty,
        lotSize: s.lotSize || null,
        pointValue: s.pointValue || 1,
        r: +(R.toFixed(2)),
        pnl: Math.round(pnl),
        tags: s.tags || '',
        notes: s.notes || ''
      };
      
      const j = loadJournal();
      j.push(entry);
      saveJournal(j);
      renderJournal();
    }

    function editJournal(i) {
      const rows = loadJournal();
      const t = rows[i];
      if (!t) return;
      
      const nd = prompt('Date (YYYY-MM-DD)', t.date) || t.date;
      const nx = +prompt('Exit price', t.exit) || t.exit;
      const np = +prompt('P&L ($)', t.pnl) || t.pnl;
      const nr = +prompt('R multiple', t.r) || t.r;
      const ntags = prompt('Tags', t.tags || '') || t.tags || '';
      const nnotes = prompt('Notes', t.notes || '') || t.notes || '';
      
      rows[i] = { ...t, date: nd, exit: nx, pnl: np, r: nr, tags: ntags, notes: nnotes };
      saveJournal(rows);
      renderJournal();
    }

    function delJournal(i) {
      const rows = loadJournal();
      const t = rows[i];
      if (!t) return;
      
      if (!confirm('Delete this trade?')) return;
      
      rows.splice(i, 1);
      saveJournal(rows);
      renderJournal();
    }

         // Export/Import functions
     function exportCsv() {
       const rows = loadJournal();
       const header = ['Date', 'Segment', 'Symbol', 'Side', 'Entry', 'Stop', 'Exit', 'Qty/Lots', 'LotSize', 'PointValue', 'R', 'PnL', 'Tags', 'Notes'];
       const lines = [header.join(',')];
       
       rows.forEach(t => {
         const vals = [
           t.date, t.segment, t.symbol, t.side, t.entry, t.stop, t.exit, t.qty,
           (t.lotSize || ''), (t.pointValue || ''), t.r, t.pnl,
           (t.tags || ''), (t.notes || '').replace(/\n/g, ' ')
         ];
         lines.push(vals.map(v => `"` + String(v).replace(/"/g, '""') + `"`).join(','));
       });
       
       const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
       const a = document.createElement('a');
       const today = new Date().toISOString().slice(0, 10);
       a.href = URL.createObjectURL(blob);
       a.download = `risk_management_journal_${today}.csv`;
       a.click();
       URL.revokeObjectURL(a.href);
     }

     // Additional toolbar functions
     function printReport() {
       window.print();
     }

     function quickBackup() {
       exportCsv();
     }

     function importCsv(event) {
       const file = event.target.files[0];
       if (!file) return;
       
       const reader = new FileReader();
       reader.onload = function(e) {
         const text = e.target.result;
         const lines = text.split('\n');
         const header = lines[0].split(',').map(h => h.replace(/"/g, ''));
         const rows = [];
         
         for (let i = 1; i < lines.length; i++) {
           if (!lines[i].trim()) continue;
           const values = lines[i].split(',').map(v => v.replace(/"/g, ''));
           const row = {};
           header.forEach((h, j) => {
             row[h] = values[j] || '';
           });
           rows.push(row);
         }
         
         saveJournal(rows);
         renderJournal();
       };
       reader.readAsText(file);
     }

     function resetJournal() {
       if (confirm('Are you sure you want to reset all journal data?')) {
         localStorage.removeItem(KEY_JOURNAL);
         renderJournal();
       }
     }

     // Filter functions
     function filterJournal() {
       const monthFilter = $('monthFilter').value;
       const symFilter = $('symFilter').value.toLowerCase();
       const tagFilter = $('tagFilter').value.toLowerCase();
       const searchBox = $('searchBox').value.toLowerCase();
       
       const rows = loadJournal();
       const tbody = $('journalTable').querySelector('tbody');
       tbody.innerHTML = '';
       
       rows.forEach((t, i) => {
         // Apply filters
         if (monthFilter && !t.date.startsWith(monthFilter)) return;
         if (symFilter && !t.symbol.toLowerCase().includes(symFilter)) return;
         if (tagFilter && !t.tags.toLowerCase().includes(tagFilter)) return;
         if (searchBox && !t.notes.toLowerCase().includes(searchBox)) return;
         
         const tr = document.createElement('tr');
         tr.innerHTML = `
           <td>${t.date || ''}</td>
           <td>${t.segment || ''}</td>
           <td>${t.symbol || ''}</td>
           <td>${t.side || ''}</td>
           <td>${(+t.entry || 0).toFixed(2)}</td>
           <td>${(+t.stop || 0).toFixed(2)}</td>
           <td>${(+t.exit || 0).toFixed(2)}</td>
           <td>${t.qty || 0} ${(t.segment === 'Equity Cash') ? 'sh' : 'lot(s)'}</td>
           <td>${(t.r ?? 0).toFixed(2)}</td>
           <td>$${F2.format(t.pnl ?? 0)}</td>
           <td>${t.tags || ''}</td>
           <td>${t.notes || ''}</td>
           <td>
             <button class="button secondary" onclick="editJournal(${i})">Edit</button>
             <button class="button danger" onclick="delJournal(${i})">Delete</button>
           </td>`;
         tbody.appendChild(tr);
       });
     }

     // Progress tracking
     function updateProgress() {
       const target = +$('monthlyTarget').value || 0;
       const rows = loadJournal();
       const currentMonth = new Date().toISOString().slice(0, 7);
       const monthPnL = rows
         .filter(t => t.date.startsWith(currentMonth))
         .reduce((sum, t) => sum + (+t.pnl || 0), 0);
       
       const progress = target > 0 ? Math.min(100, (monthPnL / target) * 100) : 0;
       $('progressBar').style.width = progress + '%';
              $('progressHint').textContent = `${progress.toFixed(1)}% of $${target.toLocaleString()} target`;
     }

     // Clear sizer function
     function clearSizer() {
       ['symbol', 'entry', 'stop', 'target', 'rMultiple', 'fees', 'atr', 'atrMult', 'sizerTags', 'sizerNotes'].forEach(id => {
         $(id).value = '';
       });
       $('entry').value = '100';
       $('stop').value = '95';
       $('useAtr').checked = false;
       computeSizer();
     }

     // Clear journal function
     function clearJournal() {
       if (confirm('Are you sure you want to clear all trade data?')) {
         localStorage.removeItem(KEY_JOURNAL);
         renderJournal();
       }
     }

     // Add demo journal entries
     function addDemoData() {
       const demoData = [
         {
           date: '2024-01-15',
           segment: 'Equity Cash',
           symbol: 'AAPL',
           side: 'Long',
           entry: 185.50,
           stop: 182.00,
           exit: 190.25,
           qty: 200,
           r: 1.83,
           pnl: 950,
           tags: 'swing, momentum',
           notes: 'Breakout trade on earnings'
         },
         {
           date: '2024-01-18',
           segment: 'Equity Cash',
           symbol: 'TSLA',
           side: 'Short',
           entry: 215.00,
           stop: 218.50,
           exit: 208.75,
           qty: 150,
           r: 2.08,
           pnl: 937,
           tags: 'short, reversal',
           notes: 'Failed breakout short'
         },
         {
           date: '2024-01-22',
           segment: 'Futures',
           symbol: 'ES',
           side: 'Long',
           entry: 4850,
           stop: 4830,
           exit: 4875,
           qty: 2,
           r: 1.25,
           pnl: 2500,
           tags: 'futures, breakout',
           notes: 'ES breakout trade'
         },
         {
           date: '2024-01-25',
           segment: 'Equity Cash',
           symbol: 'NVDA',
           side: 'Long',
           entry: 485.00,
           stop: 475.00,
           exit: 495.50,
           qty: 100,
           r: 2.10,
           pnl: 1050,
           tags: 'tech, momentum',
           notes: 'AI momentum trade'
         },
         {
           date: '2024-01-28',
           segment: 'Equity Cash',
           symbol: 'SPY',
           side: 'Long',
           entry: 485.50,
           stop: 482.00,
           exit: 480.25,
           qty: 300,
           r: -1.75,
           pnl: -1575,
           tags: 'index, swing',
           notes: 'Failed support test'
         }
       ];
       
       const existingData = loadJournal();
       if (existingData.length === 0) {
         saveJournal(demoData);
         renderJournal();
       }
     }

     // Event listeners
     ['equity', 'riskPct', 'riskFixed', 'dailyLossPct', 'maxOpenRiskPct'].forEach(id => {
       $(id).addEventListener('input', () => {
         saveSettings();
         riskBudget();
         renderJournal();
       });
     });

     ['segment', 'side', 'entry', 'stop', 'target', 'rMultiple', 'lotSize', 'pointValue', 'fees', 'roundRule', 'atr', 'atrMult', 'sizerTags', 'sizerNotes'].forEach(id => {
       $(id).addEventListener('input', () => {
         computeSizer();
       });
     });

     $('useAtr').addEventListener('change', () => {
       computeSizer();
     });

     $('preset').addEventListener('change', onPreset);

     // Toolbar button event listeners
     $('printReportBtn').addEventListener('click', printReport);
     $('exportCsvBtn').addEventListener('click', exportCsv);
     $('quickBackupBtn').addEventListener('click', quickBackup);
     $('resetJournalBtn').addEventListener('click', resetJournal);
     $('importCsv').addEventListener('change', importCsv);

     // Filter event listeners
     ['monthFilter', 'symFilter', 'tagFilter', 'searchBox', 'monthlyTarget'].forEach(id => {
       $(id).addEventListener('input', filterJournal);
     });

     // Initialize
     document.addEventListener('DOMContentLoaded', function() {
       loadSettings();
       riskBudget();
       computeSizer();
       renderJournal();
       addDemoData();
       $('year').textContent = new Date().getFullYear();
       
       // Populate month filter
       const monthFilter = $('monthFilter');
       monthFilter.innerHTML = '<option value="">All months</option>';
       const months = ['2024-01', '2024-02', '2024-03', '2024-04', '2024-05', '2024-06'];
       months.forEach(month => {
         const option = document.createElement('option');
         option.value = month;
         option.textContent = new Date(month + '-01').toLocaleDateString('en-US', { year: 'numeric', month: 'long' });
         monthFilter.appendChild(option);
       });
     });

     // Cache Busting Script
     window.addEventListener('pageshow', function(event) {
       if (event.persisted) {
         window.location.reload();
       }
     });
     
     // Add cache-busting parameter to all internal links
     document.addEventListener('DOMContentLoaded', function() {
       const links = document.querySelectorAll('a[href^="/"], a[href^="./"], a[href^="../"]');
       links.forEach(link => {
         if (!link.href.includes('?v=')) {
           link.href += (link.href.includes('?') ? '&' : '?') + 'v=' + Date.now();
         }
       });
     });
   </script>
 </body>
 </html>