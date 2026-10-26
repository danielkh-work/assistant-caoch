@extends('layouts.main_layout')
@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">Plays</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Plays</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->
    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                <a href="{{ route('play.create') }}" class="btn btn-success">Uploads Play</a>
                <br>
                </div>
            </div>
            <br>
            <!-- Small boxes (Stat box) -->
            <div class="row">
                 <div class="table-responsive">
                    <table class="table table-bordered data-table">
                        <thead>
                        <tr>
                            <th>No</th>
                            <th>Play Name</th>
                            <th>Roles</th>
                            <th>Min Expected Yard</th>
                            <th>preferred_down</th>
                            <th>possession</th>
                            <th>description</th>
                            <th>strategies</th>
                            <th width="90px">
                                Starter
                                <br>
                                <input type="checkbox" id="starterHeaderCheckbox" title="Set starter status for all Global Plays on this page">
                                <a href="#" id="starterAcrossPagesLink" style="display:none;"></a>
                            </th>
                            <th width="100px">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                 </div>
            </div>
            <!-- /.row -->
            <!-- Main row -->
            <!-- /.row (main row) -->
        </div><!-- /.container-fluid -->
    </section>

@endsection

@section('script')
    <script type="text/javascript">
        $(function () {

            var table = $('.data-table').DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,  
                ajax: {
                    url: "{{ route('play.index') }}",
                    data: function (d) {
                        d.role = $('#roleFilter').val();
                    }
                },
                columns: [
                    {data: 'id', name: 'id'},
                    {data: 'play_name', name: 'play_name'},
                    {data: 'roles', name: 'roles'},
                    {data: 'min_expected_yard', name: 'min_expected_yard'},
                    {data: 'preferred_down', name: 'preferred_down'},
                    {data: 'possession', name: 'possession'},
                    {data: 'description', name: 'description'},
                    {data: 'strategies', name: 'strategies'},
                    {data: 'starter', name: 'starter', orderable: false, searchable: false},
                    {data: 'action', name: 'action', orderable: false, searchable: false},
                ],
                order: [0, 'desc'],
                dom: '<"row mb-2 align-items-center"<"col-md-4"l><"col-md-4 text-center"<"#custom-filter-wrapper">><"col-md-4 text-right"f>>rt<"row mt-2"<"col-md-6"i><"col-md-6"p>>',
            });

              $('#custom-filter-wrapper').html(`
                <label class="mb-0">Roles:
                    <select id="roleFilter" class="form-control form-control-sm d-inline-block ml-2" style="width: auto;">
                        <option value="">All</option>
                        <option value="1">Classic basic</option>
                        <option value="2">Classic advance</option>
                        <option value="3">HD HUMAN DASHBOARD</option>
                        <option value="4">Pro basic</option>
                        <option value="5">Pro advance</option>
                    </select>
                </label>
            `);
            $('#roleFilter').on('change', function () {

                table.draw();
            });

            $(document).on('change', '.starter-toggle', function () {
                const checkbox = $(this);
                const id = checkbox.data('id');
                $.ajax({
                    url: '/play/' + id + '/toggle-starter',
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    error: function (xhr) {
                        checkbox.prop('checked', !checkbox.prop('checked')); // revert on failure
                        alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Could not update starter status.');
                    }
                });
            });

            function applyStarter(ids, value, onDone) {
                if (!ids.length) {
                    return;
                }
                $.ajax({
                    url: '{{ route('play.bulk-toggle-starter') }}',
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    data: { ids: ids, starter: value ? 1 : 0 },
                    success: function () {
                        table.ajax.reload(null, false);
                        if (onDone) { onDone(); }
                    },
                    error: function (xhr) {
                        $('#starterHeaderCheckbox').prop('checked', !value);
                        alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Could not update starter status.');
                    }
                });
            }

            $('#starterHeaderCheckbox').on('change', function () {
                const checked = this.checked;
                const ids = $('.starter-toggle').map(function () { return $(this).data('id'); }).get();
                applyStarter(ids, checked, function () {
                    $('#starterAcrossPagesLink')
                        .show()
                        .data('target', checked)
                        .text('Apply to all matching Global Plays across all pages');
                });
            });

            $(document).on('click', '#starterAcrossPagesLink', function (e) {
                e.preventDefault();
                const targetValue = $(this).data('target');
                $.ajax({
                    url: '{{ route('play.global-ids') }}',
                    method: 'GET',
                    data: {
                        role: $('#roleFilter').val(),
                        search: table.search(),
                    },
                    success: function (resp) {
                        applyStarter(resp.ids, targetValue, function () {
                            $('#starterAcrossPagesLink').hide();
                        });
                    }
                });
            });

        });
    </script>
@endsection
