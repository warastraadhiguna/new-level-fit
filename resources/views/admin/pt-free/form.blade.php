@php($isEdit = isset($trainerSession))

<div class="row mt-4">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <form action="{{ $isEdit ? route('pt-free.update', $trainerSession->id) : route('pt-free.store') }}" method="POST">
                    @csrf
                    @if ($isEdit)
                        @method('PUT')
                    @else
                        <input type="hidden" name="_submission_token" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                    @endif

                    <h3>{{ $isEdit ? 'Edit PT Free' : 'Create PT Free' }}</h3>
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (!$isEdit && $trainerPackages->isEmpty())
                        <div class="alert alert-warning">
                            Belum ada package PT Free. Tambahkan package dengan harga package dan admin Rp0 di Trainer Package.
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label class="form-label">Member Name</label>
                                @if ($isEdit)
                                    <input type="text" class="form-control" value="{{ data_get($trainerSession, 'members.full_name', '-') }} | {{ data_get($trainerSession, 'members.member_code', 'No member code') }}" disabled>
                                @else
                                    <select id="single-select5" name="member_id" class="form-control" required>
                                        <option value="">&lt;- Choose -&gt;</option>
                                        @foreach ($members as $item)
                                            <option value="{{ $item->id }}" {{ (string) old('member_id') === (string) $item->id ? 'selected' : '' }}>
                                                {{ $item->full_name }} | {{ $item->member_code ?? 'No member code' }} | {{ $item->phone_number }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>
                        </div>

                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label class="form-label">Trainer Name <small class="text-muted">(Kosongkan untuk Waiting List.)</small></label>
                                <select id="single-select6" name="trainer_id" class="form-control">
                                    <option value="">&lt;- No trainer yet (Waiting List) -&gt;</option>
                                    @foreach ($personalTrainers as $item)
                                        <option value="{{ $item->id }}" {{ (string) old('trainer_id', $isEdit ? $trainerSession->trainer_id : '') === (string) $item->id ? 'selected' : '' }}>
                                            {{ $item->full_name }} | {{ $item->phone_number }} | {{ $item->gender }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label class="form-label">PT Free Package</label>
                                <select id="single-select2" name="trainer_package_id" class="form-control" required>
                                    <option value="">&lt;- Choose -&gt;</option>
                                    @foreach ($trainerPackages as $item)
                                        <option value="{{ $item->id }}" {{ (string) old('trainer_package_id', $isEdit ? $trainerSession->trainer_package_id : '') === (string) $item->id ? 'selected' : '' }}>
                                            {{ $item->package_name }} | {{ $item->number_of_session }} Sessions | {{ $item->days }} Days
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-xl-6">
                            <div class="mb-3">
                                <label class="form-label">Start Date <small class="text-muted">(Kosongkan untuk Waiting List.)</small></label>
                                <input type="date" name="start_date" class="form-control"
                                    value="{{ old('start_date', $isEdit && $trainerSession->start_date ? \Carbon\Carbon::parse($trainerSession->start_date)->format('Y-m-d') : '') }}">
                            </div>
                        </div>

                        @if (Auth::user()->role == 'CS' || Auth::user()->isAdmin())
                            <div class="col-xl-6">
                                <div class="mb-3">
                                    <label class="form-label">Fitness Consultant</label>
                                    <select id="single-select4" name="fc_id" class="form-control">
                                        <option value="">&lt;- Choose -&gt;</option>
                                        @foreach ($fitnessConsultant as $item)
                                            <option value="{{ $item->id }}" {{ (string) old('fc_id', $isEdit ? $trainerSession->fc_id : '') === (string) $item->id ? 'selected' : '' }}>
                                                {{ $item->full_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endif

                        <div class="col-xl-12">
                            <div class="mb-3">
                                <label class="form-label text-primary">Description / Gift Reason</label>
                                <textarea class="form-control" name="description" rows="5" required
                                    placeholder="Enter Description">{{ old('description', $isEdit ? $trainerSession->description : '') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <button type="submit" class="btn btn-primary" {{ $trainerPackages->isEmpty() ? 'disabled' : '' }}>Save</button>
                        <a href="{{ route('pt-free.active') }}" class="btn btn-info">PT Free List</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
