<div class="row">
    <div class="col-xl-12">
        <div class="row">
            <div class="col-xl-12">
                <div class="page-title flex-wrap justify-content-between">
                    <div>
                        <a href="{{ route('member-expired.index', ['excel' => 1]) }}" class="btn btn-info">
                            Download Excel
                        </a>
                    </div>
                    <div>
                        @if (!empty($memberRegistrationsOver))
                            <?php
                            $earliestDate = \Carbon\Carbon::parse($memberRegistrationsOver->min('earliest_created_at'))->format('Y-m-d');
                            $latestDate = \Carbon\Carbon::parse($memberRegistrationsOver->max('latest_created_at'))->format('Y-m-d');
                            ?>
                            <div class="date-section">
                                <p>{{ $earliestDate }} <b>to</b> {{ $latestDate }}</p>

                                @foreach ($memberRegistrationsOver as $session)
                                    <!-- Your display logic for each session goes here -->
                                @endforeach
                            </div>
                        @else
                            <p>No trainer sessions found.</p>
                        @endif
                    </div>
                </div>
            </div>
            {{-- <div class="col-xl-4">
                <div class="page-title flex-wrap">
                    <div>
                        @php
                            $totalPrice = 0;
                            $adminPrice = 0;
                        @endphp
                        @foreach ($memberRegistrationsOver as $item)
                            @php
                                $totalPrice += $item->total_price;
                                $adminPrice += $item->admin_price;
                            @endphp
                        @endforeach

                        <table class="table borderless display dataTable price-table">
                            <tbody>
                                <tr>
                                    <th scope="row">Total Package Price Over</th>
                                    <td>: {{ formatRupiah($totalPrice) }}</td>
                                </tr>
                                <tr>
                                    <th scope="row">Total Admin Price over</th>
                                    <td>: {{ formatRupiah($adminPrice) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div> --}}
            <!--column-->
            <div class="col-xl-12 wow fadeInUp" data-wow-delay="1.5s">
                <div class="table-responsive full-data">
                    {{-- <table class="table-responsive-lg table display dataTablesCard student-tab dataTable no-footer"
                        id="example-student"> --}}
                    <table class="table-responsive-lg table display dataTablesCard student-tab dataTable no-footer"
                        id="myTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                {{-- <th>Image</th> --}}
                                <th>Member's Data</th>
                                <th>Last Check In</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($memberRegistrationsOver as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    {{-- <td>
                                        <div class="trans-list">
                                            @if ($item->photos)
                                                <img src="{{ Storage::url($item->photos) }}" class="lazyload"
                                                    width="100" alt="image">
                                            @else
                                                <img src="{{ asset('default.png') }}" class="lazyload" width="100"
                                                    alt="default image">
                                            @endif
                                        </div>
                                    </td> --}}
                                    <td>
                                        <h6>{{ $item->full_name }},</h6>
                                        <h6>{{ $item->member_code }}</h6>
                                    </td>
                                    <td>
                                        <span class="badge badge-danger badge-lg">
                                            Expired
                                        </span>
                                    </td>
                                    <td>
                                        <h6>{{ DateFormat($item->start_date, 'DD MMMM YYYY') }}-{{ DateFormat($item->max_end_date, 'DD MMMM YYYY') }}
                                        </h6>
                                    </td>
                                    <td>
                                        <span class="badge badge-danger badge-lg">Expired</span>
                                    </td>
                                    <td>
                                        <div>
                                            @if (Auth::user()->isAdmin())
                                                <a href="{{ route('member-active.edit', $item->mr_id) }}"
                                                    class="btn light btn-warning btn-xs mb-1 btn-block">Edit</a>
                                            @endif
                                            <a href="{{ route('member-active.show', $item->mr_id) }}"
                                                class="btn light btn-info btn-xs mb-1 btn-block">Detail</a>
                                            <a href="{{ route('renewal', $item->mr_id) }}"
                                                class="btn light btn-dark btn-xs mb-1 btn-block">Renewal</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <!--/column-->
        </div>
    </div>
</div>
