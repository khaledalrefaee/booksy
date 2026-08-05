{{-- Shared reading style for legal/policy pages — mobile-first, long-form legible. --}}
<style>
.bkf-legal{ padding:calc(var(--bk-nav-h) + var(--bk-s10)) 0 var(--bk-s20); }
.bkf-legal-wrap{ max-width:760px; margin-inline:auto; padding-inline:var(--bk-gutter); }
.bkf-legal-eyebrow{ font-size:var(--bk-eyebrow); font-weight:700; letter-spacing:.14em; text-transform:uppercase; color:var(--bk-gold-strong); margin-bottom:12px; }
.bkf-legal h1{ font-family:var(--bk-font-display); font-size:var(--bk-fs-h1); line-height:1.1; color:var(--bk-text); margin:0 0 10px; }
.bkf-legal-updated{ font-size:var(--bk-fs-sm); color:var(--bk-text-muted); margin-bottom:var(--bk-s10); }
.bkf-legal-body h2{ font-family:var(--bk-font-ui); font-size:1.2rem; font-weight:700; color:var(--bk-text); margin:var(--bk-s10) 0 var(--bk-s3); }
.bkf-legal-body p,.bkf-legal-body li{ font-size:1rem; line-height:1.85; color:var(--bk-text-soft); }
.bkf-legal-body p{ margin:0 0 var(--bk-s4); }
.bkf-legal-body ul{ margin:0 0 var(--bk-s4); padding-inline-start:1.25em; }
.bkf-legal-body li{ margin-bottom:8px; }
.bkf-legal-body strong{ color:var(--bk-text); font-weight:600; }
.bkf-legal-body a{ color:var(--bk-accent); font-weight:600; text-decoration:underline; text-underline-offset:3px; }
.bkf-legal-toc{ display:flex; flex-wrap:wrap; gap:8px; margin-bottom:var(--bk-s10); padding-bottom:var(--bk-s6); border-bottom:1px solid var(--bk-border); }
.bkf-legal-toc a{ font-size:.85rem; font-weight:600; color:var(--bk-text-soft); padding:7px 14px; border-radius:var(--bk-r-pill); border:1px solid var(--bk-border); text-decoration:none; transition:all var(--bk-t) ease; }
.bkf-legal-toc a:hover{ color:var(--bk-accent); border-color:var(--bk-accent); background:var(--bk-accent-wash); }
.bkf-legal-note{ margin-top:var(--bk-s10); padding:16px 18px; border-radius:var(--bk-r); background:var(--bk-surface-2); border:1px solid var(--bk-border); font-size:.9rem; color:var(--bk-text-muted); }
.bkf-legal-body :target{ scroll-margin-top:calc(var(--bk-nav-h) + 16px); }
</style>
