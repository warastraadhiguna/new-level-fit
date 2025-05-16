{{-- MEMBER REGISTRATION TOTAL AMOUNT --}}
@php
    $totalIncomeOfActiveMember = 0;
    $totalIncomeOfAdminActiveMember = 0;
@endphp
@foreach ($incomeOfActiveMember as $item)
    @php
        $totalIncomeOfActiveMember += $item->total_price;
        $totalIncomeOfAdminActiveMember += $item->admin_price;
    @endphp
@endforeach
<div class="row">
    <div class="col-xl-3 col-sm-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <ul class="d-flex align-items-center">
                    <li class="icon-box icon-box-lg bg-warning me-3">
                        <svg width="40" height="40" viewBox="0 0 40 40" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M36.2365 21.8435C33.9956 21.0315 31.5096 21.212 29.4077 22.3374L25.9812 23.8788C25.7043 22.3007 24.4923 21.1148 22.9963 21.0692C22.9875 21.069 17.8587 21.0111 17.8587 21.0111C13.7536 19.885 11.0862 21.332 9.56458 22.7579C9.25338 23.0496 8.9797 23.3498 8.73885 23.646C8.32825 23.2038 7.60347 23.0856 7.07492 23.3762L2.41928 25.9354C1.81543 26.2674 1.5527 26.9956 1.80594 27.6358L6.35586 39.1377C6.65483 39.8934 7.57364 40.2274 8.29056 39.8333L12.9462 37.2742C13.3826 37.0343 13.6395 36.5873 13.6536 36.1162H20.6022C21.7356 36.1162 22.8546 35.8185 23.8382 35.2553C23.8382 35.2553 36.9065 27.7589 36.9803 27.6919C38.8104 26.027 38.8668 22.7966 36.2365 21.8435C37.2858 22.2237 33.9956 21.0315 36.2365 21.8435ZM8.33218 36.688L4.7968 27.7508L7.03304 26.5216L10.5684 35.4588L8.33218 36.688ZM35.2316 25.5773L22.4747 32.8826C21.9054 33.2087 21.2578 33.381 20.6019 33.381H12.6919L10.036 26.667C10.2636 26.2034 10.7117 25.4342 11.4394 24.7522C12.93 23.3555 14.8946 22.998 17.2791 23.6898C17.3983 23.7244 17.5218 23.7427 17.6459 23.7441L22.918 23.8034C23.0526 23.815 23.3011 24.1143 23.3011 24.568C23.3011 25.035 23.0445 25.3327 22.9103 25.3327H17.7302V28.0679H22.9103C23.552 28.0679 24.1492 27.8509 24.6463 27.4791L30.5779 24.8109C30.6094 24.7968 30.6401 24.7815 30.6704 24.765C32.0933 23.9914 33.7815 23.8636 35.3018 24.4145C35.9035 24.6326 35.4688 25.3364 35.2316 25.5773ZM27 19.7079C21.5669 19.7079 17.1467 15.2874 17.1467 9.85393C17.1467 4.42051 21.5668 0 27 0C32.4331 0 36.8532 4.42051 36.8532 9.85393C36.8532 15.2874 32.433 19.7079 27 19.7079ZM27 2.73521C23.0775 2.73521 19.8864 5.92863 19.8864 9.85393C19.8864 13.7792 23.0776 16.9727 27 16.9727C30.9225 16.9727 34.1136 13.7791 34.1136 9.85393C34.1136 5.92872 30.9224 2.73521 27 2.73521Z"
                                fill="white" />
                            <path
                                d="M27.6362 8.73923C26.5469 8.29188 26.4627 8.09966 26.4627 7.87684C26.4627 7.73453 26.5333 7.40368 27.1876 7.40368C27.7862 7.40368 28.532 7.73966 29.058 8.0859L29.7897 6.16795C29.2673 5.83539 28.6324 5.54966 28.0388 5.45829V4.21103H26.0879V5.57966C24.9289 5.94496 24.2147 6.87146 24.2147 8.02479C24.2147 9.55231 25.4368 10.2343 26.6304 10.6991C27.5841 11.0828 27.664 11.3765 27.664 11.6217C27.664 11.9989 27.3164 12.2426 26.7785 12.2426C26.077 12.2426 25.2614 11.838 24.6903 11.3952L23.9863 13.3439C24.5686 13.7954 25.2426 14.0933 26.0009 14.2045V15.4969H27.964V14.0901C29.1592 13.7095 29.9242 12.7193 29.9242 11.5354C29.9242 9.87812 28.7015 9.1706 27.6362 8.73923Z"
                                fill="white" />
                        </svg>
                    </li>
                    <li class="text-end">
                        <span><b>Income of Member Registrations</b></span> <br />
                        <small>{{ $monthNow = \Carbon\Carbon::now()->tz('Asia/Jakarta')->formatLocalized('%B') }}</small>
                        <h5 class="my-3">{{ formatRupiah($totalIncomeOfActiveMember) }}</h5>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <ul class="d-flex align-items-center">
                    <li class="icon-box icon-box-lg bg-warning me-3">
                        <svg width="40" height="40" viewBox="0 0 40 40" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M36.2365 21.8435C33.9956 21.0315 31.5096 21.212 29.4077 22.3374L25.9812 23.8788C25.7043 22.3007 24.4923 21.1148 22.9963 21.0692C22.9875 21.069 17.8587 21.0111 17.8587 21.0111C13.7536 19.885 11.0862 21.332 9.56458 22.7579C9.25338 23.0496 8.9797 23.3498 8.73885 23.646C8.32825 23.2038 7.60347 23.0856 7.07492 23.3762L2.41928 25.9354C1.81543 26.2674 1.5527 26.9956 1.80594 27.6358L6.35586 39.1377C6.65483 39.8934 7.57364 40.2274 8.29056 39.8333L12.9462 37.2742C13.3826 37.0343 13.6395 36.5873 13.6536 36.1162H20.6022C21.7356 36.1162 22.8546 35.8185 23.8382 35.2553C23.8382 35.2553 36.9065 27.7589 36.9803 27.6919C38.8104 26.027 38.8668 22.7966 36.2365 21.8435C37.2858 22.2237 33.9956 21.0315 36.2365 21.8435ZM8.33218 36.688L4.7968 27.7508L7.03304 26.5216L10.5684 35.4588L8.33218 36.688ZM35.2316 25.5773L22.4747 32.8826C21.9054 33.2087 21.2578 33.381 20.6019 33.381H12.6919L10.036 26.667C10.2636 26.2034 10.7117 25.4342 11.4394 24.7522C12.93 23.3555 14.8946 22.998 17.2791 23.6898C17.3983 23.7244 17.5218 23.7427 17.6459 23.7441L22.918 23.8034C23.0526 23.815 23.3011 24.1143 23.3011 24.568C23.3011 25.035 23.0445 25.3327 22.9103 25.3327H17.7302V28.0679H22.9103C23.552 28.0679 24.1492 27.8509 24.6463 27.4791L30.5779 24.8109C30.6094 24.7968 30.6401 24.7815 30.6704 24.765C32.0933 23.9914 33.7815 23.8636 35.3018 24.4145C35.9035 24.6326 35.4688 25.3364 35.2316 25.5773ZM27 19.7079C21.5669 19.7079 17.1467 15.2874 17.1467 9.85393C17.1467 4.42051 21.5668 0 27 0C32.4331 0 36.8532 4.42051 36.8532 9.85393C36.8532 15.2874 32.433 19.7079 27 19.7079ZM27 2.73521C23.0775 2.73521 19.8864 5.92863 19.8864 9.85393C19.8864 13.7792 23.0776 16.9727 27 16.9727C30.9225 16.9727 34.1136 13.7791 34.1136 9.85393C34.1136 5.92872 30.9224 2.73521 27 2.73521Z"
                                fill="white" />
                            <path
                                d="M27.6362 8.73923C26.5469 8.29188 26.4627 8.09966 26.4627 7.87684C26.4627 7.73453 26.5333 7.40368 27.1876 7.40368C27.7862 7.40368 28.532 7.73966 29.058 8.0859L29.7897 6.16795C29.2673 5.83539 28.6324 5.54966 28.0388 5.45829V4.21103H26.0879V5.57966C24.9289 5.94496 24.2147 6.87146 24.2147 8.02479C24.2147 9.55231 25.4368 10.2343 26.6304 10.6991C27.5841 11.0828 27.664 11.3765 27.664 11.6217C27.664 11.9989 27.3164 12.2426 26.7785 12.2426C26.077 12.2426 25.2614 11.838 24.6903 11.3952L23.9863 13.3439C24.5686 13.7954 25.2426 14.0933 26.0009 14.2045V15.4969H27.964V14.0901C29.1592 13.7095 29.9242 12.7193 29.9242 11.5354C29.9242 9.87812 28.7015 9.1706 27.6362 8.73923Z"
                                fill="white" />
                        </svg>
                    </li>
                    <li class="text-end">
                        <span><b>Income of Admin Members</b></span> <br />
                        <small>{{ $monthNow = \Carbon\Carbon::now()->tz('Asia/Jakarta')->formatLocalized('%B') }}</small>
                        <h5 class="my-3">{{ formatRupiah($totalIncomeOfAdminActiveMember) }}</h5>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    @php
        $totalIncomeOfActivePT = 0;
    @endphp
    @foreach ($incomeOfActivePT as $item)
        @php
            $totalIncomeOfActivePT += $item->total_price;
        @endphp
    @endforeach
    <div class="col-xl-3 col-sm-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <ul class="d-flex align-items-center">
                    <li class="icon-box icon-box-lg bg-warning me-3">
                        <svg width="40" height="40" viewBox="0 0 40 40" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M36.2365 21.8435C33.9956 21.0315 31.5096 21.212 29.4077 22.3374L25.9812 23.8788C25.7043 22.3007 24.4923 21.1148 22.9963 21.0692C22.9875 21.069 17.8587 21.0111 17.8587 21.0111C13.7536 19.885 11.0862 21.332 9.56458 22.7579C9.25338 23.0496 8.9797 23.3498 8.73885 23.646C8.32825 23.2038 7.60347 23.0856 7.07492 23.3762L2.41928 25.9354C1.81543 26.2674 1.5527 26.9956 1.80594 27.6358L6.35586 39.1377C6.65483 39.8934 7.57364 40.2274 8.29056 39.8333L12.9462 37.2742C13.3826 37.0343 13.6395 36.5873 13.6536 36.1162H20.6022C21.7356 36.1162 22.8546 35.8185 23.8382 35.2553C23.8382 35.2553 36.9065 27.7589 36.9803 27.6919C38.8104 26.027 38.8668 22.7966 36.2365 21.8435C37.2858 22.2237 33.9956 21.0315 36.2365 21.8435ZM8.33218 36.688L4.7968 27.7508L7.03304 26.5216L10.5684 35.4588L8.33218 36.688ZM35.2316 25.5773L22.4747 32.8826C21.9054 33.2087 21.2578 33.381 20.6019 33.381H12.6919L10.036 26.667C10.2636 26.2034 10.7117 25.4342 11.4394 24.7522C12.93 23.3555 14.8946 22.998 17.2791 23.6898C17.3983 23.7244 17.5218 23.7427 17.6459 23.7441L22.918 23.8034C23.0526 23.815 23.3011 24.1143 23.3011 24.568C23.3011 25.035 23.0445 25.3327 22.9103 25.3327H17.7302V28.0679H22.9103C23.552 28.0679 24.1492 27.8509 24.6463 27.4791L30.5779 24.8109C30.6094 24.7968 30.6401 24.7815 30.6704 24.765C32.0933 23.9914 33.7815 23.8636 35.3018 24.4145C35.9035 24.6326 35.4688 25.3364 35.2316 25.5773ZM27 19.7079C21.5669 19.7079 17.1467 15.2874 17.1467 9.85393C17.1467 4.42051 21.5668 0 27 0C32.4331 0 36.8532 4.42051 36.8532 9.85393C36.8532 15.2874 32.433 19.7079 27 19.7079ZM27 2.73521C23.0775 2.73521 19.8864 5.92863 19.8864 9.85393C19.8864 13.7792 23.0776 16.9727 27 16.9727C30.9225 16.9727 34.1136 13.7791 34.1136 9.85393C34.1136 5.92872 30.9224 2.73521 27 2.73521Z"
                                fill="white" />
                            <path
                                d="M27.6362 8.73923C26.5469 8.29188 26.4627 8.09966 26.4627 7.87684C26.4627 7.73453 26.5333 7.40368 27.1876 7.40368C27.7862 7.40368 28.532 7.73966 29.058 8.0859L29.7897 6.16795C29.2673 5.83539 28.6324 5.54966 28.0388 5.45829V4.21103H26.0879V5.57966C24.9289 5.94496 24.2147 6.87146 24.2147 8.02479C24.2147 9.55231 25.4368 10.2343 26.6304 10.6991C27.5841 11.0828 27.664 11.3765 27.664 11.6217C27.664 11.9989 27.3164 12.2426 26.7785 12.2426C26.077 12.2426 25.2614 11.838 24.6903 11.3952L23.9863 13.3439C24.5686 13.7954 25.2426 14.0933 26.0009 14.2045V15.4969H27.964V14.0901C29.1592 13.7095 29.9242 12.7193 29.9242 11.5354C29.9242 9.87812 28.7015 9.1706 27.6362 8.73923Z"
                                fill="white" />
                        </svg>
                    </li>
                    <li class="text-end">
                        <span><b>Income of Trainer Session</b></span> <br />
                        <small>{{ $monthNow = \Carbon\Carbon::now()->tz('Asia/Jakarta')->formatLocalized('%B') }}</small>
                        <h5 class="my-3">{{ formatRupiah($totalIncomeOfActivePT) }}</h5>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    @php
        $totalIncomeOfActiveLGT = 0;
    @endphp
    @foreach ($incomeOfActiveLGT as $item)
        @php
            $totalIncomeOfActiveLGT += $item->total_price;
        @endphp
    @endforeach
    <div class="col-xl-3 col-sm-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <ul class="d-flex align-items-center">
                    <li class="icon-box icon-box-lg bg-warning me-3">
                        <svg width="40" height="40" viewBox="0 0 40 40" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M36.2365 21.8435C33.9956 21.0315 31.5096 21.212 29.4077 22.3374L25.9812 23.8788C25.7043 22.3007 24.4923 21.1148 22.9963 21.0692C22.9875 21.069 17.8587 21.0111 17.8587 21.0111C13.7536 19.885 11.0862 21.332 9.56458 22.7579C9.25338 23.0496 8.9797 23.3498 8.73885 23.646C8.32825 23.2038 7.60347 23.0856 7.07492 23.3762L2.41928 25.9354C1.81543 26.2674 1.5527 26.9956 1.80594 27.6358L6.35586 39.1377C6.65483 39.8934 7.57364 40.2274 8.29056 39.8333L12.9462 37.2742C13.3826 37.0343 13.6395 36.5873 13.6536 36.1162H20.6022C21.7356 36.1162 22.8546 35.8185 23.8382 35.2553C23.8382 35.2553 36.9065 27.7589 36.9803 27.6919C38.8104 26.027 38.8668 22.7966 36.2365 21.8435C37.2858 22.2237 33.9956 21.0315 36.2365 21.8435ZM8.33218 36.688L4.7968 27.7508L7.03304 26.5216L10.5684 35.4588L8.33218 36.688ZM35.2316 25.5773L22.4747 32.8826C21.9054 33.2087 21.2578 33.381 20.6019 33.381H12.6919L10.036 26.667C10.2636 26.2034 10.7117 25.4342 11.4394 24.7522C12.93 23.3555 14.8946 22.998 17.2791 23.6898C17.3983 23.7244 17.5218 23.7427 17.6459 23.7441L22.918 23.8034C23.0526 23.815 23.3011 24.1143 23.3011 24.568C23.3011 25.035 23.0445 25.3327 22.9103 25.3327H17.7302V28.0679H22.9103C23.552 28.0679 24.1492 27.8509 24.6463 27.4791L30.5779 24.8109C30.6094 24.7968 30.6401 24.7815 30.6704 24.765C32.0933 23.9914 33.7815 23.8636 35.3018 24.4145C35.9035 24.6326 35.4688 25.3364 35.2316 25.5773ZM27 19.7079C21.5669 19.7079 17.1467 15.2874 17.1467 9.85393C17.1467 4.42051 21.5668 0 27 0C32.4331 0 36.8532 4.42051 36.8532 9.85393C36.8532 15.2874 32.433 19.7079 27 19.7079ZM27 2.73521C23.0775 2.73521 19.8864 5.92863 19.8864 9.85393C19.8864 13.7792 23.0776 16.9727 27 16.9727C30.9225 16.9727 34.1136 13.7791 34.1136 9.85393C34.1136 5.92872 30.9224 2.73521 27 2.73521Z"
                                fill="white" />
                            <path
                                d="M27.6362 8.73923C26.5469 8.29188 26.4627 8.09966 26.4627 7.87684C26.4627 7.73453 26.5333 7.40368 27.1876 7.40368C27.7862 7.40368 28.532 7.73966 29.058 8.0859L29.7897 6.16795C29.2673 5.83539 28.6324 5.54966 28.0388 5.45829V4.21103H26.0879V5.57966C24.9289 5.94496 24.2147 6.87146 24.2147 8.02479C24.2147 9.55231 25.4368 10.2343 26.6304 10.6991C27.5841 11.0828 27.664 11.3765 27.664 11.6217C27.664 11.9989 27.3164 12.2426 26.7785 12.2426C26.077 12.2426 25.2614 11.838 24.6903 11.3952L23.9863 13.3439C24.5686 13.7954 25.2426 14.0933 26.0009 14.2045V15.4969H27.964V14.0901C29.1592 13.7095 29.9242 12.7193 29.9242 11.5354C29.9242 9.87812 28.7015 9.1706 27.6362 8.73923Z"
                                fill="white" />
                        </svg>
                    </li>
                    <li class="text-end">
                        <span><b>Income of Active LGT</b></span> <br />
                        <small>{{ $monthNow = \Carbon\Carbon::now()->tz('Asia/Jakarta')->formatLocalized('%B') }}</small>
                        <h5 class="my-3">{{ formatRupiah($totalIncomeOfActiveLGT) }}</h5>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    @php
        $totalIncomeOfOneDayVisit = 0;
    @endphp
    @foreach ($incomeOfOneDayVisit as $item)
        @php
            $totalIncomeOfOneDayVisit += $item->total_price;
        @endphp
    @endforeach
    <div class="col-xl-3 col-sm-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <ul class="d-flex align-items-center">
                    <li class="icon-box icon-box-lg bg-warning me-3">
                        <svg width="40" height="40" viewBox="0 0 40 40" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M36.2365 21.8435C33.9956 21.0315 31.5096 21.212 29.4077 22.3374L25.9812 23.8788C25.7043 22.3007 24.4923 21.1148 22.9963 21.0692C22.9875 21.069 17.8587 21.0111 17.8587 21.0111C13.7536 19.885 11.0862 21.332 9.56458 22.7579C9.25338 23.0496 8.9797 23.3498 8.73885 23.646C8.32825 23.2038 7.60347 23.0856 7.07492 23.3762L2.41928 25.9354C1.81543 26.2674 1.5527 26.9956 1.80594 27.6358L6.35586 39.1377C6.65483 39.8934 7.57364 40.2274 8.29056 39.8333L12.9462 37.2742C13.3826 37.0343 13.6395 36.5873 13.6536 36.1162H20.6022C21.7356 36.1162 22.8546 35.8185 23.8382 35.2553C23.8382 35.2553 36.9065 27.7589 36.9803 27.6919C38.8104 26.027 38.8668 22.7966 36.2365 21.8435C37.2858 22.2237 33.9956 21.0315 36.2365 21.8435ZM8.33218 36.688L4.7968 27.7508L7.03304 26.5216L10.5684 35.4588L8.33218 36.688ZM35.2316 25.5773L22.4747 32.8826C21.9054 33.2087 21.2578 33.381 20.6019 33.381H12.6919L10.036 26.667C10.2636 26.2034 10.7117 25.4342 11.4394 24.7522C12.93 23.3555 14.8946 22.998 17.2791 23.6898C17.3983 23.7244 17.5218 23.7427 17.6459 23.7441L22.918 23.8034C23.0526 23.815 23.3011 24.1143 23.3011 24.568C23.3011 25.035 23.0445 25.3327 22.9103 25.3327H17.7302V28.0679H22.9103C23.552 28.0679 24.1492 27.8509 24.6463 27.4791L30.5779 24.8109C30.6094 24.7968 30.6401 24.7815 30.6704 24.765C32.0933 23.9914 33.7815 23.8636 35.3018 24.4145C35.9035 24.6326 35.4688 25.3364 35.2316 25.5773ZM27 19.7079C21.5669 19.7079 17.1467 15.2874 17.1467 9.85393C17.1467 4.42051 21.5668 0 27 0C32.4331 0 36.8532 4.42051 36.8532 9.85393C36.8532 15.2874 32.433 19.7079 27 19.7079ZM27 2.73521C23.0775 2.73521 19.8864 5.92863 19.8864 9.85393C19.8864 13.7792 23.0776 16.9727 27 16.9727C30.9225 16.9727 34.1136 13.7791 34.1136 9.85393C34.1136 5.92872 30.9224 2.73521 27 2.73521Z"
                                fill="white" />
                            <path
                                d="M27.6362 8.73923C26.5469 8.29188 26.4627 8.09966 26.4627 7.87684C26.4627 7.73453 26.5333 7.40368 27.1876 7.40368C27.7862 7.40368 28.532 7.73966 29.058 8.0859L29.7897 6.16795C29.2673 5.83539 28.6324 5.54966 28.0388 5.45829V4.21103H26.0879V5.57966C24.9289 5.94496 24.2147 6.87146 24.2147 8.02479C24.2147 9.55231 25.4368 10.2343 26.6304 10.6991C27.5841 11.0828 27.664 11.3765 27.664 11.6217C27.664 11.9989 27.3164 12.2426 26.7785 12.2426C26.077 12.2426 25.2614 11.838 24.6903 11.3952L23.9863 13.3439C24.5686 13.7954 25.2426 14.0933 26.0009 14.2045V15.4969H27.964V14.0901C29.1592 13.7095 29.9242 12.7193 29.9242 11.5354C29.9242 9.87812 28.7015 9.1706 27.6362 8.73923Z"
                                fill="white" />
                        </svg>
                    </li>
                    <li class="text-end">
                        <span><b>Income of One Day Visit</b></span> <br />
                        <small>{{ $monthNow = \Carbon\Carbon::now()->tz('Asia/Jakarta')->formatLocalized('%B') }}</small>
                        <h5 class="my-3">{{ formatRupiah($totalIncomeOfOneDayVisit) }}</h5>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<hr />

<div class="col-xl-4 col-xxl-4 col-lg-6 col-sm-6">
    <a href="{{ route('members.index') }}">
        <div class="widget-stat card bg-primary">
            <div class="card-body  p-4">
                <div class="media">
                    <span class="me-3">
                        <i class="la la-users"></i>
                    </span>
                    <div class="media-body text-white text-end">
                        <p class="mb-1 text-white">Total Members</p>
                        <h3 class="text-white">{{ $totalMembers }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </a>
</div>

{{-- One Day Visit --}}
<div class="col-xl-4 col-xxl-4 col-lg-6 col-sm-6">
    <a href="{{ route('oneDayVisit') }}">
        <div class="widget-stat card bg-primary">
            <div class="card-body  p-4">
                <div class="media">
                    <span class="me-3">
                        <i class="la la-users"></i>
                    </span>
                    <div class="media-body text-white text-end">
                        <p class="mb-1 text-white">Total One Day Visit</p>
                        <h3 class="text-white">{{ $totalOneDayVisit }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </a>
</div>

<hr>

{{-- MEMBER --}}
<div class="row">
    <div class="col-xl-4 col-xxl-4 col-lg-4 col-sm-4">
        <a href="{{ route('members.index') }}">
            <div class="widget-stat card bg-primary">
                <div class="card-body p-4">
                    <div class="media">
                        <span class="me-3">
                            <i class="la la-users"></i>
                        </span>
                        <div class="media-body text-white text-end">

                            <p class="mb-1 text-white">Total Register Member Package</p>
                            <h3 class="text-white">{{ $totalMemberRegister }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-xl-4 col-xxl-4 col-lg-4 col-sm-4">
        <a href="{{ route('member-active.index') }}">
            <div class="widget-stat card bg-info">
                <div class="card-body  p-4">
                    <div class="media">
                        <span class="me-3">
                            <i class="la la-users"></i>
                        </span>
                        <div class="media-body text-white text-end">
                            <p class="mb-1 text-white">Total Member Active</p>
                            <h3 class="text-white">{{ $memberRegisterActive }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-xl-4 col-xxl-4 col-lg-4 col-sm-4">
        <a href="{{ route('member-expired.index') }}">
            <div class="widget-stat card bg-danger">
                <div class="card-body p-4">
                    <div class="media">
                        <span class="me-3">
                            <i class="la la-users"></i>
                        </span>
                        <div class="media-body text-white text-end">
                            <p class="mb-1 text-white">Total Member Expired</p>
                            <h3 class="text-white">{{ $memberRegisterExpired }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-xl-4 col-xxl-4 col-lg-4 col-sm-4">
        <a href="{{ route('member-expired.index') }}">
            <div class="widget-stat card bg-success">
                <div class="card-body p-4">
                    <div class="media">
                        <span class="me-3">
                            <i class="la la-users"></i>
                        </span>
                        <div class="media-body text-white text-end">
                            <p class="mb-1 text-white">Total Member Pending</p>
                            <h3 class="text-white">{{ $memberRegisterPending }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<hr />

{{-- TRAINER --}}
<div class="row">
    <div class="col-xl-4 col-xxl-4 col-lg-6 col-sm-6">
        <a href="{{ route('staff.index') }}">
            <div class="widget-stat card bg-primary">
                <div class="card-body p-4">
                    <div class="media">
                        <span class="me-3">
                            <i class="la la-users"></i>
                        </span>
                        <div class="media-body text-white text-end">
                            <p class="mb-1 text-white">Total Trainer Session</p>
                            <h3 class="text-white">{{ $totalTrainerSessions }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-xl-4 col-xxl-4 col-lg-6 col-sm-6">
        <a href="{{ route('trainer-session.index') }}">
            <div class="widget-stat card bg-info">
                <div class="card-body  p-4">
                    <div class="media">
                        <span class="me-3">
                            <i class="la la-users"></i>
                        </span>
                        <div class="media-body text-white text-end">
                            <p class="mb-1 text-white">Trainer Session Active</p>
                            <h3 class="text-white">{{ $trainerSessionActive }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-xl-4 col-xxl-4 col-lg-6 col-sm-6">
        <a href="{{ route('trainer-session-over.index') }}">
            <div class="widget-stat card bg-danger">
                <div class="card-body  p-4">
                    <div class="media">
                        <span class="me-3">
                            <i class="la la-users"></i>
                        </span>
                        <div class="media-body text-white text-end">
                            <p class="mb-1 text-white">Trainer Session Expired</p>
                            <h3 class="text-white">{{ $trainerSessionExpired }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <hr />

    {{-- LGT --}}
    <div class="col-xl-4 col-xxl-4 col-lg-6 col-sm-6">
        <a href="{{ route('lgt') }}">
            <div class="widget-stat card bg-primary">
                <div class="card-body  p-4">
                    <div class="media">
                        <span class="me-3">
                            <i class="la la-users"></i>
                        </span>
                        <div class="media-body text-white text-end">
                            <p class="mb-1 text-white">Total LGT</p>
                            <h3 class="text-white">{{ $totalLevelGroupTrainings }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>
