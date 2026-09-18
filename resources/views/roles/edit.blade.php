@extends('layouts.main_layout')
@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">Edit Role</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">Roles</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card permissions-card">
                        <div class="card-body">
                            @if ($role->name === 'head_coach')
                                <div class="alert alert-warning py-2 mb-3">
                                    Unchecking <code>*</code> moves head coach off full access onto whatever's
                                    checked below. <code>role_permission.manage</code> will always stay on so
                                    this panel itself never becomes unreachable - everything else is yours to change.
                                </div>
                            @endif
                            <form id="role-permissions-form" method="POST" action="{{ route('roles.update', $role->id) }}">
                                @csrf
                                @method('PUT')

                                {{-- Everything a coach needs to save is right here, above the (scrollable)
                                     permission list below - no hunting for the button after scrolling. --}}
                                <div class="permissions-toolbar">
                                    <div class="permissions-toolbar__name">
                                        <label class="mb-1 font-weight-bold small">Role name</label>
                                        <input type="text" name="name" class="form-control" value="{{ $role->name }}" required>
                                    </div>
                                    <div class="permissions-toolbar__search">
                                        <label class="mb-1 font-weight-bold small">Search permissions</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                                            </div>
                                            <input type="text" id="permission-search" class="form-control"
                                                placeholder="e.g. league, view, matchstart...">
                                        </div>
                                    </div>
                                    <div class="permissions-toolbar__save">
                                        <button class="btn btn-primary btn-block" type="submit">
                                            <i class="fas fa-save mr-1"></i> Save changes
                                        </button>
                                    </div>
                                </div>

                                @php
                                    $current = $role->permissions->pluck('name');
                                    $hasBareWildcard = $current->contains('*');
                                    $bare = $permissions->firstWhere('name', '*');

                                    $groupLabel = fn ($entity) => ucwords(str_replace('_', ' ', $entity));

                                    $grouped = $permissions
                                        ->reject(fn ($p) => $p->name === '*')
                                        ->groupBy(fn ($p) => str_contains($p->name, '.') ? explode('.', $p->name)[0] : 'legacy')
                                        ->sortKeys();
                                @endphp

                                @if ($bare)
                                    @php $directlyAssigned = $current->contains('*'); @endphp
                                    <label class="permission-chip-wrap permission-chip-wrap--all mb-3" for="perm{{ $bare->id }}">
                                        <input class="permission-chip-input" type="checkbox" name="permissions[]"
                                            value="*" id="perm{{ $bare->id }}"
                                            data-perm="*"
                                            data-direct="{{ $directlyAssigned ? '1' : '0' }}"
                                            {{ $directlyAssigned ? 'checked' : '' }}>
                                        <span class="permission-chip permission-chip--all">
                                            <i class="fas fa-unlock mr-1"></i> Full access (ALL) - grants every permission, current and future
                                        </span>
                                    </label>
                                @endif

                                <p class="text-muted small mb-2">
                                    Tap a group to open it. Groups with something already granted open automatically.
                                </p>

                                <div class="permission-groups" id="permission-groups">
                                    @foreach ($grouped as $entity => $perms)
                                        @php
                                            $groupHasDirectGrant = ! $hasBareWildcard && (
                                                $perms->contains(fn ($p) => $current->contains($p->name))
                                                || $current->contains($entity . '.*')
                                            );
                                            $grantedCount = $perms->filter(function ($p) use ($current, $entity, $hasBareWildcard) {
                                                $isWildcardRow = $p->name === '*' || str_ends_with($p->name, '.*');
                                                if ($isWildcardRow) return false;
                                                return $current->contains($p->name) || $hasBareWildcard || $current->contains($entity . '.*');
                                            })->count();
                                            $totalCount = $perms->filter(fn ($p) => $p->name !== $entity . '.*')->count();
                                        @endphp
                                        <details class="permission-card" data-group-card {{ $groupHasDirectGrant ? 'open' : '' }}>
                                            <summary class="permission-card__header">
                                                <span class="permission-card__chevron"><i class="fas fa-chevron-right"></i></span>
                                                <span class="permission-card__title">{{ $entity === 'legacy' ? 'Legacy (uploads)' : $groupLabel($entity) }}</span>
                                                <span class="badge badge-pill {{ $grantedCount > 0 ? 'badge-primary' : 'badge-light' }} ml-auto">
                                                    {{ $grantedCount }}/{{ $totalCount }}
                                                </span>
                                            </summary>
                                            <div class="permission-card__body">
                                                @foreach ($perms->sortBy(fn ($p) => $p->name !== $entity . '.*' ? 1 : 0) as $permission)
                                                    @php
                                                        $directlyAssigned = $current->contains($permission->name);
                                                        $isWildcardRow = $permission->name === '*' || str_ends_with($permission->name, '.*');
                                                        $impliedByEntityWildcard = ! $directlyAssigned && $current->contains($entity . '.*');
                                                        // Effectively granted = this exact row is assigned, OR the role
                                                        // holds '*', OR it holds the 'entity.*' wildcard covering it.
                                                        // Shown checked either way so the form reflects what the role
                                                        // can actually do, not just its literal permission rows. JS
                                                        // below keeps this in sync as wildcard checkboxes are toggled,
                                                        // without a page reload.
                                                        $effectivelyGranted = $directlyAssigned || (! $isWildcardRow && ($hasBareWildcard || $impliedByEntityWildcard));
                                                        $isDisabled = ! $isWildcardRow && $effectivelyGranted && ! $directlyAssigned;
                                                        $label = $isWildcardRow
                                                            ? 'All ' . $groupLabel($entity)
                                                            : ucfirst(str_replace('_', ' ', substr($permission->name, strlen($entity) + 1)));
                                                        $impliedBy = $hasBareWildcard ? '*' : ($impliedByEntityWildcard ? $entity . '.*' : null);
                                                    @endphp
                                                    <label class="permission-chip-wrap permission-item" for="perm{{ $permission->id }}"
                                                        data-search="{{ strtolower($permission->name) }}"
                                                        title="{{ $isDisabled ? 'Granted via ' . $impliedBy . ' - uncheck that to remove this' : '' }}">
                                                        <input class="permission-chip-input" type="checkbox" name="permissions[]"
                                                            value="{{ $permission->name }}" id="perm{{ $permission->id }}"
                                                            data-perm="{{ $permission->name }}"
                                                            data-direct="{{ $directlyAssigned ? '1' : '0' }}"
                                                            {{ $effectivelyGranted ? 'checked' : '' }}
                                                            {{ $isDisabled ? 'disabled' : '' }}>
                                                        <span class="permission-chip {{ $isWildcardRow ? 'permission-chip--wildcard' : '' }}">
                                                            {{ $label }}
                                                        </span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </details>
                                    @endforeach
                                </div>

                                <div id="no-results" class="text-muted text-center py-4" hidden>
                                    No permissions match "<span id="no-results-term"></span>".
                                </div>
                            </form>

                            <style>
                                .permissions-toolbar {
                                    display: grid;
                                    grid-template-columns: 1fr 1.3fr auto;
                                    gap: .75rem;
                                    align-items: end;
                                    padding: .85rem 1rem;
                                    margin-bottom: 1rem;
                                    background: #f8f9fb;
                                    border: 1px solid #e3e6ea;
                                    border-radius: .5rem;
                                }
                                .permissions-toolbar__save .btn { white-space: nowrap; }
                                @media (max-width: 767px) {
                                    .permissions-toolbar { grid-template-columns: 1fr; }
                                }

                                .permission-card {
                                    border: 1px solid #e3e6ea;
                                    border-radius: .4rem;
                                    background: #fff;
                                    margin-bottom: .5rem;
                                }
                                .permission-card__header {
                                    display: flex;
                                    align-items: center;
                                    gap: .6rem;
                                    padding: .5rem .85rem;
                                    cursor: pointer;
                                    list-style: none;
                                    user-select: none;
                                }
                                .permission-card__header::-webkit-details-marker { display: none; }
                                .permission-card__chevron {
                                    color: #98a2b3;
                                    font-size: .7rem;
                                    transition: transform .15s ease;
                                }
                                details[open] > .permission-card__header .permission-card__chevron {
                                    transform: rotate(90deg);
                                }
                                .permission-card__title {
                                    font-weight: 600;
                                    color: #2c3345;
                                    font-size: .92rem;
                                }
                                .permission-card__body {
                                    padding: .15rem .85rem .75rem;
                                    display: flex;
                                    flex-wrap: wrap;
                                    gap: .4rem;
                                    border-top: 1px solid #f0f1f4;
                                    margin-top: .1rem;
                                    padding-top: .6rem;
                                }

                                .permission-chip-wrap { display: inline-flex; margin: 0; cursor: pointer; }
                                .permission-chip-input {
                                    position: absolute;
                                    width: 1px; height: 1px;
                                    opacity: 0;
                                    pointer-events: none;
                                }
                                .permission-chip {
                                    display: inline-flex;
                                    align-items: center;
                                    padding: .3rem .7rem;
                                    border-radius: 1rem;
                                    border: 1px solid #d7dbe3;
                                    background: #f8f9fb;
                                    color: #495057;
                                    font-size: .8rem;
                                    line-height: 1.1;
                                    transition: background .12s ease, color .12s ease, border-color .12s ease;
                                }
                                .permission-chip-input:checked + .permission-chip {
                                    background: #4361ee;
                                    border-color: #4361ee;
                                    color: #fff;
                                }
                                .permission-chip-input:disabled + .permission-chip {
                                    opacity: .65;
                                }
                                .permission-chip-wrap:has(.permission-chip-input:disabled) { cursor: not-allowed; }
                                .permission-chip-input:focus-visible + .permission-chip {
                                    box-shadow: 0 0 0 .15rem rgba(67, 97, 238, .3);
                                }
                                .permission-chip--wildcard { font-weight: 600; }
                                .permission-chip--wildcard.permission-chip { border-style: dashed; }

                                .permission-chip-wrap--all { display: block; }
                                .permission-chip--all {
                                    display: flex;
                                    padding: .6rem 1rem;
                                    border-radius: .4rem;
                                    background: linear-gradient(135deg, #eef2ff 0%, #f7f9ff 100%);
                                    border: 1px solid #b8c8f5;
                                    color: #2c3345;
                                    font-size: .9rem;
                                    font-weight: 600;
                                }
                                .permission-chip-wrap--all .permission-chip-input:checked + .permission-chip--all {
                                    background: linear-gradient(135deg, #4361ee 0%, #5a72f0 100%);
                                    border-color: #4361ee;
                                    color: #fff;
                                }

                                .permission-item[hidden] { display: none !important; }
                            </style>

                            <script>
                                (function () {
                                    var form = document.getElementById('role-permissions-form');
                                    if (!form) return;
                                    var boxes = Array.prototype.slice.call(form.querySelectorAll('[data-perm]'));
                                    var isWildcard = function (name) { return name === '*' || name.slice(-2) === '.*'; };
                                    var entityOf = function (name) { return name.split('.')[0]; };
                                    var wildcardBoxes = boxes.filter(function (cb) { return isWildcard(cb.dataset.perm); });

                                    function recompute() {
                                        var bareActive = wildcardBoxes.some(function (cb) { return cb.dataset.perm === '*' && cb.checked; });
                                        var activeEntities = wildcardBoxes
                                            .filter(function (cb) { return cb.dataset.perm !== '*' && cb.checked; })
                                            .map(function (cb) { return entityOf(cb.dataset.perm); });

                                        boxes.forEach(function (cb) {
                                            if (isWildcard(cb.dataset.perm)) return;
                                            var wrap = cb.closest('.permission-chip-wrap');
                                            var impliedBy = bareActive
                                                ? '*'
                                                : (activeEntities.indexOf(entityOf(cb.dataset.perm)) !== -1 ? entityOf(cb.dataset.perm) + '.*' : null);

                                            if (impliedBy) {
                                                cb.checked = true;
                                                cb.disabled = true;
                                                if (wrap) wrap.title = 'Granted via ' + impliedBy + ' - uncheck that to remove this';
                                            } else {
                                                cb.disabled = false;
                                                cb.checked = cb.dataset.direct === '1';
                                                if (wrap) wrap.title = '';
                                            }
                                        });
                                    }

                                    wildcardBoxes.forEach(function (cb) { cb.addEventListener('change', recompute); });
                                    recompute();

                                    // Live filter - hides non-matching chips, folds a group with nothing
                                    // left visible, and force-opens any group that still has a match so
                                    // the coach never has to expand groups by hand while searching.
                                    var search = document.getElementById('permission-search');
                                    var groupCards = Array.prototype.slice.call(document.querySelectorAll('[data-group-card]'));
                                    var noResults = document.getElementById('no-results');
                                    var noResultsTerm = document.getElementById('no-results-term');

                                    if (search) {
                                        search.addEventListener('input', function () {
                                            var term = search.value.trim().toLowerCase();
                                            var anyVisible = false;

                                            groupCards.forEach(function (card) {
                                                var groupItems = Array.prototype.slice.call(card.querySelectorAll('.permission-item'));
                                                var groupHasMatch = false;

                                                groupItems.forEach(function (item) {
                                                    var match = !term || item.dataset.search.indexOf(term) !== -1;
                                                    item.hidden = !match;
                                                    if (match) groupHasMatch = true;
                                                });

                                                if (term) {
                                                    if (!card.dataset.wasOpen) card.dataset.wasOpen = card.open ? '1' : '0';
                                                    card.open = groupHasMatch;
                                                } else if (card.dataset.wasOpen) {
                                                    card.open = card.dataset.wasOpen === '1';
                                                    delete card.dataset.wasOpen;
                                                }

                                                card.hidden = !groupHasMatch;
                                                if (groupHasMatch) anyVisible = true;
                                            });

                                            if (noResults) {
                                                noResults.hidden = anyVisible || !term;
                                                if (noResultsTerm) noResultsTerm.textContent = search.value.trim();
                                            }
                                        });
                                    }
                                })();
                            </script>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
