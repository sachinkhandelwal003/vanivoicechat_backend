@extends('layouts.app')

@section('content')
    <div class="card">

        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                Transfer Agency
            </h5>
        </div>

        <div class="card-body">

            <form action="{{ route('agency.transfer.store') }}" method="POST">

                @csrf

                <input type="hidden" name="agency_id" value="{{ $agency->id }}">

                {{-- Agency UID --}}

                <div class="mb-3">

                    <label class="form-label">
                        Agency User UID
                    </label>

                    <input type="text" class="form-control" value="{{ $agency->user->uid }}" readonly>

                </div>

                {{-- Agency User Name --}}

                <div class="mb-3">

                    <label class="form-label">
                        Agency User
                    </label>

                    <input type="text" class="form-control" value="{{ $agency->user->name }}" readonly>

                </div>

                {{-- Country --}}

                <div class="mb-3">

                    <label class="form-label">
                        Country
                    </label>

                    <input type="text" class="form-control" value="{{ $agency->country->name }}" readonly>

                </div>

                {{-- Current Owner --}}

                <div class="mb-3">

                    <label class="form-label">
                        Current Owner
                    </label>

                    <input type="text" class="form-control"
                        value="@if ($agency->is_bd_bound) BD : {{ optional($agency->bdUser->user)->name }}
                    @else
                    Admin : {{ optional($agency->admin->user)->name }} @endif"
                        readonly>

                </div>

                {{-- Transfer Type --}}

                <div class="mb-3">

                    <label class="form-label">
                        Transfer Type
                    </label>

                    <select name="type" id="transfer_type" class="form-control" required>

                        <option value="">
                            Select Transfer Type
                        </option>

                        <option value="admin">
                            Admin
                        </option>

                        <option value="bd">
                            BD
                        </option>

                    </select>

                </div>

                {{-- UID --}}

                <div class="mb-3">

                    <label class="form-label">

                        New UID

                    </label>

                    <input type="text" name="uid" class="form-control" placeholder="Enter Admin / BD UID" required>

                    <small class="text-muted">

                        Enter the UID of the Admin or BD you want to transfer this agency to.

                    </small>

                </div>

                <div class="mt-4">

                    <button class="btn btn-success">

                        <i class="fa fa-random"></i>

                        Transfer Agency

                    </button>

                    <a href="{{ route('agency') }}" class="btn btn-secondary">

                        Back

                    </a>

                </div>

            </form>

        </div>

    </div>
@endsection
