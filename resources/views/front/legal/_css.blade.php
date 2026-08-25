{{-- Legal / policy reading system — editorial documentation layout.
     Built only on GlowRez tokens (booksy-front.css). Premium comes from
     type + spacing + hierarchy, not decoration. RTL & dark are first-class. --}}
<style>
/* ── Shell ─────────────────────────────────────────────────────────── */
.bkf-legaldoc{ padding:calc(var(--bk-nav-h) + var(--bk-s10)) 0 var(--bk-s24); }
.bkf-legaldoc-head,
.bkf-legaldoc-grid{ max-width:1080px; margin-inline:auto; padding-inline:var(--bk-gutter); }

/* ── Header (editorial, calm — not a marketing hero) ───────────────── */
.bkf-legaldoc-head{ padding-bottom:var(--bk-s8); margin-bottom:var(--bk-s10);
  border-bottom:1px solid var(--bk-border); }
.bkf-legaldoc-eyebrow{ display:inline-flex; align-items:center; gap:8px;
  font-family:var(--bk-font-ui); font-size:var(--bk-eyebrow); font-weight:700;
  letter-spacing:.16em; text-transform:uppercase; color:var(--bk-gold-strong); margin-bottom:16px; }
.bkf-legaldoc-eyebrow::before{ content:""; width:18px; height:1px; background:currentColor; opacity:.6; }
.bkf-legaldoc-head h1{ font-family:var(--bk-font-display); font-weight:600;
  font-size:var(--bk-fs-h1); line-height:1.08; letter-spacing:-.01em;
  color:var(--bk-text); margin:0; text-wrap:balance; }
html[lang="ar"] .bkf-legaldoc-head h1{ font-weight:800; letter-spacing:0; line-height:1.24; }
.bkf-legaldoc-sub{ font-family:var(--bk-font-ui); font-size:var(--bk-fs-lead);
  line-height:1.6; color:var(--bk-text-soft); max-width:56ch; margin:16px 0 0; text-wrap:pretty; }
.bkf-legaldoc-updated{ display:inline-flex; align-items:center; gap:9px;
  margin-top:var(--bk-s6); font-family:var(--bk-font-ui); font-size:var(--bk-fs-sm);
  color:var(--bk-text-muted); }
.bkf-legaldoc-updated::before{ content:""; width:6px; height:6px; border-radius:50%;
  background:var(--bk-gold); flex:none; }
.bkf-legaldoc-updated b{ color:var(--bk-text-soft); font-weight:600; font-variant-numeric:tabular-nums; }

/* ── Two-column documentation grid ────────────────────────────────── */
.bkf-legaldoc-grid{ display:grid; grid-template-columns:1fr; gap:var(--bk-s10); }
@media (min-width:960px){
  .bkf-legaldoc-grid{ grid-template-columns:210px minmax(0,760px); gap:var(--bk-s16);
    justify-content:center; align-items:start; }
}

/* ── Sidebar “On this page” (desktop, sticky, scrollspy rail) ──────── */
.bkf-legaldoc-aside{ display:none; }
@media (min-width:960px){
  .bkf-legaldoc-aside{ display:block; position:sticky;
    top:calc(var(--bk-nav-h) + var(--bk-s6)); align-self:start;
    max-height:calc(100vh - var(--bk-nav-h) - var(--bk-s8)); overflow:auto; }
}
.bkf-legaldoc-asidettl{ font-family:var(--bk-font-ui); font-size:var(--bk-fs-xs);
  font-weight:700; letter-spacing:.12em; text-transform:uppercase;
  color:var(--bk-text-muted); margin:0 0 14px; padding-inline-start:15px; }
.bkf-legaldoc-aside ul{ list-style:none; margin:0; padding:0; display:flex;
  flex-direction:column; border-inline-start:1px solid var(--bk-border); }
.bkf-legaldoc-aside a{ display:block; font-family:var(--bk-font-ui); font-size:.9rem;
  line-height:1.4; color:var(--bk-text-muted); text-decoration:none;
  padding:8px 15px; margin-inline-start:-1px; border-inline-start:2px solid transparent;
  transition:color var(--bk-t-fast) var(--bk-ease), border-color var(--bk-t-fast) var(--bk-ease); }
.bkf-legaldoc-aside a:hover{ color:var(--bk-text); }
.bkf-legaldoc-aside a.is-active{ color:var(--bk-accent); font-weight:600;
  border-inline-start-color:var(--bk-accent); }
.bkf-legaldoc-aside a:focus-visible{ outline:2px solid var(--bk-accent); outline-offset:2px; border-radius:4px; }

/* ── Mobile “On this page” disclosure ─────────────────────────────── */
.bkf-legaldoc-jump{ display:block; margin-bottom:var(--bk-s8);
  border:1px solid var(--bk-border); border-radius:var(--bk-r-sm);
  background:var(--bk-surface); overflow:hidden; }
@media (min-width:960px){ .bkf-legaldoc-jump{ display:none; } }
.bkf-legaldoc-jump summary{ list-style:none; cursor:pointer; display:flex;
  align-items:center; justify-content:space-between; gap:12px; padding:14px 16px;
  font-family:var(--bk-font-ui); font-weight:600; font-size:.92rem; color:var(--bk-text); }
.bkf-legaldoc-jump summary::-webkit-details-marker{ display:none; }
.bkf-legaldoc-jump summary:focus-visible{ outline:2px solid var(--bk-accent); outline-offset:-2px; }
.bkf-legaldoc-jump .bkf-legaldoc-chev{ flex:none; transition:transform var(--bk-t) var(--bk-ease); color:var(--bk-text-muted); }
.bkf-legaldoc-jump[open] .bkf-legaldoc-chev{ transform:rotate(180deg); }
.bkf-legaldoc-jump[open] summary{ border-bottom:1px solid var(--bk-border); }
.bkf-legaldoc-jump ul{ list-style:none; margin:0; padding:6px; display:flex; flex-direction:column; }
.bkf-legaldoc-jump a{ display:block; padding:11px 12px; border-radius:var(--bk-r-xs);
  font-family:var(--bk-font-ui); font-size:.92rem; color:var(--bk-text-soft); text-decoration:none; }
.bkf-legaldoc-jump a:hover,.bkf-legaldoc-jump a:active{ background:var(--bk-surface-2); color:var(--bk-text); }

/* ── Document body (long-form readability) ────────────────────────── */
.bkf-legaldoc-body{ font-family:var(--bk-font-body); }
.bkf-legaldoc-body > p:first-child{ font-size:1.1rem; line-height:1.75;
  color:var(--bk-text); }
.bkf-legaldoc-body h2{ font-family:var(--bk-font-ui); font-size:1.34rem; font-weight:700;
  line-height:1.3; letter-spacing:-.005em; color:var(--bk-text);
  margin:0 0 var(--bk-s4); padding-top:var(--bk-s10); margin-top:var(--bk-s10);
  border-top:1px solid var(--bk-border); scroll-margin-top:calc(var(--bk-nav-h) + 24px); }
html[lang="ar"] .bkf-legaldoc-body h2{ font-weight:800; letter-spacing:0; line-height:1.4; font-size:1.4rem; }
.bkf-legaldoc-body h2:first-of-type{ margin-top:var(--bk-s6); padding-top:0; border-top:0; }
.bkf-legaldoc-body h3{ font-family:var(--bk-font-ui); font-size:1.05rem; font-weight:600;
  color:var(--bk-text); margin:var(--bk-s6) 0 var(--bk-s3); scroll-margin-top:calc(var(--bk-nav-h) + 24px); }
.bkf-legaldoc-body p,
.bkf-legaldoc-body li{ font-size:1.02rem; line-height:1.85; color:var(--bk-text-soft); }
.bkf-legaldoc-body p{ margin:0 0 var(--bk-s5); }
.bkf-legaldoc-body ul,
.bkf-legaldoc-body ol{ margin:0 0 var(--bk-s5); padding:0; list-style:none;
  display:flex; flex-direction:column; gap:13px; }
.bkf-legaldoc-body li{ position:relative; padding-inline-start:1.6em; }
.bkf-legaldoc-body ul > li::before{ content:""; position:absolute; inset-inline-start:3px;
  top:.75em; width:6px; height:6px; border-radius:50%; background:var(--bk-accent); opacity:.6; }
.bkf-legaldoc-body ol{ counter-reset:bkf-ol; }
.bkf-legaldoc-body ol > li{ counter-increment:bkf-ol; }
.bkf-legaldoc-body ol > li::before{ content:counter(bkf-ol); position:absolute;
  inset-inline-start:0; top:.05em; font-family:var(--bk-font-ui); font-size:.85rem;
  font-weight:700; color:var(--bk-accent); font-variant-numeric:tabular-nums; }
.bkf-legaldoc-body strong{ color:var(--bk-text); font-weight:600; }
.bkf-legaldoc-body a{ color:var(--bk-accent); font-weight:600; text-decoration:underline;
  text-decoration-thickness:1px; text-underline-offset:3px;
  text-decoration-color:color-mix(in srgb,var(--bk-accent) 40%,transparent);
  transition:text-decoration-color var(--bk-t-fast) var(--bk-ease); }
.bkf-legaldoc-body a:hover{ text-decoration-color:currentColor; }
.bkf-legaldoc-body a:focus-visible{ outline:2px solid var(--bk-accent); outline-offset:2px; border-radius:3px; }
.bkf-legaldoc-body :target{ scroll-margin-top:calc(var(--bk-nav-h) + 24px); }

/* Closing revision note — one restrained gold accent, not a card grid */
.bkf-legaldoc-note{ margin-top:var(--bk-s10); padding:16px 20px;
  border-inline-start:3px solid var(--bk-gold); background:var(--bk-gold-soft);
  border-radius:var(--bk-r-xs); font-family:var(--bk-font-ui);
  font-size:.94rem; line-height:1.7; color:var(--bk-text-soft); }

/* ── Back to top ──────────────────────────────────────────────────── */
.bkf-legaldoc-top{ position:fixed; inset-block-end:var(--bk-s6); inset-inline-end:var(--bk-s6);
  width:46px; height:46px; border-radius:50%; display:grid; place-items:center;
  background:var(--bk-surface); color:var(--bk-text); border:1px solid var(--bk-border);
  box-shadow:var(--bk-shadow); cursor:pointer; z-index:var(--bk-z-drop);
  opacity:0; visibility:hidden; transform:translateY(10px);
  transition:opacity var(--bk-t) var(--bk-ease), transform var(--bk-t) var(--bk-ease),
    visibility var(--bk-t) var(--bk-ease), color var(--bk-t-fast) var(--bk-ease),
    border-color var(--bk-t-fast) var(--bk-ease); }
.bkf-legaldoc-top.is-visible{ opacity:1; visibility:visible; transform:none; }
.bkf-legaldoc-top:hover{ color:var(--bk-accent); border-color:var(--bk-accent); }
.bkf-legaldoc-top:focus-visible{ outline:2px solid var(--bk-accent); outline-offset:2px; }
@media (prefers-reduced-motion:reduce){
  .bkf-legaldoc-top{ transition:opacity var(--bk-t) linear, visibility var(--bk-t) linear; transform:none; }
}
</style>
