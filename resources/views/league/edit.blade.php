@extends('layouts.main_layout')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <h1>Edit League</h1>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" action="{{ route('league.update', $league->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-4">
                            <label>Package</label>
                            <select name="role_id[]" class="form-control select2" multiple required>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" 
                                        {{ $league->roles->contains('id', $role->id) ? 'selected' : '' }}>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{--  <div class="col-md-4">
                            <label>League Rule</label>
                            <select name="league_rule_id" class="form-control">
                                @foreach ($league_rule as $leagu)
                                    <option value="{{ $leagu->id }}" 
                                        {{ $league->league_rule_id == $leagu->id ? 'selected' : '' }}>
                                        {{ $leagu->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>  --}}

                        <div class="col-md-4">
                            <label>Sport</label>
                            <select name="sport_id" class="form-control">
                                @foreach ($sports as $sport)
                                    <option value="{{ $sport->id }}" 
                                        {{ $league->sport_id == $sport->id ? 'selected' : '' }}>
                                        {{ $sport->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-4">
                            <label>Title</label>
                            <input type="text" name="title" class="form-control" value="{{ $league->title }}">
                        </div>
                        <div class="col-md-4">
                            <label>Number of Teams</label>
                            <input type="text" id="number_of_new_teams" name="number_of_team" class="form-control" value="{{ $league->number_of_team }}">
                        </div>
                        <div class="col-md-4">
                            <label>Number of Downs</label>
                            <input type="text" name="number_of_downs" class="form-control" value="{{ $league->number_of_downs }}">
                        </div>
                        <div class="col-md-4">
                            <label>Length of Field</label>
                            <input type="text" name="length_of_field" class="form-control" value="{{ $league->length_of_field }}">
                        </div>
                        <div class="col-md-4">
                            <label>Number of Timeouts</label>
                            <input type="text" name="number_of_timeouts" class="form-control" value="{{ $league->number_of_timeouts }}">
                        </div>
                        <div class="col-md-4">
                            <label>Clock Time</label>
                            <select name="clock_time" class="form-control">
                                <option value="">Select</option>
                                @foreach(['number of players', 'nfl', 'cfl', '12 (cfl)', '11 (NFL)', 'other'] as $option)
                                    <option value="{{ $option }}" 
                                        {{ $league->clock_time === $option ? 'selected' : '' }}>
                                        {{ ucfirst($option) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label>Number of Quarters</label>
                            <select name="number_of_quarters" class="form-control">
                                @foreach([1,2,3,4] as $q)
                                    <option value="{{ $q }}" {{ $league->number_of_quarters == $q ? 'selected' : '' }}>{{ $q }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label>Length of Quarters</label>
                            <select name="length_of_quarters" class="form-control">
                                @foreach([10,20,30,40,50] as $len)
                                    <option value="{{ $len }}" {{ $league->length_of_quarters == $len ? 'selected' : '' }}>
                                        {{ $len }} minutes
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label>Stop Time Reason</label>
                            <select name="stop_time_reason" class="form-control">
                                @foreach(['learning (stops time every whistle)', 'Play Clock time', 'Time out time', 'out Of Bound'] as $reason)
                                    <option value="{{ $reason }}" {{ $league->stop_time_reason == $reason ? 'selected' : '' }}>
                                        {{ ucfirst($reason) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label>Overtime Rules</label>
                            <select name="overtime_rules" class="form-control">
                                @foreach(range(1, 10) as $rule)
                                    <option value="{{ $rule }}" {{ $league->overtime_rules == $rule ? 'selected' : '' }}>{{ $rule }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label>Number of Players</label>
                            <input type="text" name="number_of_players" class="form-control" value="{{ $league->number_of_players }}">
                        </div>
                        <div class="col-md-4">
                            <label>Flag TBD</label>
                            <input type="text" name="flag_tbd" class="form-control" value="{{ $league->flag_tbd }}">
                        </div>
                    </div>
                    <br>

                    {{--  <div class="row">
                        <div class="col-md-4">
                            <button type="button" onclick="addteam()" class="btn btn-info">Add Team</button>
                        </div>
                    </div>  --}}

                    <div class="row" id="teams">
                        @foreach ($league->teams as $index => $team)
                            <div class="col-md-3" id="existing_team_{{ $index }}">
                                <label for="">Team Name</label>
                                <div class="input-group">
                                    <input type="text" name="team_name[]" class="form-control" value="{{ $team->team_name }}" required>
                                    <div class="input-group-text" onclick="removeExistingTeam({{ $index }}, {{ $team->id }})" style="cursor:pointer;color:red;">&times;</div>
                                </div>
                            </div>
                        @endforeach
                        @if ($league->teams->isEmpty())
                            <div class="col-md-3">
                                <label for="">Team Name</label>
                                <input type="text" name="team_name[]" class="form-control" required>
                            </div>
                        @endif
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">Update League</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
<script>
        var counter = 1;
{{--  
        function addteam() {
            var html = `
              <div class="col-md-3" id="added_team${counter}">
                                        <label for="">Team Name</label>
                    <div class="input-group">
                                         <div class="input-group-text" onclick="remove(${counter})"><i>D</i></div>
      <input type="text" name="team_name[]" class="form-control" id="autoSizingInputGroup" placeholder="Team">
      </div>
                                       </div>
             

            `;
            counter++;
            $('#teams').append(html)

        }

        function remove(count) {
            $('#added_team' + count).remove();
        }  --}}


       

document.getElementById('number_of_new_teams').addEventListener('input', function () {
    const count = parseInt(this.value);
    const container = document.getElementById('teams');

    // Remove all previously added dynamic fields
    document.querySelectorAll('[id^="added_team"]').forEach(el => el.remove());

    if (isNaN(count) || count <= 0) return;

    for (let i = 0; i < count; i++) {
        const teamId = counter++;
        const html = `
            <div class="col-md-3" id="added_team${teamId}">
                <label for="">Team Name</label>
                <div class="input-group">
                    <div class="input-group-text" onclick="remove(${teamId})" style="cursor:pointer;color:red;">&times;</div>
                    <input type="text" name="team_name[]" class="form-control" placeholder="Team">
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
    }
});

function remove(count) {
    const el = document.getElementById('added_team' + count);
    if (el) el.remove();
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
