<style>
.employee-birthday a{color:#000}.employee-birthday-today{position:relative;overflow:hidden}
.employee-birthday-today::after{content:"";position:absolute;right:15px;top:5px;width:60px;height:60px;background:url('/cake.png') center/contain no-repeat;pointer-events:none;animation:employee-birthday-cake 5s linear infinite}
@keyframes employee-birthday-cake{0%,100%{transform:translateY(0);opacity:.3}50%{transform:translateY(-12px);opacity:1}}
@media(prefers-reduced-motion:reduce){.employee-birthday-today::after{animation:none}}
</style>
@foreach($birthdayMessages as $days => $names)
    @foreach($names as $employeeId => $employeeName)
        <div class="employee-birthday alert alert-primary solid {{ $days===0 ? 'bg-info employee-birthday-today' : 'bg-warning' }}">
            <a href="{{ route('employees.edit',$employeeId) }}" class="d-block" style="padding-right:65px">
                <span aria-hidden="true">🎂</span>
                @if($days===0)Hari ini ulang tahun <strong>{{ $employeeName }}</strong>.
                @elseif($days===1)Besok ulang tahun <strong>{{ $employeeName }}</strong>.
                @else<strong>{{ $employeeName }}</strong> ulang tahun 2 hari lagi.@endif
            </a>
        </div>
    @endforeach
@endforeach
