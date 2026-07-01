@extends('layouts.app')
@section('content')

<div class="card">

    <div class="card-header">
        <div class="row align-items-center">
            <div class="col">
                <h5 class="mb-0">Claimed Users List</h5>
            </div>

            <div class="col-auto">
                <a href="{{ route('red.envelope') }}" class="btn btn-outline-secondary btn-sm _effect--ripple waves-effect waves-light">
                    <i class="fas fa-arrow-left me-1"></i>
                    Back
                </a>
            </div>
        </div>
    </div>

    <div class="card-body">

        <div class="table-responsive">

            <table class="table table-bordered">

                <thead>

                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Room</th>
                        <th>Amount</th>
                        <th>Claimed At</th>
                    </tr>

                </thead>

                <tbody>

                    @forelse($envelope->claims as $claim)

                    <tr>

                        <td>
                            {{ $loop->iteration }}
                        </td>

                        <td>
                            @php
                                $image = ($claim->user && $claim->user->image)
                                    ? Helper::showImage($claim->user->image, true)
                                    : asset('assets/img/avatar.png');
                            @endphp

                            <div class="d-flex align-items-center gap-2">
                                <img src="{{ $image }}" class="rounded-circle" width="40" height="40">

                                <div>
                                    <div class="fw-bold">
                                        {{ $claim->user->name ?? '-' }}
                                    </div>

                                    <small class="text-muted">
                                        {{ $claim->user->uid ?? '-' }}
                                    </small>
                                </div>
                            </div>
                        </td>

                        <td>
                            {{ $claim->room->room_name ?? '-' }}
                        </td>

                        <td>
                            ₹ {{ number_format($claim->amount,2) }}
                        </td>

                        <td>
                            {{ $claim->claimed_at ? $claim->claimed_at->format('d M Y h:i A') : '-' }}
                        </td>

                    </tr>

                    @empty

                    <tr>
                        <td colspan="6" class="text-center">
                            No Claim Found
                        </td>
                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection