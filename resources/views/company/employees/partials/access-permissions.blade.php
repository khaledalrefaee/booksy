{{--
    Branch Access & Permissions — shared by employee create & edit.
    Simple by default (Role → Branch Access → Summary → Save); Advanced is opt-in.

    Expects: $branches (company branches), $roleSummaries (roleId => [moduleKey=>level]),
             $catalog (PermissionCatalog). Optional prefill: $employee, $accessMode,
             $selectedBranchIds, $l3Levels, $l4Levels, $perBranch, $defaultBranchId.
--}}
@php
    use App\Support\Access\PermissionCatalog;
    $isAr        = app()->getLocale() === 'ar';
    $modules     = $catalog->modules();
    $accessMode  = old('access_mode', $accessMode ?? 'selected');
    $defaultIds  = isset($defaultBranchId) ? [(int) $defaultBranchId] : [];
    $selectedIds = collect(old('branch_ids', $selectedBranchIds ?? $defaultIds))->map(fn ($i) => (int) $i)->all();
    $fullAccess  = (bool) old('full_access', $fullAccess ?? false);
    $l3Levels    = old('overrides', $l3Levels ?? []);
    $l4Levels    = old('branch_overrides', $l4Levels ?? []);
    $perBranch   = (bool) old('per_branch', $perBranch ?? false);
    $levelMeta   = [
        PermissionCatalog::LEVEL_MANAGE => ['label' => $isAr ? 'إدارة' : 'Manage',    'cls' => 'ap-lvl-manage'],
        PermissionCatalog::LEVEL_VIEW   => ['label' => $isAr ? 'عرض'  : 'View',      'cls' => 'ap-lvl-view'],
        PermissionCatalog::LEVEL_NONE   => ['label' => $isAr ? 'بلا وصول' : 'No access', 'cls' => 'ap-lvl-none'],
    ];
@endphp

@once
<style>
/* ── Access & Permissions (self-contained: works in both company & owner layouts) ── */
.ap-choice { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.ap-opt { position:relative; }
.ap-opt input { position:absolute; opacity:0; inset:0; cursor:pointer; }
.ap-opt-card {
    display:flex; gap:11px; align-items:flex-start; padding:13px 14px;
    border:1.5px solid rgba(255,255,255,.1); border-radius:13px;
    background:rgba(255,255,255,.03); transition:border-color .18s, background .18s; height:100%;
}
.bk-theme-light .ap-opt-card { background:#f8f9fa; border-color:#dee2e6; }
.ap-opt input:checked + .ap-opt-card { border-color:#667eea; background:rgba(102,126,234,.09); }
.ap-opt input:focus-visible + .ap-opt-card { box-shadow:0 0 0 3px rgba(102,126,234,.35); }
.ap-opt-ico { width:30px; height:30px; border-radius:9px; flex-shrink:0; display:flex; align-items:center; justify-content:center;
    background:rgba(102,126,234,.15); color:#a5b4fd; }
.bk-theme-light .ap-opt-ico { color:#667eea; }
.ap-opt-t { font-weight:700; font-size:13px; }
.ap-opt-d { font-size:11px; color:rgba(255,255,255,.45); margin-top:2px; line-height:1.4; }
.bk-theme-light .ap-opt-d { color:rgba(0,0,0,.5); }

.ap-branches { display:flex; flex-wrap:wrap; gap:8px; margin-top:12px; }
.ap-branch { position:relative; }
.ap-branch input { position:absolute; opacity:0; inset:0; cursor:pointer; }
.ap-branch-chip {
    display:inline-flex; align-items:center; gap:7px; padding:8px 13px; cursor:pointer;
    border:1.5px solid rgba(255,255,255,.1); border-radius:10px; font-size:12.5px; font-weight:600;
    background:rgba(255,255,255,.04); transition:all .16s; user-select:none;
}
.bk-theme-light .ap-branch-chip { background:#f8f9fa; border-color:#dee2e6; }
.ap-branch input:checked + .ap-branch-chip { border-color:rgba(67,233,123,.55); background:rgba(67,233,123,.1); color:#43e97b; }
.bk-theme-light .ap-branch input:checked + .ap-branch-chip { color:#1a7a36; border-color:#28a745; background:rgba(40,167,69,.08); }
.ap-branch input:focus-visible + .ap-branch-chip { box-shadow:0 0 0 3px rgba(102,126,234,.3); }
.ap-branch input:disabled + .ap-branch-chip { opacity:.4; cursor:not-allowed; }
.ap-branch-tick { width:14px; height:14px; opacity:.35; }
.ap-branch input:checked + .ap-branch-chip .ap-branch-tick { opacity:1; }

.ap-note { font-size:11.5px; color:rgba(255,255,255,.5); margin-top:10px; display:flex; gap:7px; align-items:center; }
.bk-theme-light .ap-note { color:rgba(0,0,0,.5); }
.ap-note svg { width:14px; height:14px; flex-shrink:0; }

/* Full access — deliberately distinct (amber) from branch access */
.ap-full { margin-top:16px; }
.ap-full .toggle-row { border-color:rgba(255,193,7,.35); }
.ap-full input:checked ~ * .ap-full-badge, .ap-full.on .toggle-row { border-color:#ffc107; background:rgba(255,193,7,.08); }
.ap-switch { position:relative; width:42px; height:24px; flex-shrink:0; }
.ap-switch input { position:absolute; opacity:0; inset:0; margin:0; cursor:pointer; }
.ap-switch-track { position:absolute; inset:0; border-radius:999px; background:rgba(255,255,255,.15); transition:background .18s; }
.ap-switch-thumb { position:absolute; top:3px; inset-inline-start:3px; width:18px; height:18px; border-radius:50%; background:#fff; transition:inset-inline-start .18s; }
.ap-switch input:checked + .ap-switch-track { background:#ffc107; }
.ap-switch input:checked + .ap-switch-track + .ap-switch-thumb { inset-inline-start:21px; }
.ap-switch input:focus-visible + .ap-switch-track { box-shadow:0 0 0 3px rgba(255,193,7,.35); }

/* Summary */
.ap-summary { display:flex; flex-direction:column; gap:1px; border-radius:12px; overflow:hidden;
    border:1.5px solid rgba(255,255,255,.08); margin-top:10px; }
.bk-theme-light .ap-summary { border-color:#e8ecf1; }
.ap-sum-row { display:flex; align-items:center; justify-content:space-between; gap:12px;
    padding:10px 14px; background:rgba(255,255,255,.02); }
.bk-theme-light .ap-sum-row { background:#fff; }
.ap-sum-row + .ap-sum-row { border-top:1px solid rgba(255,255,255,.05); }
.bk-theme-light .ap-sum-row + .ap-sum-row { border-top-color:#f0f2f5; }
.ap-sum-name { font-size:13px; font-weight:600; }
.ap-lvl { font-size:11px; font-weight:800; letter-spacing:.3px; padding:3px 10px; border-radius:999px; white-space:nowrap; }
.ap-lvl-manage { background:rgba(67,233,123,.14); color:#43e97b; }
.bk-theme-light .ap-lvl-manage { color:#1a7a36; background:rgba(40,167,69,.12); }
.ap-lvl-view { background:rgba(102,126,234,.16); color:#a5b4fd; }
.bk-theme-light .ap-lvl-view { color:#667eea; background:rgba(102,126,234,.1); }
.ap-lvl-none { background:rgba(255,255,255,.06); color:rgba(255,255,255,.4); }
.bk-theme-light .ap-lvl-none { color:rgba(0,0,0,.4); background:rgba(0,0,0,.05); }
.ap-lvl-full { background:rgba(255,193,7,.16); color:#ffc107; }

.ap-summary-banner { display:none; gap:8px; align-items:center; margin-top:10px; padding:10px 14px;
    border-radius:11px; background:rgba(255,193,7,.09); border:1.5px solid rgba(255,193,7,.3); font-size:12px; color:#ffc107; }
.ap-full.on ~ .ap-summary-wrap .ap-summary-banner { display:flex; }
.ap-empty { padding:16px; text-align:center; font-size:12px; color:rgba(255,255,255,.4); }
.bk-theme-light .ap-empty { color:rgba(0,0,0,.4); }

/* Advanced */
.ap-adv-toggle { margin-top:14px; display:inline-flex; align-items:center; gap:8px; cursor:pointer;
    background:transparent; border:1.5px dashed rgba(102,126,234,.4); color:#667eea; border-radius:10px;
    padding:8px 14px; font-size:12px; font-weight:700; transition:background .15s, border-color .15s; }
.ap-adv-toggle:hover { background:rgba(102,126,234,.08); border-color:#667eea; }
.bk-theme-light .ap-adv-toggle { color:#667eea; }
.ap-adv-toggle svg { width:14px; height:14px; transition:transform .2s; }
.ap-adv[open] .ap-adv-toggle svg.ap-chev { transform:rotate(180deg); }
.ap-adv-body { margin-top:14px; padding:16px; border-radius:13px; border:1.5px solid rgba(255,255,255,.08);
    background:rgba(255,255,255,.02); }
.bk-theme-light .ap-adv-body { background:#fafbfc; border-color:#e8ecf1; }
.ap-adv-grid { display:grid; grid-template-columns:1fr auto; gap:9px 14px; align-items:center; }
.ap-adv-name { font-size:12.5px; font-weight:600; }
.ap-adv-sel { min-width:130px; }
.ap-branch-tabs { display:flex; flex-wrap:wrap; gap:6px; margin:14px 0 10px; }
.ap-branch-tab { padding:6px 12px; border-radius:9px; border:1.5px solid rgba(255,255,255,.1);
    background:transparent; color:inherit; font-size:12px; font-weight:700; cursor:pointer; transition:all .15s; }
.ap-branch-tab.active { border-color:#667eea; background:rgba(102,126,234,.12); color:#a5b4fd; }
.bk-theme-light .ap-branch-tab.active { color:#667eea; }
.ap-per-branch-panel { display:none; }
.ap-per-branch-panel.active { display:block; }
.ap-adv summary { list-style:none; }
.ap-adv summary::-webkit-details-marker { display:none; }
@media (max-width:560px){ .ap-choice { grid-template-columns:1fr; } }
</style>
@endonce

<div class="col-12">
    <div id="access-permissions"
         data-role-summaries='@json($roleSummaries)'
         data-modules='@json(collect($modules)->map(fn($m)=>['key'=>$m['key'],'label'=>$isAr?$m['ar']:$m['en']]))'
         data-full-label="{{ $isAr ? 'كامل' : 'Full' }}"
         data-level-manage="{{ $isAr ? 'إدارة' : 'Manage' }}"
         data-level-view="{{ $isAr ? 'عرض' : 'View' }}"
         data-level-none="{{ $isAr ? 'بلا وصول' : 'No access' }}">

        {{-- ── Branch access (WHERE) ── --}}
        <label class="f-label">{{ $isAr ? 'الوصول للفروع' : 'Branch access' }} <span class="text-danger">*</span></label>
        <div class="ap-choice" role="radiogroup" aria-label="{{ $isAr ? 'الوصول للفروع' : 'Branch access' }}">
            <label class="ap-opt">
                <input type="radio" name="access_mode" value="selected" id="ap-mode-selected"
                       {{ $accessMode !== 'all' ? 'checked' : '' }}>
                <span class="ap-opt-card">
                    <span class="ap-opt-ico"><i data-feather="git-branch"></i></span>
                    <span>
                        <span class="ap-opt-t">{{ $isAr ? 'فروع مختارة' : 'Selected branches' }}</span>
                        <span class="ap-opt-d">{{ $isAr ? 'يعمل في الفروع التي تحددها فقط' : 'Works only in the branches you pick' }}</span>
                    </span>
                </span>
            </label>
            <label class="ap-opt">
                <input type="radio" name="access_mode" value="all" id="ap-mode-all"
                       {{ $accessMode === 'all' ? 'checked' : '' }}>
                <span class="ap-opt-card">
                    <span class="ap-opt-ico"><i data-feather="globe"></i></span>
                    <span>
                        <span class="ap-opt-t">{{ $isAr ? 'كل الفروع' : 'All branches' }}</span>
                        <span class="ap-opt-d">{{ $isAr ? 'كل فروع الشركة، بما فيها الجديدة' : 'Every company branch, including new ones' }}</span>
                    </span>
                </span>
            </label>
        </div>

        <div id="ap-branch-picker">
            <div class="ap-branches">
                @foreach($branches as $b)
                    <label class="ap-branch">
                        <input type="checkbox" name="branch_ids[]" value="{{ $b->id }}"
                               {{ in_array((int) $b->id, $selectedIds, true) ? 'checked' : '' }}>
                        <span class="ap-branch-chip">
                            <i data-feather="check" class="ap-branch-tick"></i>{{ $b->localizedName() }}
                        </span>
                    </label>
                @endforeach
            </div>
            @error('branch_ids')<div style="color:#f5576c;font-size:12px;margin-top:7px;">{{ $message }}</div>@enderror
        </div>

        <div id="ap-all-note" class="ap-note" style="display:none;">
            <i data-feather="info"></i>{{ $isAr ? 'صلاحيات الدور تُطبَّق على جميع الفروع.' : "The role's permissions apply across every branch." }}
        </div>

        {{-- ── Full Access (WHAT) — independent of branch access ── --}}
        <div class="ap-full {{ $fullAccess ? 'on' : '' }}" id="ap-full">
            <label class="toggle-row" style="cursor:pointer;">
                <span class="ap-switch">
                    <input type="checkbox" name="full_access" value="1" id="ap-full-input" {{ $fullAccess ? 'checked' : '' }}>
                    <span class="ap-switch-track"></span><span class="ap-switch-thumb"></span>
                </span>
                <span style="flex:1;">
                    <span style="font-weight:700;font-size:13px;display:block;">{{ $isAr ? 'صلاحيات كاملة (Full Access)' : 'Full Access' }}</span>
                    <span class="ap-opt-d">{{ $isAr ? 'كل الصلاحيات (ماذا يفعل). مستقل عن الفروع (أين يفعل).' : 'Every permission (what they can do). Independent of branch access (where).' }}</span>
                </span>
            </label>
        </div>

        {{-- ── Permissions summary (read-only) ── --}}
        <div class="ap-summary-wrap" style="margin-top:16px;">
            <label class="f-label">{{ $isAr ? 'ملخّص الصلاحيات' : 'Permissions summary' }}</label>
            <div class="ap-summary-banner"><i data-feather="zap"></i>{{ $isAr ? 'صلاحيات كاملة — كل الصلاحيات ممنوحة (يمكن تقييدها من المتقدّم).' : 'Full access — every permission granted (can be narrowed in Advanced).' }}</div>
            <div class="ap-summary" id="ap-summary" role="list"></div>
        </div>

        {{-- ── Advanced (opt-in) ── --}}
        <details class="ap-adv" id="ap-adv" {{ (! empty($l3Levels) || $perBranch) ? 'open' : '' }}>
            <summary>
                <span class="ap-adv-toggle">
                    <i data-feather="sliders"></i>{{ $isAr ? 'صلاحيات متقدّمة' : 'Advanced permissions' }}
                    <i data-feather="chevron-down" class="ap-chev"></i>
                </span>
            </summary>
            <div class="ap-adv-body">
                <p class="ap-opt-d" style="margin:0 0 12px;">
                    {{ $isAr ? 'تجاوز الافتراضي لكل وحدة. «افتراضي» يترك صلاحية الدور كما هي.' : "Override the default per module. “Default” keeps the role's permission as-is." }}
                </p>

                {{-- L3: employee-level overrides --}}
                <div class="ap-adv-grid">
                    @foreach($modules as $m)
                        <span class="ap-adv-name">{{ $isAr ? $m['ar'] : $m['en'] }}</span>
                        <select name="overrides[{{ $m['key'] }}]" class="f-input form-select ap-adv-sel" data-module="{{ $m['key'] }}">
                            <option value="default" {{ ($l3Levels[$m['key']] ?? 'default') === 'default' ? 'selected' : '' }}>{{ $isAr ? 'افتراضي (الدور)' : 'Default (role)' }}</option>
                            <option value="manage" {{ ($l3Levels[$m['key']] ?? '') === 'manage' ? 'selected' : '' }}>{{ $isAr ? 'إدارة' : 'Manage' }}</option>
                            @if($m['view'])<option value="view" {{ ($l3Levels[$m['key']] ?? '') === 'view' ? 'selected' : '' }}>{{ $isAr ? 'عرض فقط' : 'View only' }}</option>@endif
                            <option value="none" {{ ($l3Levels[$m['key']] ?? '') === 'none' ? 'selected' : '' }}>{{ $isAr ? 'بلا وصول' : 'No access' }}</option>
                        </select>
                    @endforeach
                </div>

                {{-- L4: per-branch overrides --}}
                <div id="ap-per-branch-wrap" style="margin-top:18px;">
                    <label class="toggle-row" style="cursor:pointer;">
                        <span class="ap-switch">
                            <input type="checkbox" name="per_branch" value="1" id="ap-per-branch-input" {{ $perBranch ? 'checked' : '' }}>
                            <span class="ap-switch-track" style="background:rgba(102,126,234,.25);"></span><span class="ap-switch-thumb"></span>
                        </span>
                        <span style="flex:1;">
                            <span style="font-weight:700;font-size:13px;display:block;">{{ $isAr ? 'صلاحيات مختلفة لكل فرع' : 'Different permissions per branch' }}</span>
                            <span class="ap-opt-d">{{ $isAr ? 'مثال: إدارة في دمشق، عرض فقط في حلب.' : 'e.g. Manage in Damascus, View only in Aleppo.' }}</span>
                        </span>
                    </label>

                    <div id="ap-per-branch" style="{{ $perBranch ? '' : 'display:none;' }}margin-top:12px;">
                        <div class="ap-branch-tabs" id="ap-branch-tabs"></div>
                        @foreach($branches as $b)
                            <div class="ap-per-branch-panel" data-branch="{{ $b->id }}">
                                <div class="ap-adv-grid">
                                    @foreach($modules as $m)
                                        <span class="ap-adv-name">{{ $isAr ? $m['ar'] : $m['en'] }}</span>
                                        <select name="branch_overrides[{{ $b->id }}][{{ $m['key'] }}]" class="f-input form-select ap-adv-sel">
                                            <option value="default" {{ ($l4Levels[$b->id][$m['key']] ?? 'default') === 'default' ? 'selected' : '' }}>{{ $isAr ? 'افتراضي' : 'Default' }}</option>
                                            <option value="manage" {{ ($l4Levels[$b->id][$m['key']] ?? '') === 'manage' ? 'selected' : '' }}>{{ $isAr ? 'إدارة' : 'Manage' }}</option>
                                            @if($m['view'])<option value="view" {{ ($l4Levels[$b->id][$m['key']] ?? '') === 'view' ? 'selected' : '' }}>{{ $isAr ? 'عرض فقط' : 'View only' }}</option>@endif
                                            <option value="none" {{ ($l4Levels[$b->id][$m['key']] ?? '') === 'none' ? 'selected' : '' }}>{{ $isAr ? 'بلا وصول' : 'No access' }}</option>
                                        </select>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </details>
    </div>
</div>

@once
<script>
(function () {
    const root = document.getElementById('access-permissions');
    if (!root) return;

    const roleSummaries = JSON.parse(root.dataset.roleSummaries || '{}');
    const modules       = JSON.parse(root.dataset.modules || '[]');
    const L = { manage: root.dataset.levelManage, view: root.dataset.levelView, none: root.dataset.levelNone, full: root.dataset.fullLabel };

    const roleSelect  = document.getElementById('role-select');
    const fullInput   = document.getElementById('ap-full-input');
    const summaryEl   = document.getElementById('ap-summary');
    const modeSelected = document.getElementById('ap-mode-selected');
    const modeAll      = document.getElementById('ap-mode-all');
    const branchPicker = document.getElementById('ap-branch-picker');
    const allNote      = document.getElementById('ap-all-note');
    const fullWrap     = document.getElementById('ap-full');

    function levelPill(level) {
        const map = { manage: ['ap-lvl-manage', L.manage], view: ['ap-lvl-view', L.view], none: ['ap-lvl-none', L.none] };
        const [cls, label] = map[level] || map.none;
        return '<span class="ap-lvl ' + cls + '" >' + label + '</span>';
    }

    function currentRoleSummary() {
        const rid = roleSelect ? roleSelect.value : '';
        return roleSummaries[rid] || {};
    }

    function overrideSelectFor(key) {
        return root.querySelector('select[name="overrides[' + key + ']"]');
    }

    function renderSummary() {
        const isFull = fullInput && fullInput.checked;
        fullWrap.classList.toggle('on', !!isFull);

        if (!roleSelect || !roleSelect.value) {
            summaryEl.innerHTML = '<div class="ap-empty">' + (document.documentElement.lang === 'ar' ? 'اختر دوراً لعرض الصلاحيات' : 'Pick a role to see permissions') + '</div>';
            return;
        }
        const base = currentRoleSummary();
        let html = '';
        modules.forEach(function (m) {
            const ov = overrideSelectFor(m.key);
            let level = base[m.key] || 'none';
            if (isFull) level = 'manage';
            if (ov && ov.value && ov.value !== 'default') level = ov.value; // explicit override wins in the summary
            html += '<div class="ap-sum-row" role="listitem"><span class="ap-sum-name">' + m.label + '</span>' + levelPill(level) + '</div>';
        });
        summaryEl.innerHTML = html;
    }

    function syncBranchMode() {
        const all = modeAll && modeAll.checked;
        branchPicker.style.display = all ? 'none' : '';
        allNote.style.display = all ? '' : 'none';
        // Disabled inputs are not submitted — so "All" never posts stray branch ids.
        branchPicker.querySelectorAll('input[name="branch_ids[]"]').forEach(function (cb) { cb.disabled = !!all; });
        const perBranchWrap = document.getElementById('ap-per-branch-wrap');
        if (perBranchWrap) perBranchWrap.style.display = all ? 'none' : '';
    }

    // ── Per-branch tabs (L4) ──
    function setupPerBranchTabs() {
        const tabsEl = document.getElementById('ap-branch-tabs');
        const panels = root.querySelectorAll('.ap-per-branch-panel');
        if (!tabsEl || !panels.length) return;
        tabsEl.innerHTML = '';
        const checkedBranchLabels = {};
        branchPicker.querySelectorAll('input[name="branch_ids[]"]').forEach(function (cb) {
            checkedBranchLabels[cb.value] = cb.closest('.ap-branch').querySelector('.ap-branch-chip').textContent.trim();
        });
        let first = true;
        panels.forEach(function (panel) {
            const bid = panel.dataset.branch;
            const cb = branchPicker.querySelector('input[name="branch_ids[]"][value="' + bid + '"]');
            const active = cb && cb.checked;
            panel.classList.remove('active');
            if (!active) return; // only show tabs for branches the employee can access
            const tab = document.createElement('button');
            tab.type = 'button'; tab.className = 'ap-branch-tab' + (first ? ' active' : '');
            tab.textContent = checkedBranchLabels[bid] || ('#' + bid);
            tab.addEventListener('click', function () {
                tabsEl.querySelectorAll('.ap-branch-tab').forEach(t => t.classList.remove('active'));
                panels.forEach(p => p.classList.remove('active'));
                tab.classList.add('active'); panel.classList.add('active');
            });
            tabsEl.appendChild(tab);
            if (first) panel.classList.add('active');
            first = false;
        });
        if (first) tabsEl.innerHTML = '<span class="ap-opt-d">' + (document.documentElement.lang === 'ar' ? 'اختر فرعاً واحداً على الأقل أولاً.' : 'Select at least one branch first.') + '</span>';
    }

    // Events
    if (roleSelect) roleSelect.addEventListener('change', renderSummary);
    if (fullInput) fullInput.addEventListener('change', renderSummary);
    [modeSelected, modeAll].forEach(el => el && el.addEventListener('change', function () { syncBranchMode(); setupPerBranchTabs(); }));
    root.querySelectorAll('select[name^="overrides["]').forEach(s => s.addEventListener('change', renderSummary));
    branchPicker.querySelectorAll('input[name="branch_ids[]"]').forEach(cb => cb.addEventListener('change', setupPerBranchTabs));

    const perBranchInput = document.getElementById('ap-per-branch-input');
    if (perBranchInput) perBranchInput.addEventListener('change', function () {
        document.getElementById('ap-per-branch').style.display = this.checked ? '' : 'none';
        if (this.checked) setupPerBranchTabs();
    });

    // Init
    syncBranchMode();
    renderSummary();
    setupPerBranchTabs();
    if (window.feather) window.feather.replace();
})();
</script>
@endonce
