<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Staff\PersonalTrainer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PersonalTrainerListController extends Controller
{
    public function index()
    {
        $data = [
            'content'   => 'admin/gym-report/personal-trainer-list/index'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function filter(Request $request)
    {
        $personalTrainer = PersonalTrainer::orderBy('id', 'desc')
            ->when(
                $request->startDate && $request->endDate,
                function (Builder $builder) use ($request) {
                    $builder->whereBetween(
                        DB::raw('DATE(created_at)'),
                        [
                            $request->startDate,
                            $request->endDate
                        ]
                    );
                }
            )->paginate(10);

        $data = [
            'title'                 => 'Report Personal Trainer List',
            'member'                => $personalTrainer,
            'request'               => $request,
            'memberPackage'         => MemberPackage::get(),
            'sourceCode'            => SourceCode::get(),
            'methodPayment'         => MethodPayment::get(),
            'soldBy'                => Sold::get(),
            'referralName'          => Refferal::get(),
            'content'               => 'admin/gym-report/member-list/list'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function allData()
    {
        $data = [
            'title'                 => 'Report Personal Trainer List',
            'member'                => Member::where('status', 'Active')->get(),
            'memberPackage'         => MemberPackage::get(),
            'sourceCode'            => SourceCode::get(),
            'methodPayment'         => MethodPayment::get(),
            'soldBy'                => Sold::get(),
            'referralName'          => Refferal::get(),
            'content'               => 'admin/gym-report/member-list/all-data'
        ];

        return view('admin.layouts.wrapper', $data);
    }
}
