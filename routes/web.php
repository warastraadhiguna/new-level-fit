<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Member\MemberCheckInController;
use App\Http\Controllers\Member\MemberController;
use App\Http\Controllers\Member\MemberPackageController;
use App\Http\Controllers\Member\MemberRegistrationController;
use App\Http\Controllers\Member\MemberRegistrationOverController;
use App\Http\Controllers\Member\MissedGuestController;
use App\Http\Controllers\MergeCreateDataController;
use App\Http\Controllers\Pos\PosController;
use App\Http\Controllers\Pos\ProductCategoryController;
use App\Http\Controllers\Pos\ProductController;
use App\Http\Controllers\Pos\PurchaseController;
use App\Http\Controllers\Pos\StockAdjustmentController;
use App\Http\Controllers\Pos\SupplierController;
use App\Http\Controllers\Report\MemberExpiredListController;
use App\Http\Controllers\Report\MemberListController;
use App\Http\Controllers\Report\ReportController;
use App\Http\Controllers\Staff\AdministratorController;
use App\Http\Controllers\Staff\ClassInstructorController;
use App\Http\Controllers\Staff\CustomerServiceController;
use App\Http\Controllers\Staff\FitnessConsultantController;
use App\Http\Controllers\Staff\PersonalTrainerController;
use App\Http\Controllers\Staff\StaffController;
use App\Http\Controllers\Trainer\LGTController;
use App\Http\Controllers\Trainer\TrainerPackageController;
use App\Http\Controllers\Trainer\TrainerSessionCheckInController;
use App\Http\Controllers\Trainer\TrainerSessionController;
use App\Http\Controllers\Trainer\TrainerSessionOverController;
use App\Models\Trainer\CheckInTrainerSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });

Route::prefix('/')->namespace('Admin')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/add-data', [MergeCreateDataController::class, 'index'])->name('add-data');
    Route::get('/1-day-visit-lead', [MergeCreateDataController::class, 'create'])->name('one-day-visit-lead');
    Route::get('/openMembers', [MergeCreateDataController::class, 'openMembers'])->name('openMembers');
    Route::post('renewal/store/{id}', [MemberRegistrationController::class, 'renewMemberRegistration'])->name('renewMemberRegistration');
    // Route::get('1-day-visit', [MemberController::class, 'dayVisit'])->name('1-day-visit');
    Route::get('one-day-visit', [MemberRegistrationController::class, 'oneDayVisit'])->name('oneDayVisit');


    Route::resource('member', '\App\Http\Controllers\Member\MemberController');

    Route::resource('member-package', '\App\Http\Controllers\Member\MemberPackageController');
    Route::get('member-packages/data-soft', [MemberPackageController::class, 'dataSoft'])->name('dataSoft');
    Route::get('restore-member-package-data/{id}', [MemberPackageController::class, 'restore'])->name('restore-member-package-data');
    Route::delete('member-packages-force-delete/{id}', [MemberPackageController::class, 'forceDelete'])->name('member-packages-force-delete');

    Route::resource('member-package-type', '\App\Http\Controllers\Member\MemberPackageTypeController');
    Route::resource('member-package-category', '\App\Http\Controllers\Member\MemberPackageCategoryController');
    Route::resource('member-payment', '\App\Http\Controllers\Member\MemberPaymentController');
    Route::post('member-second-store', [MemberRegistrationController::class, 'memberSecondStore'])->name('member-second-store');
    // Route::get('print-member-card', [MemberController::class, 'print_member_card'])->name('print-member-card');
    Route::resource('print-member-card', '\App\Http\Controllers\Member\MemberPrintCardController');
    Route::resource('member-check-in', '\App\Http\Controllers\Member\MemberCheckInController')->except(['store']);
    Route::post('member-check-in', [MemberCheckInController::class, 'toggleByCardNumber'])->name('member-check-in.toggle-by-card-number');
    Route::resource('trainer-session-check-in', '\App\Http\Controllers\Trainer\TrainerSessionCheckInController'); 
    Route::get('member-check-in/by-registration/{memberRegistrationId}', [MemberCheckInController::class, 'toggleByRegistrationId'])->name('member-check-in.toggle-by-registration-id');
    Route::get('pt-check-in/{id}', [TrainerSessionCheckInController::class, 'secondStore'])->name('PTSecondCheckIn');
    Route::post('lgt-check-in', [TrainerSessionCheckInController::class, 'lgtStore'])->name('LGTCheckIn');
    Route::get('lgt-second-check-in/{id}', [TrainerSessionCheckInController::class, 'lgtSecondStore'])->name('LGTSecondCheckIn');
    Route::get('history-member-registration', [MemberRegistrationController::class, 'history'])->name('history-member-registration');
    Route::get('history-member-registration/detail/{id}', [MemberRegistrationController::class, 'historyDetail'])->name('detail-history-member-registration');

    Route::get('pt-history', [TrainerSessionController::class, 'history'])->name('pt-history');
    Route::get('pt-history/detail/{id}', [TrainerSessionController::class, 'historyDetail'])->name('detail-pt-history');

    Route::resource('trainer-package', '\App\Http\Controllers\Trainer\TrainerPackageController');
    Route::get('trainer-packages/data-soft', [TrainerPackageController::class, 'dataSoft'])->name('trainer-package-data-soft');
    Route::get('restore-trainer-package-data/{id}', [TrainerPackageController::class, 'restore'])->name('restore-trainer-package-data');
    Route::delete('trainer-packages-force-delete/{id}', [TrainerPackageController::class, 'forceDelete'])->name('trainer-packages-force-delete');
    Route::resource('trainer-package-type', '\App\Http\Controllers\Trainer\TrainerPackageTypeController');
    Route::resource('trainer-transaction-type', '\App\Http\Controllers\Trainer\TrainerTransactionTypeController');

    Route::resource('source-code', '\App\Http\Controllers\Admin\SourceCodeController');
    Route::resource('payment-method', '\App\Http\Controllers\Admin\MethodPaymentController');
    Route::resource('secret-branch-store', '\App\Http\Controllers\Admin\BranchStoreController')
        ->except(['create', 'show', 'edit'])
        ->middleware('admin.only');
    Route::resource('sold-by', '\App\Http\Controllers\Admin\SoldByController');
    Route::resource('referral', '\App\Http\Controllers\Admin\RefferalController');

    Route::middleware('pos.enabled')->group(function () {
        Route::get('pos', ['\App\Http\Controllers\Pos\PosController', 'index'])->name('pos.index');
        Route::post('pos/checkout', ['\App\Http\Controllers\Pos\PosController', 'checkout'])->name('pos.checkout');
        Route::get('pos-sales', ['\App\Http\Controllers\Pos\PosController', 'sales'])->name('pos-sales.index');
        Route::get('pos-sales/{id}/receipt', ['\App\Http\Controllers\Pos\PosController', 'receipt'])->name('pos-sales.receipt');
        Route::get('pos-sales/{id}', ['\App\Http\Controllers\Pos\PosController', 'showSale'])->name('pos-sales.show');

        Route::middleware('admin.only')->group(function () {
            Route::post('pos-sales/{id}/void', ['\App\Http\Controllers\Pos\PosController', 'voidSale'])->name('pos-sales.void');
            Route::resource('pos-products', '\App\Http\Controllers\Pos\ProductController')->only(['index', 'store', 'update', 'destroy']);
            Route::post('pos-products-attach', ['\App\Http\Controllers\Pos\ProductController', 'attach'])->name('pos-products.attach');
            Route::resource('pos-product-categories', '\App\Http\Controllers\Pos\ProductCategoryController')->only(['store', 'update']);
            Route::resource('pos-suppliers', '\App\Http\Controllers\Pos\SupplierController')->only(['index', 'store', 'update', 'destroy']);
            Route::resource('pos-purchases', '\App\Http\Controllers\Pos\PurchaseController')->only(['index', 'create', 'store', 'show', 'destroy']);
            Route::post('pos-purchases/{id}/receive', ['\App\Http\Controllers\Pos\PurchaseController', 'receive'])->name('pos-purchases.receive');
            Route::get('pos-stock', ['\App\Http\Controllers\Pos\StockAdjustmentController', 'index'])->name('pos-stock.index');
            Route::post('pos-stock/adjust', ['\App\Http\Controllers\Pos\StockAdjustmentController', 'store'])->name('pos-stock.adjust');
        });
    });

    Route::resource('staff', '\App\Http\Controllers\Staff\StaffController');
    Route::get('old-staff', [StaffController::class, 'showOldStaff'])->name('old-staff');
    
    Route::get('cetak-staff-pdf', [StaffController::class, 'cetak_pdf'])->name('cetak-staff-pdf');
    Route::resource('administrator', '\App\Http\Controllers\Staff\AdministratorController');
    Route::get('restore-administrator/{id}', [AdministratorController::class, 'restore'])->name('restore-administrator');
    Route::delete('administrator-force-delete/{id}', [AdministratorController::class, 'forceDelete'])->name('administrator-force-delete');

    Route::put('administrator-branch-update', [AdministratorController::class, 'branchUpdate'])->name('administrator-branch-update');

    Route::resource('customer-service', '\App\Http\Controllers\Staff\CustomerServiceController');
    Route::get('restore-customer-service/{id}', [CustomerServiceController::class, 'restore'])->name('restore-customer-service');    
    Route::delete('customer-service-force-delete/{id}', [CustomerServiceController::class, 'forceDelete'])->name('customer-service-force-delete'); 

    Route::resource('customer-service-pos', '\App\Http\Controllers\Staff\CustomerPosServiceController');
    Route::resource('fitness-consultant', '\App\Http\Controllers\Staff\FitnessConsultantController');
    Route::get('restore-fitness-consultant/{id}', [FitnessConsultantController::class, 'restore'])->name('restore-fitness-consultant');    
    Route::delete('fitness-consultant-force-delete/{id}', [FitnessConsultantController::class, 'forceDelete'])->name('fitness-consultant-force-delete');    

    Route::resource('personal-trainer', '\App\Http\Controllers\Staff\PersonalTrainerController');
    Route::get('restore-personal-trainer/{id}', [PersonalTrainerController::class, 'restore'])->name('restore-personal-trainer');    
    Route::delete('personal-trainer-force-delete/{id}', [PersonalTrainerController::class, 'forceDelete'])->name('personal-trainer-force-delete');    

    Route::resource('class-instructor', '\App\Http\Controllers\Staff\ClassInstructorController');  
    Route::get('restore-class-instructor/{id}', [ClassInstructorController::class, 'restore'])->name('restore-class-instructor');    
    Route::delete('class-instructor-force-delete/{id}', [ClassInstructorController::class, 'forceDelete'])->name('class-instructor-force-delete');        
    
    Route::resource('class-session', '\App\Http\Controllers\Class\ClassSessionController');    
    Route::resource('class-schedule', '\App\Http\Controllers\Class\ClassScheduleController');    
    Route::resource('class-detail', '\App\Http\Controllers\Class\ClassDetailController');    

    Route::resource('physiotherapy', '\App\Http\Controllers\Staff\PhysiotherapyController');
    Route::resource('pt-leader', '\App\Http\Controllers\Staff\PTLeaderController');
    Route::get('trainer-report-excel', [MemberRegistrationController::class, 'excel'])->name('trainer-report-excel');

    Route::get('trainer-session/{id}/move-branch', [TrainerSessionController::class, 'moveBranchPage'])
        ->middleware('admin.only')
        ->name('trainer-session.move-branch-page');
    Route::put('trainer-session/{id}/move-branch', [TrainerSessionController::class, 'moveBranch'])
        ->middleware('admin.only')
        ->name('trainer-session.move-branch');
    Route::resource('trainer-session', '\App\Http\Controllers\Trainer\TrainerSessionController');
    Route::resource('trainer-session-payment', '\App\Http\Controllers\Trainer\TrainerSessionPaymentController');

    Route::resource('trainer-session-over', '\App\Http\Controllers\Trainer\TrainerSessionOverController');
    Route::get('trainer-session-over-pdf', [TrainerSessionOverController::class, 'pdfReport'])->name('trainer-session-over-pdf');
    Route::put('trainer-session-freeze/{id}/freeze', [TrainerSessionController::class, 'freeze'])->name('trainer-session-freeze');
    Route::resource('running-session', '\App\Http\Controllers\Trainer\RunningSessionController');
    Route::get('cutiTrainerSession/{id}', [TrainerSessionController::class, 'cuti'])->name('cutiTrainerSession');
    Route::get('leave-days-lgt/{id}', [LGTController::class, 'cuti'])->name('cutiLGT');
    Route::get('leave-day-list/{id}', [TrainerSessionController::class, 'listCuti'])->name('pt-leave-days-list');

    Route::get('lgt', [LGTController::class, 'index'])->name('lgt');

    Route::resource('trainer-session-FO', '\App\Http\Controllers\Trainer\TrainerSessionFOController');
    Route::get('cetak-trainer-session-pdf', [TrainerSessionController::class, 'cetak_pdf'])->name('cetak-trainer-session-pdf');
    Route::get('print-trainer-session-detail-pdf', [TrainerSessionController::class, 'print_trainer_session_detail_pdf'])->name('print-trainer-session-detail-pdf');
    Route::get('pt-agreement/{id}', [TrainerSessionController::class, 'agreement'])->name('pt-agreement');
    Route::get('session-pending', [TrainerSessionController::class, 'pending'])->name('trainer-session-pending');
    Route::get('session-waiting-list', [TrainerSessionController::class, 'waitingList'])->name('trainer-session-waiting-list');
    Route::get('trainer-session-unpaid', [TrainerSessionController::class, 'unpaid'])->name('trainer-session-unpaid');


    Route::resource('buddy-referral', '\App\Http\Controllers\Admin\BuddyReferralController');
    // Route::resource('appointment', '\App\Http\Controllers\Admin\AppointmentController');
    // Route::get('/appointment-status-show/{id}', [AppointmentStatusChangeController::class, 'appointment_status_show']);
    // Route::get('/appointment-status-hide/{id}', [AppointmentStatusChangeController::class, 'appointment_status_hide']);
    // Route::get('/appointment-status-missed-guest/{id}', [AppointmentStatusChangeController::class, 'appointment_status_missed_guest']);

    Route::resource('class', '\App\Http\Controllers\Admin\ClassRecapController');
    Route::resource('leads', '\App\Http\Controllers\Admin\LeadController');

    Route::resource('transfer-package', '\App\Http\Controllers\Admin\TransferPackageController');

    Route::resource('studio', '\App\Http\Controllers\Admin\StudioController');

    Route::resource('report-gym', '\App\Http\Controllers\Report\ReportFitnessController');

    // Route::resource('appointment-list', '\App\Http\Controllers\Report\AppointmentListController');
    // Route::get('all-appointment', [AppointmentListController::class, 'allData'])->name('all-appointment');
    // Route::get('appointment-filter', [AppointmentListController::class, 'filter'])->name('appointment-filter');

    Route::resource('member-list', '\App\Http\Controllers\Report\MemberListController');
    Route::resource('member-active', '\App\Http\Controllers\Member\MemberRegistrationController');
    Route::resource('member-registration-payment', '\App\Http\Controllers\Member\MemberRegistrationPaymentController');
    Route::get('member-one-visit-detail/{id}', [MemberRegistrationController::class, 'showOneVisit'])->name('member-one-visit-detail');
    Route::get('mmember-active-excel', [MemberRegistrationController::class, 'excel'])->name('member-active-excel');
    Route::get('mmember-expired-excel', [MemberRegistrationOverController::class, 'excel'])->name('member-expired-excel');
    Route::resource('member-expired', '\App\Http\Controllers\Member\MemberRegistrationOverController');
    Route::get('member-unpaid', [MemberRegistrationController::class, 'unpaid'])->name('member-unpaid');

    Route::resource('members', '\App\Http\Controllers\Member\MemberController');
    Route::post('members/{id}/small-photo', [MemberController::class, 'updateSmallPhoto'])->name('members.small-photo.update');
    Route::get('members/{id}/create-membership', [MemberRegistrationController::class, 'createMembership'])
        ->middleware('admin.only')
        ->name('members.create-membership');
    Route::post('members/{id}/store-membership', [MemberRegistrationController::class, 'storeMembership'])
        ->middleware('admin.only')
        ->name('members.store-membership');
    Route::get('all-member', [MemberListController::class, 'allData'])->name('all-member');
    Route::get('member-filter', [MemberListController::class, 'filter'])->name('member-filter');
    Route::get('print-member-registration-over-pdf', [MemberRegistrationOverController::class, 'pdfReport'])->name('print-member-registration-over-pdf');
    Route::get('membership-agreement/{id}', [MemberRegistrationController::class, 'agreement'])->name('membership-agreement');
    Route::put('member-registration-freeze/{id}/freeze', [MemberRegistrationController::class, 'freeze'])->name('member-registration-freeze');
    Route::get('member-report', [MemberController::class, 'cetak_pdf'])->name('member-report');
    Route::get('cuti/{id}', [MemberRegistrationController::class, 'cuti'])->name('cuti');
    Route::put('stopLeaveDays', [MemberRegistrationController::class, 'stopLeaveDays'])->name('stopLeaveDays');
    Route::get('renewal/{id}', [MemberRegistrationController::class, 'renewal'])->name('renewal');
    // Route::put('renewal/{id}', [MemberRegistrationController::class, 'renewMember'])->name('memberRenewal');
    // Route::post('renewal/store', [MemberRegistrationController::class, 'renewMemberRegistration'])->name('renewMemberRegistration');

    // Route::resource('renewal', '\App\Http\Controllers\Member\MemberRenewalController');

    Route::resource('missed-guest', '\App\Http\Controllers\Member\MissedGuestController');

    Route::resource('member-expired-list', '\App\Http\Controllers\Report\MemberListController');
    Route::get('all-member-expired', [MemberListController::class, 'allData'])->name('all-member-expired');
    Route::get('member-expired-filter', [MemberExpiredListController::class, 'filter'])->name('member-expired-filter');

    Route::resource('personal-trainer-list', '\App\Http\Controllers\Report\MemberListController');
    Route::get('all-personal-trainer', [MemberListController::class, 'allData'])->name('all-personal-trainer');
    Route::get('personal-trainer-filter', [MemberExpiredListController::class, 'filter'])->name('personal-trainer-filter');

    Route::get('personal-trainer-filter', [CheckInTrainerSession::class, 'checkMemberExistence'])->name('checkMemberExistence');

    // Route::get('member-active-filter', [MemberRegistrationController::class, 'filter'])->name('member-active-filter');
    Route::get('/filter-data', [MemberRegistrationController::class, 'filterData']);
    Route::get('member-pending', [MemberRegistrationController::class, 'pending'])->name('member-pending');
    Route::get('trainer-session-filter', [TrainerSessionController::class, 'filter'])->name('trainer-session-filter');
    Route::get('edit-member-sell/{id}', [MemberController::class, 'secondEdit'])->name('edit-member-sell');
    Route::post('update-member-sell/{id}', [MemberController::class, 'secondUpdate'])->name('update-member-sell');

    // Route::get('/member-details', function () {
    //     return view('admin.member-registration.member_details');
    // })->name('member.details');

    Route::get('member-active/excel', [MemberRegistrationController::class, 'excel'])->name('memberRegistrationExcel');
    Route::get('reset-check-in/{id}', [MemberController::class, 'resetCheckIn'])->name('resetCheckIn');
    Route::get('pt/excel', [StaffController::class, 'excel'])->name('ptReportExcel');

    Route::get('layout-orientation/{id}', [MemberController::class, 'layoutOrientation'])->name('useLayoutOrientation');
    Route::put('process-layout-orientation/{id}', [MemberController::class, 'updateLO'])->name('prosesLayoutOrientation');
    Route::post('stop-layout-orientation/{id}', [MemberController::class, 'stopLO'])->name('stopLayoutOrientation');

    // REPORT
    Route::get('pt-total-report', [StaffController::class, 'ptTotalReport'])->name('pt-total-report');
    Route::get('pt-detail-report', [StaffController::class, 'ptDetailReport'])->name('pt-detail-report');

    Route::get('report-member-checkin', [ReportController::class, 'reportMemberCheckIn'])->name('report-member-checkin');
    Route::get('report-member-pt-checkin', [ReportController::class, 'reportMemberPTCheckIn'])->name('report-member-pt-checkin');
    Route::get('cs-detail-report-member-checkin', [StaffController::class, 'csDetailReportMemberCheckIn'])->name('cs-detail-report-member-checkin');
    Route::get('cs-total-report-pt', [StaffController::class, 'csTotalReportPT'])->name('cs-total-report-pt');
    Route::get('cs-detail-report-pt', [StaffController::class, 'csDetailReportPT'])->name('cs-detail-report-pt');

    Route::get('fc-total-report-member-checkin', [StaffController::class, 'fcTotalReportMemberCheckIn'])->name('fc-total-report-member-checkin');
    Route::get('fc-detail-report-member-checkin', [StaffController::class, 'fcDetailReportMemberCheckIn'])->name('fc-detail-report-member-checkin');
    Route::get('fc-total-report-pt', [StaffController::class, 'fcTotalReportPT'])->name('fc-total-report-pt');
    Route::get('fc-detail-report-pt', [StaffController::class, 'fcDetailReportPT'])->name('fc-detail-report-pt');
    Route::get('lo', [StaffController::class, 'lo'])->name('lo-report');
    Route::get('one-visit-report', [StaffController::class, 'oneVisit'])->name('one-visit-report');

    Route::get('appointment/{id}', [MissedGuestController::class, 'appointment'])->name('appointment');
    Route::get('appointment-schedule', [MissedGuestController::class, 'appointmentSchedule'])->name('appointmentSchedule');
    Route::post('store-appointment/{id}', [MissedGuestController::class, 'storeAppointment'])->name('storeAppointment');

    Route::get('/tes', function () {
        return view('admin.member-registration.tes');
    })->name('member.details');
});

Auth::routes();

// Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
