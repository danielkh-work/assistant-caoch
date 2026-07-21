@extends('layouts.main_layout')
@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">Restore Games</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                        <li class="breadcrumb-item active">Restore Games</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <section class="content">
        <div class="container-fluid">
            @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('status') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Ended Games</h3>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover text-nowrap mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Match</th>
                                        <th>League</th>
                                        <th>Ended At</th>
                                        <th class="text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($endedGames as $game)
                                        <tr>
                                            <td>{{ $game->id }}</td>
                                            <td>
                                                {{ optional($game->myTeam)->team_name ?? 'Team '.$game->my_team_id }}
                                                vs
                                                {{ optional($game->opponentTeam)->team_name ?? 'Team '.$game->oponent_team_id }}
                                            </td>
                                            <td>{{ optional($game->league)->title ?? 'League '.$game->league_id }}</td>
                                            <td>
                                                {{ $game->match_end_date ? $game->match_end_date->format('M d, Y h:i A') : 'Not captured' }}
                                            </td>
                                            <td class="text-right">
                                                @php
                                                    $matchTitle = (optional($game->myTeam)->team_name ?? 'Team '.$game->my_team_id)
                                                        .' vs '
                                                        .(optional($game->opponentTeam)->team_name ?? 'Team '.$game->oponent_team_id);
                                                @endphp
                                                <form action="{{ route('games.restore-playable', $game) }}" method="POST" class="d-inline js-restore-game-form"
                                                    data-match="{{ $matchTitle }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-redo-alt mr-1"></i>
                                                        Make Playable Again
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No ended games found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="modal fade" id="restoreGameModal" tabindex="-1" role="dialog" aria-labelledby="restoreGameModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="restoreGameModalLabel">Make game playable again?</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-1">This will restore the ended game so it can be played again.</p>
                    <p class="mb-0 font-weight-bold" id="restoreGameMatch"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" id="confirmRestoreGame">
                        <i class="fas fa-redo-alt mr-1"></i>
                        Make Playable Again
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(function () {
            var restoreForm = null;

            $('.js-restore-game-form').on('submit', function (event) {
                event.preventDefault();

                restoreForm = this;
                $('#restoreGameMatch').text($(this).data('match') || '');
                $('#restoreGameModal').modal('show');
            });

            $('#confirmRestoreGame').on('click', function () {
                if (restoreForm) {
                    this.disabled = true;
                    restoreForm.submit();
                }
            });

            $('#restoreGameModal').on('hidden.bs.modal', function () {
                restoreForm = null;
                $('#confirmRestoreGame').prop('disabled', false);
            });
        });
    </script>
@endsection
