@extends('layouts.main_layout')

@section('content')
<div class="container">
    <h2>League Details</h2>

    <div class="row">
        <div class="col-md-4"><strong>League Rule ID:</strong> {{ $league->league_rule_id }}</div>
        <div class="col-md-4"><strong>Sport ID:</strong> {{ $league->sport_id }}</div>
        <div class="col-md-4"><strong>Title:</strong> {{ $league->title }}</div>
    </div>

    <div class="row mt-3">
        <div class="col-md-4"><strong>Number of Teams:</strong> {{ $league->number_of_team }}</div>
        <div class="col-md-4"><strong>Number of Downs:</strong> {{ $league->number_of_downs }}</div>
        <div class="col-md-4"><strong>Length of Field:</strong> {{ $league->length_of_field }}</div>
    </div>

    <div class="row mt-3">
        <div class="col-md-4"><strong>Number of Timeouts:</strong> {{ $league->number_of_timeouts }}</div>
        <div class="col-md-4"><strong>Clock Time:</strong> {{ $league->clock_time }}</div>
        <div class="col-md-4"><strong>Number of Quarters:</strong> {{ $league->number_of_quarters }}</div>
    </div>

    <div class="row mt-3">
        <div class="col-md-4"><strong>Length of Quarters:</strong> {{ $league->length_of_quarters }}</div>
        <div class="col-md-4"><strong>Stop Time Reason:</strong> {{ $league->stop_time_reason }}</div>
        <div class="col-md-4"><strong>Overtime Rules:</strong> {{ $league->overtime_rules }}</div>
    </div>

    <div class="row mt-3">
        <div class="col-md-4"><strong>Number of Players:</strong> {{ $league->number_of_players }}</div>
        <div class="col-md-4"><strong>Flag TBD:</strong> {{ $league->flag_tbd }}</div>
    </div>

    {{-- Teams can be shown if they are stored in a relation --}}
    {{-- Example:
    @foreach ($league->teams as $team)
        <div>{{ $team->name }}</div>
    @endforeach
    --}}
</div>
@endsection
