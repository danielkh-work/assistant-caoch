@extends('layouts.main_layout')
@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">league</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">league</li>
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
                <a href="{{ route('league.create') }}" class="btn btn-success">Add league</a>
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
                            <th>Name</th>
                            <th>Roles</th>
                            <th>number_of_team</th>
                            <th>number_of_downs</th>
                            <th>length_of_field</th>
                            <th>number_of_timeouts</th>
                            <th>clock_time</th>
                            <th>number_of_quarters</th>
                            <th>length_of_quarters</th>
                            <th>stop_time_reason</th>
                            <th>overtime_rules</th>
                            <th>number_of_players</th>
                            <th>flag_tbd</th>
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
                 dom: '<"row mb-2 align-items-center"<"col-md-4"l><"col-md-4 text-center"<"#role-filter-wrapper">><"col-md-4 text-right"f>>rt<"row mt-2"<"col-md-6"i><"col-md-6"p>>',
                processing: true,
                serverSide: true,
                autoWidth: false,
                 ajax: {
                    url: "{{ route('league.index') }}",
                    data: function (d) {
                        d.role = $('#roleFilter').val();
                    }
                },
                columns: [
                    {data: 'id', name: 'id'},
                    {data: 'title', name: 'title'},
                    {data: 'roles', name: 'roles'},
                    {data: 'number_of_team', name: 'number_of_team'},
                    {data: 'number_of_downs', name: 'number_of_downs'},
                    {data: 'length_of_field', name: 'length_of_field'},
                    {data: 'number_of_timeouts', name: 'number_of_timeouts'},
                    {data: 'clock_time', name: 'clock_time'},
                    {data: 'number_of_quarters', name: 'number_of_quarters'},
                    {data: 'length_of_quarters', name: 'length_of_quarters'},
                    {data: 'stop_time_reason', name: 'stop_time_reason'},
                    {data: 'overtime_rules', name: 'overtime_rules'},
                    {data: 'number_of_players', name: 'number_of_players'},
                    {data: 'flag_tbd', name: 'flag_tbd'},
                    {data: 'action', name: 'action', orderable: false, searchable: false},
                ],
                order: [0, 'desc'],
                
            });

                
                $('#role-filter-wrapper').html(`
                    <label class="mb-0">Filter by Role:
                        <select id="roleFilter" class="form-control form-control-sm ml-2" style="width: 200px;">
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

         

        });
    </script>
@endsection
