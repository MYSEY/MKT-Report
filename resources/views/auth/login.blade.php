<html lang="en"><head>
    <meta charset="utf-8">
    <title>
        Camma Reports
    </title>
    <meta name="description" content="Login">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, user-scalable=no, minimal-ui">
    <!-- Call App Mode on ios devices -->
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
<body class="desktop chrome webkit pace-done blur"><div class="pace  pace-inactive"><div class="pace-progress" data-progress-text="100%" data-progress="99" style="transform: translate3d(100%, 0px, 0px);">
<div class="pace-progress-inner"></div>
</div>
<div class="pace-activity">
    </div></div>
    <div class="blankpage-form-field">
        <div class="page-logo m-0 w-100 align-items-center justify-content-center rounded border-bottom-left-radius-0 border-bottom-right-radius-0 px-4" style="background-color: #0f619f !important;">
            <a href="javascript:void(0)" class="page-logo-link press-scale-down d-flex align-items-center">
                <img src="{{asset('admins/img/favicon/commalogo1.png')}}" alt="Support Form" aria-roledescription="logo" style="width: 85px !important">
                <span class="page-logo-text mr-1">Welcome! Please login.</span>
                <i class="fal fa-angle-down d-inline-block ml-1 fs-lg color-primary-300"></i>
            </a>
        </div>
        <div class="card p-4 border-top-left-radius-0 border-top-right-radius-0">
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="username">Employee ID</label>
                    <input type="text" name="number_employee" class="form-control" required>
                    <span class="help-block">
                        Your unique employee ID
                    </span>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="password" required>
                    <span class="help-block">
                        Your password
                    </span>
                </div>
                <button type="submit" class="btn btn-danger float-right waves-effect waves-themed">Secure login</button>
            </form>
        </div>
    </div>
    <script src="{{asset('admins/js/vendors.bundle.js')}}"></script>
    <script src="{{asset('admins/js/app.bundle.js')}}"></script>
    <script src="http://cdn.bootcss.com/toastr.js/latest/js/toastr.min.js"></script>
    {!! Toastr::message() !!}

</body></html>