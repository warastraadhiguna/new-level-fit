<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="author" content="DexignLab">
    <meta name="robots" content="">
    <meta name="keywords"
        content="school, school admin, education, academy, admin dashboard, college, college management, education management, institute, school management, school management system, student management, teacher management, university, university management">
    <meta name="description"
        content="Discover Akademi - the ultimate admin dashboard and Bootstrap 5 template. Specially designed for professionals, and for business. Akademi provides advanced features and an easy-to-use interface for creating a top-quality website with School and Education Dashboard">
    <meta property="og:title" content="Akademi : School and Education Management Admin Dashboard Template">
    <meta property="og:description"
        content="Akademi - the ultimate admin dashboard and Bootstrap 5 template. Specially designed for professionals, and for business. Akademi provides advanced features and an easy-to-use interface for creating a top-quality website with School and Education Dashboard">
    <meta property="og:image" content="https://akademi.dexignlab.com/xhtml/social-image.png">
    <meta name="format-detection" content="telephone=no">

    <!-- Mobile Specific -->
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Page Title Here -->
    <title>{{ config('app.name') }} Management System</title>

    <!-- FAVICONS ICON -->
    <link rel="shortcut icon" type="{{ asset('admingym/image/png') }}"
        href="{{ asset('admingym/images/gym/fav-icon.png') }}">
    <link href="{{ asset('admingym/vendor/wow-master/css/libs/animate.css') }}" rel="stylesheet">
    <link href="{{ asset('admingym/vendor/bootstrap-select/dist/css/bootstrap-select.min.css') }}" rel="stylesheet">
    <link rel="stylesheet"
        href="{{ asset('admingym/vendor/bootstrap-select-country/css/bootstrap-select-country.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admingym/vendor/jquery-nice-select/css/nice-select.css') }}">
    <link href="{{ asset('admingym/vendor/datepicker/css/bootstrap-datepicker.min.css') }}" rel="stylesheet">

    <link href="{{ asset('admingym/vendor/datatables/css/jquery.dataTables.min.css') }}" rel="stylesheet">
    <!--swiper-slider-->
    <link rel="stylesheet" href="{{ asset('admingym/vendor/swiper/css/swiper-bundle.min.css') }}">
    <!-- Style css -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Icons" rel="stylesheet">
    <link href="{{ asset('admingym/css/style.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('admingym/vendor/select2/css/select2.min.css') }}">
    <link href="{{ asset('admingym/vendor/bootstrap-select/dist/css/bootstrap-select.min.css') }}" rel="stylesheet">
    <link href="{{ asset('admingym/vendor/jquery-nice-select/css/nice-select.css') }}" rel="stylesheet">
    <link href="{{ asset('admingym/css/style.css') }}" rel="stylesheet">

    <!-- Daterange picker -->
    <link href="{{ asset('admingym/vendor/bootstrap-daterangepicker/daterangepicker.css') }}" rel="stylesheet">
    <!-- Clockpicker -->
    <link href="{{ asset('admingym/vendor/clockpicker/css/bootstrap-clockpicker.min.css') }}" rel="stylesheet">
    <!-- asColorpicker -->
    <link href="{{ asset('admingym/vendor/jquery-asColorPicker/css/asColorPicker.min.css') }}" rel="stylesheet">
    <!-- Material color picker -->
    <link
        href="{{ asset('admingym/vendor/bootstrap-material-datetimepicker/css/bootstrap-material-datetimepicker.css') }}"
        rel="stylesheet">

    <!-- Pick date -->
    <link rel="stylesheet" href="{{ asset('admingym/vendor/pickadate/themes/default.css') }}">
    <link rel="stylesheet" href="{{ asset('admingym/vendor/pickadate/themes/default.date.css') }}">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="{{ asset('admingym/vendor/bootstrap-select/dist/css/bootstrap-select.min.css') }}" rel="stylesheet">
    <!-- Clockpicker -->
    <link href="{{ asset('admingym/vendor/clockpicker/css/bootstrap-clockpicker.min.css') }}" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css"
        integrity="sha512-vKMx8UnXk60zUwyUnUPM3HbQo8QfmNx7+ltw8Pm5zLusl1XIfwcxo8DbWCqMGKaWeNxWA8yrx5v3SaVpMvR3CA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

</head>

<body>

    <!--*******************
        Preloader start
    ********************-->
    <div id="preloader">
        <div class="loader">
            <div class="dots">
                <div class="dot mainDot"></div>
                <div class="dot"></div>
                <div class="dot"></div>
                <div class="dot"></div>
                <div class="dot"></div>
            </div>
        </div>
    </div>
    <!--*******************
        Preloader end
    ********************-->
    <!--**********************************
        Main wrapper start
    ***********************************-->
    <div id="main-wrapper" class="wallet-open active">

        <!--**********************************
            Nav header start
        ***********************************-->
        <div class="nav-header">
            <a href="{{ route('dashboard') }}" class="brand-logo">
                {{-- <svg class="logo-abbr" width="40" height="40" viewBox="0 0 48 54" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <rect y="3" width="48" height="48" rx="16" fill="#FB7D5B" />
                    <path
                        d="M28.964 35.536H19.532L18.02 40H11.576L20.72 14.728H27.848L36.992 40H30.476L28.964 35.536ZM27.38 30.784L24.248 21.532L21.152 30.784H27.38Z"
                        fill="white" />
                </svg>
                <div class="brand-title">
                    <svg width="140" height="30" viewBox="0 0 167 30" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M17.964 24.536H8.532L7.02 29H0.576L9.72 3.728H16.848L25.992 29H19.476L17.964 24.536ZM16.38 19.784L13.248 10.532L10.152 19.784H16.38ZM41.051 29L34.931 20.576V29H28.775V2.36H34.931V17.084L41.015 8.912H48.611L40.259 18.992L48.683 29H41.051ZM49.8049 18.92C49.8049 16.856 50.1889 15.044 50.9569 13.484C51.7489 11.924 52.8169 10.724 54.1609 9.884C55.5049 9.044 57.0049 8.624 58.6609 8.624C60.0769 8.624 61.3129 8.912 62.3689 9.488C63.4489 10.064 64.2769 10.82 64.8529 11.756V8.912H71.0089V29H64.8529V26.156C64.2529 27.092 63.4129 27.848 62.3329 28.424C61.2769 29 60.0409 29.288 58.6249 29.288C56.9929 29.288 55.5049 28.868 54.1609 28.028C52.8169 27.164 51.7489 25.952 50.9569 24.392C50.1889 22.808 49.8049 20.984 49.8049 18.92ZM64.8529 18.956C64.8529 17.42 64.4209 16.208 63.5569 15.32C62.7169 14.432 61.6849 13.988 60.4609 13.988C59.2369 13.988 58.1929 14.432 57.3289 15.32C56.4889 16.184 56.0689 17.384 56.0689 18.92C56.0689 20.456 56.4889 21.68 57.3289 22.592C58.1929 23.48 59.2369 23.924 60.4609 23.924C61.6849 23.924 62.7169 23.48 63.5569 22.592C64.4209 21.704 64.8529 20.492 64.8529 18.956ZM74.2385 18.92C74.2385 16.856 74.6225 15.044 75.3905 13.484C76.1825 11.924 77.2505 10.724 78.5945 9.884C79.9385 9.044 81.4385 8.624 83.0945 8.624C84.4145 8.624 85.6145 8.9 86.6945 9.452C87.7985 10.004 88.6625 10.748 89.2865 11.684V2.36H95.4425V29H89.2865V26.12C88.7105 27.08 87.8825 27.848 86.8025 28.424C85.7465 29 84.5105 29.288 83.0945 29.288C81.4385 29.288 79.9385 28.868 78.5945 28.028C77.2505 27.164 76.1825 25.952 75.3905 24.392C74.6225 22.808 74.2385 20.984 74.2385 18.92ZM89.2865 18.956C89.2865 17.42 88.8545 16.208 87.9905 15.32C87.1505 14.432 86.1185 13.988 84.8945 13.988C83.6705 13.988 82.6265 14.432 81.7625 15.32C80.9225 16.184 80.5025 17.384 80.5025 18.92C80.5025 20.456 80.9225 21.68 81.7625 22.592C82.6265 23.48 83.6705 23.924 84.8945 23.924C86.1185 23.924 87.1505 23.48 87.9905 22.592C88.8545 21.704 89.2865 20.492 89.2865 18.956ZM118.832 18.632C118.832 19.208 118.796 19.808 118.724 20.432H104.792C104.888 21.68 105.284 22.64 105.98 23.312C106.7 23.96 107.576 24.284 108.608 24.284C110.144 24.284 111.212 23.636 111.812 22.34H118.364C118.028 23.66 117.416 24.848 116.528 25.904C115.664 26.96 114.572 27.788 113.252 28.388C111.932 28.988 110.456 29.288 108.824 29.288C106.856 29.288 105.104 28.868 103.568 28.028C102.032 27.188 100.832 25.988 99.9681 24.428C99.1041 22.868 98.6721 21.044 98.6721 18.956C98.6721 16.868 99.0921 15.044 99.9321 13.484C100.796 11.924 101.996 10.724 103.532 9.884C105.068 9.044 106.832 8.624 108.824 8.624C110.768 8.624 112.496 9.032 114.008 9.848C115.52 10.664 116.696 11.828 117.536 13.34C118.4 14.852 118.832 16.616 118.832 18.632ZM112.532 17.012C112.532 15.956 112.172 15.116 111.452 14.492C110.732 13.868 109.832 13.556 108.752 13.556C107.72 13.556 106.844 13.856 106.124 14.456C105.428 15.056 104.996 15.908 104.828 17.012H112.532ZM147.712 8.696C150.208 8.696 152.188 9.452 153.652 10.964C155.14 12.476 155.884 14.576 155.884 17.264V29H149.764V18.092C149.764 16.796 149.416 15.8 148.72 15.104C148.048 14.384 147.112 14.024 145.912 14.024C144.712 14.024 143.764 14.384 143.068 15.104C142.396 15.8 142.06 16.796 142.06 18.092V29H135.94V18.092C135.94 16.796 135.592 15.8 134.896 15.104C134.224 14.384 133.288 14.024 132.088 14.024C130.888 14.024 129.94 14.384 129.244 15.104C128.572 15.8 128.236 16.796 128.236 18.092V29H122.08V8.912H128.236V11.432C128.86 10.592 129.676 9.932 130.684 9.452C131.692 8.948 132.832 8.696 134.104 8.696C135.616 8.696 136.96 9.02 138.136 9.668C139.336 10.316 140.272 11.24 140.944 12.44C141.64 11.336 142.588 10.436 143.788 9.74C144.988 9.044 146.296 8.696 147.712 8.696ZM163.285 6.824C162.205 6.824 161.317 6.512 160.621 5.888C159.949 5.24 159.613 4.448 159.613 3.512C159.613 2.552 159.949 1.76 160.621 1.136C161.317 0.487998 162.205 0.163998 163.285 0.163998C164.341 0.163998 165.205 0.487998 165.877 1.136C166.573 1.76 166.921 2.552 166.921 3.512C166.921 4.448 166.573 5.24 165.877 5.888C165.205 6.512 164.341 6.824 163.285 6.824ZM166.345 8.912V29H160.189V8.912H166.345Z"
                            fill="white" />
                    </svg>
                </div> --}}
                <img src="{{ asset('LEVELFIT.png') }}" class="img-fluid" alt="">
            </a>


            <div class="nav-control">
                <div class="hamburger">
                    <span class="line"></span><span class="line"></span><span class="line"></span>
                    <svg width="26" height="26" viewBox="0 0 26 26" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <rect x="22" y="11" width="4" height="4" rx="2" fill="#2A353A" />
                        <rect x="11" width="4" height="4" rx="2" fill="#2A353A" />
                        <rect x="22" width="4" height="4" rx="2" fill="#2A353A" />
                        <rect x="11" y="11" width="4" height="4" rx="2" fill="#2A353A" />
                        <rect x="11" y="22" width="4" height="4" rx="2" fill="#2A353A" />
                        <rect width="4" height="4" rx="2" fill="#2A353A" />
                        <rect y="11" width="4" height="4" rx="2" fill="#2A353A" />
                        <rect x="22" y="22" width="4" height="4" rx="2" fill="#2A353A" />
                        <rect y="22" width="4" height="4" rx="2" fill="#2A353A" />
                    </svg>
                </div>
            </div>
        </div>
        <!--**********************************
            Nav header end
        ***********************************-->
