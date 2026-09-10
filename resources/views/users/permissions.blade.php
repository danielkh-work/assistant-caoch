@extends('layouts.main_layout')
@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">Permissions — {{ $user->name }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
                        <li class="breadcrumb-item active">Permissions</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <p class="text-muted">
                                Role: <strong>{{ $user->role }}</strong> &mdash;
                                "Inherited" follows the role default, "Granted" adds this permission beyond the role,
                                "Denied" removes it even if the role would otherwise grant it.
                            </p>
                            <form method="POST" action="{{ route('users.permissions.update', $user->id) }}">
                                @csrf
                                @method('PUT')
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Permission</th>
                                            <th width="120px">Inherited</th>
                                            <th width="120px">Granted</th>
                                            <th width="120px">Denied</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($rows as $row)
                                            <tr>
                                                <td>{{ $row->permission->name }}</td>
                                                <td class="text-center">
                                                    <input type="radio" name="permission_state[{{ $row->permission->id }}]"
                                                        value="inherited" {{ $row->state === 'inherited' || $row->state === 'none' ? 'checked' : '' }}>
                                                </td>
                                                <td class="text-center">
                                                    <input type="radio" name="permission_state[{{ $row->permission->id }}]"
                                                        value="direct" {{ $row->state === 'direct' ? 'checked' : '' }}>
                                                </td>
                                                <td class="text-center">
                                                    <input type="radio" name="permission_state[{{ $row->permission->id }}]"
                                                        value="denied" {{ $row->state === 'denied' ? 'checked' : '' }}>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <input class="btn btn-success" type="submit" value="Save">
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
