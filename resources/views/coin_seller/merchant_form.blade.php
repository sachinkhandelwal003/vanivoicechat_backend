@extends('layouts.app')

@section('content')

<div class="card mb-3">

    <div class="card-header d-flex justify-content-between align-items-center">
        <h5>{{ isset($merchant) ? 'Edit' : 'Add' }} Merchant</h5>

        <a href="{{ route('merchant') }}" class="btn btn-outline-secondary btn-sm">
            Back
        </a>
    </div>

    <div class="card-body">

        <form action="{{ isset($merchant) ? route('merchant.save',$merchant->id) : route('merchant.save') }}" method="POST">
            @csrf

            <div class="row g-3">

                <div class="col-md-6">
                    <label>*User UID</label>
                    <input type="text" name="user_uid" class="form-control"
                        value="{{ old('user_uid',$merchant->user->uid ?? '') }}">
                </div>

                <div class="col-md-6">
                    <label>*Country</label>
                    <select name="country_id" class="form-control">
                        @foreach($countries as $c)
                        <option value="{{ $c->id }}"
                            {{ old('country_id',$merchant->country_id ?? '')==$c->id?'selected':'' }}>
                            {{ $c->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label>*Whatsapp</label>
                    <input type="text" name="whatsapp_number" class="form-control"
                        value="{{ old('whatsapp_number',$merchant->whatsapp_number ?? '') }}">
                </div>

            </div>

            <button class="btn btn-success mt-3">
                {{ isset($merchant) ? 'Update' : 'Add' }}
            </button>

        </form>

    </div>
</div>

@endsection