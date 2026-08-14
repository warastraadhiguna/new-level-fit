<div class="row">
    <div class="col-xl-12">
        <div class="row">
            <div class="col-xl-12">
                <div class="page-title flex-wrap justify-content-between">
                    <a href="{{ route('member-pending', ['excel' => 1]) }}" class="btn btn-info">
                        Download Excel
                    </a>
                </div>
            </div>
            <!--column-->
            <div class="col-xl-12 wow fadeInUp" data-wow-delay="1.5s">
                <div class="table-responsive full-data">
                    <table class="table-responsive-lg table display dataTablesCard student-tab dataTable no-footer"
                        id="myTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                {{-- <th>Image</th> --}}
                                <th>Member Data</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($memberRegistrations as $item)
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
                                        <h6>{{ $item->member_name }},</h6>
                                        <h6>{{ $item->member_code }}</h6>
                                    </td>
                                    <td>
                                        <h6>{{ DateFormat($item->start_date, 'DD MMMM YYYY') }}-{{ DateFormat($item->expired_date, 'DD MMMM YYYY') }}
                                        </h6>
                                    </td>
                                    <td>
                                        <span class="badge badge-warning badge-lg">PENDING</span>
                                    </td>
                                    <td>
                                        @if (Auth::user()->role == 'ADMIN')
                                            <a href="{{ route('member-active.edit', $item->id) }}"
                                                class="btn light btn-warning btn-xs mb-1 btn-block">Edit</a>
                                        @endif
                                        <a href="{{ route('member-active.show', $item->id) }}"
                                            class="btn light btn-info btn-xs mb-1 btn-block">Detail</a>
                                        <a href="{{ route('renewal', $item->id) }}"
                                            class="btn light btn-dark btn-xs mb-1 btn-block">Renewal</a>
                                        @if (Auth::user()->role == 'ADMIN')
                                            <form action="{{ route('member-active.destroy', $item->id) }}"
                                                method="POST">
                                                @method('delete')
                                                @csrf
                                                <button type="submit"
                                                    class="btn light btn-danger btn-xs btn-block mb-1"
                                                    onclick="return confirm('Delete {{ $item->member_name }} member package ?')">Delete</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
