<?php

namespace App\Http\Controllers\Trainer;

use App\Exports\LGTExport;
use App\Http\Controllers\Controller;
use App\Models\Member\Member;
use App\Models\Trainer\LGT;
use App\Models\Trainer\TrainerSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class LGTController extends Controller
{
    public function index()
    {
        $fromDate   = Request()->input('fromDate');
        $toDate     = Request()->input('toDate');

        $excel = Request()->input('excel');
        if ($excel && $excel == "1") {
            return Excel::download(new LGTExport(), 'LGT, ' . $fromDate . ' to ' . $toDate . '.xlsx');
        }

        $trainerSessions = TrainerSession::lgtActive();

        $birthdayMessages = [
            0 => [],
            1 => [],
            2 => [],
        ];

        foreach ($trainerSessions as $memberRegistration) {
            $diff = BirthdayDiff($memberRegistration->born);
            if ($diff >= 0 && $diff <= 2) {
                $birthdayMessages[$diff][$memberRegistration->member_id] = $memberRegistration->member_name;
            }
        }

        $idCodeMaxCount = env("ID_CODE_MAX_COUNT", 3);

        $data = [
            'title'             => 'LGT Active',
            'trainerSessions'   => $trainerSessions,
            'content'           => 'admin/lgt/index',
            'idCodeMaxCount'    =>  $idCodeMaxCount,
            'birthdayMessages'  => $birthdayMessages,
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function cuti($id)
    {
        $trainerSession = TrainerSession::getActiveLGTListById($id);
        // dd($trainerSession[0]);

        $fileName1 = $trainerSession[0]->member_name;
        $fileName2 = $trainerSession[0]->start_date;

        $pdf = Pdf::loadView('admin/lgt/cuti', [
            'trainerSession'        => $trainerSession[0],
        ]);
        return $pdf->stream('Cuti Trainer Session -' . $fileName1 . '-' . $fileName2 . '.pdf');
    }
}
