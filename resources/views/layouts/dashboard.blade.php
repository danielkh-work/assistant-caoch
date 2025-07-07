@extends('layouts.main_layout')
@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">Dashboard</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-3 col-6">
                    <a 
                    {{-- href="{{ route('customers.index') }}" --}}
                    >
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3>{{ $customers ?? 0 }}</h3>

                                <p>Players</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-bag"></i>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-3 col-6">
                    <a 
                    {{-- href="{{ route('guards.index') }}" --}}
                    >
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3>{{ $guards ?? 0 }}</h3>

                                <p>Plays</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-stats-bars"></i>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-3 col-6">
                    <a 
                    {{-- href="{{ route('guard.booking.index') }}" --}}
                    >
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3>{{ $rides ?? 0 }}</h3>

                                <p>League</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-person-add"></i>
                            </div>
                        </div>
                    </a>
                </div>
                 <div class="col-lg-3 col-6">
                    <a 
                    {{-- href="{{ route('guard.booking.index') }}" --}}
                    >
                        <div class="small-box bg-dark">
                            <div class="inner">
                                <h3>{{ $teams ?? 0 }}</h3>

                                <p>Teams</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-person-add"></i>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            <div class="row">
                <table class="table">
  <thead>
    <tr>
      <th>Role</th>
      <th>Total Players</th>
      <th>Total Plays</th>
      <th>Total Leagues</th>
    </tr>
  </thead>
  <tbody>
    @foreach($stats as $stat)
      <tr>
        <td>{{ $stat->role_name }}</td>
        <td>{{ $stat->total_players }}</td>
        <td>{{ $stat->total_plays }}</td>
        <td>{{ $stat->total_leagues }}</td>
      </tr>
    @endforeach
  </tbody>
</table>
            </div>
        </div>
    </section>
@endsection
