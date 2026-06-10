<x-layouts.taab
    title="AI Automation Readiness Scorecard — TAAB"
    description="10 questions across 5 dimensions. Get an honest picture of whether you're ready to go all in on AI automation.">

@push('styles')
<style>
  .container { max-width: 720px; margin: 0 auto; padding: 3rem 1.5rem 5rem; position: relative; z-index: 1; }

  .header { margin-bottom: 3rem; animation: fadeUp 0.6s ease both; }
  .badge {
    display: inline-flex; align-items: center; gap: 6px;
    font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 600;
    letter-spacing: 0.1em; text-transform: uppercase; color: var(--accent);
    background: var(--accent-dim); border: 1px solid rgba(200,240,100,0.2);
    padding: 5px 12px; border-radius: 100px; margin-bottom: 1.25rem;
  }
  .badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: var(--accent); animation: pulse 2s ease-in-out infinite; }
  h1 { font-family: 'Syne', sans-serif; font-size: clamp(2rem, 5vw, 3rem); font-weight: 800; line-height: 1.1; letter-spacing: -0.02em; margin-bottom: 0.75rem; }
  h1 span { color: var(--accent); }
  .header-sub { font-size: 15px; color: var(--muted); font-weight: 300; max-width: 480px; }

  .progress-wrap { margin-bottom: 2.5rem; animation: fadeUp 0.6s 0.1s ease both; }
  .progress-meta { display: flex; justify-content: space-between; font-size: 12px; color: var(--muted); margin-bottom: 8px; font-family: 'Syne', sans-serif; font-weight: 600; letter-spacing: 0.05em; }
  .progress-track { height: 3px; background: var(--surface2); border-radius: 100px; overflow: hidden; }
  .progress-fill { height: 100%; background: var(--accent); border-radius: 100px; transition: width 0.5s cubic-bezier(0.4,0,0.2,1); width: 0%; }

  .dim-tabs { display: flex; gap: 6px; margin-bottom: 2rem; flex-wrap: wrap; animation: fadeUp 0.6s 0.15s ease both; }
  .dim-tab { font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 600; letter-spacing: 0.06em; text-transform: uppercase; padding: 5px 12px; border-radius: 100px; border: 1px solid var(--border); color: var(--muted); background: transparent; transition: all 0.2s; cursor: default; }
  .dim-tab.active { background: var(--accent-dim); border-color: rgba(200,240,100,0.3); color: var(--accent); }
  .dim-tab.done { background: rgba(200,240,100,0.05); border-color: rgba(200,240,100,0.15); color: rgba(200,240,100,0.5); }

  .question-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 2rem; margin-bottom: 1rem; animation: fadeUp 0.4s ease both; }
  .q-number { font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--accent); margin-bottom: 0.75rem; }
  .q-text { font-size: 18px; font-weight: 500; line-height: 1.4; margin-bottom: 1.75rem; }

  .options { display: flex; flex-direction: column; gap: 10px; }
  .option { display: flex; align-items: flex-start; gap: 14px; padding: 14px 16px; border: 1px solid var(--border); border-radius: var(--radius-sm); cursor: pointer; transition: all 0.2s; background: transparent; text-align: left; width: 100%; font-family: inherit; }
  .option:hover { border-color: var(--border-hover); background: var(--surface2); }
  .option.selected { border-color: rgba(200,240,100,0.4); background: var(--accent-dim2); }
  .option-dot { width: 18px; height: 18px; min-width: 18px; border-radius: 50%; border: 1.5px solid var(--border-hover); display: flex; align-items: center; justify-content: center; margin-top: 1px; transition: all 0.2s; }
  .option.selected .option-dot { border-color: var(--accent); background: var(--accent); }
  .option.selected .option-dot::after { content: ''; width: 6px; height: 6px; border-radius: 50%; background: var(--bg); }
  .option-label { font-size: 14px; color: var(--text); line-height: 1.5; font-weight: 300; }
  .option.selected .option-label { color: var(--text); font-weight: 400; }
  .option-score { margin-left: auto; font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 700; color: var(--muted); min-width: 28px; text-align: right; padding-top: 2px; }
  .option.selected .option-score { color: var(--accent); }

  .nav-row { display: flex; gap: 10px; margin-top: 1.5rem; justify-content: flex-end; animation: fadeUp 0.4s 0.1s ease both; }
  .btn { font-family: 'Syne', sans-serif; font-size: 13px; font-weight: 700; letter-spacing: 0.04em; padding: 12px 24px; border-radius: var(--radius-sm); border: 1px solid var(--border); cursor: pointer; transition: all 0.2s; background: transparent; color: var(--muted); }
  .btn:hover { border-color: var(--border-hover); color: var(--text); }
  .btn-primary { background: var(--accent); color: var(--bg); border-color: var(--accent); }
  .btn-primary:hover { background: #d4f474; border-color: #d4f474; color: var(--bg); }
  .btn:disabled { opacity: 0.3; cursor: not-allowed; }

  #results { display: none; }
  .result-hero { text-align: center; padding: 3rem 2rem; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); margin-bottom: 2rem; animation: fadeUp 0.6s ease both; }
  .score-ring { width: 120px; height: 120px; margin: 0 auto 1.5rem; position: relative; }
  .score-ring svg { transform: rotate(-90deg); }
  .score-ring-track { fill: none; stroke: var(--surface2); stroke-width: 6; }
  .score-ring-fill { fill: none; stroke-width: 6; stroke-linecap: round; transition: stroke-dashoffset 1.2s cubic-bezier(0.4,0,0.2,1); }
  .score-number { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-family: 'Syne', sans-serif; font-size: 28px; font-weight: 800; }
  .verdict-label { font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; margin-bottom: 0.5rem; }
  .verdict-title { font-family: 'Syne', sans-serif; font-size: 1.6rem; font-weight: 800; margin-bottom: 0.75rem; line-height: 1.2; }
  .verdict-text { font-size: 14px; color: var(--muted); font-weight: 300; max-width: 460px; margin: 0 auto; line-height: 1.7; }

  .breakdown { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.5rem; margin-bottom: 1.5rem; animation: fadeUp 0.6s 0.1s ease both; }
  .breakdown-title { font-family: 'Syne', sans-serif; font-size: 12px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--muted); margin-bottom: 1.25rem; }
  .dim-row { display: flex; align-items: center; gap: 14px; margin-bottom: 14px; }
  .dim-row:last-child { margin-bottom: 0; }
  .dim-name { font-size: 13px; font-weight: 500; min-width: 90px; }
  .dim-bar-wrap { flex: 1; height: 6px; background: var(--surface2); border-radius: 100px; overflow: hidden; }
  .dim-bar { height: 100%; border-radius: 100px; transition: width 1s cubic-bezier(0.4,0,0.2,1); width: 0%; }
  .dim-pct { font-family: 'Syne', sans-serif; font-size: 12px; font-weight: 700; min-width: 36px; text-align: right; }

  .next-steps { animation: fadeUp 0.6s 0.2s ease both; }
  .next-steps-title { font-family: 'Syne', sans-serif; font-size: 12px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--muted); margin-bottom: 1rem; }
  .step-item { display: flex; gap: 14px; padding: 14px 16px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm); margin-bottom: 8px; }
  .step-num { font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 800; color: var(--accent); min-width: 20px; padding-top: 2px; }
  .step-text { font-size: 14px; font-weight: 300; line-height: 1.5; }
  .step-text strong { font-weight: 500; color: var(--text); }

  .result-cta { display: flex; flex-direction: column; gap: 10px; margin-top: 2rem; }
  .result-cta .btn { text-decoration: none; text-align: center; }

  @keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
  @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
</style>
@endpush

<div class="container">

  <div class="header">
    <div class="badge">TAAB · Readiness Scorecard</div>
    <h1>Are you ready to go <span>all in</span> on AI Automation?</h1>
    <p class="header-sub">10 questions across 5 dimensions. No fluff — just an honest picture of where you stand.</p>
  </div>

  <div id="quiz">
    <div class="progress-wrap">
      <div class="progress-meta">
        <span id="prog-label">Question 1 of 10</span>
        <span id="prog-pct">0%</span>
      </div>
      <div class="progress-track"><div class="progress-fill" id="prog-fill"></div></div>
    </div>

    <div class="dim-tabs" id="dim-tabs">
      <div class="dim-tab active" data-dim="0">Skills</div>
      <div class="dim-tab" data-dim="1">Time</div>
      <div class="dim-tab" data-dim="2">Capital</div>
      <div class="dim-tab" data-dim="3">Mindset</div>
      <div class="dim-tab" data-dim="4">Market</div>
    </div>

    <div class="question-card" id="question-card">
      <div class="q-number" id="q-number">Skills · Q1</div>
      <div class="q-text" id="q-text"></div>
      <div class="options" id="options-list"></div>
    </div>

    <div class="nav-row">
      <button class="btn" id="btn-back" onclick="navigate(-1)" disabled>Back</button>
      <button class="btn btn-primary" id="btn-next" onclick="navigate(1)" disabled>Next</button>
    </div>
  </div>

  <div id="results">
    <div class="result-hero" id="result-hero">
      <div class="score-ring">
        <svg width="120" height="120" viewBox="0 0 120 120">
          <circle class="score-ring-track" cx="60" cy="60" r="52"/>
          <circle class="score-ring-fill" id="ring-fill" cx="60" cy="60" r="52" stroke-dasharray="326.7" stroke-dashoffset="326.7"/>
        </svg>
        <div class="score-number" id="score-display">0</div>
      </div>
      <div class="verdict-label" id="verdict-label"></div>
      <div class="verdict-title" id="verdict-title"></div>
      <div class="verdict-text" id="verdict-text"></div>
    </div>

    <div class="breakdown">
      <div class="breakdown-title">Score by dimension</div>
      <div class="dim-row"><div class="dim-name">Skills</div><div class="dim-bar-wrap"><div class="dim-bar" id="bar-0" style="background:var(--accent)"></div></div><div class="dim-pct" id="pct-0" style="color:var(--accent)">0%</div></div>
      <div class="dim-row"><div class="dim-name">Time</div><div class="dim-bar-wrap"><div class="dim-bar" id="bar-1" style="background:#60c8f0"></div></div><div class="dim-pct" id="pct-1" style="color:#60c8f0">0%</div></div>
      <div class="dim-row"><div class="dim-name">Capital</div><div class="dim-bar-wrap"><div class="dim-bar" id="bar-2" style="background:#f5a623"></div></div><div class="dim-pct" id="pct-2" style="color:#f5a623">0%</div></div>
      <div class="dim-row"><div class="dim-name">Mindset</div><div class="dim-bar-wrap"><div class="dim-bar" id="bar-3" style="background:#c87df0"></div></div><div class="dim-pct" id="pct-3" style="color:#c87df0">0%</div></div>
      <div class="dim-row"><div class="dim-name">Market access</div><div class="dim-bar-wrap"><div class="dim-bar" id="bar-4" style="background:#f06488"></div></div><div class="dim-pct" id="pct-4" style="color:#f06488">0%</div></div>
    </div>

    <div class="next-steps">
      <div class="next-steps-title">Your next steps</div>
      <div id="next-steps-list"></div>
    </div>

    <div class="result-cta">
      <a href="{{ config('taab.accelerator_url') }}" class="btn btn-primary">Explore the AI Automation Accelerator →</a>
      <button class="btn" onclick="restart()">↩ Retake scorecard</button>
    </div>
  </div>

</div>

@push('scripts')
<script>
const questions = [
  { dim: 0, dimName: 'Skills', text: 'How comfortable are you working with web-based tools and APIs (connecting apps, configuring integrations)?', options: [
    { label: 'I have no idea what APIs are', score: 0 },
    { label: 'I understand the concept but have never actually built one', score: 3 },
    { label: 'I have connected a few tools using no-code platforms (Zapier, Make)', score: 7 },
    { label: 'I can work with APIs confidently — REST calls, JSON, auth flows', score: 10 },
  ]},
  { dim: 0, dimName: 'Skills', text: 'How would you describe your ability to think through and map out a business process before automating it?', options: [
    { label: 'I tend to jump in without mapping things out', score: 2 },
    { label: 'I can follow a process map if someone else creates it', score: 4 },
    { label: 'I can map out a simple process on my own', score: 7 },
    { label: 'I regularly document and optimise workflows professionally', score: 10 },
  ]},
  { dim: 1, dimName: 'Time', text: 'How many hours per week can you realistically dedicate to learning and building AI automation — consistently?', options: [
    { label: 'Less than 3 hours — I have very little free time right now', score: 2 },
    { label: '3–5 hours — possible but it would require discipline', score: 5 },
    { label: '6–10 hours — I have carved out deliberate time for this', score: 8 },
    { label: '10+ hours — this is a primary focus for me', score: 10 },
  ]},
  { dim: 1, dimName: 'Time', text: 'What is your expected timeline to land your first paid client or deliver your first automation project?', options: [
    { label: 'Within 2 weeks — I need income immediately', score: 1 },
    { label: '1–2 months — I need it to move quickly', score: 5 },
    { label: '3–6 months — I am thinking medium-term', score: 9 },
    { label: 'I have no immediate financial pressure — I can build properly first', score: 10 },
  ]},
  { dim: 2, dimName: 'Capital', text: 'How much can you invest in tools and learning per month right now (excluding your time)?', options: [
    { label: 'Under ₦10k ($6) — essentially zero budget', score: 1 },
    { label: '₦10k–₦30k ($6–$18) — very tight but workable', score: 4 },
    { label: '₦30k–₦80k ($18–$50) — I can manage the basics', score: 7 },
    { label: 'Over ₦80k ($50+) — I am ready to invest properly', score: 10 },
  ]},
  { dim: 2, dimName: 'Capital', text: 'Do you have financial runway — savings or income — to support yourself for at least 3 months while you build this?', options: [
    { label: 'No — I need this to generate money within a few weeks', score: 0 },
    { label: 'About 1 month of runway', score: 4 },
    { label: '2–3 months of runway', score: 7 },
    { label: '3+ months — I can build without financial panic', score: 10 },
  ]},
  { dim: 3, dimName: 'Mindset', text: 'How do you typically respond when you hit a wall — a tool that does not work, a concept you cannot grasp?', options: [
    { label: 'I get frustrated and often give up or pause for a long time', score: 1 },
    { label: 'I push through eventually but it affects my motivation significantly', score: 4 },
    { label: 'I treat it as a puzzle — I look it up, ask for help, keep going', score: 8 },
    { label: 'I genuinely enjoy debugging and figuring things out', score: 10 },
  ]},
  { dim: 3, dimName: 'Mindset', text: 'How consistent have you been with other self-directed learning efforts (courses, online skills, personal projects)?', options: [
    { label: 'I start things but rarely finish them', score: 1 },
    { label: 'I finish maybe 1 in 3 things I start', score: 4 },
    { label: 'I usually follow through if I stay accountable to something', score: 7 },
    { label: 'I have a strong track record of completing what I start', score: 10 },
  ]},
  { dim: 4, dimName: 'Market access', text: 'How well do you understand the types of businesses that need AI automation and what problems they want solved?', options: [
    { label: 'I have no idea who would buy this or why', score: 0 },
    { label: 'I have a vague sense — something like "businesses want to save time"', score: 3 },
    { label: 'I can name 2–3 specific business types and their pain points', score: 7 },
    { label: 'I have a clear niche, specific problems, and can speak their language', score: 10 },
  ]},
  { dim: 4, dimName: 'Market access', text: 'How strong is your existing network for finding early clients or collaborators in this space?', options: [
    { label: 'I know almost no one in business or tech circles', score: 0 },
    { label: 'I have some contacts but nothing specifically relevant', score: 3 },
    { label: 'I have a network with a few people who could be clients or referrers', score: 7 },
    { label: 'I have direct access to business owners or decision-makers already', score: 10 },
  ]},
];

const verdicts = {
  high: {
    label: '🟢 Ready to start',
    title: 'You are built for this.',
    text: 'Your score puts you firmly in the "ready" tier. You have the right foundation across skills, time, capital, mindset, and market access. The main risk for people like you is overthinking and under-executing. Pick a niche, build one automation, pitch one client. Start this week.',
    steps: [
      { text: '<strong>Enrol in the Accelerator</strong> — you are exactly who it is built for. Skip solo fumbling and get to results faster.' },
      { text: '<strong>Identify your first client target</strong> — someone in your network with an obvious repetitive workflow problem.' },
      { text: '<strong>Set a 30-day deadline</strong> to deliver your first working automation, even for free, to a real business.' },
    ]
  },
  mid: {
    label: '🟡 Almost ready',
    title: 'Close — but there are gaps to close first.',
    text: 'You have solid foundations in some areas but real gaps in others. Jumping in now risks frustration and wasted money. The good news: your gaps are solvable in 4–8 weeks with focused effort. Use your scorecard to see exactly which dimensions pulled you down.',
    steps: [
      { text: '<strong>Target your weakest dimension</strong> — that is your bottleneck. Fixing skills? Build a small project. Low capital? Start on free tiers.' },
      { text: '<strong>Join a structured cohort</strong> rather than going solo — accountability closes the "almost ready" gap faster than self-study.' },
      { text: '<strong>Do not invest heavily yet</strong> — spend the next 30 days building clarity, not tools.' },
    ]
  },
  low: {
    label: '🔴 Not yet',
    title: 'The foundation is not there — and that is okay.',
    text: 'Jumping into AI automation right now would likely lead to wasted money and early burnout. Your scorecard shows significant gaps across multiple dimensions that need addressing first. This is not a "never" — it is a "not now." Here is what to do instead.',
    steps: [
      { text: '<strong>Stabilise your finances first</strong> — AI automation is not a fast money solution. You need runway to learn properly.' },
      { text: '<strong>Start with free tools</strong> — explore n8n cloud (free tier), Make free plan, and watch YouTube walkthroughs before spending anything.' },
      { text: '<strong>Come back in 3 months</strong> — with stronger fundamentals. Your score will be very different.' },
    ]
  }
};

let current = 0;
let answers = new Array(10).fill(null);

function render() {
  const q = questions[current];
  document.getElementById('q-number').textContent = `${q.dimName} · Q${current + 1} of 10`;
  document.getElementById('q-text').textContent = q.text;

  const list = document.getElementById('options-list');
  list.innerHTML = '';
  q.options.forEach((opt, i) => {
    const btn = document.createElement('button');
    btn.className = 'option' + (answers[current] === i ? ' selected' : '');
    btn.innerHTML = `<div class="option-dot"></div><div class="option-label">${opt.label}</div><div class="option-score">${opt.score}</div>`;
    btn.onclick = () => selectOption(i);
    list.appendChild(btn);
  });

  const pct = Math.round((current / 10) * 100);
  document.getElementById('prog-label').textContent = `Question ${current + 1} of 10`;
  document.getElementById('prog-pct').textContent = pct + '%';
  document.getElementById('prog-fill').style.width = pct + '%';

  const dimForQ = questions[current].dim;
  document.querySelectorAll('.dim-tab').forEach((tab, i) => {
    tab.classList.remove('active', 'done');
    const lastQOfDim = questions.map(q => q.dim).lastIndexOf(i);
    if (i === dimForQ) tab.classList.add('active');
    else if (current > lastQOfDim) tab.classList.add('done');
  });

  document.getElementById('btn-back').disabled = current === 0;
  document.getElementById('btn-next').disabled = answers[current] === null;
  document.getElementById('btn-next').textContent = current === 9 ? 'See my results →' : 'Next';
}

function selectOption(i) {
  answers[current] = i;
  document.querySelectorAll('.option').forEach((btn, idx) => btn.classList.toggle('selected', idx === i));
  document.getElementById('btn-next').disabled = false;
}

function navigate(dir) {
  if (dir === 1 && current === 9) { taabRequireLead('scorecard', showResults); return; }
  current = Math.max(0, Math.min(9, current + dir));
  document.getElementById('question-card').style.animation = 'none';
  requestAnimationFrame(() => { document.getElementById('question-card').style.animation = ''; render(); });
}

function showResults() {
  let total = 0;
  const dimScores = [0, 0, 0, 0, 0];
  const dimMax = [0, 0, 0, 0, 0];
  questions.forEach((q, i) => {
    const chosen = answers[i];
    const score = chosen !== null ? q.options[chosen].score : 0;
    total += score;
    dimScores[q.dim] += score;
    dimMax[q.dim] += 10;
  });
  const pct = Math.round(total / 100 * 100);

  const verdict = pct >= 65 ? verdicts.high : pct >= 40 ? verdicts.mid : verdicts.low;
  const color = pct >= 65 ? '#c8f064' : pct >= 40 ? '#f5a623' : '#ff6b6b';

  document.getElementById('quiz').style.display = 'none';
  document.getElementById('results').style.display = 'block';

  const ring = document.getElementById('ring-fill');
  ring.style.stroke = color;
  const circumference = 326.7;
  ring.style.strokeDashoffset = circumference;
  document.getElementById('score-display').style.color = color;

  setTimeout(() => {
    ring.style.strokeDashoffset = circumference - (circumference * pct / 100);
    let n = 0;
    const timer = setInterval(() => {
      n = Math.min(n + 2, pct);
      document.getElementById('score-display').textContent = n;
      if (n >= pct) clearInterval(timer);
    }, 16);
  }, 200);

  document.getElementById('verdict-label').textContent = verdict.label;
  document.getElementById('verdict-label').style.color = color;
  document.getElementById('verdict-title').textContent = verdict.title;
  document.getElementById('verdict-text').textContent = verdict.text;

  setTimeout(() => {
    dimScores.forEach((s, i) => {
      const p = Math.round(s / dimMax[i] * 100);
      document.getElementById('bar-' + i).style.width = p + '%';
      document.getElementById('pct-' + i).textContent = p + '%';
    });
  }, 400);

  const list = document.getElementById('next-steps-list');
  list.innerHTML = '';
  verdict.steps.forEach((step, i) => {
    const el = document.createElement('div');
    el.className = 'step-item';
    el.innerHTML = `<div class="step-num">0${i+1}</div><div class="step-text">${step.text}</div>`;
    list.appendChild(el);
  });

  document.getElementById('prog-fill').style.width = '100%';
  document.getElementById('prog-pct').textContent = '100%';
  document.getElementById('prog-label').textContent = 'Complete';
}

function restart() {
  current = 0;
  answers = new Array(10).fill(null);
  document.getElementById('results').style.display = 'none';
  document.getElementById('next-steps-list').innerHTML = '';
  document.querySelectorAll('#results .dim-bar').forEach(b => b.style.width = '0%');
  document.getElementById('quiz').style.display = 'block';
  render();
}

render();
</script>
@endpush
</x-layouts.taab>
