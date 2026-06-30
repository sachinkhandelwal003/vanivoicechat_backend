@extends('layouts.auth')

@section('content')
<form method="POST" action="{{ route('register') }}">
    @csrf
    <div class="row">
        <div class="col-md-12 mb-3">
            <h2>Sign Up as <b class="text-secondary">Company</b> </h2>
            <p>Enter your email and password to register</p>
        </div>
        <div class="col-md-12">
            <div class="mb-3">
                <input placeholder="Name" id="name" type="text" class="form-control @error('name') is-invalid @enderror"
                    name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
                @error('name')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
        </div>
        <div class="col-md-12">
            <div class="mb-3">
                <input class="form-control @error('email') is-invalid @enderror" type="email" name="email" required
                    autocomplete="email" placeholder="Email Address" value="{{ old('email') }}" />
                @error('email')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
        </div>
        <div class="col-12">
            <div class="mb-3">
                <input class="form-control @error('mobile') is-invalid @enderror" type="text" name="mobile" required
                    autocomplete="mobile" placeholder="Mobile" value="{{ old('mobile') }}" maxlength="10" />
                @error('mobile')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
        </div>
        <div class="col-md-8 col-6">
            <div class="mb-3">
                <input class="form-control @error('otp') is-invalid @enderror" type="otp" name="otp"
                    placeholder="OTP Code" id="otp" />
                @error('otp')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="mb-3 text-end">
                <button type="button" id="sendOtp" class="btn btn-secondary">
                    Send OTP <i class="fa fa-refresh ms-2"></i>
                </button>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <input class="form-control @error('password') is-invalid @enderror" type="password" name="password"
                    placeholder="Password" id="new-password" maxlength="6" />
                @error('password')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <input class="form-control @error('password_confirmation') is-invalid @enderror" type="password"
                    name="password_confirmation" placeholder="Confirm Password" autocomplete="current-password" />
                @error('password_confirmation')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
        </div>
        <div class="col-12">
            <div class="mb-3">
                <div class="form-check form-check-primary d-flex">
                    <input name="terms" class="form-check-input me-3" type="checkbox" id="form-check-default">
                    <label class="form-check-label" for="form-check-default">
                        I agree the <a href="javascript:void(0);" class="text-primary">Terms and Conditions</a>
                    </label>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="mb-4">
                <button class="btn btn-secondary w-50">SIGN UP</button>
            </div>
        </div>
        <div class="col-12">
            <div class="text-center">
                @if (Route::has('login'))
                <p class="mb-0">Already have an account ?
                    <a class="text-warning" href="{{ route('login') }}">Log In</a>
                </p>
                @endif
            </div>
        </div>
    </div>
</form>
@endsection

@section('js')
<script type="text/javascript">
    $(function () {
        var validator = $("form").validate({
            rules: {
                name: {
                    required: true,
                    minlength: 2,
                    maxlength: 100
                },
                email: {
                    required: true,
                    customEmail: true,
                    email: true
                },
                mobile: {
                    required: true,
                    number: true,
                    indiaMobile: true,
                    exactlength: 10,
                },
                otp: {
                    required: true,
                    number: true,
                    exactlength: 6,
                },
                password: {
                    required: true,
                    minlength: 8,
                    maxlength: 50
                },
                password_confirmation: {
                    required: true,
                    minlength: 8,
                    maxlength: 50,
                    equalTo: "#new-password"
                },
                terms: {
                    required: true,
                },
            },
            messages: {
                name: {
                    required: "Please enter name",
                },
                email: {
                    required: "Please enter Email",
                },
                mobile: {
                    required: "Please enter Mobile number",
                },
                password: {
                    required: "Please enter Password",
                },
                otp: {
                    required: "Please enter OTP Code.",
                },
                password_confirmation: {
                    required: "Please enter Confirm Password",
                },
                terms: {
                    required: "Please select Terms and Conditions checkbox",
                },
            },
            errorPlacement: function (error, element) {
                if (element.attr("name") == "terms") {
                    error.insertAfter(".form-check");
                }
                else {
                    error.insertAfter(element);
                }
            }
        });

        $('#sendOtp').on('click', function () {
            var mobile = $('[name="mobile"]').val();
            if (!mobile) {
                return validator.showErrors({ mobile: 'Please enter mobile number first..!!' });
            }

            $(this).prop('disabled', true);
            $(this).find('i').addClass('fa-spin');
            var button = this;
            $.ajax({
                url: "{{ url('api/send-otp') }}",
                type: 'post',
                data: { mobile, is_register: 1 },
                headers: {
                    'x-api-key': "{{ config('constant.secret_token') }}"
                },
                dataType: 'json',
                success: function (data) {
                    if (data.status) {
                        toastr.success(data.message);
                        setTimeout(() => {
                            $(button).prop('disabled', false)
                            $(button).find('i').removeClass('fa-spin');
                        }, 30000);
                    } else {
                        toastr.error(data.message);
                        validator.showErrors(data.data);
                        $(button).prop('disabled', false)
                        $(button).find('i').removeClass('fa-spin');
                    }
                },
                error: function (data) {
                    console.log('error', data);
                    alert("Outlet Creation Failed, please try again.");
                }
            });
        });
    });
</script>
@endsection