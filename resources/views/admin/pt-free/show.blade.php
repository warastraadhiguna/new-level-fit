<div class="col-xl-12">
    <div class="card">
        <div class="card-body">
            <div class="teacher-deatails">
                <h3 class="heading">Member's Profile:</h3>
                <div class="table-responsive">
                    <table class="table" border="2">
                        <tbody style="color: rgb(85, 85, 85);">
                            <tr>
                                <th><b>Full Name</b></th><th>: {{ data_get($trainerSession, 'members.full_name', '-') }}</th>
                                <th><b>Nick Name</b></th><th>: {{ data_get($trainerSession, 'members.nickname', '-') }}</th>
                            </tr>
                            <tr>
                                <th><b>Member Number</b></th><th>: {{ data_get($trainerSession, 'members.member_code', '-') }}</th>
                                <th><b>Card Number</b></th><th>: {{ data_get($trainerSession, 'members.card_number', '-') }}</th>
                            </tr>
                            <tr>
                                <th><b>Date of Birth</b></th><th>: {{ data_get($trainerSession, 'members.born') ? DateFormat($trainerSession->members->born, 'DD MMMM YYYY') : '-' }}</th>
                                <th><b>Phone Number</b></th><th>: {{ data_get($trainerSession, 'members.phone_number', '-') }}</th>
                            </tr>
                            <tr>
                                <th><b>Gender</b></th><th>: {{ data_get($trainerSession, 'members.gender', '-') }}</th>
                                <th><b>Address</b></th><th>: {{ data_get($trainerSession, 'members.address', '-') }}</th>
                            </tr>
                            <tr>
                                <th><b>Email</b></th><th>: {{ data_get($trainerSession, 'members.email', '-') }}</th>
                                <th><b>Instagram</b></th><th>: {{ data_get($trainerSession, 'members.ig', '-') }}</th>
                            </tr>
                            <tr>
                                <th><b>Emergency Contact</b></th><th>: {{ data_get($trainerSession, 'members.emergency_contact', '-') }}</th>
                                <th><b>Emergency Contact Name</b></th><th>: {{ data_get($trainerSession, 'members.ec_name', '-') }}</th>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="col-xl-12">
    <div class="card">
        <div class="card-body">
            <div class="accordion accordion-flush" id="ptFreePackageAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header text-white">
                        <button class="accordion-button bg-info text-white" type="button" data-bs-toggle="collapse"
                            data-bs-target="#ptFreePackageInfo" aria-expanded="true">Package Info: 1</button>
                    </h2>
                    <div id="ptFreePackageInfo" class="accordion-collapse collapse show" data-bs-parent="#ptFreePackageAccordion">
                        <div class="accordion-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <tbody style="color: rgb(85, 85, 85);">
                                        <tr><th><b>Package Name</b></th><td>{{ data_get($trainerSession, 'trainerPackages.package_name', '-') }}</td></tr>
                                        <tr><th><b>Number of Days</b></th><td>{{ $trainerSession->days }} Days</td></tr>
                                        <tr><th><b>Package Price</b></th><td>Free</td></tr>
                                        <tr><th><b>Personal Trainer</b></th><td>{{ data_get($trainerSession, 'personalTrainers.full_name', '-') }}</td></tr>
                                        <tr><th><b>Start Date</b></th><td>{{ $trainerSession->start_date ? DateFormat($trainerSession->start_date, 'DD MMMM YYYY') : '-' }}</td></tr>
                                        <tr><th><b>Expired Date</b></th><td>{{ $expiredDate ? DateFormat($expiredDate, 'DD MMMM YYYY') : '-' }}</td></tr>
                                        <tr><th><b>Session Usage</b></th><td>{{ $usedSessions }} / {{ $trainerSession->number_of_session }} ({{ max(0, (int) $trainerSession->number_of_session - $usedSessions) }} remaining)</td></tr>
                                        <tr><th><b>Description</b></th><td>{{ $trainerSession->description ?: '-' }}</td></tr>
                                        <tr><th><b>Created By</b></th><td>{{ data_get($trainerSession, 'users.full_name', '-') }}</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="col-xl-12">
    <div class="card">
        <div class="card-body">
            <h3 class="heading">Check In/Out History</h3>
            <div class="table-responsive full-data">
                <table class="table-responsive-lg table display dataTablesCard student-tab dataTable no-footer">
                    <thead><tr><th>No</th><th>Check In</th><th>Check Out</th><th>Trainer</th><th>Staff</th></tr></thead>
                    <tbody>
                        @forelse ($checkIns as $item)
                            <tr>
                                <td>{{ $checkIns->firstItem() + $loop->index }}</td>
                                <td>{{ DateFormat($item->check_in_time, 'DD MMMM YYYY, HH:mm:ss') }}</td>
                                <td>{{ $item->check_out_time ? DateFormat($item->check_out_time, 'DD MMMM YYYY, HH:mm:ss') : '-' }}</td>
                                <td>{{ data_get($item, 'personalTrainer.full_name', data_get($trainerSession, 'personalTrainers.full_name', '-')) }}</td>
                                <td>{{ data_get($item, 'users.full_name', '-') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center">No data available in table</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $checkIns->links('pagination::bootstrap-4') }}</div>
        </div>
    </div>
</div>
