@extends('layouts.main_layout')
@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">Create League</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">League</li>
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
                            <form method="POST" enctype="multipart/form-data" action="{{ route('league.store') }}">
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
                                        <label for="">League</label>
                                        <select name="league_rule_id" class="form-control" id="">
                                            @foreach ($league_rule as $leagu)
                                                <option value="{{ $leagu->id }}">{{ $leagu->title }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="">Sport</label>
                                        <select name="sport_id" class="form-control" id="">
                                            @foreach ($sports as $sport)
                                                <option value="{{ $sport->id }}">{{ $sport->title }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="">Title</label>
                                        <input type="name" name="title" class="form-control">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="">number_of_team</label>
                                        <input type="text" name="number_of_team" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="">number_of_downs</label>
                                        <input type="text" name="number_of_downs" class="form-control">
                                    </div>

                                    <div class="col-md-4">
                                        <label for="">length_of_field</label>
                                        <input type="text" name="length_of_field" class="form-control">
                                    </div>

                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="">number_of_timeouts</label>
                                        <input type="text" name="number_of_timeouts" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="">clock_time</label>
                                      

                                        <select  id="playerSpeed" class="form-control" name="clock_time" class="form-select input-style"
                                            required="">
                                            <option  value="">Select an option</option>
                                            <option  value="number of players">Number of Players</option>
                                            <option  value="nfl">NFL</option>
                                            <option  value="cfl">CFL</option>
                                            <option  value="12 (cfl)">12 (cfl)</option>
                                            <option  value="11 (NFL)">11 (NFL)</option>
                                            <option  value="other">Other</option>
                                        </select>

                                    </div>

                                    <div class="col-md-4">
                                        <label for="">number_of_quarters</label>
                                        <select id="playerStrength" class="form-control" name="number_of_quarters"
                                            required="">
                                            <option value="">Select an option</option>
                                            <option value="1">1</option>
                                            <option value="2">2</option>
                                            <option value="3">3</option>
                                            <option value="4">4</option>
                                        </select>

                                    </div>

                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="">length_of_quarters</label>

                                        <select id="playerStrength" name="length_of_quarters"
                                          class="form-control" required="">
                                            <option value="">Select an option</option>
                                            <option value="10">10 minutes</option>
                                            <option value="20">20 minutes</option>
                                            <option value="30">30 minutes</option>
                                            <option value="40">40 minutes</option>
                                            <option value="50">50 minutes</option>
                                        </select>
                                       
                                    </div>
                                    <div class="col-md-4">
                                        <label for="">stop_time_reason</label>
                                        <select id="playerSize" name="stop_time_reason" class="form-control"
                                            required="">
                                            <option value="">Select an option</option>
                                            <option value="learning (stops time every whistle)">learning
                                                (stops time every whistle)</option>
                                            <option value="Play Clock time">play clock time</option>
                                            <option value="Time out time">time out time</option>
                                            <option value="out Of Bound">out of bound</option>
                                        </select>


                                    </div>
                                    <div class="col-md-4">
                                        <label for="">overtime_rules</label>
                                        <select id="playerStrength" name="overtime_rules" class="form-control"
                                            required="">
                                            <option value="">Select an option</option>
                                            <option value="1">1</option>
                                            <option value="2">2</option>
                                            <option value="3">3</option>
                                            <option value="4">4</option>
                                            <option value="5">5</option>
                                            <option value="6">6</option>
                                            <option value="7">7</option>
                                            <option value="8">8</option>
                                            <option value="9">9</option>
                                            <option value="10">10</option>
                                        </select>

                                    </div>
                                    <div class="col-md-4">
                                        <label for="">number_of_players</label>
                                        <input type="text" name="number_of_players" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="">flag_tbd</label>
                                        <input type="text" name="flag_tbd" class="form-control">
                                    </div>

                                </div>
                                <br>
                                <div class="row">
                                    <div class="col-md-4">
                                        <button type="button" onclick="addteam()" class="btn btn-info">Add Team</button>
                                    </div>
                                </div>
                                <div class="row" id="teams">
                                    <div class="col-md-3">
                                        <label for="">Team Name</label>
                                        <input type="text" name="team_name[]" class="form-control" required>
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
        var counter = 1;

        function addteam() {
            var html = `
              <div class="col-md-3" id="added_team${counter}">
                                        <label for="">Team Name</label>
                    <div class="input-group">
                                         <div class="input-group-text" onclick="remove(${counter})"><i>D</i></div>
      <input type="text" class="form-control" id="autoSizingInputGroup" placeholder="Username">
      </div>
                                       </div>
             

            `;
            counter++;
            $('#teams').append(html)

        }

        function remove(count) {
            $('#added_team' + count).remove();
        }
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
