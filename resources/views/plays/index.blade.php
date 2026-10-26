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
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="card card-body py-2 px-3">
                        <div class="d-flex flex-row align-items-center flex-wrap justify-content-between" style="gap: 16px;">
                            <div class="form-check mb-0">
                                <input type="checkbox" class="form-check-input" id="starterHeaderCheckbox">
                                <label class="form-check-label" for="starterHeaderCheckbox">
                                    Toggle "Starter" for every Global Play shown on this page
                                </label>
                            </div>
                            <div class="d-flex align-items-center" style="gap: 10px;">
                                <a href="#" id="starterAcrossPagesLink" class="btn btn-outline-primary btn-sm mb-0" style="display:none;"></a>
                                <button type="button" id="playFiltersToggle" class="btn btn-outline-secondary btn-sm mb-0">
                                    Filters <span id="playFiltersBadge" class="badge badge-primary" style="display:none;"></span>
                                </button>
                            </div>
                        </div>

                        <div id="playFiltersPanel" style="display:none; margin-top: 14px; border-top: 1px solid #e9ecef; padding-top: 14px;">
                            <div class="d-flex flex-wrap" style="gap: 28px;">
                                <div>
                                    <div class="d-flex align-items-center justify-content-between mb-1" style="width: 180px;">
                                        <strong class="small text-uppercase text-muted">Strategies</strong>
                                        <span>
                                            <a href="#" class="small play-filter-select-all" data-group="strategies">All</a> /
                                            <a href="#" class="small play-filter-clear-group" data-group="strategies">None</a>
                                        </span>
                                    </div>
                                    <label class="d-block mb-0"><input type="checkbox" class="play-filter-checkbox" data-group="strategies" value="regular"> Regular</label>
                                    <label class="d-block mb-0"><input type="checkbox" class="play-filter-checkbox" data-group="strategies" value="red zone"> Red Zone</label>
                                    <label class="d-block mb-0"><input type="checkbox" class="play-filter-checkbox" data-group="strategies" value="hurry up"> Hurry Up</label>
                                    <label class="d-block mb-0"><input type="checkbox" class="play-filter-checkbox" data-group="strategies" value="aggressive"> Aggressive</label>
                                    <label class="d-block mb-0"><input type="checkbox" class="play-filter-checkbox" data-group="strategies" value="chew clock"> Chew Clock</label>
                                </div>

                                <div>
                                    <div class="d-flex align-items-center justify-content-between mb-1" style="width: 150px;">
                                        <strong class="small text-uppercase text-muted">Preferred Down</strong>
                                        <span>
                                            <a href="#" class="small play-filter-select-all" data-group="preferred_down">All</a> /
                                            <a href="#" class="small play-filter-clear-group" data-group="preferred_down">None</a>
                                        </span>
                                    </div>
                                    <label class="d-block mb-0"><input type="checkbox" class="play-filter-checkbox" data-group="preferred_down" value="1"> 1st</label>
                                    <label class="d-block mb-0"><input type="checkbox" class="play-filter-checkbox" data-group="preferred_down" value="2"> 2nd</label>
                                    <label class="d-block mb-0"><input type="checkbox" class="play-filter-checkbox" data-group="preferred_down" value="3"> 3rd</label>
                                    <label class="d-block mb-0"><input type="checkbox" class="play-filter-checkbox" data-group="preferred_down" value="4"> 4th</label>
                                </div>

                                <div>
                                    <strong class="small text-uppercase text-muted d-block mb-1">Yardage Gain</strong>
                                    <select id="yardageGainFilter" class="form-control form-control-sm" style="width: 160px;">
                                        <option value="">All</option>
                                        <option value="short">Short (1-4)</option>
                                        <option value="medium">Medium (4-7)</option>
                                        <option value="long">Long (7+)</option>
                                        <option value="open_down">Open Down</option>
                                    </select>
                                </div>

                                <div>
                                    <strong class="small text-uppercase text-muted d-block mb-1">Play Target</strong>
                                    <select id="playTargetFilter" class="form-control form-control-sm" style="width: 140px;">
                                        <option value="">All</option>
                                        <option value="offensive">Offensive</option>
                                        <option value="defensive">Defensive</option>
                                    </select>
                                </div>

                                <div class="align-self-end">
                                    <button type="button" id="clearAllPlayFiltersBtn" class="btn btn-sm btn-link">Clear all filters</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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

            var table = $('.data-table').DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,  
                ajax: {
                    url: "{{ route('play.index') }}",
                    data: function (d) {
                        d.role = $('#roleFilter').val();
                        d.strategies = getCheckedFilterValues('strategies').join(',');
                        d.preferred_down = getCheckedFilterValues('preferred_down').join(',');
                        d.yardage_gain = $('#yardageGainFilter').val();
                        d.play_target = $('#playTargetFilter').val();
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

            function getCheckedFilterValues(group) {
                return $('.play-filter-checkbox[data-group="' + group + '"]:checked')
                    .map(function () { return $(this).val(); })
                    .get();
            }

            function updatePlayFiltersBadge() {
                const count = getCheckedFilterValues('strategies').length
                    + getCheckedFilterValues('preferred_down').length
                    + ($('#yardageGainFilter').val() ? 1 : 0)
                    + ($('#playTargetFilter').val() ? 1 : 0);
                if (count > 0) {
                    $('#playFiltersBadge').text(count).show();
                } else {
                    $('#playFiltersBadge').hide();
                }
            }

            $('#playFiltersToggle').on('click', function () {
                $('#playFiltersPanel').slideToggle(150);
            });

            $(document).on('change', '.play-filter-checkbox, #yardageGainFilter, #playTargetFilter', function () {
                updatePlayFiltersBadge();
                table.draw();
            });

            $(document).on('click', '.play-filter-select-all', function (e) {
                e.preventDefault();
                const group = $(this).data('group');
                $('.play-filter-checkbox[data-group="' + group + '"]').prop('checked', true);
                updatePlayFiltersBadge();
                table.draw();
            });

            $(document).on('click', '.play-filter-clear-group', function (e) {
                e.preventDefault();
                const group = $(this).data('group');
                $('.play-filter-checkbox[data-group="' + group + '"]').prop('checked', false);
                updatePlayFiltersBadge();
                table.draw();
            });

            $('#clearAllPlayFiltersBtn').on('click', function () {
                $('.play-filter-checkbox').prop('checked', false);
                $('#yardageGainFilter, #playTargetFilter').val('');
                updatePlayFiltersBadge();
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
                        strategies: getCheckedFilterValues('strategies').join(','),
                        preferred_down: getCheckedFilterValues('preferred_down').join(','),
                        yardage_gain: $('#yardageGainFilter').val(),
                        play_target: $('#playTargetFilter').val(),
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
