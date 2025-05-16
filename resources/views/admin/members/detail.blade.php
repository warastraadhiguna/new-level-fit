<div class="col-xl-12">
    <div class="card">
        <div class="card-body">
            <div class="teacher-deatails">
                <h3 class="heading">Member's Profile:</h3>
                <table class="table" border="2">
                    <thead>
                        <tr>
                            <td>
                                <h6>Full Name</h6>
                            </td>
                            <td style="border-right: 2px solid rgb(212, 212, 212);">
                                <h6> : {{ $members->full_name }} </h6>
                            </td>
                            <td>
                                <h6>Nickname
                            </td>
                            <td>
                                <h6> : {{ $members->nickname }}</h6>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <h6>Member Code
                            </td>
                            <td style="border-right: 2px solid rgb(212, 212, 212);">
                                <h6> : {{ $members->member_code }}</h6>
                            </td>
                            <td>
                                <h6>Card Number
                            </td>
                            <td>
                                <h6>: {{ $members->card_number }}</h6>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <h6>Date of Birth</h6>
                            </td>
                            <td style="border-right: 2px solid rgb(212, 212, 212);">
                                <h6>: {{ DateFormat($members->born, 'DD MMMM YYYY') }}</h6>
                            </td>
                            <td>
                                <h6>Phone Number
                            </td>
                            <td>
                                <h6>: {{ $members->phone_number }}</h6>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <h6>Gender </h6>
                            </td>
                            <td style="border-right: 2px solid rgb(212, 212, 212);">
                                <h6> : {{ $members->gender }}</h6>
                            </td>
                            <td>
                                <h6>Address
                            </td>
                            <td>
                                <h6>: {{ $members->address }}</h6>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <h6>Email</h6>
                            </td>
                            <td style="text-transform: lowercase; border-right: 2px solid rgb(212, 212, 212);">
                                <h6>:
                                    {{ $members->email }}</h6>
                            </td>
                            <td>
                                <h6>Instragram</h6>
                            </td>
                            <td>
                                <h6>: {{ $members->ig }}</h6>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <h6>Emergency Contact</h6>
                            </td>
                            <td style="text-transform: lowercase; border-right: 2px solid rgb(212, 212, 212);">
                                <h6>:
                                    {{ $members->emergency_contact }}</h6>
                            </td>
                            <td>
                                <h6>Emergency Contact Name </h6>
                            </td>
                            <td>
                                <h6>: {{ $members->ec_name }}</h6>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <h6>Photo :</h6>
                                <img src="{{ Storage::url($members->photos) }}" class="lazyload mt-2"
                                    style="width: 200px;" alt="image">
                            </td>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-between">
        <a href="{{ route('members.index') }}" class="btn btn-info text-right">Back</a>
    </div>
</div>
