@extends('layouts.main_layout')
@section('content')
<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">Edit Play</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                    <li class="breadcrumb-item active">Plays</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- /.content-header -->

<section class="content">
    <div class="container-fluid">
        <div class="row">
        <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" action="{{ route('play.update', $play->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-3">
                            <label for="">League</label>
                            <select required name="league_id" class="form-control">
                                @foreach ($league as $leagu)
                                    <option value="{{ $leagu->id }}" {{ $play->league_id == $leagu->id ? 'selected' : '' }}>
                                        {{ $leagu->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="">Name</label>
                            <input required type="text" name="play_name" class="form-control" value="{{ old('play_name', $play->play_name) }}">
                        </div>
                        <div class="col-md-3">
                            <label for="">Image</label>
                            <input type="file" name="image" class="form-control">
                            @if($play->image)
                                <div class="mt-2">
                                    <img src="{{ asset('storage/' . $play->image) }}" alt="Current Image" width="100">
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-4">
                            <label for="">Expected Yardage Gain</label>
                            <input type="text" required name="min_expected_yard" class="form-control" value="{{ old('min_expected_yard', $play->min_expected_yard) }}">
                        </div>
                        <div class="col-md-4">
                            <label for="">Possession</label>
                            <select name="possession" class="form-control">
                                <option value="offensive" {{ $play->possession == 'offensive' ? 'selected' : '' }}>offence</option>
                                <option value="defensive" {{ $play->possession == 'defensive' ? 'selected' : '' }}>deffence</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-4">
                            <label for="">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="3">{{ old('description', $play->description) }}</textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold d-block text-start">Preferred Down Selection</label>
                            @php
                                $selectedDowns = is_array($play->preferred_down) ? $play->preferred_down : explode(',', $play->preferred_down ?? '');
                            @endphp
                            @for($i = 1; $i <= 4; $i++)
                                <div class="mb-2 form-check">
                                    <input class="form-check-input" type="checkbox" name="preferred_down[]" value="{{ $i }}" id="down{{ $i }}" {{ in_array($i, $selectedDowns) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="down{{ $i }}">
                                        {{ $i }}{{ $i == 1 ? 'st' : ($i == 2 ? 'nd' : ($i == 3 ? 'rd' : 'th')) }} Down
                                    </label>
                                </div>
                            @endfor
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold d-block text-start">Strategies</label>
                            @php
                                $selectedStrategies = is_array($play->strategies) ? $play->strategies : explode(',', $play->strategies ?? '');
                            @endphp
                            @foreach (['regular', 'red zone', 'hurry up', 'aggressive', 'chew clock'] as $strategy)
                                <div class="mb-2 form-check">
                                    <input class="form-check-input" type="checkbox" name="strategies[]" value="{{ $strategy }}" id="strategy{{ ucfirst(str_replace(' ', '', $strategy)) }}" {{ in_array($strategy, $selectedStrategies) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="strategy{{ ucfirst(str_replace(' ', '', $strategy)) }}">
                                        {{ ucfirst($strategy) }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-4">
                            <h5 class="mb-3">Target Offensive Player</h5>
                            @foreach ($offensive_position as $position)
                         
                                 @php
                                        $matching = $play->offensivePositions->firstWhere('id', $position->id);
                                     
                                        $strength = old('offensive.' . $position->id, $matching ? $matching->pivot->strength : 0);
                                      
                                 @endphp

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
                                            value="{{$strength}}"
                                        >
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="col-md-4">
                            <h5 class="mb-3">Opposing Defensive Player Targeted</h5>
                            @foreach ($defensive_positions as $position)
                                @php
                                        $def_matching = $play->deffensivePositions->firstWhere('id', $position->id);
                                     
                                        $def_strength = old('offensive.' . $position->id, $def_matching ? $def_matching->pivot->strength : 0);
                                      
                                 @endphp
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
                                            value="{{ $def_strength }}"
                                        >
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <br>

                    <div class="row">
                        <div class="col-12 d-flex justify-content-end p-4">
                            <input class="btn btn-success" type="submit" value="Update">
                        </div>
                    </div>
                </form>
            </div>
        </div>
        </div>
        </div>
    </div>
</section>
@endsection
