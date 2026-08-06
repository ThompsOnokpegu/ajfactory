{{-- Shared chrome styles for the written self-hosting guides (GCP + Hostinger).
     These pages are standalone HTML — no Tailwind — so the whole design system
     lives here. Edit once; both guides follow. --}}
<style>
  :root {
    --font-sans: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    --font-mono: ui-monospace, "SF Mono", "JetBrains Mono", "Cascadia Code", Menlo, Consolas, monospace;

    /* light (default) — cool paper */
    --bg: #eef2f6;
    --surface: #ffffff;
    --surface-2: #f5f8fb;
    --text: #0d1720;
    --muted: #56697b;
    --faint: #8497a8;
    --border: rgba(13,35,56,0.12);
    --border-strong: rgba(13,35,56,0.20);
    --accent: #0e7490;      /* cyan, darkened for contrast on paper */
    --accent-soft: rgba(14,116,144,0.10);
    --ok: #15803d;
    --ok-soft: rgba(21,128,61,0.10);
    --warn: #b45309;
    --warn-soft: rgba(180,83,9,0.10);

    /* terminal console — always dark, both themes */
    --con-bg: #0b1220;
    --con-head: #0f1a2b;
    --con-border: rgba(120,170,210,0.16);
    --con-text: #d7e2ee;
    --con-prompt: #4dd6ee;
    --con-muted: #6f8199;

    --maxw: 46rem;
  }
  @media (prefers-color-scheme: dark) {
    :root {
      --bg: #080c13;
      --surface: #0f1722;
      --surface-2: #0c141f;
      --text: #e6edf3;
      --muted: #8b9aad;
      --faint: #647589;
      --border: rgba(120,165,205,0.12);
      --border-strong: rgba(120,165,205,0.22);
      --accent: #34d3ee;
      --accent-soft: rgba(52,211,238,0.10);
      --ok: #4ade80;
      --ok-soft: rgba(74,222,128,0.10);
      --warn: #fbbf24;
      --warn-soft: rgba(251,191,36,0.10);
    }
  }
  :root[data-theme="light"] {
    --bg: #eef2f6; --surface: #ffffff; --surface-2: #f5f8fb; --text: #0d1720;
    --muted: #56697b; --faint: #8497a8; --border: rgba(13,35,56,0.12); --border-strong: rgba(13,35,56,0.20);
    --accent: #0e7490; --accent-soft: rgba(14,116,144,0.10); --ok: #15803d; --ok-soft: rgba(21,128,61,0.10);
    --warn: #b45309; --warn-soft: rgba(180,83,9,0.10);
  }
  :root[data-theme="dark"] {
    --bg: #080c13; --surface: #0f1722; --surface-2: #0c141f; --text: #e6edf3;
    --muted: #8b9aad; --faint: #647589; --border: rgba(120,165,205,0.12); --border-strong: rgba(120,165,205,0.22);
    --accent: #34d3ee; --accent-soft: rgba(52,211,238,0.10); --ok: #4ade80; --ok-soft: rgba(74,222,128,0.10);
    --warn: #fbbf24; --warn-soft: rgba(251,191,36,0.10);
  }

  * { box-sizing: border-box; }
  html { scroll-behavior: smooth; scroll-padding-top: 5rem; }
  @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } * { animation: none !important; transition: none !important; } }
  body {
    margin: 0; background: var(--bg); color: var(--text);
    font-family: var(--font-sans); font-size: 17px; line-height: 1.68;
    -webkit-font-smoothing: antialiased; text-rendering: optimizeLegibility;
  }
  ::selection { background: var(--accent); color: #04121a; }

  /* scroll progress */
  #progress { position: fixed; top: 0; left: 0; height: 2px; width: 0%; background: var(--accent); z-index: 50; transition: width .1s linear; }

  /* top bar */
  .topbar {
    position: sticky; top: 0; z-index: 40; backdrop-filter: blur(10px);
    background: color-mix(in srgb, var(--bg) 82%, transparent);
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
    padding: .7rem clamp(1rem, 4vw, 2rem);
  }
  .brand { font-weight: 800; letter-spacing: -.02em; font-size: 1rem; text-transform: uppercase; font-style: italic; }
  .brand b { color: var(--accent); }
  .themetoggle {
    font-family: var(--font-mono); font-size: .7rem; letter-spacing: .12em; text-transform: uppercase;
    background: transparent; color: var(--muted); border: 1px solid var(--border-strong);
    padding: .4rem .7rem; border-radius: 7px; cursor: pointer;
  }
  .themetoggle:hover { color: var(--accent); border-color: var(--accent); }

  .wrap { display: grid; grid-template-columns: 1fr; max-width: 78rem; margin: 0 auto; padding: 0 clamp(1rem,4vw,2rem); }
  @media (min-width: 1040px) { .wrap { grid-template-columns: 15rem minmax(0,1fr); gap: 3rem; } }

  /* table of contents */
  nav.toc { display: none; }
  @media (min-width: 1040px) {
    nav.toc { display: block; position: sticky; top: 4.4rem; align-self: start; height: calc(100vh - 5rem); overflow-y: auto; padding: 2rem 0; }
  }
  nav.toc .lbl { font-family: var(--font-mono); font-size: .66rem; letter-spacing: .18em; text-transform: uppercase; color: var(--faint); margin-bottom: 1rem; }
  nav.toc a {
    display: flex; gap: .7rem; align-items: baseline; text-decoration: none; color: var(--muted);
    font-size: .84rem; padding: .32rem 0; border-left: 2px solid transparent; padding-left: .9rem; margin-left: -2px;
  }
  nav.toc a .n { font-family: var(--font-mono); font-size: .72rem; color: var(--faint); font-variant-numeric: tabular-nums; }
  nav.toc a:hover { color: var(--text); }
  nav.toc a.active { color: var(--accent); border-left-color: var(--accent); }
  nav.toc a.active .n { color: var(--accent); }

  main { padding: 2.5rem 0 5rem; min-width: 0; }
  .col { max-width: var(--maxw); }

  /* hero */
  .hero { padding: 1.5rem 0 2.5rem; border-bottom: 1px solid var(--border); margin-bottom: 2.5rem; }
  .eyebrow { font-family: var(--font-mono); font-size: .72rem; letter-spacing: .16em; text-transform: uppercase; color: var(--accent); display: inline-flex; align-items: center; gap: .5rem; }
  .eyebrow::before { content: "▍"; }
  h1 { font-size: clamp(2rem, 5vw, 3rem); line-height: 1.05; letter-spacing: -.03em; font-weight: 800; margin: 1rem 0 .8rem; text-wrap: balance; }
  h1 em { font-style: normal; color: var(--accent); }
  .lede { font-size: 1.12rem; color: var(--muted); max-width: 38rem; }
  .meta { display: flex; flex-wrap: wrap; gap: .6rem; margin-top: 1.6rem; }
  .chip { font-family: var(--font-mono); font-size: .74rem; letter-spacing: .04em; color: var(--text); background: var(--surface); border: 1px solid var(--border-strong); border-radius: 999px; padding: .38rem .8rem; }
  .chip b { color: var(--accent); }

  /* outcome terminal mock */
  .term-mock { margin-top: 1.8rem; border: 1px solid var(--con-border); border-radius: 12px; overflow: hidden; background: var(--con-bg); box-shadow: 0 24px 50px -30px rgba(0,0,0,.6); }
  .term-mock .bar { background: var(--con-head); padding: .6rem .9rem; display: flex; align-items: center; gap: .45rem; border-bottom: 1px solid var(--con-border); }
  .term-mock .bar i { width: 11px; height: 11px; border-radius: 50%; display: inline-block; }
  .term-mock .bar .u { margin-left: .6rem; font-family: var(--font-mono); font-size: .72rem; color: var(--con-muted); }
  .term-mock .body { padding: 1rem 1.1rem; font-family: var(--font-mono); font-size: .82rem; line-height: 1.85; color: var(--con-text); }
  .term-mock .body .c { color: var(--con-prompt); } .term-mock .body .g { color: #6ee7a8; } .term-mock .body .m { color: var(--con-muted); }

  /* sections */
  section { padding-top: 2.5rem; scroll-margin-top: 5rem; }
  .part-h { display: flex; align-items: baseline; gap: 1rem; margin-bottom: .4rem; }
  .part-h .num { font-family: var(--font-mono); font-size: .8rem; color: var(--accent); letter-spacing: .1em; font-variant-numeric: tabular-nums; padding-top: .35rem; }
  h2 { font-size: clamp(1.5rem, 3.4vw, 2rem); letter-spacing: -.02em; font-weight: 800; margin: 0; text-wrap: balance; }
  .part-sub { color: var(--muted); margin: .1rem 0 1.4rem; }
  h3 { font-size: 1.12rem; font-weight: 700; letter-spacing: -.01em; margin: 2rem 0 .6rem; }
  h3 .k { font-family: var(--font-mono); font-size: .78rem; color: var(--accent); margin-right: .5rem; }
  p { margin: 0 0 1rem; }
  a.link { color: var(--accent); text-decoration: none; border-bottom: 1px solid var(--accent-soft); }
  a.link:hover { border-bottom-color: var(--accent); }
  strong { font-weight: 700; }
  code.inl { font-family: var(--font-mono); font-size: .86em; background: var(--surface-2); border: 1px solid var(--border); padding: .08em .4em; border-radius: 5px; color: var(--text); }
  kbd { font-family: var(--font-mono); font-size: .8em; background: var(--surface); border: 1px solid var(--border-strong); border-bottom-width: 2px; border-radius: 5px; padding: .1em .45em; }

  ul.clean { list-style: none; padding: 0; margin: 0 0 1.2rem; }
  ul.clean > li { position: relative; padding-left: 1.5rem; margin: .5rem 0; }
  ul.clean > li::before { content: "›"; position: absolute; left: .2rem; color: var(--accent); font-weight: 700; }
  ul.check > li::before { content: "☐"; color: var(--faint); }
  ol.steps { list-style: none; counter-reset: s; padding: 0; margin: 0 0 1.2rem; }
  ol.steps > li { counter-increment: s; position: relative; padding-left: 2.4rem; margin: .85rem 0; }
  ol.steps > li::before { content: counter(s); position: absolute; left: 0; top: .05rem; width: 1.6rem; height: 1.6rem; border-radius: 7px; background: var(--accent-soft); color: var(--accent); font-family: var(--font-mono); font-size: .82rem; font-weight: 700; display: grid; place-items: center; }

  /* console / code blocks */
  .console { margin: 1.1rem 0 1.3rem; border: 1px solid var(--con-border); border-radius: 10px; overflow: hidden; background: var(--con-bg); }
  .console .chead { display: flex; align-items: center; justify-content: space-between; background: var(--con-head); border-bottom: 1px solid var(--con-border); padding: .4rem .75rem; }
  .console .chead .tag { font-family: var(--font-mono); font-size: .68rem; letter-spacing: .12em; text-transform: uppercase; color: var(--con-muted); display: flex; align-items: center; gap: .5rem; }
  .console .chead .tag i { width: 9px; height: 9px; border-radius: 50%; background: #f26d5b; box-shadow: 16px 0 0 #f5bf4f, 32px 0 0 #64c97b; }
  .copybtn { font-family: var(--font-mono); font-size: .66rem; letter-spacing: .1em; text-transform: uppercase; color: var(--con-muted); background: transparent; border: 1px solid var(--con-border); border-radius: 6px; padding: .28rem .55rem; cursor: pointer; }
  .copybtn:hover { color: var(--con-prompt); border-color: var(--con-prompt); }
  .copybtn.done { color: #6ee7a8; border-color: #6ee7a8; }
  .console pre { margin: 0; padding: .9rem 1rem; overflow-x: auto; }
  .console code { font-family: var(--font-mono); font-size: .84rem; line-height: 1.7; color: var(--con-text); white-space: pre; }
  .console.cmd code::before { content: "$ "; color: var(--con-prompt); }
  .console .cmt { color: var(--con-muted); }

  /* callouts */
  .call { border: 1px solid var(--border); border-left-width: 3px; border-radius: 8px; padding: .9rem 1.1rem; margin: 1.2rem 0; background: var(--surface); font-size: .96rem; }
  .call .h { font-family: var(--font-mono); font-size: .7rem; letter-spacing: .12em; text-transform: uppercase; display: flex; align-items: center; gap: .5rem; margin-bottom: .35rem; }
  .call p:last-child { margin-bottom: 0; }
  .call.check { border-left-color: var(--ok); background: var(--ok-soft); } .call.check .h { color: var(--ok); }
  .call.warn { border-left-color: var(--warn); background: var(--warn-soft); } .call.warn .h { color: var(--warn); }
  .call.note { border-left-color: var(--accent); background: var(--accent-soft); } .call.note .h { color: var(--accent); }

  /* tables */
  .tablewrap { overflow-x: auto; margin: 1.2rem 0; border: 1px solid var(--border); border-radius: 10px; }
  table { border-collapse: collapse; width: 100%; font-size: .92rem; }
  th, td { text-align: left; padding: .7rem .9rem; border-bottom: 1px solid var(--border); vertical-align: top; }
  thead th { font-family: var(--font-mono); font-size: .68rem; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); background: var(--surface-2); }
  tbody tr:last-child td { border-bottom: 0; }
  td b { color: var(--accent); }

  .foot { margin-top: 3rem; padding-top: 1.6rem; border-top: 1px solid var(--border); color: var(--faint); font-size: .85rem; font-family: var(--font-mono); }

  a:focus-visible, button:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; border-radius: 4px; }
</style>
