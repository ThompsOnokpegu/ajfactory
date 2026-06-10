<x-layouts.taab
    title="AI Automation ROI Calculator — TAAB"
    description="Fill in your real numbers. Get an honest projection of what AI automation costs and when it pays.">

@push('styles')
<style>
  .container { max-width: 780px; margin: 0 auto; padding: 3rem 1.5rem 5rem; position: relative; z-index: 1; }

  .header { margin-bottom: 2.5rem; }
  .badge { display: inline-block; font-family: 'Syne', sans-serif; font-size: 10px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; background: var(--accent); color: var(--bg); padding: 4px 12px; border-radius: 100px; margin-bottom: 1rem; }
  h1 { font-family: 'Syne', sans-serif; font-size: clamp(1.75rem, 4vw, 2.5rem); font-weight: 800; line-height: 1.15; letter-spacing: -0.02em; margin-bottom: 0.5rem; }
  .header-sub { font-size: 15px; color: var(--muted); font-weight: 300; }

  .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
  @media (max-width: 600px) { .grid { grid-template-columns: 1fr; } }

  .panel { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.5rem; }
  .panel-title { font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--muted); margin-bottom: 1.25rem; display: flex; align-items: center; gap: 8px; }
  .panel-title::after { content: ''; flex: 1; height: 1px; background: var(--border); }

  .field { margin-bottom: 1.25rem; }
  .field:last-child { margin-bottom: 0; }
  .field-label { font-size: 13px; font-weight: 500; color: var(--text); margin-bottom: 4px; display: flex; justify-content: space-between; align-items: baseline; }
  .field-hint { font-size: 11px; color: var(--muted); font-weight: 300; }

  .slider-wrap { position: relative; }
  input[type="range"] { -webkit-appearance: none; appearance: none; width: 100%; height: 4px; border-radius: 100px; background: var(--border-strong); outline: none; margin: 8px 0 4px; cursor: pointer; }
  input[type="range"]::-webkit-slider-thumb { -webkit-appearance: none; width: 18px; height: 18px; border-radius: 50%; background: var(--accent); cursor: pointer; border: 2px solid var(--surface); box-shadow: 0 1px 4px rgba(0,0,0,0.4); }
  input[type="range"]::-moz-range-thumb { width: 18px; height: 18px; border-radius: 50%; background: var(--accent); cursor: pointer; border: 2px solid var(--surface); }
  .slider-labels { display: flex; justify-content: space-between; font-size: 10px; color: var(--muted); margin-top: 2px; }

  .number-input-wrap { display: flex; align-items: center; border: 1px solid var(--border-strong); border-radius: var(--radius-sm); overflow: hidden; background: var(--bg); }
  .num-prefix { padding: 8px 12px; font-size: 13px; color: var(--muted); background: var(--surface2); border-right: 1px solid var(--border); white-space: nowrap; }
  .num-input { border: none; outline: none; padding: 8px 12px; font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 600; color: var(--text); width: 100%; background: transparent; -moz-appearance: textfield; }
  .num-input::-webkit-outer-spin-button, .num-input::-webkit-inner-spin-button { -webkit-appearance: none; }

  .toggle-group { display: flex; gap: 6px; flex-wrap: wrap; }
  .toggle-btn { font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 400; padding: 7px 14px; border-radius: var(--radius-sm); border: 1px solid var(--border-strong); background: transparent; color: var(--muted); cursor: pointer; transition: all 0.15s; }
  .toggle-btn:hover { border-color: var(--accent); color: var(--text); }
  .toggle-btn.active { background: var(--accent); color: var(--bg); border-color: var(--accent); }

  /* Gated results */
  .roi-results-wrap { position: relative; margin-top: 1.25rem; }
  .results-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
  @media (max-width: 600px) { .results-grid { grid-template-columns: 1fr; } }
  #roi-results.locked { filter: blur(8px); pointer-events: none; user-select: none; }
  .roi-gate-overlay { position: absolute; inset: 0; z-index: 5; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 14px; text-align: center; padding: 1.5rem; }
  .roi-gate-overlay.hidden { display: none; }
  .reveal-btn { font-family: 'Syne', sans-serif; font-weight: 700; font-size: 14px; background: var(--accent); color: var(--bg); border: none; padding: 14px 28px; border-radius: var(--radius-sm); cursor: pointer; transition: background 0.15s; }
  .reveal-btn:hover { background: #d4f474; }
  .roi-gate-copy { font-size: 13px; color: var(--muted); font-weight: 300; max-width: 320px; }

  .summary-panel { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.5rem; }
  .summary-title { font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--muted); margin-bottom: 1.25rem; }
  .summary-big { font-family: 'Syne', sans-serif; font-size: 2.4rem; font-weight: 800; letter-spacing: -0.03em; line-height: 1; margin-bottom: 4px; color: var(--accent); }
  .summary-label { font-size: 13px; color: var(--muted); font-weight: 300; margin-bottom: 1.5rem; }
  .summary-divider { height: 1px; background: var(--border); margin: 1rem 0; }
  .summary-row { display: flex; justify-content: space-between; align-items: baseline; font-size: 13px; margin-bottom: 8px; }
  .summary-row:last-child { margin-bottom: 0; }
  .summary-row-label { color: var(--muted); font-weight: 300; }
  .summary-row-val { font-family: 'Syne', sans-serif; font-weight: 600; font-size: 14px; color: var(--text); }

  .timeline-panel { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.5rem; grid-column: 1 / -1; }
  .timeline-months { display: flex; gap: 6px; margin-bottom: 1rem; }
  .month-col { flex: 1; }
  .month-bar-wrap { height: 80px; display: flex; align-items: flex-end; margin-bottom: 6px; }
  .month-bar { width: 100%; border-radius: 4px 4px 0 0; transition: height 0.5s cubic-bezier(0.4,0,0.2,1); min-height: 3px; }
  .month-label { font-family: 'Syne', sans-serif; font-size: 10px; font-weight: 600; color: var(--muted); text-align: center; }
  .month-amount { font-family: 'Syne', sans-serif; font-size: 10px; font-weight: 700; text-align: center; margin-bottom: 2px; min-height: 14px; }
  .legend-row { display: flex; gap: 20px; margin-top: .75rem; flex-wrap: wrap; }
  .legend-item { display: flex; align-items: center; gap: 6px; font-size: 11px; color: var(--muted); }
  .legend-dot { width: 8px; height: 8px; border-radius: 2px; }

  .insights { grid-column: 1 / -1; display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
  @media (max-width: 600px) { .insights { grid-template-columns: 1fr; } }
  .insight-card { border-radius: var(--radius-sm); padding: 14px; border: 1px solid; }
  .insight-card.green { background: var(--green-bg); border-color: var(--green-border); }
  .insight-card.amber { background: var(--amber-bg); border-color: var(--amber-border); }
  .insight-card.red   { background: var(--red-bg);   border-color: var(--red-border); }
  .insight-icon { font-size: 18px; margin-bottom: 6px; }
  .insight-title { font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 4px; }
  .insight-card.green .insight-title { color: var(--green); }
  .insight-card.amber .insight-title { color: var(--amber); }
  .insight-card.red   .insight-title { color: var(--red); }
  .insight-text { font-size: 12px; font-weight: 300; color: var(--text); line-height: 1.55; }

  .note { font-size: 11px; color: var(--muted); font-weight: 300; line-height: 1.6; padding: 12px 16px; background: var(--surface2); border-radius: var(--radius-sm); margin-top: .5rem; grid-column: 1 / -1; }
  .note strong { font-weight: 500; color: var(--text); }

  .roi-cta { margin-top: 2rem; text-align: center; }
  .roi-cta a { display: inline-block; font-family: 'Syne', sans-serif; font-weight: 700; font-size: 14px; background: var(--accent); color: var(--bg); text-decoration: none; padding: 14px 28px; border-radius: var(--radius-sm); }
</style>
@endpush

<div class="container">

  <div class="header">
    <div class="badge">TAAB · ROI Calculator</div>
    <h1>What does this actually cost — and when does it pay?</h1>
    <p class="header-sub">Fill in your real numbers. Get an honest projection. No sugar-coating.</p>
  </div>

  <div class="grid">
    <!-- Input: Costs -->
    <div class="panel">
      <div class="panel-title">Monthly costs</div>
      <div class="field">
        <div class="field-label"><span>Automation tool</span><span class="field-hint" id="tool-label">Free (n8n self-hosted)</span></div>
        <div class="toggle-group">
          <button class="toggle-btn active" onclick="setTool(this, 0, 'Free (n8n self-hosted)')">Free</button>
          <button class="toggle-btn" onclick="setTool(this, 20, 'n8n Cloud Starter ($20)')">n8n Cloud</button>
          <button class="toggle-btn" onclick="setTool(this, 9, 'Make Starter ($9)')">Make</button>
          <button class="toggle-btn" onclick="setTool(this, 49, 'Zapier Professional ($49)')">Zapier Pro</button>
        </div>
      </div>
      <div class="field">
        <div class="field-label"><span>AI API budget / month</span><span class="field-hint">OpenAI / Claude</span></div>
        <div class="slider-wrap">
          <input type="range" id="ai-api" min="0" max="100" step="5" value="20" oninput="update()">
          <div class="slider-labels"><span>$0</span><span id="ai-api-val">$20</span><span>$100</span></div>
        </div>
      </div>
      <div class="field">
        <div class="field-label"><span>Other tools</span><span class="field-hint">Airtable, Twilio, Cal.com…</span></div>
        <div class="slider-wrap">
          <input type="range" id="other-tools" min="0" max="100" step="5" value="15" oninput="update()">
          <div class="slider-labels"><span>$0</span><span id="other-tools-val">$15</span><span>$100</span></div>
        </div>
      </div>
      <div class="field">
        <div class="field-label"><span>One-time learning investment</span><span class="field-hint">Course, bootcamp, coaching</span></div>
        <div class="number-input-wrap"><div class="num-prefix">₦</div><input class="num-input" type="number" id="learning-cost" value="80000" oninput="update()"></div>
      </div>
    </div>

    <!-- Input: Income -->
    <div class="panel">
      <div class="panel-title">Revenue inputs</div>
      <div class="field">
        <div class="field-label"><span>Your income model</span></div>
        <div class="toggle-group">
          <button class="toggle-btn active" onclick="setModel(this,'freelancer')">Freelancer</button>
          <button class="toggle-btn" onclick="setModel(this,'agency')">Agency</button>
          <button class="toggle-btn" onclick="setModel(this,'course')">Course / community</button>
        </div>
      </div>
      <div class="field">
        <div class="field-label"><span>Target project / retainer fee</span></div>
        <div class="number-input-wrap"><div class="num-prefix">₦</div><input class="num-input" type="number" id="project-fee" value="250000" oninput="update()"></div>
      </div>
      <div class="field">
        <div class="field-label"><span>Estimated months to first client</span><span class="field-hint" id="months-hint">2 months</span></div>
        <div class="slider-wrap"><input type="range" id="months-to-client" min="1" max="9" step="1" value="2" oninput="update()"><div class="slider-labels"><span>1 mo</span><span>9 mo</span></div></div>
      </div>
      <div class="field">
        <div class="field-label"><span>Clients / projects per month (at month 6)</span><span class="field-hint" id="clients-hint">2 clients</span></div>
        <div class="slider-wrap"><input type="range" id="clients-pm" min="1" max="8" step="1" value="2" oninput="update()"><div class="slider-labels"><span>1</span><span>8</span></div></div>
      </div>
      <div class="field">
        <div class="field-label"><span>Hours per week available</span><span class="field-hint" id="hours-hint">8 hrs</span></div>
        <div class="slider-wrap"><input type="range" id="hrs-week" min="2" max="40" step="2" value="8" oninput="update()"><div class="slider-labels"><span>2 hrs</span><span>40 hrs</span></div></div>
      </div>
    </div>
  </div>

  <!-- GATED RESULTS -->
  <div class="roi-results-wrap">
    <div class="roi-gate-overlay" id="roi-gate-overlay">
      <button class="reveal-btn" onclick="roiReveal()">See your full projection →</button>
      <p class="roi-gate-copy">Unlock your break-even point, 12-month outlook, and personalised insights.</p>
    </div>

    <div id="roi-results" class="locked">
      <div class="results-grid">
        <!-- Summary -->
        <div class="summary-panel">
          <div class="summary-title">Break-even</div>
          <div class="summary-big" id="be-months">—</div>
          <div class="summary-label" id="be-label">months to recover all costs</div>
          <div class="summary-divider"></div>
          <div class="summary-row"><span class="summary-row-label">Monthly tool costs</span><span class="summary-row-val" id="s-tool-cost">—</span></div>
          <div class="summary-row"><span class="summary-row-label">Total upfront investment</span><span class="summary-row-val" id="s-upfront">—</span></div>
          <div class="summary-row"><span class="summary-row-label">First revenue month</span><span class="summary-row-val" id="s-first-rev">—</span></div>
        </div>

        <!-- 12-month -->
        <div class="panel">
          <div class="panel-title">12-month outlook</div>
          <div class="summary-row" style="margin-bottom:10px"><span class="summary-row-label">6-month cumulative revenue</span><span class="summary-row-val" id="s-6mo">—</span></div>
          <div class="summary-row" style="margin-bottom:10px"><span class="summary-row-label">12-month cumulative revenue</span><span class="summary-row-val" id="s-12mo">—</span></div>
          <div class="summary-row"><span class="summary-row-label">Effective hourly rate (mo. 6)</span><span class="summary-row-val" id="s-hourly">—</span></div>
        </div>

        <!-- Timeline -->
        <div class="timeline-panel">
          <div class="panel-title">Monthly revenue vs costs — first 12 months</div>
          <div class="timeline-months" id="timeline"></div>
          <div class="legend-row">
            <div class="legend-item"><div class="legend-dot" style="background:#8fe07a"></div>Revenue</div>
            <div class="legend-item"><div class="legend-dot" style="background:#3a3a42"></div>Tool costs</div>
          </div>
        </div>

        <!-- Insights -->
        <div class="insights" id="insights">
          <div class="insight-card green"><div class="insight-icon">💡</div><div class="insight-title">Efficiency</div><div class="insight-text" id="insight-eff">—</div></div>
          <div class="insight-card amber"><div class="insight-icon">⚠️</div><div class="insight-title">Watch out</div><div class="insight-text" id="insight-warn">—</div></div>
          <div class="insight-card red"><div class="insight-icon">🔑</div><div class="insight-title">Key lever</div><div class="insight-text" id="insight-lever">—</div></div>
        </div>

        <div class="note">
          <strong>Assumptions:</strong> Revenue grows linearly from first-client month to month 6, then holds steady. Costs are constant. Exchange rate used: $1 = ₦{{ number_format((int) config('taab.fx_rate')) }}. Tool costs converted from USD. These are estimates — real results depend on niche, effort, and market conditions. This calculator does not account for taxes, infrastructure one-offs, or client acquisition costs beyond your time.
        </div>
      </div>
    </div>
  </div>

  <div class="roi-cta">
    <a href="{{ config('taab.accelerator_url') }}">Ready to build it for real? Explore the Accelerator →</a>
  </div>

</div>

@push('scripts')
<script>
const RATE = {{ (int) config('taab.fx_rate') }};
let toolCost = 0;
let model = 'freelancer';

const fmt = (n) => '₦' + Math.round(n).toLocaleString();
const fmtUSD = (n) => '$' + Math.round(n);

function roiReveal() {
  taabRequireLead('roi', () => {
    document.getElementById('roi-results').classList.remove('locked');
    document.getElementById('roi-gate-overlay').classList.add('hidden');
    update();
  });
}

function setTool(el, cost, label) {
  document.querySelectorAll('.toggle-group')[0].querySelectorAll('.toggle-btn').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
  toolCost = cost;
  document.getElementById('tool-label').textContent = label;
  update();
}

function setModel(el, m) {
  document.querySelectorAll('.toggle-group')[1].querySelectorAll('.toggle-btn').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
  model = m;
  update();
}

function update() {
  const aiAPI = +document.getElementById('ai-api').value;
  const otherTools = +document.getElementById('other-tools').value;
  const monthlyToolUSD = toolCost + aiAPI + otherTools;
  const monthlyToolNGN = monthlyToolUSD * RATE;

  const learningCostNGN = +document.getElementById('learning-cost').value || 0;
  const projectFeeNGN = +document.getElementById('project-fee').value || 0;
  const monthsToClient = +document.getElementById('months-to-client').value;
  const clientsPM = +document.getElementById('clients-pm').value;
  const hrsWeek = +document.getElementById('hrs-week').value;

  document.getElementById('ai-api-val').textContent = fmtUSD(aiAPI);
  document.getElementById('other-tools-val').textContent = fmtUSD(otherTools);
  document.getElementById('months-hint').textContent = monthsToClient + ' month' + (monthsToClient !== 1 ? 's' : '');
  document.getElementById('clients-hint').textContent = clientsPM + ' client' + (clientsPM !== 1 ? 's' : '');
  document.getElementById('hours-hint').textContent = hrsWeek + ' hrs';

  const monthlyRevenue = [];
  for (let m = 1; m <= 12; m++) {
    if (m <= monthsToClient) { monthlyRevenue.push(0); }
    else {
      const rampMonths = Math.max(1, 6 - monthsToClient);
      const elapsed = m - monthsToClient;
      const rampFactor = Math.min(1, elapsed / rampMonths);
      monthlyRevenue.push(projectFeeNGN * clientsPM * rampFactor);
    }
  }

  const totalRev6 = monthlyRevenue.slice(0, 6).reduce((a, b) => a + b, 0);
  const totalRev12 = monthlyRevenue.reduce((a, b) => a + b, 0);

  let cumRev = 0;
  let beMonth = null;
  const totalUpfront = learningCostNGN;
  for (let m = 0; m < 12; m++) {
    cumRev += monthlyRevenue[m];
    const cumCosts = totalUpfront + monthlyToolNGN * (m + 1);
    if (cumRev >= cumCosts && beMonth === null) beMonth = m + 1;
  }

  document.getElementById('be-months').textContent = beMonth ? 'Month ' + beMonth : '12+';
  document.getElementById('be-label').textContent = beMonth ? 'break-even point — costs fully recovered' : 'break-even beyond month 12 at these inputs';
  document.getElementById('s-tool-cost').textContent = fmtUSD(monthlyToolUSD) + '/mo (' + fmt(monthlyToolNGN) + ')';
  document.getElementById('s-upfront').textContent = fmt(learningCostNGN);
  document.getElementById('s-first-rev').textContent = 'Month ' + (monthsToClient + 1);
  document.getElementById('s-6mo').textContent = fmt(totalRev6);
  document.getElementById('s-12mo').textContent = fmt(totalRev12);

  const mo6Rev = monthlyRevenue[5];
  const hrsMonth = hrsWeek * 4;
  const hourlyNGN = hrsMonth > 0 ? mo6Rev / hrsMonth : 0;
  document.getElementById('s-hourly').textContent = hourlyNGN > 0 ? fmt(hourlyNGN) + '/hr' : '—';

  const maxRev = Math.max(...monthlyRevenue, monthlyToolNGN, 1);
  const chartH = 80;
  const timeline = document.getElementById('timeline');
  timeline.innerHTML = '';
  for (let m = 0; m < 12; m++) {
    const rev = monthlyRevenue[m];
    const revH = Math.round((rev / maxRev) * chartH);
    const costH = Math.round((monthlyToolNGN / maxRev) * chartH);
    const isBeMonth = beMonth === m + 1;
    const col = document.createElement('div');
    col.className = 'month-col';
    const amountStr = rev > 0 ? (rev >= 1000000 ? '₦' + (rev/1000000).toFixed(1) + 'M' : '₦' + Math.round(rev/1000) + 'k') : '';
    col.innerHTML = `
      <div class="month-amount" style="color:${rev > 0 ? '#8fe07a' : 'transparent'};font-size:9px">${amountStr}</div>
      <div class="month-bar-wrap" style="position:relative">
        <div style="position:absolute;bottom:0;left:0;right:0;display:flex;gap:2px;align-items:flex-end">
          <div class="month-bar" style="flex:1;height:${revH}px;background:${isBeMonth ? '#c8f064' : '#8fe07a'};"></div>
          <div class="month-bar" style="flex:1;height:${costH}px;background:#3a3a42;"></div>
        </div>
      </div>
      <div class="month-label">${['J','F','M','A','M','J','J','A','S','O','N','D'][m]}</div>
    `;
    timeline.appendChild(col);
  }

  const efficiencyScore = hrsWeek >= 10 ? 'Your 10+ hrs/week pace is serious. Most successful freelancers in this space hit their first client in under 6 weeks at this intensity.' : hrsWeek >= 6 ? 'At ' + hrsWeek + ' hrs/week you can move meaningfully — expect 6–10 weeks to first client with focused effort.' : 'At ' + hrsWeek + ' hrs/week, progress will be slow. Even adding 2 more hours per week cuts your time-to-first-client significantly.';
  const warnMsg = monthsToClient <= 2 ? 'A ' + monthsToClient + '-month timeline to first client is optimistic. Most learners take 2–4 months. Build in buffer so financial pressure doesn\'t kill your momentum.' : monthlyToolNGN > 50000 ? 'Your tool costs are high relative to starting income. Use free tiers while learning — n8n self-hosted is free and production-ready.' : 'Your projection looks realistic. The main risk is scope creep on early projects — fix your fee before starting, not after.';
  const leverMsg = projectFeeNGN < 150000 ? 'Your project fee is on the low end. Automations that save a business 10+ hours/month should command ₦150k–₦500k minimum. Don\'t undervalue the work.' : clientsPM === 1 ? 'With one client/month, one churn event wipes your income. Focus on retainer agreements, not one-off projects, as soon as possible.' : 'Retainer agreements are your biggest lever — they turn unpredictable project income into stable monthly recurring revenue.';

  document.getElementById('insight-eff').textContent = efficiencyScore;
  document.getElementById('insight-warn').textContent = warnMsg;
  document.getElementById('insight-lever').textContent = leverMsg;
}

update();
</script>
@endpush
</x-layouts.taab>
