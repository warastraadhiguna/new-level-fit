<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Http\Requests\TrainerStoreRequest;
use App\Models\Member\Member;
use App\Models\MethodPayment;
use App\Models\Staff\FitnessConsultant;
use App\Models\Staff\PersonalTrainer;
use App\Models\Trainer\Trainer;
use App\Models\Trainer\TrainerPackage;
use App\Models\Trainer\TrainerTransaction;
use App\Models\Trainer\TrainerTransactionDetail;
use App\Models\Trainer\TrainerTransactionType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrainerController extends Controller
{
    public function index()
    {
        $data = [
            'title'                     => 'Trainer List',
            'trainers'                  => Trainer::get(),
            'members'                   => Member::get(),
            'trainerTransactionType'    => TrainerTransactionType::get(),
            'trainerPackage'            => TrainerPackage::get(),
            'methodPayment'             => MethodPayment::get(),
            'fc'                        => FitnessConsultant::get(),
            'personalTrainer'           => PersonalTrainer::get(),
            'content'                   => 'admin/trainer/index'
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function create()
    {
        $sessionNumber = Trainer::with('');

        $data = [
            'title'                     => 'New Trainer',
            'members'                   => Member::get(),
            'trainerTransactionType'    => TrainerTransactionType::get(),
            'trainerPackage'            => TrainerPackage::get(),
            'methodPayment'             => MethodPayment::get(),
            'fc'                        => FitnessConsultant::get(),
            'personalTrainer'           => PersonalTrainer::get(),
            'users'                     => User::get(),
            'content'                   => 'admin/trainer/create',
        ];

        return view('admin.layouts.wrapper', $data);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'transaction_type_id'   => 'required|exists:trainer_transaction_types,id',
            'member_id'             => 'required|exists:members,id',
            'trainer_package_id'    => 'required|exists:trainer_packages,id',
            'method_payment_id'     => 'required|exists:method_payments,id',
            'fc_id'                 => 'required|exists:fitness_consultants,id',
            'trainer_id'            => 'required|exists:personal_trainers,id',
            'description'           => '',
            'photos'                => 'mimes:png,jpg,jpeg',
            'user_id'               => ''
        ]);

        $data['user_id'] = Auth::user()->id;

        if ($request->hasFile('photos')) {

            if ($request->photos != null) {
                $realLocation = "storage/" . $request->photos;
                if (file_exists($realLocation) && !is_dir($realLocation)) {
                    unlink($realLocation);
                }
            }

            $photos = $request->file('photos');
            $file_name = time() . '-' . $photos->getClientOriginalName();

            $data['photos'] = $request->file('photos')->store('assets/trainer', 'public');
        } else {
            $data['photos'] = $request->photos;
        }

        Trainer::create($data);
        return redirect()->route('trainer.index')->with('message', 'Trainer Added Successfully');
    }

    public function edit(string $id)
    {
        // 
    }

    public function update(Request $request, string $id)
    {
        $item = Trainer::find($id);
        $data = $request->validate([
            'transaction_type_id'   => '',
            'member_id'             => '',
            'trainer_package_id'    => 'exists:trainer_packages,id',
            'method_payment_id'     => 'exists:method_payments,id',
            'fc_id'                 => 'exists:fitness_consultants,id',
            'trainer_id'            => 'exists:personal_trainers,id',
            'description'           => '',
            'photos'                => 'nullable|mimes:png,jpg,jpeg'
        ]);
        $data['user_id'] = Auth::user()->id;

        if ($request->hasFile('photos')) {

            if ($item->photos != null) {
                $realLocation = "storage/" . $item->photos;
                if (file_exists($realLocation) && !is_dir($realLocation)) {
                    unlink($realLocation);
                }
            }

            $photos = $request->file('photos');
            $file_name = time() . '-' . $photos->getClientOriginalName();

            $data['photos'] = $request->file('photos')->store('assets/trainer', 'public');
        } else {
            $data['photos'] = $item->photos;
        }

        $item->update($data);
        return redirect()->route('trainer.index')->with('message', 'Trainer Updated Successfully');
    }

    public function destroy(Trainer $trainer)
    {
        try {
            $trainer->delete();
            return redirect()->back()->with('message', 'Trainer Deleted Successfully');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'Trainer Deleted Failed, please check other session where using this trainer');
        }
    }
}
