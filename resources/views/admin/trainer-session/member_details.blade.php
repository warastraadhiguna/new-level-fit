<div>
    @if (session('message'))
        <p>{{ session('message') }}</p>
    @endif
    @if (session('memberPhoto'))
        <img src="{{ session('memberPhoto') }}" alt="Member Photo">
    @endif
    @if (session('memberName'))
        <p>{{ session('memberName') }}</p>
    @endif
    @if (session('nickName'))
        <p>{{ session('nickName') }}</p>
    @endif
    @if (session('memberCode'))
        <p>{{ session('memberCode') }}</p>
    @endif
    @if (session('phoneNumber'))
        <p>{{ session('phoneNumber') }}</p>
    @endif
    @if (session('born'))
        <p>{{ session('born') }}</p>
    @endif
    @if (session('gender'))
        <p>{{ session('gender') }}</p>
    @endif
    @if (session('email'))
        <p>{{ session('email') }}</p>
    @endif
    @if (session('ig'))
        <p>{{ session('ig') }}</p>
    @endif
    @if (session('eContact'))
        <p>{{ session('eContact') }}</p>
    @endif
    @if (session('address'))
        <p>{{ session('address') }}</p>
    @endif
    @if (session('memberPackage'))
        <p>{{ session('memberPackage') }}</p>
    @endif
    @if (session('days'))
        <p>{{ session('days') }}</p>
    @endif
    @if (session('startDate'))
        <p>{{ session('startDate') }}</p>
    @endif
    @if (session('expiredDate'))
        <p>{{ session('expiredDate') }}</p>
    @endif
</div>

<script>
    function formatDate(date) {
        var day = date.getDate();
        var monthIndex = date.getMonth();
        var year = date.getFullYear();

        var monthNames = [
            "January", "February", "March",
            "April", "May", "June", "July",
            "August", "September", "October",
            "November", "December"
        ];

        return day + ' ' + monthNames[monthIndex] + ' ' + year;
    }

    var message = "{{ $message }}";
    var memberPhoto = "{{ $memberPhoto }}";
    var memberName = "{{ $memberName }}";
    var nickName = "{{ $nickName }}";
    var memberCode = "{{ $memberCode }}";
    var phoneNumber = "{{ $phoneNumber }}";
    var born = "{{ $born }}";
    var gender = "{{ $gender }}";
    var email = "{{ $email }}";
    var ig = "{{ $ig }}";
    var eContact = "{{ $eContact }}";
    var address = "{{ $address }}";
    var memberPackage = "{{ $memberPackage }}";
    var days = "{{ $days }}";
    var startDateStr = "{{ $startDate }}";
    var expiredDateStr = "{{ $expiredDate }}";

    var startDate = new Date(startDateStr);
    var expiredDate = new Date(expiredDateStr);

    var contentHTML = `
        <div style="text-align: center;">
            <div style="background-color: rgb(0, 201, 33); font-size: 20px; color: rgb(255, 255, 255); text-align: center; border-radius: 7px;">
                <p>${message}</p>
            </div>
            <div class="trans-list" style="margin-top: 10px;">
                @if ($memberPhoto)
                    <img src="{{ Storage::url($memberPhoto) }}" class="lazyload" style="width: 100px;" alt="image">
                @else
                    <img src="{{ asset('default.png') }}" class="lazyload" style="width: 100px;" alt="default image">
                @endif
            </div>
            <h2 style="margin-top: 10px;">${memberName}</h2>
            <table class="table" border="1" style="margin: auto;">
                <thead>
                    <tr>
                        <th><b>Nick Name</b></th>
                        <td>${nickName}</td>
                    </tr>
                    <tr>
                        <th><b>Member Code</b></th>
                        <td>${memberCode}</td>
                    </tr>
                    <tr>
                        <th><b>Phone Number</b></th>
                        <td>${phoneNumber}</td>
                    </tr>
                    <tr>
                        <th><b>Date of Birth</b></th>
                        <td>${born}</td>
                    </tr>
                    <tr>
                        <th><b>Gender</b></th>
                        <td>${gender}</td>
                    </tr>
                    <tr>
                        <th><b>Email</b></th>
                        <td>${email}</td>
                    </tr>
                    <tr>
                        <th><b>Instagram</b></th>
                        <td>${ig}</td>
                    </tr>
                    <tr>
                        <th><b>Emergency Contact</b></th>
                        <td>${eContact}</td>
                    </tr>
                    <tr>
                        <th><b>Address</b></th>
                        <td>${address}</td>
                    </tr>
                </thead>
            </table>
        </div>

         <hr />

         <table class="table" border="1" style="margin: auto;">
                <thead>
                    <tr>
                        <th><b>Member Package</b></th>
                        <td>${memberPackage}</td>
                    </tr>
                    <tr>
                        <th><b>Number of Days</b></th>
                        <td>${days}</td>
                    </tr>
                    <tr>
                        <th><b>Start Date</b></th>
                        <td>${formatDate(startDate)}</td>
                    </tr>
                    <tr>
                        <th><b>Expired Date</b></th>
                        <td>${formatDate(expiredDate)}</td>
                    </tr>
                </thead>
            </table>
    `;

    function openNewWindow(contentHTML, width, height) {
        var leftPosition = (screen.width - width) / 2;
        var topPosition = (screen.height - height) / 2;

        var newWindow = window.open('', '_blank', 'width=' + width + ', height=' + height + ', left=' + leftPosition +
            ', top=' + topPosition);
        newWindow.document.write(contentHTML);

        setTimeout(function() {
            window.location.href = "{{ route('trainer-session.index') }}";
        }, 0);
    }

    document.addEventListener("DOMContentLoaded", function() {
        openNewWindow(contentHTML, 1000, 1000);
    });
</script>
