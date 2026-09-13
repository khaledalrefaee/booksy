{{-- Team management (tm-*) — small additions layered on the shared bm-* system. --}}
@once
@push('owner-styles')
<style>
.tm-form { display:flex; flex-direction:column; }
.tm-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.tm-form .form-control { border-radius:11px; min-height:44px; border:1px solid var(--bk-border); background:var(--bk-bg); color:var(--bk-text); }
.tm-form .form-control:focus { border-color:var(--bk-accent); box-shadow:0 0 0 3px var(--bk-accent-wash); }
.tm-err { color:var(--bk-danger); font-size:.78rem; margin-top:5px; }

/* Switch */
.tm-switch { display:inline-flex; align-items:center; gap:10px; cursor:pointer; height:44px; }
.tm-switch input[type="checkbox"] { position:absolute; opacity:0; }
.tm-switch-track { width:44px; height:26px; border-radius:999px; background:var(--bk-surface-2); border:1px solid var(--bk-border); position:relative; transition:all .18s; flex-shrink:0; }
.tm-switch-track::after { content:''; position:absolute; top:2px; inset-inline-start:2px; width:20px; height:20px; border-radius:50%; background:#fff; box-shadow:0 1px 3px rgba(0,0,0,.25); transition:all .18s; }
.tm-switch input:checked + .tm-switch-track { background:var(--bk-accent); border-color:var(--bk-accent); }
.tm-switch input:checked + .tm-switch-track::after { inset-inline-start:20px; }
.tm-switch-text { font-size:.85rem; color:var(--bk-text-soft); font-weight:600; }

/* Role cards */
.tm-roles { display:grid; grid-template-columns:repeat(auto-fill, minmax(240px,1fr)); gap:12px; }
.tm-role { position:relative; display:flex; align-items:flex-start; gap:12px; padding:14px 14px; border-radius:14px;
    border:1px solid var(--bk-border); background:var(--bk-surface); cursor:pointer; transition:all .16s; }
.tm-role input { position:absolute; opacity:0; }
.tm-role:hover { border-color:var(--bk-gold); }
.tm-role.is-active { border-color:var(--bk-accent); background:var(--bk-accent-wash); box-shadow:0 0 0 3px var(--bk-accent-wash); }
.tm-role-ic { width:40px; height:40px; border-radius:11px; flex-shrink:0; display:flex; align-items:center; justify-content:center;
    background:var(--bk-accent-wash); color:var(--bk-accent); border:1px solid var(--bk-border); }
.tm-role-ic i, .tm-role-ic svg { width:18px; height:18px; }
.tm-role-body { min-width:0; }
.tm-role-name { display:block; font-weight:700; font-size:.9rem; color:var(--bk-text); }
.tm-role-desc { display:block; font-size:.76rem; color:var(--bk-text-muted); margin-top:2px; line-height:1.4; }
.tm-role-check { position:absolute; top:10px; inset-inline-end:10px; width:20px; height:20px; border-radius:50%; display:none;
    align-items:center; justify-content:center; background:var(--bk-accent); color:#fff; }
.tm-role-check i, .tm-role-check svg { width:12px; height:12px; stroke-width:3; }
.tm-role.is-active .tm-role-check { display:flex; }

/* Permission grid */
.tm-perm-groups { display:grid; grid-template-columns:repeat(auto-fill, minmax(280px,1fr)); gap:18px; }
.tm-perm-group-title { font-size:.72rem; text-transform:uppercase; letter-spacing:.06em; color:var(--bk-gold-strong); font-weight:700; margin-bottom:9px; }
.tm-perm { display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:10px; cursor:pointer; transition:background .14s; }
.tm-perm:hover { background:var(--bk-accent-wash); }
.tm-perm input { position:absolute; opacity:0; }
.tm-perm-box { width:20px; height:20px; border-radius:6px; border:1.5px solid var(--bk-border); background:var(--bk-surface);
    display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; transition:all .14s; color:#fff; }
.tm-perm-box i, .tm-perm-box svg { width:13px; height:13px; stroke-width:3; opacity:0; }
.tm-perm input:checked + .tm-perm-box { background:var(--bk-accent); border-color:var(--bk-accent); }
.tm-perm input:checked + .tm-perm-box i { opacity:1; }
.tm-perm.is-role input:checked + .tm-perm-box { background:var(--bk-gold-strong); border-color:var(--bk-gold-strong); }
.tm-perm-label { font-size:.85rem; color:var(--bk-text); }
.tm-perm-tag { margin-inline-start:auto; font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em;
    color:var(--bk-gold-strong); background:var(--bk-gold-soft); padding:2px 7px; border-radius:999px; }
.tm-perm.is-role { cursor:default; }

/* Temp credentials banner */
.tm-creds { display:flex; align-items:flex-start; gap:14px; padding:16px 18px; border-radius:14px; margin-bottom:18px;
    background:var(--bk-success-bg); border:1px solid color-mix(in srgb, var(--bk-success) 30%, transparent); }
.tm-creds-ic { width:42px; height:42px; border-radius:11px; flex-shrink:0; display:flex; align-items:center; justify-content:center; background:var(--bk-success); color:#fff; }
.tm-creds-ic i, .tm-creds-ic svg { width:20px; height:20px; }
.tm-creds-body { flex:1; min-width:0; }
.tm-creds-title { font-weight:700; color:var(--bk-text); margin-bottom:2px; }
.tm-creds-sub { font-size:.82rem; color:var(--bk-text-muted); margin-bottom:10px; }
.tm-creds-fields { display:flex; flex-wrap:wrap; gap:10px; }
.tm-cred { display:flex; align-items:center; gap:8px; background:var(--bk-surface); border:1px solid var(--bk-border); border-radius:10px; padding:7px 10px; }
.tm-cred-k { font-size:.7rem; text-transform:uppercase; letter-spacing:.05em; color:var(--bk-text-muted); font-weight:700; }
.tm-cred-v { font-family:ui-monospace, Menlo, Consolas, monospace; font-weight:700; color:var(--bk-text); font-size:.9rem; }
.tm-copy { border:none; background:var(--bk-accent-wash); color:var(--bk-accent); width:28px; height:28px; border-radius:8px; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; }
.tm-copy i, .tm-copy svg { width:14px; height:14px; }

/* Role badge in the table */
.tm-role-badge { display:inline-flex; align-items:center; gap:6px; padding:5px 11px; border-radius:999px; font-size:.76rem; font-weight:600;
    background:var(--bk-accent-wash); color:var(--bk-accent); border:1px solid color-mix(in srgb, var(--bk-accent) 26%, transparent); white-space:nowrap; }
.tm-role-badge i, .tm-role-badge svg { width:12px; height:12px; }
.tm-role-badge.is-super { background:var(--bk-gold-soft); color:var(--bk-gold-strong); border-color:color-mix(in srgb, var(--bk-gold) 34%, transparent); }

@media (max-width:640px){ .tm-grid-2 { grid-template-columns:1fr; } }
</style>
@endpush
@endonce
