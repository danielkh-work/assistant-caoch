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
                    <div class="card">
                        <div class="card-body">
                            @if ($role->name === 'head_coach')
                                <div class="alert alert-warning">
                                    Unchecking <code>*</code> moves head coach off full access onto whatever's
                                    checked below. <code>role_permission.manage</code> will always stay on so
                                    this panel itself never becomes unreachable - everything else is yours to change.
                                </div>
                            @endif
                            <form id="role-permissions-form" method="POST" action="{{ route('roles.update', $role->id) }}">
                                @csrf
                                @method('PUT')
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Name</label>
                                        <input type="text" name="name" class="form-control" value="{{ $role->name }}" required>
                                    </div>
                                </div>
                                <br>
                                <div class="row">
                                    <div class="col-md-12">
                                        <label>Permissions</label>
                                        <div class="row">
                                            @php
                                                $current = $role->permissions->pluck('name');
                                                $hasBareWildcard = $current->contains('*');
                                            @endphp
                                            @foreach ($permissions as $permission)
                                                @php
                                                    $directlyAssigned = $current->contains($permission->name);
                                                    $entity = explode('.', $permission->name)[0];
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
                                                @endphp
                                                <div class="col-md-4">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="permissions[]"
                                                            value="{{ $permission->name }}" id="perm{{ $permission->id }}"
                                                            data-perm="{{ $permission->name }}"
                                                            data-direct="{{ $directlyAssigned ? '1' : '0' }}"
                                                            {{ $effectivelyGranted ? 'checked' : '' }}
                                                            {{ $isDisabled ? 'disabled' : '' }}>
                                                        <label class="form-check-label {{ $permission->name === '*' ? 'font-weight-bold' : '' }}" for="perm{{ $permission->id }}">
                                                            {{ $permission->name === '*' ? 'ALL' : $permission->name }}
                                                        </label>
                                                        <small class="text-muted d-block perm-note"></small>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                <br>
                                <div class="row">
                                    <div class="col-md-12">
                                        <input class="btn btn-success" type="submit" value="Save">
                                    </div>
                                </div>
                            </form>
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
                                            var note = cb.closest('.form-check').querySelector('.perm-note');
                                            var impliedBy = bareActive
                                                ? '*'
                                                : (activeEntities.indexOf(entityOf(cb.dataset.perm)) !== -1 ? entityOf(cb.dataset.perm) + '.*' : null);

                                            if (impliedBy) {
                                                cb.checked = true;
                                                cb.disabled = true;
                                                if (note) note.textContent = 'granted via ' + impliedBy + ' - uncheck that to remove this';
                                            } else {
                                                cb.disabled = false;
                                                cb.checked = cb.dataset.direct === '1';
                                                if (note) note.textContent = '';
                                            }
                                        });
                                    }

                                    wildcardBoxes.forEach(function (cb) { cb.addEventListener('change', recompute); });
                                    recompute();
                                })();
                            </script>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
