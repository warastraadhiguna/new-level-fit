<?php

namespace App\Models\Trainer;

use App\Models\BranchStore;
use App\Models\Member\Member;
use App\Models\MethodPayment;
use App\Models\Staff\PersonalTrainer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TrainerSession extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected static function booted()
    {
        static::addGlobalScope('without_lgt', function ($query) {
            if ($query->getQuery()->from === 'trainer_sessions') {
                $query->whereNotIn('trainer_sessions.trainer_package_id', function ($query) {
                    $query->select('id')->from('trainer_packages')->where('status', 'LGT');
                });
            }
        });
        static::saving(function ($session) {
            if (\Illuminate\Support\Facades\DB::table('trainer_packages')
                ->where('id', $session->trainer_package_id)->where('status', 'LGT')->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'trainer_package_id' => 'Paket tersebut tidak tersedia.',
                ]);
            }
        });
    }

    protected $fillable = [
        'member_id',
        'trainer_id',
        'start_date',
        'trainer_package_id',
        'is_pt_free',
        'days',
        'old_days',
        'package_price',
        'admin_price',
        'discount_amount',
        'payment_deadline',
        'number_of_session',
        'description',
        'is_approved',
        'method_payment_id',
        'fc_id',
        'user_id',
        'branch_store_id'
    ];

    protected $casts = [
        'payment_deadline' => 'integer',
        'discount_amount' => 'integer',
        'is_pt_free' => 'boolean',
        'is_approved' => 'boolean',
    ];

    protected $hidden = [];

    public function getTotalPayableAttribute(): int
    {
        return max(0, (int) $this->package_price + (int) $this->admin_price - (int) $this->discount_amount);
    }

    public function members()
    {
        return $this->belongsTo(Member::class, 'member_id', 'id');
    }

    public function personalTrainers()
    {
        return $this->belongsTo(PersonalTrainer::class, 'trainer_id', 'id');
    }

    public function trainerPackages()
    {
        return $this->belongsTo(TrainerPackage::class, 'trainer_package_id', 'id');
    }

    public function trainerPackageWithTrashed()
    {
        return $this->belongsTo(TrainerPackage::class, 'trainer_package_id', 'id')->withTrashed();
    }

    public function users()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function fitnessConsultants()
    {
        return $this->belongsTo(User::class, 'fc_id', 'id');
    }

    public function trainerSessionCheckIn()
    {
        return $this->hasMany(CheckInTrainerSession::class);
    }

    public function latestCheckIn()
    {
        return $this->hasOne(CheckInTrainerSession::class)->latestOfMany();
    }

    public function payments()
    {
        return $this->hasMany(TrainerSessionPayment::class);
    }

    public function leaveDays()
    {
        return $this->hasMany(PtLeaveDay::class);
    }

    public function methodPayment()
    {
        return $this->belongsTo(MethodPayment::class, 'method_payment_id', 'id');
    }

    public function branchStore()
    {
        return $this->belongsTo(BranchStore::class);
    }

    private static function trainerSessionPaymentSummarySql(string $sessionAlias = 'train_sess'): string
    {
        return "IFNULL((SELECT SUM(value) FROM trainer_session_payments tsp WHERE {$sessionAlias}.id=tsp.trainer_session_id),0)";
    }

    private static function trainerSessionTotalPriceSql(string $sessionAlias = 'train_sess'): string
    {
        return "({$sessionAlias}.package_price + {$sessionAlias}.admin_price - IFNULL({$sessionAlias}.discount_amount, 0))";
    }

    private static function trainerSessionPaymentStatusCondition(string $paymentStatus = "paid", string $sessionAlias = 'train_sess'): string
    {
        $paymentSummarySql = self::trainerSessionPaymentSummarySql($sessionAlias);
        $totalPriceSql = self::trainerSessionTotalPriceSql($sessionAlias);

        if ($paymentStatus == "unpaid") {
            return " AND {$paymentSummarySql} < {$totalPriceSql} ";
        }

        if ($paymentStatus == "all") {
            return "";
        }

        return " AND {$paymentSummarySql} >= {$totalPriceSql} ";
    }    

    public static function getActivePTList($card_number = "", $trainner_session_id = "", $paymentStatus = "paid")
    {
        $sql = "SELECT mbr.full_name AS member_name, mbr.nickname, mbr.phone_number, mbr.gender, mbr.born, mbr.member_code, mbr.email, mbr.ig, mbr.emergency_contact, mbr.ec_name,train_sess.package_price AS ts_package_price, train_sess.admin_price AS ts_admin_price, train_sess.discount_amount AS ts_discount_amount, train_sess.branch_store_id, bs.name as branch_store_name,
        mbr.card_number, mbr.id_code_count, mbr.photos, mbr.status, mbr.address, mbr.id AS member_id,
        train_sess.id, train_sess.start_date, train_sess.trainer_package_id, train_sess.number_of_session AS ts_number_of_session, train_sess.days, train_sess.description, train_sess.is_approved,
        train_pack.package_name,
        pers_train.full_name AS trainer_name, pers_train.id AS trainer_id,
        cits_view.current_check_in_trainer_sessions_id, cits_view.check_in_time, cits_view.check_out_time, cits_view.updated_at_check_in,
	
        DATE_ADD(train_sess.start_date, INTERVAL COALESCE(leave_days_view.total_days_continue, 0) + train_sess.days DAY) AS expired_date,
        leave_days_view.expired_leave_days AS expired_leave_days,

        CASE WHEN mbr_reg_member_id IS NULL THEN 'No Leave Days' ELSE 'Freeze' END AS leave_day_status,

        IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) AS remaining_sessions,
        
        CASE WHEN NOW() > DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue, 0)) DAY) THEN 'Over'
        WHEN NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue, 0)) DAY) THEN 'Running'
        ELSE 'Not Started'
        END as STATUS,
        ifnull((select sum(value) from trainer_session_payments tsp where train_sess.id=tsp.trainer_session_id),0) as payment_summary
        FROM members AS mbr
        INNER JOIN trainer_sessions AS train_sess ON mbr.id = train_sess.member_id
        INNER JOIN branch_stores as bs on train_sess.branch_store_id = bs.id
        INNER JOIN trainer_packages AS train_pack ON train_pack.id = train_sess.trainer_package_id AND train_pack.status IS NULL AND train_sess.is_pt_free = 0
        INNER JOIN personal_trainers AS pers_train ON pers_train.id = train_sess.trainer_id
        -- INNER JOIN fitness_consultants AS fit_cons ON fit_cons.id= train_sess.fc_id
        INNER JOIN method_payments AS met_pay ON met_pay.id = train_sess.method_payment_id
        INNER JOIN users ON users.id = train_sess.user_id
        
        LEFT JOIN (select cits1.id as current_check_in_trainer_sessions_id, cits1.updated_at AS 
        updated_at_check_in, cits1.trainer_session_id, cits1.check_in_time, cits1.check_out_time from check_in_trainer_sessions cits1
        INNER JOIN (SELECT max(id) as max_id FROM check_in_trainer_sessions group by trainer_session_id) as cits2 on cits1.id=cits2.max_id) as cits_view on cits_view.trainer_session_id = train_sess.id
        
        LEFT JOIN (SELECT trainer_session_id, COUNT(id) AS check_in_count FROM check_in_trainer_sessions WHERE check_out_time 
        IS NOT NULL GROUP BY trainer_session_id)
        AS count_check_in_view ON train_sess.id = count_check_in_view.trainer_session_id

        LEFT JOIN (SELECT check_in_train_sess.trainer_session_id, check_in_train_sess.check_in_time, check_in_train_sess.check_out_time FROM check_in_trainer_sessions AS check_in_train_sess
        INNER JOIN 
        (SELECT MAX(id) AS max_check_in_id FROM check_in_trainer_sessions GROUP BY trainer_session_id)
        AS max_check_in_view ON check_in_train_sess.id = max_check_in_view.max_check_in_id) 
        AS last_check_in_view ON train_sess.id = last_check_in_view.trainer_session_id
        
        LEFT JOIN (
            SELECT
                pld.trainer_session_id,
                MAX(CASE
                    WHEN NOW() BETWEEN pld.submission_date
                        AND DATE_ADD(pld.submission_date, INTERVAL pld.days DAY)
                    THEN pld.trainer_session_id
                END) AS mbr_reg_member_id,
                MAX(pld.submission_date) AS submission_date_continue,
                SUM(pld.days) AS total_days_continue,
                SUM(pld.price) AS total_price_continue,
                MAX(DATE_ADD(pld.submission_date, INTERVAL pld.days DAY)) AS expired_leave_days
            FROM pt_leave_days AS pld
            GROUP BY pld.trainer_session_id
        ) AS leave_days_view ON train_sess.id = leave_days_view.trainer_session_id
        
        WHERE
        train_sess.branch_store_id = ". Auth::user()->branch_store_id ."  and
            NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue,0)) DAY) "
            . self::trainerSessionPaymentStatusCondition($paymentStatus) . ($card_number ? " and mbr.card_number='$card_number' " : '') . ($trainner_session_id ? " and train_sess.id='$trainner_session_id' " : '') . "
            order by cits_view.updated_at_check_in desc";
        $activeTrainerSessions = DB::select($sql);

        return $activeTrainerSessions;
    }

    public static function getPendingPTList($card_number = "", $trainner_session_id = "", $paymentStatus = "paid")
    {
        $sql = "SELECT mbr.full_name AS member_name, mbr.nickname, mbr.phone_number, mbr.gender, mbr.born, mbr.member_code, mbr.email, mbr.ig, mbr.emergency_contact, mbr.ec_name, train_sess.package_price AS ts_package_price, train_sess.admin_price AS ts_admin_price, train_sess.discount_amount AS ts_discount_amount, bs.name as branch_store_name,
        mbr.card_number, mbr.id_code_count, mbr.photos, mbr.status, mbr.address, mbr.id AS member_id,
        train_sess.id, train_sess.start_date, train_sess.number_of_session AS ts_number_of_session, train_sess.days, train_sess.description, train_sess.is_approved,
        train_pack.package_name,
        pers_train.full_name AS trainer_name,
        cits_view.current_check_in_trainer_sessions_id, cits_view.check_in_time, cits_view.check_out_time, cits_view.updated_at_check_in,

        DATE_ADD(train_sess.start_date, INTERVAL COALESCE(leave_days_view.total_days_continue, 0) + train_sess.days DAY) AS expired_date,
        leave_days_view.expired_leave_days AS expired_leave_days,

        CASE WHEN mbr_reg_member_id IS NULL THEN 'No Leave Days' ELSE 'Freeze' END AS leave_day_status,

        IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) AS remaining_sessions,

        CASE WHEN NOW() > DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue, 0)) DAY) THEN 'Over'
        WHEN NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue, 0)) DAY) THEN 'Running'
        ELSE 'Not Started'
        END as STATUS,
        ifnull((select sum(value) from trainer_session_payments tsp where train_sess.id=tsp.trainer_session_id),0) as payment_summary
        FROM members AS mbr
        
        INNER JOIN trainer_sessions AS train_sess ON mbr.id = train_sess.member_id
        INNER JOIN branch_stores as bs on train_sess.branch_store_id = bs.id        
        INNER JOIN trainer_packages AS train_pack ON train_pack.id = train_sess.trainer_package_id AND train_pack.status IS NULL AND train_sess.is_pt_free = 0
        INNER JOIN personal_trainers AS pers_train ON pers_train.id = train_sess.trainer_id
        -- INNER JOIN fitness_consultants AS fit_cons ON fit_cons.id= train_sess.fc_id
        INNER JOIN method_payments AS met_pay ON met_pay.id = train_sess.method_payment_id
        INNER JOIN users ON users.id = train_sess.user_id

        LEFT JOIN (select cits1.id as current_check_in_trainer_sessions_id, cits1.updated_at AS
        updated_at_check_in, cits1.trainer_session_id, cits1.check_in_time, cits1.check_out_time from check_in_trainer_sessions cits1
        INNER JOIN (SELECT max(id) as max_id FROM check_in_trainer_sessions group by trainer_session_id) as cits2 on cits1.id=cits2.max_id) as cits_view on cits_view.trainer_session_id = train_sess.id

        LEFT JOIN (SELECT trainer_session_id, COUNT(id) AS check_in_count FROM check_in_trainer_sessions WHERE check_out_time
        IS NOT NULL GROUP BY trainer_session_id)
        AS count_check_in_view ON train_sess.id = count_check_in_view.trainer_session_id

        LEFT JOIN (SELECT check_in_train_sess.trainer_session_id, check_in_train_sess.check_in_time, check_in_train_sess.check_out_time FROM check_in_trainer_sessions AS check_in_train_sess
        INNER JOIN 
        (SELECT MAX(id) AS max_check_in_id FROM check_in_trainer_sessions GROUP BY trainer_session_id)
        AS max_check_in_view ON check_in_train_sess.id = max_check_in_view.max_check_in_id) 
        AS last_check_in_view ON train_sess.id = last_check_in_view.trainer_session_id
        
        LEFT JOIN (
            SELECT
                pld.trainer_session_id,
                MAX(CASE
                    WHEN NOW() BETWEEN pld.submission_date
                        AND DATE_ADD(pld.submission_date, INTERVAL pld.days DAY)
                    THEN pld.trainer_session_id
                END) AS mbr_reg_member_id,
                MAX(pld.submission_date) AS submission_date_continue,
                SUM(pld.days) AS total_days_continue,
                SUM(pld.price) AS total_price_continue,
                MAX(DATE_ADD(pld.submission_date, INTERVAL pld.days DAY)) AS expired_leave_days
            FROM pt_leave_days AS pld
            GROUP BY pld.trainer_session_id
        ) AS leave_days_view ON train_sess.id = leave_days_view.trainer_session_id

        WHERE
            -- IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) = 0
        -- AND NOW() < DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue,0)) DAY)
        train_sess.branch_store_id = ". Auth::user()->branch_store_id ."  and
        NOW() < train_sess.start_date"
            . self::trainerSessionPaymentStatusCondition($paymentStatus) . ($card_number ? " and mbr.card_number='$card_number' " : '') . ($trainner_session_id ? " and train_sess.id='$trainner_session_id' " : '') . "
            order by cits_view.updated_at_check_in desc";
        $activeTrainerSessions = DB::select($sql);

        return $activeTrainerSessions;
    }
    public static function getPTWaitingList($card_number = "", $trainner_session_id = "", $paymentStatus = "paid")
    {
        $sql = "SELECT mbr.full_name AS member_name, mbr.nickname, mbr.phone_number, mbr.gender, mbr.born, mbr.member_code, mbr.email, mbr.ig, mbr.emergency_contact, mbr.ec_name, train_sess.package_price AS ts_package_price, train_sess.admin_price AS ts_admin_price, train_sess.discount_amount AS ts_discount_amount, branch_stores.name as branch_store_name,
        mbr.card_number, mbr.id_code_count, mbr.photos, mbr.status, mbr.address, mbr.id AS member_id,
        train_sess.id, train_sess.start_date, train_sess.number_of_session AS ts_number_of_session, train_sess.days, train_sess.description,
        trainer_packages.package_name,
        personal_trainers.full_name AS trainer_name,
	
        ifnull((select sum(value) from trainer_session_payments tsp where train_sess.id=tsp.trainer_session_id),0) as payment_summary
        FROM members AS mbr
        
        INNER JOIN trainer_sessions AS train_sess ON mbr.id = train_sess.member_id
        INNER JOIN branch_stores on train_sess.branch_store_id = branch_stores.id        
        INNER JOIN trainer_packages ON trainer_packages.id = train_sess.trainer_package_id AND trainer_packages.status IS NULL AND train_sess.is_pt_free = 0
        LEFT JOIN personal_trainers ON personal_trainers.id = train_sess.trainer_id
        INNER JOIN method_payments AS met_pay ON met_pay.id = train_sess.method_payment_id
        INNER JOIN users ON users.id = train_sess.user_id
                
        WHERE
        train_sess.branch_store_id = ". Auth::user()->branch_store_id ."  and
        (train_sess.trainer_id is null or train_sess.start_date is null)"
            . self::trainerSessionPaymentStatusCondition($paymentStatus) . ($card_number ? " and mbr.card_number='$card_number' " : '') . ($trainner_session_id ? " and train_sess.id='$trainner_session_id' " : '') . "
            order by train_sess.created_at desc";
        $activeTrainerSessions = DB::select($sql);

        return $activeTrainerSessions;
    }

    public static function getUnpaidPTList($branchStoreId = "")
    {
        $branchStoreId = $branchStoreId ?: Auth::user()->branch_store_id;

        $sql = "SELECT mbr.full_name AS member_name, mbr.nickname, mbr.phone_number, mbr.gender, mbr.born, mbr.member_code, mbr.email, mbr.ig, mbr.emergency_contact, mbr.ec_name, train_sess.package_price AS ts_package_price, train_sess.admin_price AS ts_admin_price, train_sess.discount_amount AS ts_discount_amount, bs.name as branch_store_name,
        mbr.card_number, mbr.id_code_count, mbr.photos, mbr.status, mbr.address, mbr.id AS member_id,
        train_sess.id, train_sess.start_date, train_sess.created_at as trainer_session_created_at,
        train_sess.payment_deadline,
        CASE WHEN train_sess.payment_deadline > 0
            THEN DATE_ADD(train_sess.created_at, INTERVAL train_sess.payment_deadline DAY)
            ELSE NULL
        END AS payment_deadline_date,
        train_sess.number_of_session AS ts_number_of_session, train_sess.days, train_sess.is_approved,
        train_pack.package_name, train_pack.status AS trainer_package_status,
        COALESCE(pers_train.full_name, '-') AS trainer_name, pers_train.id AS trainer_id,
        cits_view.current_check_in_trainer_sessions_id, cits_view.check_in_time, cits_view.check_out_time, cits_view.updated_at_check_in,

        DATE_ADD(train_sess.start_date, INTERVAL COALESCE(leave_days_view.total_days_continue, 0) + train_sess.days DAY) AS expired_date,
        leave_days_view.expired_leave_days AS expired_leave_days,

        CASE WHEN mbr_reg_member_id IS NULL THEN 'No Leave Days' ELSE 'Freeze' END AS leave_day_status,

        IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) AS remaining_sessions,

        CASE WHEN NOW() > DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue, 0)) DAY) THEN 'Over'
        WHEN NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue, 0)) DAY) THEN 'Running'
        ELSE 'Not Started'
        END as STATUS,
        ifnull((select sum(value) from trainer_session_payments tsp where train_sess.id=tsp.trainer_session_id),0) as payment_summary
        FROM members AS mbr
        INNER JOIN trainer_sessions AS train_sess ON mbr.id = train_sess.member_id
        INNER JOIN branch_stores as bs on train_sess.branch_store_id = bs.id
        INNER JOIN trainer_packages AS train_pack ON train_pack.id = train_sess.trainer_package_id AND train_pack.status IS NULL AND train_sess.is_pt_free = 0
        LEFT JOIN personal_trainers AS pers_train ON pers_train.id = train_sess.trainer_id
        INNER JOIN method_payments AS met_pay ON met_pay.id = train_sess.method_payment_id
        INNER JOIN users ON users.id = train_sess.user_id

        LEFT JOIN (select cits1.id as current_check_in_trainer_sessions_id, cits1.updated_at AS
        updated_at_check_in, cits1.trainer_session_id, cits1.check_in_time, cits1.check_out_time from check_in_trainer_sessions cits1
        INNER JOIN (SELECT max(id) as max_id FROM check_in_trainer_sessions group by trainer_session_id) as cits2 on cits1.id=cits2.max_id) as cits_view on cits_view.trainer_session_id = train_sess.id

        LEFT JOIN (SELECT trainer_session_id, COUNT(id) AS check_in_count FROM check_in_trainer_sessions WHERE check_out_time
        IS NOT NULL GROUP BY trainer_session_id)
        AS count_check_in_view ON train_sess.id = count_check_in_view.trainer_session_id

        LEFT JOIN (
            SELECT
                pld.trainer_session_id,
                MAX(CASE
                    WHEN NOW() BETWEEN pld.submission_date
                        AND DATE_ADD(pld.submission_date, INTERVAL pld.days DAY)
                    THEN pld.trainer_session_id
                END) AS mbr_reg_member_id,
                MAX(pld.submission_date) AS submission_date_continue,
                SUM(pld.days) AS total_days_continue,
                SUM(pld.price) AS total_price_continue,
                MAX(DATE_ADD(pld.submission_date, INTERVAL pld.days DAY)) AS expired_leave_days
            FROM pt_leave_days AS pld
            GROUP BY pld.trainer_session_id
        ) AS leave_days_view ON train_sess.id = leave_days_view.trainer_session_id

        WHERE
        train_sess.branch_store_id = ". $branchStoreId ."
        ". self::trainerSessionPaymentStatusCondition("unpaid") ."
        order by train_sess.created_at desc";
        $unpaidTrainerSessions = DB::select($sql);

        return $unpaidTrainerSessions;
    }

    public static function getActivePTListById($id = "")
    {
        $sql = "SELECT mbr.full_name AS member_name, mbr.id AS member_id, mbr.nickname, mbr.phone_number, mbr.gender, mbr.born, mbr.member_code, mbr.email, mbr.ig, mbr.emergency_contact, mbr.ec_name,
        mbr.card_number, mbr.id_code_count, mbr.photos, mbr.status, mbr.address,
        train_sess.id, train_sess.start_date, train_sess.number_of_session AS ts_number_of_session, train_sess.days AS ts_number_of_days, train_sess.package_price AS ts_package_price, train_sess.description,
        train_pack.package_name, pers_train.full_name AS trainer_name, met_pay.name AS method_payment_name,
        cits_view.current_check_in_trainer_sessions_id, cits_view.check_in_time, cits_view.check_out_time, cits_view.updated_at_check_in,
        leave_days_view.submission_date_continue, leave_days_view.total_price_continue, users.full_name AS fc_name,
	
        DATE_ADD(train_sess.start_date, INTERVAL COALESCE(leave_days_view.total_days_continue, 0) + train_sess.days DAY) AS expired_date,
        leave_days_view.expired_leave_days AS expired_leave_days,

        CASE WHEN mbr_reg_member_id IS NULL THEN 'No Leave Days' ELSE 'Freeze' END AS leave_day_status,

        IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) AS remaining_sessions,
        
        CASE WHEN NOW() > DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue, 0)) DAY) THEN 'Over'
        WHEN NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue, 0)) DAY) THEN 'Running'
        ELSE 'Not Started'
        END as STATUS
    
        FROM members AS mbr
        
        INNER JOIN trainer_sessions AS train_sess ON mbr.id = train_sess.member_id
        INNER JOIN trainer_packages AS train_pack ON train_pack.id = train_sess.trainer_package_id AND train_pack.status IS NULL AND train_sess.is_pt_free = 0
        INNER JOIN personal_trainers AS pers_train ON pers_train.id = train_sess.trainer_id
        -- INNER JOIN fitness_consultants AS fit_cons ON fit_cons.id= train_sess.fc_id
        INNER JOIN method_payments AS met_pay ON met_pay.id = train_sess.method_payment_id
        INNER JOIN users ON users.id = train_sess.user_id
        
        LEFT JOIN (select cits1.id as current_check_in_trainer_sessions_id, cits1.updated_at AS 
        updated_at_check_in, cits1.trainer_session_id, cits1.check_in_time, cits1.check_out_time from check_in_trainer_sessions cits1
        INNER JOIN (SELECT max(id) as max_id FROM check_in_trainer_sessions group by trainer_session_id) as cits2 on cits1.id=cits2.max_id) as cits_view on cits_view.trainer_session_id = train_sess.id
        
        LEFT JOIN (SELECT trainer_session_id, COUNT(id) AS check_in_count FROM check_in_trainer_sessions WHERE check_out_time 
        IS NOT NULL GROUP BY trainer_session_id)
        AS count_check_in_view ON train_sess.id = count_check_in_view.trainer_session_id

        LEFT JOIN (SELECT check_in_train_sess.trainer_session_id, check_in_train_sess.check_in_time, check_in_train_sess.check_out_time FROM check_in_trainer_sessions AS check_in_train_sess
        INNER JOIN 
        (SELECT MAX(id) AS max_check_in_id FROM check_in_trainer_sessions GROUP BY trainer_session_id)
        AS max_check_in_view ON check_in_train_sess.id = max_check_in_view.max_check_in_id) 
        AS last_check_in_view ON train_sess.id = last_check_in_view.trainer_session_id

        LEFT JOIN (
            SELECT
                pld.trainer_session_id,
                MAX(CASE
                    WHEN NOW() BETWEEN pld.submission_date
                        AND DATE_ADD(pld.submission_date, INTERVAL pld.days DAY)
                    THEN pld.trainer_session_id
                END) AS mbr_reg_member_id,
                MAX(pld.submission_date) AS submission_date_continue,
                SUM(pld.days) AS total_days_continue,
                SUM(pld.price) AS total_price_continue,
                MAX(DATE_ADD(pld.submission_date, INTERVAL pld.days DAY)) AS expired_leave_days
            FROM pt_leave_days AS pld
            GROUP BY pld.trainer_session_id
        ) AS leave_days_view ON train_sess.id = leave_days_view.trainer_session_id
        
        WHERE
            IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) > 0
        AND NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue,0)) DAY)"
            . ($id ? " and train_sess.id = '$id' " : '') . "
            order by cits_view.updated_at_check_in desc";
        $activeTrainerSessions = DB::select($sql);

        return $activeTrainerSessions;
    }





    public static function getExpiredPT($memberId = "")
    {
        $sql = "SELECT mbr.id, mbr.full_name AS member_name, mbr.photos, mbr.member_code,
                train_sess.start_date, train_sess.id AS ts_id, train_sess.days AS ts_days, train_sess.days AS ts_number_of_days, train_sess.package_price AS ts_package_price, train_sess.member_id AS registered_member_id,
                pers_train.full_name AS trainer_full_name, train_sess.description, train_sess.is_approved,
                train_pack.package_name, met_pay.name AS method_payment_name,

                DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue, 0)) DAY) AS expired_date,
                DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue, 0)) DAY) AS max_end_date

                FROM trainer_sessions AS train_sess

                INNER JOIN (SELECT MAX(id) AS max_train_sess_id FROM trainer_sessions AS train_sess
                    WHERE is_pt_free = 0
                    AND branch_store_id = " . (int) Auth::user()->branch_store_id . "
                    GROUP BY member_id)
                AS max_train_sess_view ON train_sess.id = max_train_sess_view.max_train_sess_id

                INNER JOIN trainer_packages AS train_pack ON train_pack.id = train_sess.trainer_package_id AND (train_pack.status IS NULL OR train_pack.status <> 'LGT')
                INNER JOIN members AS mbr ON mbr.id = train_sess.member_id
                INNER JOIN personal_trainers AS pers_train ON pers_train.id = train_sess.trainer_id
                INNER JOIN method_payments AS met_pay ON met_pay.id = train_sess.method_payment_id

                LEFT JOIN (SELECT trainer_session_id, COUNT(id) AS check_in_count FROM check_in_trainer_sessions WHERE check_out_time 
                IS NOT NULL GROUP BY trainer_session_id)
                AS count_check_in_view ON train_sess.id = count_check_in_view.trainer_session_id

                LEFT JOIN (
                    SELECT trainer_session_id, SUM(days) AS total_days_continue
                    FROM pt_leave_days
                    GROUP BY trainer_session_id
                ) AS leave_days_view ON train_sess.id = leave_days_view.trainer_session_id

                WHERE train_pack.status IS NULL
                AND train_sess.is_pt_free = 0
                AND train_sess.branch_store_id = " . (int) Auth::user()->branch_store_id . "
                AND NOW() > DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue, 0)) DAY)
                " . ($memberId ? " AND mbr.id='$memberId' " : '') . "
                ORDER BY max_end_date";

        $activeTrainerSessions = DB::select($sql);
        return $activeTrainerSessions;
    }



    public static function getPendingPT($memberId)
    {
        $sql = "SELECT mbr.full_name AS member_name, mbr.nickname, mbr.phone_number, mbr.gender, mbr.born, mbr.member_code, mbr.email, mbr.ig, mbr.emergency_contact, mbr.ec_name,
        mbr.card_number, mbr.id_code_count, mbr.photos, mbr.status, mbr.address, mbr.id AS member_id,
        train_sess.id, train_sess.start_date, train_sess.number_of_session AS ts_number_of_session, train_sess.days AS ts_number_of_days, train_sess.package_price AS ts_package_price, train_sess.description,
        train_pack.package_name,
        pers_train.full_name AS trainer_name,
        cits_view.current_check_in_trainer_sessions_id, cits_view.check_in_time, cits_view.check_out_time, cits_view.updated_at_check_in,
        met_pay.name AS method_payment_name,
	
        DATE_ADD(train_sess.start_date, INTERVAL COALESCE(leave_days_view.total_days_continue, 0) + train_sess.days DAY) AS expired_date,
        leave_days_view.expired_leave_days AS expired_leave_days,

        CASE WHEN mbr_reg_member_id IS NULL THEN 'No Leave Days' ELSE 'Freeze' END AS leave_day_status,

        IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) AS remaining_sessions,
        
        CASE WHEN NOW() > DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue, 0)) DAY) THEN 'Over'
        WHEN NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue, 0)) DAY) THEN 'Running'
        ELSE 'Not Started'
        END as STATUS
    
        FROM members AS mbr
        
        INNER JOIN trainer_sessions AS train_sess ON mbr.id = train_sess.member_id
        INNER JOIN trainer_packages AS train_pack ON train_pack.id = train_sess.trainer_package_id AND train_pack.status IS NULL AND train_sess.is_pt_free = 0
        INNER JOIN personal_trainers AS pers_train ON pers_train.id = train_sess.trainer_id
        -- INNER JOIN fitness_consultants AS fit_cons ON fit_cons.id= train_sess.fc_id
        INNER JOIN method_payments AS met_pay ON met_pay.id = train_sess.method_payment_id
        INNER JOIN users ON users.id = train_sess.user_id
        
        LEFT JOIN (select cits1.id as current_check_in_trainer_sessions_id, cits1.updated_at AS 
        updated_at_check_in, cits1.trainer_session_id, cits1.check_in_time, cits1.check_out_time from check_in_trainer_sessions cits1
        INNER JOIN (SELECT max(id) as max_id FROM check_in_trainer_sessions group by trainer_session_id) as cits2 on cits1.id=cits2.max_id) as cits_view on cits_view.trainer_session_id = train_sess.id
        
        LEFT JOIN (SELECT trainer_session_id, COUNT(id) AS check_in_count FROM check_in_trainer_sessions WHERE check_out_time 
        IS NOT NULL GROUP BY trainer_session_id)
        AS count_check_in_view ON train_sess.id = count_check_in_view.trainer_session_id

        LEFT JOIN (SELECT check_in_train_sess.trainer_session_id, check_in_train_sess.check_in_time, check_in_train_sess.check_out_time FROM check_in_trainer_sessions AS check_in_train_sess
        INNER JOIN 
        (SELECT MAX(id) AS max_check_in_id FROM check_in_trainer_sessions GROUP BY trainer_session_id)
        AS max_check_in_view ON check_in_train_sess.id = max_check_in_view.max_check_in_id) 
        AS last_check_in_view ON train_sess.id = last_check_in_view.trainer_session_id
        
        LEFT JOIN (
            SELECT
                pld.trainer_session_id,
                MAX(CASE
                    WHEN NOW() BETWEEN pld.submission_date
                        AND DATE_ADD(pld.submission_date, INTERVAL pld.days DAY)
                    THEN pld.trainer_session_id
                END) AS mbr_reg_member_id,
                MAX(pld.submission_date) AS submission_date_continue,
                SUM(pld.days) AS total_days_continue,
                SUM(pld.price) AS total_price_continue,
                MAX(DATE_ADD(pld.submission_date, INTERVAL pld.days DAY)) AS expired_leave_days
            FROM pt_leave_days AS pld
            GROUP BY pld.trainer_session_id
        ) AS leave_days_view ON train_sess.id = leave_days_view.trainer_session_id
        
        WHERE
            -- IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) = 0
        -- AND NOW() < DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue,0)) DAY)
        train_sess.branch_store_id = " . (int) Auth::user()->branch_store_id . "
        AND NOW() < train_sess.start_date"
             . ($memberId ? " and mbr.id='$memberId' " : '') . "
            order by cits_view.updated_at_check_in desc";
        $pendingTrainerSessions = DB::select($sql);

        return $pendingTrainerSessions;
    }

    public static function getExpiredTrainerSession($memberId)
    {
        $sql = "SELECT mbr.full_name AS member_name, mbr.nickname, mbr.phone_number, mbr.gender, mbr.born, mbr.member_code, mbr.email, mbr.ig, mbr.emergency_contact, mbr.ec_name,
        mbr.card_number, mbr.id_code_count, mbr.photos, mbr.status, mbr.address, mbr.id AS member_id,
        train_sess.id, train_sess.start_date, train_sess.number_of_session AS ts_number_of_session, train_sess.days AS ts_number_of_days, train_sess.package_price AS ts_package_price, train_sess.description,
        train_pack.package_name,
        pers_train.full_name AS trainer_name,
        cits_view.current_check_in_trainer_sessions_id, cits_view.check_in_time, cits_view.check_out_time, cits_view.updated_at_check_in,
        met_pay.name AS method_payment_name,
	
        DATE_ADD(train_sess.start_date, INTERVAL COALESCE(leave_days_view.total_days_continue, 0) + train_sess.days DAY) AS expired_date,
        leave_days_view.expired_leave_days AS expired_leave_days,

        CASE WHEN mbr_reg_member_id IS NULL THEN 'No Leave Days' ELSE 'Freeze' END AS leave_day_status,

        IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) AS remaining_sessions,
        
        CASE WHEN NOW() > DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue, 0)) DAY) THEN 'Over'
        WHEN NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue, 0)) DAY) THEN 'Running'
        ELSE 'Not Started'
        END as STATUS
    
        FROM members AS mbr
        
        INNER JOIN trainer_sessions AS train_sess ON mbr.id = train_sess.member_id
        INNER JOIN trainer_packages AS train_pack ON train_pack.id = train_sess.trainer_package_id AND train_pack.status IS NULL AND train_sess.is_pt_free = 0
        INNER JOIN personal_trainers AS pers_train ON pers_train.id = train_sess.trainer_id
        -- INNER JOIN fitness_consultants AS fit_cons ON fit_cons.id= train_sess.fc_id
        INNER JOIN method_payments AS met_pay ON met_pay.id = train_sess.method_payment_id
        INNER JOIN users ON users.id = train_sess.user_id
        
        LEFT JOIN (select cits1.id as current_check_in_trainer_sessions_id, cits1.updated_at AS 
        updated_at_check_in, cits1.trainer_session_id, cits1.check_in_time, cits1.check_out_time from check_in_trainer_sessions cits1
        INNER JOIN (SELECT max(id) as max_id FROM check_in_trainer_sessions group by trainer_session_id) as cits2 on cits1.id=cits2.max_id) as cits_view on cits_view.trainer_session_id = train_sess.id
        
        LEFT JOIN (SELECT trainer_session_id, COUNT(id) AS check_in_count FROM check_in_trainer_sessions WHERE check_out_time 
        IS NOT NULL GROUP BY trainer_session_id)
        AS count_check_in_view ON train_sess.id = count_check_in_view.trainer_session_id

        LEFT JOIN (SELECT check_in_train_sess.trainer_session_id, check_in_train_sess.check_in_time, check_in_train_sess.check_out_time FROM check_in_trainer_sessions AS check_in_train_sess
        INNER JOIN 
        (SELECT MAX(id) AS max_check_in_id FROM check_in_trainer_sessions GROUP BY trainer_session_id)
        AS max_check_in_view ON check_in_train_sess.id = max_check_in_view.max_check_in_id) 
        AS last_check_in_view ON train_sess.id = last_check_in_view.trainer_session_id
        
        LEFT JOIN (
            SELECT
                pld.trainer_session_id,
                MAX(CASE
                    WHEN NOW() BETWEEN pld.submission_date
                        AND DATE_ADD(pld.submission_date, INTERVAL pld.days DAY)
                    THEN pld.trainer_session_id
                END) AS mbr_reg_member_id,
                MAX(pld.submission_date) AS submission_date_continue,
                SUM(pld.days) AS total_days_continue,
                SUM(pld.price) AS total_price_continue,
                MAX(DATE_ADD(pld.submission_date, INTERVAL pld.days DAY)) AS expired_leave_days
            FROM pt_leave_days AS pld
            GROUP BY pld.trainer_session_id
        ) AS leave_days_view ON train_sess.id = leave_days_view.trainer_session_id
        
        WHERE
            -- IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) = 0
        -- AND NOW() < DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue,0)) DAY)
        train_sess.branch_store_id = " . (int) Auth::user()->branch_store_id . "
        AND NOW() > DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue, 0)) DAY)"
             . ($memberId ? " and mbr.id='$memberId' " : '') . "
            order by cits_view.updated_at_check_in desc";
        $pendingTrainerSessions = DB::select($sql);

        return $pendingTrainerSessions;
    }

    public static function checkInPT($card_number = "", $trainner_session_id = "")
    {
        $sql = "SELECT mbr.full_name AS member_name, mbr.nickname, mbr.phone_number, mbr.gender, mbr.born, mbr.member_code, mbr.email, mbr.ig, mbr.emergency_contact, mbr.ec_name,
        mbr.card_number, mbr.id_code_count, mbr.photos, mbr.status, mbr.address, mbr.id AS member_id,
        train_sess.id, train_sess.start_date, train_sess.created_at as trainer_session_created_at,
        train_sess.payment_deadline,
        CASE WHEN train_sess.payment_deadline > 0 THEN DATE_ADD(train_sess.created_at, INTERVAL train_sess.payment_deadline DAY) ELSE NULL END AS payment_deadline_date,
        train_sess.number_of_session AS ts_number_of_session, train_sess.days, train_sess.package_price AS ts_package_price, train_sess.admin_price AS ts_admin_price, train_sess.discount_amount AS ts_discount_amount,
        train_pack.package_name,
        pers_train.full_name AS trainer_name, pers_train.id AS trainer_id,
        cits_view.current_check_in_trainer_sessions_id, cits_view.check_in_time, cits_view.check_out_time, cits_view.updated_at_check_in,
	
        DATE_ADD(train_sess.start_date, INTERVAL COALESCE(leave_days_view.total_days_continue, 0) + train_sess.days DAY) AS expired_date,
        leave_days_view.expired_leave_days AS expired_leave_days,

        CASE WHEN mbr_reg_member_id IS NULL THEN 'No Leave Days' ELSE 'Freeze' END AS leave_day_status,

        IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) AS remaining_sessions,
        
        CASE WHEN NOW() > DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue, 0)) DAY) THEN 'Over'
        WHEN NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue, 0)) DAY) THEN 'Running'
        ELSE 'Not Started'
        END as STATUS,
        ifnull((select sum(value) from trainer_session_payments tsp where train_sess.id=tsp.trainer_session_id),0) as payment_summary
    
        FROM members AS mbr
        
        INNER JOIN trainer_sessions AS train_sess ON mbr.id = train_sess.member_id
        INNER JOIN trainer_packages AS train_pack ON train_pack.id = train_sess.trainer_package_id AND train_pack.status IS NULL AND train_sess.is_pt_free = 0
        INNER JOIN personal_trainers AS pers_train ON pers_train.id = train_sess.trainer_id
        -- INNER JOIN fitness_consultants AS fit_cons ON fit_cons.id= train_sess.fc_id
        INNER JOIN method_payments AS met_pay ON met_pay.id = train_sess.method_payment_id
        INNER JOIN users ON users.id = train_sess.user_id
        
        LEFT JOIN (select cits1.id as current_check_in_trainer_sessions_id, cits1.updated_at AS 
        updated_at_check_in, cits1.trainer_session_id, cits1.check_in_time, cits1.check_out_time from check_in_trainer_sessions cits1
        INNER JOIN (SELECT max(id) as max_id FROM check_in_trainer_sessions group by trainer_session_id) as cits2 on cits1.id=cits2.max_id) as cits_view on cits_view.trainer_session_id = train_sess.id
        
        LEFT JOIN (SELECT trainer_session_id, COUNT(id) AS check_in_count FROM check_in_trainer_sessions WHERE check_out_time 
        IS NOT NULL GROUP BY trainer_session_id)
        AS count_check_in_view ON train_sess.id = count_check_in_view.trainer_session_id

        LEFT JOIN (SELECT check_in_train_sess.trainer_session_id, check_in_train_sess.check_in_time, check_in_train_sess.check_out_time FROM check_in_trainer_sessions AS check_in_train_sess
        INNER JOIN 
        (SELECT MAX(id) AS max_check_in_id FROM check_in_trainer_sessions GROUP BY trainer_session_id)
        AS max_check_in_view ON check_in_train_sess.id = max_check_in_view.max_check_in_id) 
        AS last_check_in_view ON train_sess.id = last_check_in_view.trainer_session_id
        
        LEFT JOIN (
            SELECT
                pld.trainer_session_id,
                MAX(CASE
                    WHEN NOW() BETWEEN pld.submission_date
                        AND DATE_ADD(pld.submission_date, INTERVAL pld.days DAY)
                    THEN pld.trainer_session_id
                END) AS mbr_reg_member_id,
                MAX(pld.submission_date) AS submission_date_continue,
                SUM(pld.days) AS total_days_continue,
                SUM(pld.price) AS total_price_continue,
                MAX(DATE_ADD(pld.submission_date, INTERVAL pld.days DAY)) AS expired_leave_days
            FROM pt_leave_days AS pld
            GROUP BY pld.trainer_session_id
        ) AS leave_days_view ON train_sess.id = leave_days_view.trainer_session_id
        
        WHERE
            train_sess.branch_store_id = ". Auth::user()->branch_store_id ." AND
            NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue,0)) DAY) AND
            IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) > 0"
            . ($card_number ? " and mbr.card_number='$card_number' " : '') . ($trainner_session_id ? " and train_sess.id='$trainner_session_id' " : '') . " 
            order by cits_view.updated_at_check_in desc";
        $activeTrainerSessions = DB::select($sql);

        return $activeTrainerSessions;
    }



    public static function agreement($trainner_session_id = "")
    {
        $sql = "SELECT mbr.full_name AS member_name, mbr.nickname, mbr.phone_number, mbr.gender, mbr.born, mbr.member_code, mbr.email, mbr.ig, mbr.emergency_contact, mbr.ec_name,
        mbr.card_number, mbr.id_code_count, mbr.photos, mbr.status, mbr.address, mbr.id AS member_id,
        train_sess.id, train_sess.start_date, train_sess.number_of_session AS ts_number_of_session, train_sess.days, train_sess.package_price AS ts_package_price,
        train_pack.package_name,
        -- fit_cons.id AS fit_cons_id, fit_cons.full_name AS fc_name,
        pers_train.full_name AS trainer_name, pers_train.id AS trainer_id,
        cits_view.current_check_in_trainer_sessions_id, cits_view.check_in_time, cits_view.check_out_time, cits_view.updated_at_check_in,
	
        DATE_ADD(train_sess.start_date, INTERVAL COALESCE(leave_days_view.total_days_continue, 0) + train_sess.days DAY) AS expired_date,
        leave_days_view.expired_leave_days AS expired_leave_days,

        CASE WHEN mbr_reg_member_id IS NULL THEN 'No Leave Days' ELSE 'Freeze' END AS leave_day_status,

        IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) AS remaining_sessions,
        
        CASE WHEN NOW() > DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue, 0)) DAY) THEN 'Over'
        WHEN NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue, 0)) DAY) THEN 'Running'
        ELSE 'Not Started'
        END as STATUS
    
        FROM members AS mbr
        
        INNER JOIN trainer_sessions AS train_sess ON mbr.id = train_sess.member_id
        INNER JOIN trainer_packages AS train_pack ON train_pack.id = train_sess.trainer_package_id AND train_pack.status IS NULL AND train_sess.is_pt_free = 0
        INNER JOIN personal_trainers AS pers_train ON pers_train.id = train_sess.trainer_id
        -- INNER JOIN fitness_consultants AS fit_cons ON fit_cons.id= train_sess.fc_id
        INNER JOIN method_payments AS met_pay ON met_pay.id = train_sess.method_payment_id
        INNER JOIN users ON users.id = train_sess.user_id
        
        LEFT JOIN (select cits1.id as current_check_in_trainer_sessions_id, cits1.updated_at AS 
        updated_at_check_in, cits1.trainer_session_id, cits1.check_in_time, cits1.check_out_time from check_in_trainer_sessions cits1
        INNER JOIN (SELECT max(id) as max_id FROM check_in_trainer_sessions group by trainer_session_id) as cits2 on cits1.id=cits2.max_id) as cits_view on cits_view.trainer_session_id = train_sess.id
        
        LEFT JOIN (SELECT trainer_session_id, COUNT(id) AS check_in_count FROM check_in_trainer_sessions WHERE check_out_time 
        IS NOT NULL GROUP BY trainer_session_id)
        AS count_check_in_view ON train_sess.id = count_check_in_view.trainer_session_id

        LEFT JOIN (SELECT check_in_train_sess.trainer_session_id, check_in_train_sess.check_in_time, check_in_train_sess.check_out_time FROM check_in_trainer_sessions AS check_in_train_sess
        INNER JOIN 
        (SELECT MAX(id) AS max_check_in_id FROM check_in_trainer_sessions GROUP BY trainer_session_id)
        AS max_check_in_view ON check_in_train_sess.id = max_check_in_view.max_check_in_id) 
        AS last_check_in_view ON train_sess.id = last_check_in_view.trainer_session_id
        
        LEFT JOIN (
            SELECT
                pld.trainer_session_id,
                MAX(CASE
                    WHEN NOW() BETWEEN pld.submission_date
                        AND DATE_ADD(pld.submission_date, INTERVAL pld.days DAY)
                    THEN pld.trainer_session_id
                END) AS mbr_reg_member_id,
                MAX(pld.submission_date) AS submission_date_continue,
                SUM(pld.days) AS total_days_continue,
                SUM(pld.price) AS total_price_continue,
                MAX(DATE_ADD(pld.submission_date, INTERVAL pld.days DAY)) AS expired_leave_days
            FROM pt_leave_days AS pld
            GROUP BY pld.trainer_session_id
        ) AS leave_days_view ON train_sess.id = leave_days_view.trainer_session_id
        where "
             . ($trainner_session_id ? " train_sess.id='$trainner_session_id' " : '') . "
            order by cits_view.updated_at_check_in desc";
        $activeTrainerSessions = DB::select($sql);

        return $activeTrainerSessions;
    }

    public static function history($card_number = "", $trainner_session_id = "", $fromDate, $toDate)
    {
        $sql = "SELECT mbr.full_name AS member_name, mbr.nickname, mbr.phone_number, mbr.gender, mbr.born, mbr.member_code, mbr.email, mbr.ig, mbr.emergency_contact, mbr.ec_name,
        mbr.card_number, mbr.id_code_count, mbr.photos, mbr.status, mbr.address, mbr.id AS member_id,
        train_sess.id, train_sess.start_date, train_sess.number_of_session AS ts_number_of_session, train_sess.days,
        train_pack.package_name,
        pers_train.full_name AS trainer_name, pers_train.id AS trainer_id,
        cits_view.current_check_in_trainer_sessions_id, cits_view.check_in_time, cits_view.check_out_time, cits_view.updated_at_check_in,
	
        DATE_ADD(train_sess.start_date, INTERVAL COALESCE(leave_days_view.total_days_continue, 0) + train_sess.days DAY) AS expired_date,
        leave_days_view.expired_leave_days AS expired_leave_days,

        CASE WHEN mbr_reg_member_id IS NULL THEN 'No Leave Days' ELSE 'Freeze' END AS leave_day_status,

        IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) AS remaining_sessions,
        
        CASE WHEN NOW() > DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue, 0)) DAY) THEN 'Over'
        WHEN NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue, 0)) DAY) THEN 'Running'
        ELSE 'Not Started'
        END as STATUS
    
        FROM members AS mbr
        
        INNER JOIN trainer_sessions AS train_sess ON mbr.id = train_sess.member_id
        INNER JOIN trainer_packages AS train_pack ON train_pack.id = train_sess.trainer_package_id AND train_pack.status IS NULL AND train_sess.is_pt_free = 0
        INNER JOIN personal_trainers AS pers_train ON pers_train.id = train_sess.trainer_id
        -- INNER JOIN fitness_consultants AS fit_cons ON fit_cons.id= train_sess.fc_id
        INNER JOIN method_payments AS met_pay ON met_pay.id = train_sess.method_payment_id
        INNER JOIN users ON users.id = train_sess.user_id
        
        LEFT JOIN (select cits1.id as current_check_in_trainer_sessions_id, cits1.updated_at AS 
        updated_at_check_in, cits1.trainer_session_id, cits1.check_in_time, cits1.check_out_time from check_in_trainer_sessions cits1
        INNER JOIN (SELECT max(id) as max_id FROM check_in_trainer_sessions group by trainer_session_id) as cits2 on cits1.id=cits2.max_id) as cits_view on cits_view.trainer_session_id = train_sess.id
        
        LEFT JOIN (SELECT trainer_session_id, COUNT(id) AS check_in_count FROM check_in_trainer_sessions WHERE check_out_time 
        IS NOT NULL GROUP BY trainer_session_id)
        AS count_check_in_view ON train_sess.id = count_check_in_view.trainer_session_id

        LEFT JOIN (SELECT check_in_train_sess.trainer_session_id, check_in_train_sess.check_in_time, check_in_train_sess.check_out_time FROM check_in_trainer_sessions AS check_in_train_sess
        INNER JOIN 
        (SELECT MAX(id) AS max_check_in_id FROM check_in_trainer_sessions GROUP BY trainer_session_id)
        AS max_check_in_view ON check_in_train_sess.id = max_check_in_view.max_check_in_id) 
        AS last_check_in_view ON train_sess.id = last_check_in_view.trainer_session_id
        
        LEFT JOIN (
            SELECT
                pld.trainer_session_id,
                MAX(CASE
                    WHEN NOW() BETWEEN pld.submission_date
                        AND DATE_ADD(pld.submission_date, INTERVAL pld.days DAY)
                    THEN pld.trainer_session_id
                END) AS mbr_reg_member_id,
                MAX(pld.submission_date) AS submission_date_continue,
                SUM(pld.days) AS total_days_continue,
                SUM(pld.price) AS total_price_continue,
                MAX(DATE_ADD(pld.submission_date, INTERVAL pld.days DAY)) AS expired_leave_days
            FROM pt_leave_days AS pld
            GROUP BY pld.trainer_session_id
        ) AS leave_days_view ON train_sess.id = leave_days_view.trainer_session_id
        
        where train_sess.branch_store_id = " . (int) Auth::user()->branch_store_id . "
        AND train_sess.start_date >= '$fromDate' AND train_sess.start_date <= '$toDate'
        "
            . ($trainner_session_id ? " and train_sess.id='$trainner_session_id' " : '');
        $activeTrainerSessions = DB::select($sql);

        return $activeTrainerSessions;
    }

    public static function historyById($id = "")
    {
        $sql = "SELECT mbr.full_name AS member_name, mbr.id AS member_id, mbr.nickname, mbr.phone_number, mbr.gender, mbr.born, mbr.member_code, mbr.email, mbr.ig, mbr.emergency_contact, mbr.ec_name,
        mbr.card_number, mbr.id_code_count, mbr.photos, mbr.status, mbr.address,
        train_sess.id, train_sess.start_date, train_sess.number_of_session AS ts_number_of_session, train_sess.days AS ts_number_of_days, train_sess.package_price AS ts_package_price, train_sess.description,
        train_pack.package_name, pers_train.full_name AS trainer_name, met_pay.name AS method_payment_name,
        cits_view.current_check_in_trainer_sessions_id, cits_view.check_in_time, cits_view.check_out_time, cits_view.updated_at_check_in,
        leave_days_view.submission_date_continue, leave_days_view.total_price_continue, users.full_name AS fc_name,
	
        DATE_ADD(train_sess.start_date, INTERVAL COALESCE(leave_days_view.total_days_continue, 0) + train_sess.days DAY) AS expired_date,
        leave_days_view.expired_leave_days AS expired_leave_days,

        CASE WHEN mbr_reg_member_id IS NULL THEN 'No Leave Days' ELSE 'Freeze' END AS leave_day_status,

        IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) AS remaining_sessions,
        
        CASE WHEN NOW() > DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue, 0)) DAY) THEN 'Over'
        WHEN NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue, 0)) DAY) THEN 'Running'
        ELSE 'Not Started'
        END as STATUS
    
        FROM members AS mbr
        
        INNER JOIN trainer_sessions AS train_sess ON mbr.id = train_sess.member_id
        INNER JOIN trainer_packages AS train_pack ON train_pack.id = train_sess.trainer_package_id AND train_pack.status IS NULL AND train_sess.is_pt_free = 0
        INNER JOIN personal_trainers AS pers_train ON pers_train.id = train_sess.trainer_id
        -- INNER JOIN fitness_consultants AS fit_cons ON fit_cons.id= train_sess.fc_id
        INNER JOIN method_payments AS met_pay ON met_pay.id = train_sess.method_payment_id
        INNER JOIN users ON users.id = train_sess.user_id
        
        LEFT JOIN (select cits1.id as current_check_in_trainer_sessions_id, cits1.updated_at AS 
        updated_at_check_in, cits1.trainer_session_id, cits1.check_in_time, cits1.check_out_time from check_in_trainer_sessions cits1
        INNER JOIN (SELECT max(id) as max_id FROM check_in_trainer_sessions group by trainer_session_id) as cits2 on cits1.id=cits2.max_id) as cits_view on cits_view.trainer_session_id = train_sess.id
        
        LEFT JOIN (SELECT trainer_session_id, COUNT(id) AS check_in_count FROM check_in_trainer_sessions WHERE check_out_time 
        IS NOT NULL GROUP BY trainer_session_id)
        AS count_check_in_view ON train_sess.id = count_check_in_view.trainer_session_id

        LEFT JOIN (SELECT check_in_train_sess.trainer_session_id, check_in_train_sess.check_in_time, check_in_train_sess.check_out_time FROM check_in_trainer_sessions AS check_in_train_sess
        INNER JOIN 
        (SELECT MAX(id) AS max_check_in_id FROM check_in_trainer_sessions GROUP BY trainer_session_id)
        AS max_check_in_view ON check_in_train_sess.id = max_check_in_view.max_check_in_id) 
        AS last_check_in_view ON train_sess.id = last_check_in_view.trainer_session_id

        LEFT JOIN (
            SELECT
                pld.trainer_session_id,
                MAX(CASE
                    WHEN NOW() BETWEEN pld.submission_date
                        AND DATE_ADD(pld.submission_date, INTERVAL pld.days DAY)
                    THEN pld.trainer_session_id
                END) AS mbr_reg_member_id,
                MAX(pld.submission_date) AS submission_date_continue,
                SUM(pld.days) AS total_days_continue,
                SUM(pld.price) AS total_price_continue,
                MAX(DATE_ADD(pld.submission_date, INTERVAL pld.days DAY)) AS expired_leave_days
            FROM pt_leave_days AS pld
            GROUP BY pld.trainer_session_id
        ) AS leave_days_view ON train_sess.id = leave_days_view.trainer_session_id
        where train_sess.id = '$id'";
        $activeTrainerSessions = DB::select($sql);

        return $activeTrainerSessions;
    }
}
