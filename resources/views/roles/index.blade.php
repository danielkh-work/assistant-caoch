@extends('layouts.main_layout')
@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">Roles</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                        <li class="breadcrumb-item active">Roles</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <section class="content">
        <div class="container-fluid">
            <div class="card roles-card">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
                    <div>
                        <h5 class="mb-0">All roles</h5>
                        <small class="text-muted">What each role can do, at a glance - open one to change it.</small>
                    </div>
                    <a href="{{ route('roles.create') }}" class="btn btn-primary mt-2 mt-sm-0">
                        <i class="fas fa-plus mr-1"></i> Add role
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover roles-table data-table">
                            <thead>
                            <tr>
                                <th>No</th>
                                <th>Name</th>
                                <th>Permissions</th>
                                <th width="200px">Action</th>
                            </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <style>
        .roles-card .card-header { background: #fff; }
        .roles-table td { vertical-align: middle; }
        .roles-table .permissions-cell { line-height: 1.9; }

        .perm-badge {
            display: inline-block;
            padding: .18rem .55rem;
            margin: .1rem .2rem .1rem 0;
            border-radius: 1rem;
            background: #f0f2f7;
            color: #495057;
            font-size: .74rem;
            white-space: nowrap;
        }
        .perm-badge b { font-weight: 700; color: #2c3345; }
        .perm-badge--all {
            background: linear-gradient(135deg, #eef2ff 0%, #f7f9ff 100%);
            border: 1px solid #b8c8f5;
            color: #2c3345;
            font-weight: 600;
        }
        .perm-badge--all i { margin-right: .3rem; }

        .roles-table .btn-group .btn { display: inline-flex; align-items: center; gap: .3rem; }
    </style>
@endsection

@section('script')
    <script type="text/javascript">
        $(function () {
            var table = $('.data-table').DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                ajax: { url: "{{ route('roles.index') }}" },
                columns: [
                    {data: 'id', name: 'id'},
                    {data: 'name', name: 'name'},
                    {data: 'permissions', name: 'permissions', className: 'permissions-cell', orderable: false},
                    {data: 'action', name: 'action', orderable: false, searchable: false},
                ],
                order: [0, 'desc'],
            });

            // Delete buttons are rendered fresh on every DataTables redraw, so this
            // is delegated on the table body rather than bound per-button.
            $('.roles-table').on('click', '.role-delete-btn', function () {
                var formId = $(this).data('form');
                var name = $(this).data('name');
                if (confirm('Delete the "' + name + '" role? This cannot be undone.')) {
                    document.getElementById(formId).submit();
                }
            });

            @if (session('success'))
                toastr.success("{{ session('success') }}");
            @endif
        });
    </script>
@endsection
