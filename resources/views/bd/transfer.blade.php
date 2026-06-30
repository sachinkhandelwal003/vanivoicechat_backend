@extends('layouts.app')

@section('content')
<div class="card mb-3">

    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0">Transfer BD</h5>
            </div>
            <div class="col-auto ms-auto">
                <a href="{{ route('bd-user') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>
    </div>

    <div class="card-body">

        <form action="{{ route('bd-user.transfer.save', $bd->id) }}" method="POST">
            @csrf

            <div class="row g-4">

                <!-- BD USER -->
                <div class="col-md-12">
                    <label class="form-label">BD User</label>
                    <input type="text" class="form-control"
                        value="{{ $bd->user->uid }}" readonly>

                    <small class="text-muted">
                        User: {{ $bd->user->name }}
                    </small>
                </div>

                <!-- NEW ADMIN UID -->
                <div class="col-md-12">
                    <label class="form-label">New Admin UID *</label>
                    <input type="text" name="admin_uid" class="form-control"
                        placeholder="Enter New Admin UID"
                        value="{{ old('admin_uid') }}">

                    <small class="text-muted">
                        Enter the User ID of the Admin to transfer this BD to.
                    </small>
                </div>

            </div>

            <div class="mt-4">
                <button class="btn btn-success">
                    <i class="fa fa-exchange-alt me-1"></i> Transfer
                </button>

                <a href="{{ route('bd-user') }}" class="btn btn-secondary">
                    Return
                </a>
            </div>

        </form>

    </div>
</div>
@endsection