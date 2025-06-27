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
                               <select required name="league_id" id="" class="form-control">
                                    @foreach ($league as $leagu)
                                        <option value="{{ $leagu->id }}">{{ $leagu->title }}</option>
                                    @endforeach
                               </select>
                            </div>
                            <div class="col-md-3">
                                <label for="">Name</label>
                                <input required type="text" name="play_name" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label for="">Image</label>
                                <input required type="file" name="image" class="form-control">
                            </div>
                          
                        </div>
                      
                        <div class="row">
                             <div class="col-md-4">
                                <label for="">Expected Yardage Gain</label>
                              <input type="text" required name="min_expected_yard" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label for="">possession</label>
                                <select name="possession" id="" class="form-control">
                                    <option value="offensive">offence</option>
                                    <option value="defensive">deffence</option>
                                </select>
                            </div>
                            
                            

                        </div>
                        <div class="row">
                           <div class="col-md-4">
                                <label for="">description</label>
                                 <textarea id="description" name="description" class="form-control" rows="3" placeholder="Enter description"></textarea>
                              
                            </div>

                             <div class="col-md-4">
                                <label class="form-label fw-bold d-block text-start">
                                Preferred Down Selection
                                </label>

                                <div class="mb-2 form-check">
                                <input class="form-check-input" checked type="checkbox" name="preferred_down" value="1" id="down1">
                                <label class="form-check-label" for="down1">
                                    1st Down
                                </label>
                                </div>

                                <div class="mb-2 form-check">
                                <input class="form-check-input" type="checkbox" name="preferred_down" value="2" id="down2">
                                <label class="form-check-label" for="down2">
                                    2nd Down
                                </label>
                                </div>

                                <div class="mb-2 form-check">
                                <input class="form-check-input" type="checkbox" name="preferred_down" value="3" id="down3">
                                <label class="form-check-label" for="down3">
                                    3rd Down
                                </label>
                                </div>

                                <div class="mb-2 form-check">
                                <input class="form-check-input" type="checkbox" name="preferred_down" value="4" id="down4">
                                <label class="form-check-label" for="down4">
                                    4th Down
                                </label>
                                </div>

                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold d-block text-start">Strategies</label>
                                <div class="mb-2 form-check">
                                <input class="form-check-input" checked type="checkbox" name="strategies" value="regular" id="strategyRegular">
                                <label class="form-check-label" for="strategyRegular">
                                    Regular
                                </label>
                                </div>

                                <div class="mb-2 form-check">
                                <input class="form-check-input" type="checkbox" name="strategies" value="red zone" id="strategyRedZone">
                                <label class="form-check-label" for="strategyRedZone">
                                    Red Zone
                                </label>
                                </div>

                                <div class="mb-2 form-check">
                                <input class="form-check-input" type="checkbox" name="strategies" value="hurry up" id="strategyHurryUp">
                                <label class="form-check-label" for="strategyHurryUp">
                                    Hurry Up
                                </label>
                                </div>

                                <div class="mb-2 form-check">
                                <input class="form-check-input" type="checkbox" name="strategies" value="aggressive" id="strategyAggressive">
                                <label class="form-check-label" for="strategyAggressive">
                                    Aggressive
                                </label>
                                </div>

                                <div class="mb-2 form-check">
                                <input class="form-check-input" type="checkbox" name="strategies" value="chew clock" id="strategyChewClock">
                                <label class="form-check-label" for="strategyChewClock">
                                    Chew Clock
                                </label>
                                </div>
                                {{--  <label for="">strategies</label>
                              <input type="text" name="strategies" class="form-control">  --}}
                            </div>

                        </div>

                        <div class="row">
                             <div class="col-md-4">
                                
                                <h5 class="mb-3">Target Offensive Player</h5>

                                @foreach ($offensive_position as $position)
                                    <div class="row align-items-center mb-2">
                                    <div class="col-6">
                                        <strong>{{ $position['name'] }}</strong>
                                    </div>
                                    <div class="col-6">
                                        <input
                                        type="number"
                                        name="offensive[{{ $position['id'] }}]"
                                        min="0"
                                        max="5"
                                        class="form-control"
                                        placeholder="Enter play details for {{ $position['name'] }}"
                                         value="{{ old('offensive.' . $position['id'], 0) }}"
                                        >
                                    </div>
                                    </div>
                                @endforeach
                                </div>
                                <div class="col-md-4">
                                    <h5 class="mb-3">Opposing Defensive Player Targeted</h5>

                                    @foreach ($defensive_positions as $position)
                                        <div class="row align-items-center mb-2">
                                        <div class="col-6">
                                            <strong>{{ $position['name'] }}</strong>
                                        </div>
                                        <div class="col-6">
                                            <input
                                            type="number"
                                            name="defensive[{{ $position['id'] }}]"
                                            min="0"
                                            max="5"
                                            class="form-control"
                                            placeholder="Enter play details for {{ $position['name'] }}"
                                            value="{{ old('defensive.' . $position['id'], 0) }}"
                                            >
                                        </div>
                                        </div>
                                    @endforeach
                                    </div>

                             </div>
                        </div>
                    
                        <br>
                       
                        <div class="row">
                            <div class="col-12 d-flex justify-content-end p-4">
                            <input class="btn btn-success" type="submit" value="Submit">
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


