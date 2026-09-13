@extends('owner.dashboard')
@section('content')
@include('owner.branches.partials._ui')
@include('owner.team._styles')

@php
    $authId = auth('owner')->id();
    $statusMeta = [
        'active'   => ['label' => __('Active'),   'cls' => 'bm-badge-active',   'icon' => 'check-circle'],
        'disabled' => ['label' => __('Disabled'), 'cls' => 'bm-badge-inactive', 'icon' => 'slash'],
    ];
@endphp

<div class="page-content bm-wrap">

    <header class="bm-head bm-reveal">
        <div>
            <div class="bm-eyebrow">
                <a href="{{ route('owner.dashboard') }}">{{ __('Dashboard') }}</a>
                <span aria-hidden="true">·</span> {{ __('Platform') }}
            </div>
            <h1 class="bm-title">{{ __('Team') }}</h1>
            <p class="bm-subtitle">{{ __('The GlowRez internal team — admins, sales managers, field reps, marketing and support. Set each member’s role and exactly what they can access.') }}</p>
        </div>
        <div class="bm-head-actions">
            <a href="{{ route('owner.team.create') }}" class="bm-btn bm-btn-primary"><i data-feather="user-plus"></i>{{ __('Add member') }}</a>
        </div>
    </header>

    @include('owner.partials.flash')

    {{-- One-time password, shown once after create / reset --}}
    @if($creds = session('temp_credentials'))
        <div class="tm-creds bm-reveal" id="tm-creds">
            <span class="tm-creds-ic"><i data-feather="key"></i></span>
            <div class="tm-creds-body">
                <div class="tm-creds-title">{{ __('Temporary password — copy it now') }}</div>
                <div class="tm-creds-sub">{{ __('Share these with :name. They’ll be asked to set a new password on first sign-in. This password is shown only once.', ['name' => $creds['name']]) }}</div>
                <div class="tm-creds-fields">
                    <div class="tm-cred"><span class="tm-cred-k">{{ __('Email') }}</span><span class="tm-cred-v" dir="ltr">{{ $creds['email'] }}</span><button type="button" class="tm-copy" data-copy="{{ $creds['email'] }}" title="{{ __('Copy') }}"><i data-feather="copy"></i></button></div>
                    <div class="tm-cred"><span class="tm-cred-k">{{ __('Password') }}</span><span class="tm-cred-v" dir="ltr">{{ $creds['password'] }}</span><button type="button" class="tm-copy" data-copy="{{ $creds['password'] }}" title="{{ __('Copy') }}"><i data-feather="copy"></i></button></div>
                </div>
            </div>
        </div>
    @endif

    {{-- Stats --}}
    <section class="bm-stats bm-reveal" aria-label="{{ __('Overview') }}">
        <div class="bm-stat" style="--accent:var(--bk-accent);"><span class="bm-stat-label"><i data-feather="users"></i>{{ __('Members') }}</span><span class="bm-stat-value">{{ number_format($stats['total']) }}</span></div>
        <div class="bm-stat" style="--accent:var(--bk-success);"><span class="bm-stat-label"><i data-feather="check-circle"></i>{{ __('Active') }}</span><span class="bm-stat-value">{{ number_format($stats['active']) }}</span></div>
        <div class="bm-stat" style="--accent:var(--bk-danger);"><span class="bm-stat-label"><i data-feather="slash"></i>{{ __('Disabled') }}</span><span class="bm-stat-value">{{ number_format($stats['disabled']) }}</span></div>
        <div class="bm-stat" style="--accent:var(--bk-gold);"><span class="bm-stat-label"><i data-feather="map-pin"></i>{{ __('Field reps') }}</span><span class="bm-stat-value">{{ number_format($stats['field']) }}</span></div>
    </section>

    {{-- Toolbar --}}
    <form method="GET" action="{{ route('owner.team.index') }}" class="bm-toolbar bm-reveal" id="tm-filter">
        <div class="bm-toolbar-row">
            <div class="bm-search">
                <button type="submit" class="bm-search-btn" tabindex="-1"><i data-feather="search"></i></button>
                <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('Search by name, email or phone…') }}" autocomplete="off">
            </div>
            <select name="role" class="bm-select" onchange="document.getElementById('tm-filter').submit()">
                <option value="">{{ __('All roles') }}</option>
                @foreach($roleMeta as $key => $meta)
                    <option value="{{ $key }}" @selected($role === $key)>{{ __($meta['label']) }}</option>
                @endforeach
            </select>
            <select name="status" class="bm-select" onchange="document.getElementById('tm-filter').submit()">
                <option value="">{{ __('All statuses') }}</option>
                <option value="active" @selected($status === 'active')>{{ __('Active') }}</option>
                <option value="disabled" @selected($status === 'disabled')>{{ __('Disabled') }}</option>
            </select>
            @if($search !== '' || $role !== '' || $status !== '')
                <a href="{{ route('owner.team.index') }}" class="bm-clear"><i data-feather="x"></i>{{ __('Clear') }}</a>
            @endif
        </div>
    </form>

    {{-- Table --}}
    <div class="bm-card bm-reveal">
        <div class="bm-table-scroll">
            <table class="bm-table">
                <thead>
                    <tr>
                        <th>{{ __('Member') }}</th>
                        <th>{{ __('Role') }}</th>
                        <th class="bm-col-address">{{ __('Last seen') }}</th>
                        <th class="bm-center bm-col-count">{{ __('Visits') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="bm-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $m)
                        @php
                            $meta = $roleMeta[$m->role] ?? ['label' => $m->role, 'icon' => 'user'];
                            $st   = $m->is_active ? $statusMeta['active'] : $statusMeta['disabled'];
                            $isSuper = $m->role === 'super_admin';
                            $self = $m->id === $authId;
                        @endphp
                        <tr>
                            <td>
                                <div class="bm-branch">
                                    <span class="bm-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($m->name, 0, 2)) }}</span>
                                    <div style="min-width:0;">
                                        <div class="bm-branch-name">{{ $m->name }} @if($self)<span class="bm-badge bm-badge-head"><i data-feather="user"></i>{{ __('You') }}</span>@endif</div>
                                        <div class="bm-branch-meta"><span class="bm-meta-line" dir="ltr"><i data-feather="mail"></i>{{ $m->email }}</span></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="tm-role-badge {{ $isSuper ? 'is-super' : '' }}"><i data-feather="{{ $meta['icon'] }}"></i>{{ __($meta['label']) }}</span></td>
                            <td class="bm-col-address"><span class="bm-meta-line">{{ $m->last_activity_at ? $m->last_activity_at->diffForHumans() : __('Never') }}</span></td>
                            <td class="bm-center bm-col-count"><span class="bm-count {{ $m->field_visits_count ? '' : 'is-zero' }}">{{ $m->field_visits_count }}</span></td>
                            <td><span class="bm-badge {{ $st['cls'] }}"><i data-feather="{{ $st['icon'] }}"></i>{{ $st['label'] }}</span></td>
                            <td class="bm-end">
                                <div class="bm-actions">
                                    <a href="{{ route('owner.team.edit', $m) }}" class="bm-act bm-act-primary" title="{{ __('Edit') }}"><i data-feather="edit-2"></i></a>
                                    <button type="button" class="bm-act dropdown-toggle" data-bs-toggle="dropdown" data-bs-boundary="viewport" title="{{ __('More') }}"><i data-feather="more-horizontal"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end bm-menu">
                                        <li>
                                            <form method="POST" action="{{ route('owner.team.reset-password', $m) }}" onsubmit="return confirm('{{ __('Generate a new temporary password?') }}');">
                                                @csrf
                                                <button type="submit" class="dropdown-item"><i data-feather="key"></i>{{ __('Reset password') }}</button>
                                            </form>
                                        </li>
                                        @unless($self)
                                        <li>
                                            <form method="POST" action="{{ route('owner.team.toggle-active', $m) }}">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="dropdown-item">
                                                    <i data-feather="{{ $m->is_active ? 'slash' : 'check-circle' }}"></i>{{ $m->is_active ? __('Disable account') : __('Enable account') }}
                                                </button>
                                            </form>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" action="{{ route('owner.team.destroy', $m) }}" onsubmit="return confirm('{{ __('Delete this member? This cannot be undone.') }}');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger"><i data-feather="trash-2"></i>{{ __('Delete member') }}</button>
                                            </form>
                                        </li>
                                        @endunless
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="bm-empty">
                                    <span class="bm-empty-ic"><i data-feather="users"></i></span>
                                    <p class="bm-empty-title">{{ __('No team members found') }}</p>
                                    <p class="bm-empty-sub">
                                        @if($search !== '' || $role !== '' || $status !== '')
                                            {{ __('No members match your filters.') }}
                                        @else
                                            {{ __('Add your first GlowRez staff member to get started.') }}
                                        @endif
                                    </p>
                                    <a href="{{ route('owner.team.create') }}" class="bm-btn bm-btn-primary"><i data-feather="user-plus"></i>{{ __('Add member') }}</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($employees->hasPages())
            <div class="bm-pagination">
                <div class="bm-pagination-info">{{ __('Showing :from–:to of :total', ['from' => $employees->firstItem(), 'to' => $employees->lastItem(), 'total' => $employees->total()]) }}</div>
                {{ $employees->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
(function () {
    document.querySelectorAll('.tm-copy').forEach(function (b) {
        b.addEventListener('click', function () {
            var v = this.getAttribute('data-copy');
            if (navigator.clipboard) navigator.clipboard.writeText(v).catch(function(){});
            var i = this.querySelector('[data-feather]');
            if (i) { i.setAttribute('data-feather', 'check'); if (window.feather) feather.replace(); }
        });
    });
    if (typeof feather !== 'undefined') setTimeout(function () { feather.replace(); }, 60);
})();
</script>
@endpush
@endsection
