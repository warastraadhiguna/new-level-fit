<form method="GET" action="{{ route('employee-schedules.index') }}" class="row g-3 align-items-end mb-3">
    <div class="col-md-2"><label for="scheduleFrom" class="form-label">Dari</label><input id="scheduleFrom" type="date" name="from" value="{{ $from }}" class="form-control" required></div>
    <div class="col-md-2"><label for="scheduleTo" class="form-label">Sampai</label><input id="scheduleTo" type="date" name="to" value="{{ $to }}" class="form-control" required></div>
    <div class="col-md-3"><label for="schedulePosition" class="form-label">Jabatan</label><select id="schedulePosition" name="position_category" class="form-control">
        <option value="">Semua jabatan</option>
        @foreach($positionCategories as $code=>$category)<option value="{{ $code }}" {{ (string)request('position_category')===(string)$code ? 'selected':'' }}>{{ $category->name }}{{ !$category->is_active ? ' (Nonaktif)':'' }}</option>@endforeach
    </select></div>
    <div class="col-md-3"><label for="scheduleSearch" class="form-label">Search</label><input id="scheduleSearch" name="search" value="{{ request('search') }}" class="form-control" maxlength="150" placeholder="Cari nama atau kode pegawai"></div>
    <div class="col-md-2 d-flex gap-2"><button type="submit" class="btn btn-primary">Filter</button><a href="{{ route('employee-schedules.index') }}" class="btn btn-outline-secondary">Reset</a></div>
</form>
