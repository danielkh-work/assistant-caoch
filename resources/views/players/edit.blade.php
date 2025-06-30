@extends('layouts.main_layout')
@section('content')
<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">Edit Player</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                    <li class="breadcrumb-item active">Edit Player</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" action="{{ route('players.update', $player->id) }}">
                            @csrf
                            @method('PUT')

                            <div class="row">
                                <div class="col-md-4">
                                    <label>Package</label>
                                    <select name="role_id[]" class="form-control select2" multiple required>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->id }}" 
                                                {{ in_array($role->id, $player->roles->pluck('id')->toArray()) ? 'selected' : '' }}>
                                                {{ $role->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label>Name</label>
                                    <input type="text" name="title" value="{{ $player->name }}" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label>Image</label>
                                    <input type="file" name="image" class="form-control">
                                    @if($player->image)
                                        <img src="{{ asset('uploads/' . $player->image) }}" width="50" class="mt-2">
                                    @endif
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <label>Number</label>
                                    <input name="number" class="form-control" value="{{ $player->number }}">
                                </div>
                                <div class="col-md-4">
                                    <label>Position</label>
                                    <select name="position" class="form-control">
                                        <option value="1" {{ $player->position == 1 ? 'selected' : '' }}>offence</option>
                                        <option value="2" {{ $player->position == 2 ? 'selected' : '' }}>deffence</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label>Size</label>
                                    <input type="text" name="size" value="{{ $player->size }}" class="form-control">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <label>Speed</label>
                                    <input type="text" name="speed" value="{{ $player->speed }}" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label>Type</label>
                                    <select name="type" class="form-control">
                                        <option value="offensive" {{ $player->type == 'offensive' ? 'selected' : '' }}>offence</option>
                                        <option value="deffence" {{ $player->type == 'deffence' ? 'selected' : '' }}>deffence</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label>Strength</label>
                                    <input type="text" name="strength" value="{{ $player->strength }}" class="form-control">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <label>Weight</label>
                                    <input type="text" name="weight" value="{{ $player->weight }}" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label>Height</label>
                                    <input type="text" name="height" value="{{ $player->height }}" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label>DOB</label>
                                    <input type="date" name="dob" value="{{ $player->dob }}" class="form-control">
                                </div>
                            </div>

                            <br>
                            <div class="row">
                                <div class="col-md-12">
                                    <input class="btn btn-success" type="submit" value="Update Player">
                                </div>
                            </div>
                        </form>
                    </div> <!-- /.card-body -->
                </div> <!-- /.card -->
            </div>
        </div>
    </div>
</section>

<script>
    @if (session('success'))
        toastr.success("{{ session('success') }}");
    @endif
    @if (session('error'))
        toastr.error("{{ session('error') }}");
    @endif
</script>
@endsection
