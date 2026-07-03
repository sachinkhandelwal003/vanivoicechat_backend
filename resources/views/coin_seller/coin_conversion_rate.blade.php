@extends('layouts.app')

@section('content')

<div class="card shadow-sm border-0">

    <div class="card-header border-0">
        <div>
            <h4 class="mb-1 fw-bold">
                Coin Conversion Rate Settings
            </h4>

            <p class="text-muted mb-0">
                Manage coin conversion values used across merchant, seller and user transactions.
            </p>
        </div>
    </div>

    <div class="card-body">

        <form action="{{ route('coin-conversion-rate.update') }}" method="POST">

            @csrf

            <div class="row g-4">

                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">

                            <label class="form-label fw-bold text-primary">
                                Merchant → User
                            </label>

                            <input type="number" name="merchant_to_user_rate" class="form-control form-control-lg" value="{{ $rate->merchant_to_user_rate }}" required>

                            <small class="text-muted">
                                Coins credited to user per $1.
                            </small>

                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">

                            <label class="form-label fw-bold text-success">
                                Merchant → Seller
                            </label>

                            <input type="number" name="merchant_to_seller_rate" class="form-control form-control-lg" value="{{ $rate->merchant_to_seller_rate }}" required>

                            <small class="text-muted">
                                Coins credited to seller per $1.
                            </small>

                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">

                            <label class="form-label fw-bold text-warning">
                                Seller → User
                            </label>

                            <input type="number" name="seller_to_user_rate" class="form-control form-control-lg" value="{{ $rate->seller_to_user_rate }}" required>

                            <small class="text-muted">
                                Coins credited to user per $1 from seller.
                            </small>

                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">

                            <label class="form-label fw-bold text-warning">
                                Coin Exchange Rate
                            </label>

                            <input type="number" name="coin_exchange_rate" class="form-control form-control-lg" value="{{ $rate->coin_exchange_rate }}" required>

                            <small class="text-muted">
                                 Coin Exchange Rate per $1.
                            </small>

                        </div>
                    </div>
                </div>

            </div>

            <div class="alert border-0 mt-4" style="background:linear-gradient(135deg,#fff8e1,#fff3cd);">

                <div class="d-flex">

                    <div class="me-3">
                        <i class="fas fa-coins fa-2x text-warning"></i>
                    </div>

                    <div>
                        <h6 class="fw-bold mb-1">Conversion Information</h6>

                        <p class="mb-0 text-dark">
                            All rates are based on <strong>1 USD ($1)</strong>.
                            These values define the number of coins credited during Merchant ↔ User and Seller ↔ User transactions.
                        </p>
                    </div>

                </div>

            </div>

            <div class="text-end mt-4">

                <button type="submit" class="btn btn-primary btn-lg px-5">
                    <i class="fas fa-save me-2"></i>
                    Save Changes
                </button>

            </div>

        </form>

    </div>

</div>

@endsection