<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PtFreeSessionExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    private $sessions;

    public function __construct(Collection $sessions)
    {
        $this->sessions = $sessions;
    }

    public function collection()
    {
        return $this->sessions;
    }

    public function headings(): array
    {
        return [
            'Member Code',
            'Member Name',
            'Package Name',
            'Trainer Name',
            'Branch',
            'Start Date',
            'Expired Date',
            'Total Session',
            'Used Session',
            'Remaining Session',
            'Last Check In',
            'Last Check Out',
            'Description',
            'Created By',
        ];
    }

    public function map($session): array
    {
        return [
            $session->member_code,
            $session->member_name,
            $session->package_name,
            $session->trainer_name ?: '-',
            $session->branch_store_name,
            $this->formatDate($session->start_date),
            $this->formatDate($session->expired_date),
            (int) $session->number_of_session,
            (int) $session->used_sessions,
            max(0, (int) $session->remaining_sessions),
            $this->formatDateTime($session->check_in_time),
            $this->formatDateTime($session->check_out_time),
            $session->description,
            $session->staff_name,
        ];
    }

    private function formatDate($value): string
    {
        return $value ? Carbon::parse($value)->format('d-m-Y') : '-';
    }

    private function formatDateTime($value): string
    {
        return $value ? Carbon::parse($value)->format('d-m-Y H:i:s') : '-';
    }
}
