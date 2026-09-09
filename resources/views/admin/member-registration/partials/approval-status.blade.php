@if (optional(Auth::user()->branchStore)->member_approval_enabled)
    <div class="mt-1">
        @if ((bool) ($item->is_approved ?? false))
            <span class="badge badge-success badge-sm">Approved</span>
        @else
            <span class="badge badge-warning badge-sm">Need Approval</span>
        @endif
    </div>
@endif
