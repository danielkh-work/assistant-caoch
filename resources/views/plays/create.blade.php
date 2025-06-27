@extends('layouts.main_layout')
@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">Upload Play</h1>
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
            <div class="card">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" action="{{ route('play.store') }}">
                      @csrf
                    <div class="row">
                            <div class="col-md-3">
                                <label for="">league</label>
                               <select name="" id="" class="form-control">
                                    @foreach ($league as $leagu)
                                        <option value="{{ $leagu->id }}">{{ $leagu->title }}</option>
                                    @endforeach
                               </select>
                            </div>
                            <div class="col-md-3">
                                <label for="">Name</label>
                                <input type="name" name="title" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label for="">Image</label>
                                <input type="file" name="image" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label for="">Video</label>
                                <input type="file" name="image" class="form-control">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <label for="">play_name</label>
                              <input name="play_name" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label for="">type</label>
                                <select name="type" id="" class="form-control">
                                    <option value="1">offence</option>
                                    <option value="2">deffence</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="">preferred_down</label>
                              <input type="text" name="preferred_down" class="form-control">
                            </div>

                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <label for="">description</label>
                              <input name="description" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label for="">possession</label>
                                <select name="possession" id="" class="form-control">
                                    <option value="1">offence</option>
                                    <option value="2">deffence</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="">strategies</label>
                              <input type="text" name="strategies" class="form-control">
                            </div>

                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <label for="">play_type</label>
                              <input name="play_type" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label for="">zone_selection</label>
                                 <input name="zone_selection" class="form-control">
                                {{-- <select name="zone_selection" id="" class="form-control">
                                    <option value="1">offence</option>
                                    <option value="2">deffence</option>
                                </select> --}}
                            </div>
                            <div class="col-md-4">
                                <label for="">min_expected_yard</label>
                              <input type="text" name="min_expected_yard" class="form-control">
                            </div>

                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <label for="">max_expected_yard</label>
                              <input type="text" name="max_expected_yard" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label for=""> Target (Offensive)</label>
                                <select name="target_offensive" id="" class="form-control">
                                    <option value="offensive">offence</option>
                                    <option value="deffence">deffence</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="">Opposing (Defensive)</label>
                                 <select name="opposing_defensive" id="" class="form-control">
                                    <option value="offensive">offence</option>
                                    <option value="deffence">deffence</option>
                                 </select>
                            </div>

                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <label for="">pre_snap_motion</label>
                              <input type="text" name="pre_snap_motion" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label for="">play_action_fake</label>
                                <input type="text"  name="play_action_fake" class="form-control">
                                
                            </div>
                            <div class="col-md-4">
                                <label for="">perfer_down_selection</label>
                                 <select name="perfer_down_selection" id="" class="form-control">
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                </select>
                             
                            </div>
                            <div class="col-md-4">
                                <label for="">quarter</label>
                              <input type="text" name="quarter" class="form-control">
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


