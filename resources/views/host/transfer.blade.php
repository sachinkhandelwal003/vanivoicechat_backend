@extends('layouts.app')

@section('content')
<div class="card mb-3">

    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0">Transfer Host</h5>
            </div>
        </div>
    </div>

    <div class="card-body">

        <form action="{{ route('host.transfer.save', $host->id) }}" method="POST">
            @csrf

            <div class="mb-3">
                <label class="form-label">Host User ID</label>
                <input type="text" class="form-control" value="{{ $host->user->uid }}" readonly>
            </div>

            <div class="mb-3">
                <label class="form-label">Current Agency ID</label>
                <input type="text" class="form-control" value="{{ $host->agency->id ?? '-' }}" readonly>
            </div>

            <div class="mb-3">
                <label class="form-label">New Agency UID *</label>
                <input type="text" name="agency_uid" class="form-control"
                    placeholder="Enter New Agency UID">

                <small class="text-muted">
                    Transfer allowed only if agency country matches host country.
                </small>
            </div>

            <button class="btn btn-success">
                <i class="fa fa-exchange-alt"></i> Transfer
            </button>

            <a href="{{ route('host') }}" class="btn btn-secondary">Back</a>

        </form>

    </div>
</div>
@endsection