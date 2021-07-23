@extends('layouts.app-index-admin-dashboard')

@section('content')

    <div class="dashboard-ecommerce">
        <div class="container-fluid dashboard-content ">
            @if ($message = Session::get('success'))
                <div id=alert>
                    <div class="alert alert-card alert-success" role="alert">
                        <strong>Operasi Berjaya! </strong>
                        {{ $message }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
            @elseif ($message = Session::get('error'))
                <div id="alert">
                    <div class="alert alert-card alert-danger" role="alert">
                        <strong>Ralat! </strong>
                        {{ $message }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
            @endif
            <div class="card" style="padding: 10px;">

                <div style="padding: 5px;"></div>
                <h2 class="card-header" style="text-align: center;"><i class="fa fa-users" aria-hidden="true"></i>Users Game Progress List
                </h2>
                <div class="p-0 card-body">
                    <div style="padding: 10px;"></div>
                    <div class="table-responsive">
                        <table id="table_data_list_progress" class="table table-striped table-bordered second"
                            style="width:100%">
                            <thead style="text-align: center;">
                                <tr>
                                    <th>BIL</th>
                                    <th>NAME</th>
                                    <th>IDENTIFICATION NUMBER</th>
                                    <th>SCHOOL</th>
                                    <th>STATE</th>
                                    <th>DISTRICT</th>
                                    <th>GAME PROGRESS (%)</th>
                                    <th>GAME ID</th>
                                </tr>
                            </thead>
                            <tbody style="text-align: center;">
                                @foreach ($users as $data)
                                    <tr>
                                        <td>{{ $data->id }}</td>
                                        <td>{{ $data->name }}</td>
                                        <td>{{ $data->ic_number }}</td>
                                        <td>{{ $data->school }}</td>
                                        <td>{{ $data->state }}</td>
                                        <td>{{ $data->district }}</td>
                                        <td>{{ $data->game_progress }}</td>
                                        <td>{{ $data->game_id ?? '-'}}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>

@endsection
