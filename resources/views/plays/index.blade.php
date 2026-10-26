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

                        <div id="playFiltersPanel" class="play-filters-panel" style="display:none;">
                            <div class="play-filter-group">
                                <div class="play-filter-group__header">
                                    <span class="play-filter-group__title">Strategies</span>
                                    <span class="play-filter-group__links">
                                        <a href="#" class="play-filter-select-all" data-group="strategies">All</a><span class="play-filter-group__sep">/</span><a href="#" class="play-filter-clear-group" data-group="strategies">None</a>
                                    </span>
                                </div>
                                <div class="play-filter-pills">
                                    <label class="play-filter-pill"><input type="checkbox" class="play-filter-checkbox" data-group="strategies" value="regular"><span>Regular</span></label>
                                    <label class="play-filter-pill"><input type="checkbox" class="play-filter-checkbox" data-group="strategies" value="red zone"><span>Red Zone</span></label>
                                    <label class="play-filter-pill"><input type="checkbox" class="play-filter-checkbox" data-group="strategies" value="hurry up"><span>Hurry Up</span></label>
                                    <label class="play-filter-pill"><input type="checkbox" class="play-filter-checkbox" data-group="strategies" value="aggressive"><span>Aggressive</span></label>
                                    <label class="play-filter-pill"><input type="checkbox" class="play-filter-checkbox" data-group="strategies" value="chew clock"><span>Chew Clock</span></label>
                                </div>
                            </div>

                            <div class="play-filter-group">
                                <div class="play-filter-group__header">
                                    <span class="play-filter-group__title">Preferred Down</span>
                                    <span class="play-filter-group__links">
                                        <a href="#" class="play-filter-select-all" data-group="preferred_down">All</a><span class="play-filter-group__sep">/</span><a href="#" class="play-filter-clear-group" data-group="preferred_down">None</a>
                                    </span>
                                </div>
                                <div class="play-filter-pills">
                                    <label class="play-filter-pill"><input type="checkbox" class="play-filter-checkbox" data-group="preferred_down" value="1"><span>1st</span></label>
                                    <label class="play-filter-pill"><input type="checkbox" class="play-filter-checkbox" data-group="preferred_down" value="2"><span>2nd</span></label>
                                    <label class="play-filter-pill"><input type="checkbox" class="play-filter-checkbox" data-group="preferred_down" value="3"><span>3rd</span></label>
                                    <label class="play-filter-pill"><input type="checkbox" class="play-filter-checkbox" data-group="preferred_down" value="4"><span>4th</span></label>
                                </div>
                            </div>

                            <div class="play-filter-group play-filter-group--select">
                                <span class="play-filter-group__title">Yardage Gain</span>
                                <select id="yardageGainFilter" class="play-filter-select">
                                    <option value="">All</option>
                                    <option value="short">Short (1-4)</option>
                                    <option value="medium">Medium (4-7)</option>
                                    <option value="long">Long (7+)</option>
                                    <option value="open_down">Open Down</option>
                                </select>
                            </div>

                            <div class="play-filter-group play-filter-group--select">
                                <span class="play-filter-group__title">Play Target</span>
                                <select id="playTargetFilter" class="play-filter-select">
                                    <option value="">All</option>
                                    <option value="offensive">Offensive</option>
                                    <option value="defensive">Defensive</option>
                                </select>
                            </div>

                            <div class="play-filter-footer">
                                <button type="button" id="clearAllPlayFiltersBtn" class="play-filter-clear-all">Clear all filters</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <style>
                .play-filters-panel {
                    margin-top: 14px;
                    padding-top: 16px;
                    border-top: 1px solid #e9ecef;
                    display: flex;
                    flex-wrap: wrap;
                    align-items: stretch;
                    gap: 16px;
                }
                .play-filter-group {
                    background: #f8f9fa;
                    border: 1px solid #e9ecef;
                    border-radius: 8px;
                    padding: 10px 14px 12px;
                    min-width: 180px;
                }
                .play-filter-group--select {
                    display: flex;
                    flex-direction: column;
                    gap: 8px;
                    min-width: 160px;
                    justify-content: flex-start;
                }
                .play-filter-group__header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 10px;
                    margin-bottom: 8px;
                }
                .play-filter-group__title {
                    font-size: 11px;
                    font-weight: 700;
                    letter-spacing: 0.5px;
                    text-transform: uppercase;
                    color: #6c757d;
                    white-space: nowrap;
                }
                .play-filter-group__links {
                    font-size: 11.5px;
                    white-space: nowrap;
                }
                .play-filter-group__links a {
                    color: #0d6efd;
                    text-decoration: none;
                    font-weight: 600;
                }
                .play-filter-group__links a:hover { text-decoration: underline; }
                .play-filter-group__sep { margin: 0 4px; color: #ced4da; }

                .play-filter-pills {
                    display: flex;
                    flex-direction: column;
                    gap: 5px;
                }
                .play-filter-pill {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    font-size: 13px;
                    color: #495057;
                    cursor: pointer;
                    margin: 0;
                    user-select: none;
                }
                .play-filter-pill input {
                    width: 15px;
                    height: 15px;
                    margin: 0;
                    cursor: pointer;
                    accent-color: #0d6efd;
                }

                .play-filter-select {
                    padding: 7px 10px;
                    border: 1px solid #ced4da;
                    border-radius: 6px;
                    font-size: 13px;
                    color: #495057;
                    background: #ffffff;
                    min-width: 160px;
                }
                .play-filter-select:focus {
                    outline: none;
                    border-color: #0d6efd;
                    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.12);
                }

                .play-filter-footer {
                    display: flex;
                    align-items: center;
                }
                .play-filter-clear-all {
                    background: none;
                    border: 1px solid #dc3545;
                    color: #dc3545;
                    border-radius: 6px;
                    font-size: 12px;
                    font-weight: 600;
                    padding: 7px 14px;
                    cursor: pointer;
                    transition: background 0.15s, color 0.15s;
                    white-space: nowrap;
                }
                .play-filter-clear-all:hover { background: #dc3545; color: #ffffff; }
            </style>
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
