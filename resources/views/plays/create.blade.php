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
{{--                         <div class="col-md-3">--}}
{{--                                        <label for="">Package</label>--}}
{{--                                        <select name="role_id[]" class="form-control select2" multiple id="" required>--}}
{{--                                            <option value="">Select Package</option>--}}
{{--                                            @foreach ($roles as $role)--}}
{{--                                                <option value="{{ $role->id }}">{{ $role->name }}</option>--}}
{{--                                            @endforeach--}}
{{--                                        </select>--}}
{{--                                    </div>--}}
                        <div class="col-md-3">
                            <label for="">League</label>
                            <select name="league_id" class="form-control">
                                <option value="" >— Global (no league) —</option>
                                @foreach ($league as $leagu)
                                    <option value="{{ $leagu->id }}" >
                                        {{ $leagu->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                            <div class="col-md-3">
                                <label for="">Name</label>
                                <input required type="text" name="play_name" class="form-control">
                            </div>

                        <div class="row mt-3">
                            @foreach (['hmark_left' => 'Left', 'hmark_center' => 'Center', 'hmark_right' => 'Right'] as $hmarkField => $label)
                                <div class="col-md-3 mb-3">
                                    <label for="{{ $hmarkField }}">{{ $label }} Image</label>
                                    <input type="file"
                                           id="{{ $hmarkField }}"
                                           name="{{ $hmarkField }}"
                                           class="form-control hmark-file-input"
                                           accept="image/*"
                                           data-preview-target="hmarkPreview_{{ $hmarkField }}">
                                    <div class="hmark-preview hmark-preview--empty mt-2">
                                        <img id="hmarkPreview_{{ $hmarkField }}" alt="{{ $label }} image">
                                        <span class="hmark-preview__placeholder">No image</span>
                                    </div>
                                    @error($hmarkField)
                                    <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            @endforeach
                        </div>

                        <style>
                            .hmark-preview img { max-width: 100%; max-height: 160px; display: block; }
                            .hmark-preview--empty img { display: none; }
                            .hmark-preview:not(.hmark-preview--empty) .hmark-preview__placeholder { display: none; }
                            .hmark-preview__placeholder { color: #888; font-size: 0.875rem; }
                        </style>


                    </div>

                        <div class="row">
                            <div class="col-md-4">
                                <label for="min_expected_yard">Expected Yardage Gain</label>
                                <select id="min_expected_yard" name="min_expected_yard" class="form-control" required>
                                    <option value="">Select an option</option>
                                    <option value="short" {{ old('min_expected_yard') == 'short' ? 'selected' : '' }}>short 1-4</option>
                                    <option value="medium" {{ old('min_expected_yard') == 'medium' ? 'selected' : '' }}>medium 4-7</option>
                                    <option value="long" {{ old('min_expected_yard') == 'long' ? 'selected' : '' }}>long 7+</option>
                                </select>
                                @error('min_expected_yard')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
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
                                <label for="">Read 1</label>
                                 <textarea id="description" name="description" class="form-control" rows="3" placeholder="Enter description"></textarea>

                            </div>

                            <div class="col-md-4">
                                <label for="">Read 2</label>
                                 <textarea id="read_1" name="read_1" class="form-control" rows="3" placeholder="Enter read 2"></textarea>
                            </div>

                            <div class="col-md-4">
                                <label for="">Read 3</label>
                                 <textarea id="read_2" name="read_2" class="form-control" rows="3" placeholder="Enter read 3"></textarea>
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

    document.querySelectorAll('.hmark-file-input').forEach(function (input) {
        input.addEventListener('change', function () {
            var img = document.getElementById(input.dataset.previewTarget);
            var box = img.closest('.hmark-preview');

            if (img.dataset.objectUrl) {
                URL.revokeObjectURL(img.dataset.objectUrl);
                delete img.dataset.objectUrl;
            }

            var file = input.files && input.files[0];
            if (file && file.type.startsWith('image/')) {
                var url = URL.createObjectURL(file);
                img.dataset.objectUrl = url;
                img.src = url;
                box.classList.remove('hmark-preview--empty');
            } else {
                img.removeAttribute('src');
                box.classList.add('hmark-preview--empty');
            }
        });
    });
</script>
@endsection


