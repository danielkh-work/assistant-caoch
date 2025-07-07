@extends('layouts.main_layout')
@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">Player</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Player</li>
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
                <a href="{{ route('players.create') }}" class="btn btn-success">Add Player</a>
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
                        <th>Created By</th>
                        <th>Number</th>
                        <th>Position</th>
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
                    url: "{{ route('players.index') }}",
                    data: function (d) {
                        d.role = $('#roleFilter').val();
                    }
                },
                columns: [
                    {data: 'id', name: 'id'},
                    {data: 'name', name: 'name'},
                    {data: 'roles', name: 'roles'},
                    {data: 'created_by', name: 'created_by'},
                    
                    {data: 'number', name: 'number'},
                    {data: 'position', name: 'position'},
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

        });
    </script>
@endsection
