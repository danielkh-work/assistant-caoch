@extends('layouts.main_layout')
@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">Add Player</h1>
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
            <div class="card">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" action="{{ route('players.store') }}">
                      @csrf
                    <div class="row">
                           <div class="col-md-4">
                                        <label for="">Package</label>
                                        <select name="role_id[]" class="form-control select2" multiple id="" required>
                                            <option value="">Select Package</option>
                                            @foreach ($roles as $role)
                                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                            <div class="col-md-4">
                                <label for="">Name</label>
                                <input type="name" name="title" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label for="">Image</label>
                                <input type="file" name="image" class="form-control">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <label for="">Number</label>
                              <input name="number" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label for="">Position</label>
                                <select name="position" id="" class="form-control">
                                    <option value="1">offence</option>
                                    <option value="2">deffence</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="">size</label>
                              <input type="text" name="size" class="form-control">
                            </div>

                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <label for="">Speed</label>
                              <input type="text" name="speed" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label for="">Type</label>
                                <select name="type" id="" class="form-control">
                                    <option value="offensive">offence</option>
                                    <option value="deffence">deffence</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="">Strength</label>
                              <input type="text" name="strength" class="form-control">
                            </div>

                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <label for="">weight</label>
                              <input type="text" name="weight" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label for="">height</label>
                                <input type="text"  name="height" class="form-control">
                                
                            </div>
                            <div class="col-md-4">
                                <label for="">dob</label>
                              <input type="date" name="dob" class="form-control">
                            </div>

                        </div>
                        <br>
                        <div class="row">
                             <div class="col-md-12">
                            <input class="btn btn-success" type="submit" value="submit">
                             </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>

        </div>
        </div><!-- /.container-fluid -->
    </section>
<script>
    @if (session('success'))
        toastr.success("{{ session('success') }}");
    @endif

    @if (session('error'))
        toastr.error("{{ session('error') }}");
    @endif

    @if (session('info'))
        toastr.info("{{ session('info') }}");
    @endif

    @if (session('warning'))
        toastr.warning("{{ session('warning') }}");
    @endif
</script>
@endsection


