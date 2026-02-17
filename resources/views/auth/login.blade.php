<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>
            Camma Reports
        </title>
        <meta name="description" content="Login">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, user-scalable=no, minimal-ui">
        <!-- Call App Mode on ios devices -->
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <!-- Remove Tap Highlight on Windows Phone IE -->
        <meta name="msapplication-tap-highlight" content="no">
        <!-- base css -->
        <link rel="stylesheet" media="screen, print" href="{{asset('admins/css/page-login.css')}}">
        <!-- base css -->
        <link rel="stylesheet" media="screen, print" href="{{asset('/admins/css/vendors.bundle.css')}}">
        <link rel="stylesheet" media="screen, print" href="{{asset('/admins/css/app.bundle.css')}}">
        <!-- Place favicon.ico in the root directory -->
        <link rel="apple-touch-icon" sizes="180x180" href="{{asset('/admins/img/favicon/logo.png')}}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{asset('/admins/img/favicon/logo.png') }}">
        <link rel="mask-icon" href="img/favicon/safari-pinned-tab.svg" color="#5bbad5">
        <!-- Optional: page related CSS-->
        <link rel="stylesheet" media="screen, print" href="{{asset('/admins/css/fa-brands.css') }}">

        <link rel="stylesheet" href="http://cdn.bootcss.com/toastr.js/latest/css/toastr.min.css">
    </head>
    <body class="desktop chrome webkit pace-done blur">
        {!! Toastr::message() !!}
        <div class="pace  pace-inactive">
            <div class="pace-progress" data-progress-text="100%" data-progress="99" style="transform: translate3d(100%, 0px, 0px);">
                <div class="pace-progress-inner"></div>
            </div>
            <div class="pace-activity">
            </div>
        </div>
        <div class="blankpage-form-field">
            <div class="page-logo m-0 w-100 align-items-center justify-content-center rounded border-bottom-left-radius-0 border-bottom-right-radius-0 px-4" style="background-color: #0f619f !important;">
                <a href="javascript:void(0)" class="page-logo-link press-scale-down d-flex align-items-center">
                    <img src="{{asset('admins/img/favicon/commalogo1.png')}}" alt="Support Form" aria-roledescription="logo" style="width: 85px !important">
                    <span class="page-logo-text mr-1">Welcome! Please login.</span>
                    <i class="fal fa-angle-down d-inline-block ml-1 fs-lg color-primary-300"></i>
                </a>
            </div>
            <div class="card p-4 border-top-left-radius-0 border-top-right-radius-0">
                <form id="loginForm">
                    <div class="form-group">
                        <label class="form-label" for="number_employee">EmployeeID</label>
                        <input type="text" name="number_employee" id="number_employee" class="form-control" required>
                        @error('number_employee')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
                        @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-danger float-right waves-effect waves-themed" id="btnLogin">login</button>
                    </div>
                </form>
            </div>
        </div>
        <script src="{{asset('admins/js/vendors.bundle.js')}}"></script>
        <script src="{{asset('admins/js/app.bundle.js')}}"></script>
        <script src="http://cdn.bootcss.com/toastr.js/latest/js/toastr.min.js"></script>
    </body>
    <script>
        $(function() {
            $('#loginForm').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    type: "POST",
                    url: "{{ route('login') }}",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        number_employee: $("#number_employee").val(),
                        password: $("#password").val(),
                    },
                    dataType: "JSON",
                    success: function(response) {
                        let data = response;
                        if (data.status == "success") {
                            toastr.success(data.message);
                            window.location.replace("{{ url('admin/dashboard') }}");
                            return false;
                        }
                        if (data.status == "error") {
                            toastr.error(data.message);
                            return false;
                        }

                        // if (data.status == "success" && data.role == "Employee") {
                        //     toastr.success(data.message);
                        //     window.location.replace(dashboadEmployee); 
                        // }else{
                        //     toastr.success(data.message);
                        //     window.location.replace(dashboadAdmin); 
                        // }
                    }
                });
            });
        });
    </script>
</html>