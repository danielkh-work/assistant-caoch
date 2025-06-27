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
                <table class="table table-bordered data-table">
                    <thead>
                    <tr>
                        <th>No</th>
                        <th>Play Name</th>
                        <th>Play Type</th>
                        <th>Zone Selection</th>
                        <th>Min Expected Yard</th>
                        <th>Max Expected Yard</th>
                        <th>pre_snap_motion</th>
                        <th>play_action_fake</th>
                        <th>video_path</th>
                        <th>type</th>
                        <th>preferred_down</th>
                        <th>possession</th>
                        <th>description</th>
                        <th>position_status</th>
                        <th>strategies</th>
                        <th>quarter</th>
                        <th width="100px">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
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
                ajax: "{{ route('play.index') }}",
                columns: [
                    {data: 'id', name: 'id'},
                    {data: 'play_name', name: 'play_name'},
                    {data: 'play_type', name: 'play_type'},
                    {data: 'zone_selection', name: 'zone_selection'},
                    {data: 'min_expected_yard', name: 'min_expected_yard'},
                    {data: 'max_expected_yard', name: 'max_expected_yard'},
                    {data: 'pre_snap_motion', name: 'pre_snap_motion'},
                    {data: 'play_action_fake', name: 'play_action_fake'},
                    {data: 'video_path', name: 'video_path'},
                    {data: 'type', name: 'image'},
                    {data: 'preferred_down', name: 'preferred_down'},
                    {data: 'possession', name: 'possession'},
                    {data: 'description', name: 'description'},
                    {data: 'position_status', name: 'position_status'},
                    {data: 'strategies', name: 'strategies'},
                    {data: 'quarter', name: 'quarter'},
                    {data: 'action', name: 'action', orderable: false, searchable: false},
                ],
                order: [0, 'desc']
            });

        });
    </script>
@endsection
