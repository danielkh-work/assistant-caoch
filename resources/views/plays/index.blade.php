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
            <div class="row">
                <div class="col-md-12">
                    <div id="bulkActions" class="mb-2" style="display:none;">
                        <button type="button" id="markStarterBtn" class="btn btn-primary btn-sm">Mark Selected as Starter</button>
                        <button type="button" id="unmarkStarterBtn" class="btn btn-secondary btn-sm">Unmark Selected as Starter</button>
                        <span id="selectionSummary" class="ml-2"></span>
                        <a href="#" id="selectAllMatchingLink" style="display:none;" class="ml-2"></a>
                        <a href="#" id="clearSelectionLink" class="ml-2">Clear selection</a>
                    </div>
                </div>
            </div>
            <!-- Small boxes (Stat box) -->
            <div class="row">
                 <div class="table-responsive">
                    <table class="table table-bordered data-table">
                        <thead>
                        <tr>
                            <th width="40px"><input type="checkbox" id="selectAllOnPage" title="Select all Global Plays on this page"></th>
                            <th>No</th>
                            <th>Play Name</th>
                            <th>Roles</th>
                            <th>Min Expected Yard</th>
                            <th>preferred_down</th>
                            <th>possession</th>
                            <th>description</th>
                            <th>strategies</th>
                            <th width="80px">Starter</th>
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

            var selectedIds = new Set();

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
                    {data: 'select', name: 'select', orderable: false, searchable: false},
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
                order: [1, 'desc'],
                dom: '<"row mb-2 align-items-center"<"col-md-4"l><"col-md-4 text-center"<"#custom-filter-wrapper">><"col-md-4 text-right"f>>rt<"row mt-2"<"col-md-6"i><"col-md-6"p>>',
                drawCallback: function () {
                    $('.row-select').each(function () {
                        const id = $(this).data('id');
                        $(this).prop('checked', selectedIds.has(id));
                    });
                },
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

            function updateBulkUI() {
                if (selectedIds.size > 0) {
                    $('#bulkActions').show();
                    $('#selectionSummary').text(selectedIds.size + ' selected');
                } else {
                    $('#bulkActions').hide();
                }
            }

            $(document).on('change', '.row-select', function () {
                const id = $(this).data('id');
                if (this.checked) {
                    selectedIds.add(id);
                } else {
                    selectedIds.delete(id);
                    $('#selectAllOnPage').prop('checked', false);
                }
                updateBulkUI();
            });

            $('#selectAllOnPage').on('change', function () {
                const checked = this.checked;
                $('.row-select').each(function () {
                    $(this).prop('checked', checked);
                    const id = $(this).data('id');
                    if (checked) {
                        selectedIds.add(id);
                    } else {
                        selectedIds.delete(id);
                    }
                });
                updateBulkUI();
                if (checked) {
                    $('#selectAllMatchingLink').show().text('Select all matching Global Plays across all pages');
                } else {
                    $('#selectAllMatchingLink').hide();
                }
            });

            $('#selectAllMatchingLink').on('click', function (e) {
                e.preventDefault();
                $.ajax({
                    url: '{{ route('play.global-ids') }}',
                    method: 'GET',
                    data: {
                        role: $('#roleFilter').val(),
                        search: table.search(),
                    },
                    success: function (resp) {
                        resp.ids.forEach(function (id) { selectedIds.add(id); });
                        $('.row-select').each(function () {
                            $(this).prop('checked', selectedIds.has($(this).data('id')));
                        });
                        updateBulkUI();
                        $('#selectAllMatchingLink').hide();
                    }
                });
            });

            $('#clearSelectionLink').on('click', function (e) {
                e.preventDefault();
                selectedIds.clear();
                $('.row-select, #selectAllOnPage').prop('checked', false);
                $('#selectAllMatchingLink').hide();
                updateBulkUI();
            });

            function bulkToggle(starterValue) {
                if (selectedIds.size === 0) {
                    return;
                }
                $.ajax({
                    url: '{{ route('play.bulk-toggle-starter') }}',
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    data: {
                        ids: Array.from(selectedIds),
                        starter: starterValue,
                    },
                    success: function () {
                        selectedIds.clear();
                        $('#selectAllOnPage').prop('checked', false);
                        $('#selectAllMatchingLink').hide();
                        updateBulkUI();
                        table.ajax.reload(null, false);
                    },
                    error: function (xhr) {
                        alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Could not update starter status for the selected plays.');
                    }
                });
            }

            $('#markStarterBtn').on('click', function () { bulkToggle(true); });
            $('#unmarkStarterBtn').on('click', function () { bulkToggle(false); });

        });
    </script>
@endsection
