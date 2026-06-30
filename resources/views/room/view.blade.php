@extends('layouts.app')

@section('content')
<div class="card">

    <div class="card-header d-flex justify-content-between">
        <h5 class="mb-0">Room Details</h5>

        <a href="{{ route('room') }}" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-left"></i> Back
        </a>
    </div>

    <div class="card-body">

        <div class="row">

            <!-- Room Image -->
            <div class="col-md-4 text-center">
                <img src="{{ $room->room_image 
                    ? \App\Helper\Helper::showImage($room->room_image, true) 
                    : asset('assets/img/default-room.png') }}"
                    class="img-fluid rounded mb-3"
                    style="max-height:200px;">

                <h5>{{ $room->room_name }}</h5>
                <small class="text-muted">Room ID: {{ $room->room_id }}</small>
            </div>

            <!-- Room Details -->
            <div class="col-md-8">

                <table class="table table-bordered">

                    <tr>
                        <th>Owner</th>
                        <td>
                            {{ $room->user->name ?? '-' }}
                            ({{ $room->user->uid ?? '-' }})
                        </td>
                    </tr>

                    <tr>
                        <th>Room Seats</th>
                        <td>{{ $room->room_seat ?? '-' }}</td>
                    </tr>

                    <tr>
                        <th>Total Points</th>
                        <td>{{ $room->total_points ?? 0 }}</td>
                    </tr>

                    <tr>
                        <th>Status</th>
                        <td>
                            @if($room->status == 1)
                            <span class="badge bg-success">Active</span>
                            @else
                            <span class="badge bg-danger">Inactive</span>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <th>Locked</th>
                        <td>
                            @if($room->is_locked)
                            <span class="badge bg-warning text-dark">Yes</span>
                            @else
                            <span class="badge bg-secondary">No</span>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <th>Created At</th>
                        <td>{{ \Carbon\Carbon::parse($room->created_at)->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</td>
                    </tr>

                    <tr>
                        <th>Description</th>
                        <td>{{ $room->bio ?? '-' }}</td>
                    </tr>

                </table>

            </div>

        </div>

    </div>
</div>
@endsection