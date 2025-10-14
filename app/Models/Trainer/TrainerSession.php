<?php

namespace App\Models\Trainer;

use App\Models\Member\Member;
use App\Models\MethodPayment;
use App\Models\Staff\FitnessConsultant;
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

    protected $fillable = [
        'member_id',
        'trainer_id',
        'start_date',
        'trainer_package_id',
        'days',
        'old_days',
        'package_price',
        'admin_price',
        'number_of_session',
        'description',
        'method_payment_id',
        'fc_id',
        'user_id',
    ];

    protected $hidden = [];

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

    public function methodPayment()
    {
        return $this->belongsTo(MethodPayment::class, 'method_payment_id', 'id');
    }

    public static function getActivePTList($card_number = "", $trainner_session_id = "")
    {
        $sql = "SELECT mbr.full_name AS member_name, mbr.nickname, mbr.phone_number, mbr.gender, mbr.born, mbr.member_code, mbr.email, mbr.ig, mbr.emergency_contact, mbr.ec_name,train_sess.package_price AS ts_package_price, train_sess.admin_price AS ts_admin_price,
        mbr.card_number, mbr.id_code_count, mbr.photos, mbr.status, mbr.address, mbr.id AS member_id,
        train_sess.id, train_sess.start_date, train_sess.number_of_session AS ts_number_of_session, train_sess.days,
        train_pack.package_name,
        pers_train.full_name AS trainer_name, pers_train.id AS trainer_id,
        cits_view.current_check_in_trainer_sessions_id, cits_view.check_in_time, cits_view.check_out_time, cits_view.updated_at_check_in,
	
        DATE_ADD(train_sess.start_date, INTERVAL COALESCE(leave_days_view.total_days_continue, 0) + train_sess.days DAY) AS expired_date,
        DATE_ADD(leave_days_view.submission_date_continue, INTERVAL leave_days_view.total_days_continue DAY) AS expired_leave_days,

        CASE WHEN mbr_reg_member_id IS NULL THEN 'No Leave Days' ELSE 'Freeze' END AS leave_day_status,

        IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) AS remaining_sessions,
        
        CASE WHEN NOW() > DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY) THEN 'Over'
        WHEN NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY) THEN 'Running'
        ELSE 'Not Started'
        END as STATUS,
        ifnull((select sum(value) from trainer_session_payments tsp where train_sess.id=tsp.trainer_session_id),0) as payment_summary
        FROM members AS mbr
        
        INNER JOIN trainer_sessions AS train_sess ON mbr.id = train_sess.member_id
        INNER JOIN trainer_packages AS train_pack ON train_pack.id = train_sess.trainer_package_id AND train_pack.status IS NULL
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
        
        LEFT JOIN (SELECT mbr_reg.member_id AS mbr_reg_member_id, ld_continue_view.submission_date_continue, ld_continue_view.total_days_continue from
        (SELECT ld.id, ld.member_registration_id as member_registration_id_continue, ld.submission_date as submission_date_continue, 
        ld_view.total_days as total_days_continue FROM  leave_days ld 
        INNER JOIN 
        (SELECT leave_day_continue_id, sum(days) AS total_days 
        FROM (SELECT id,ifnull(leave_day_continue_id, id) AS leave_day_continue_id,days FROM leave_days) AS view_1
        GROUP BY leave_day_continue_id) AS ld_view ON ld.id=ld_view.leave_day_continue_id 
        WHERE NOW() BETWEEN ld.submission_date AND DATE_ADD(ld.submission_date, INTERVAL (ifnull(total_days,0)) DAY))
        AS ld_continue_view
        INNER JOIN member_registrations AS mbr_reg ON mbr_reg.id = ld_continue_view.member_registration_id_continue)
        AS leave_days_view ON mbr.id = leave_days_view.mbr_reg_member_id
        
        WHERE
        mbr.branch_store_id = ". Auth::user()->branch_store_id ."  and
            NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue,0)) DAY) "
            . ($card_number ? " and mbr.card_number='$card_number' " : '') . ($trainner_session_id ? " and train_sess.id='$trainner_session_id' " : '') . " 
            order by cits_view.updated_at_check_in desc";
        $activeTrainerSessions = DB::select($sql);

        return $activeTrainerSessions;
    }

    public static function getPendingPTList($card_number = "", $trainner_session_id = "")
    {
        $sql = "SELECT mbr.full_name AS member_name, mbr.nickname, mbr.phone_number, mbr.gender, mbr.born, mbr.member_code, mbr.email, mbr.ig, mbr.emergency_contact, mbr.ec_name, train_sess.package_price AS ts_package_price, train_sess.admin_price AS ts_admin_price,
        mbr.card_number, mbr.id_code_count, mbr.photos, mbr.status, mbr.address, mbr.id AS member_id,
        train_sess.id, train_sess.start_date, train_sess.number_of_session AS ts_number_of_session, train_sess.days,
        train_pack.package_name,
        pers_train.full_name AS trainer_name,
        cits_view.current_check_in_trainer_sessions_id, cits_view.check_in_time, cits_view.check_out_time, cits_view.updated_at_check_in,
	
        DATE_ADD(train_sess.start_date, INTERVAL COALESCE(leave_days_view.total_days_continue, 0) + train_sess.days DAY) AS expired_date,
        DATE_ADD(leave_days_view.submission_date_continue, INTERVAL leave_days_view.total_days_continue DAY) AS expired_leave_days,

        CASE WHEN mbr_reg_member_id IS NULL THEN 'No Leave Days' ELSE 'Freeze' END AS leave_day_status,

        IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) AS remaining_sessions,
        
        CASE WHEN NOW() > DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY) THEN 'Over'
        WHEN NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY) THEN 'Running'
        ELSE 'Not Started'
        END as STATUS,
        ifnull((select sum(value) from trainer_session_payments tsp where train_sess.id=tsp.trainer_session_id),0) as payment_summary
        FROM members AS mbr
        
        INNER JOIN trainer_sessions AS train_sess ON mbr.id = train_sess.member_id
        INNER JOIN trainer_packages AS train_pack ON train_pack.id = train_sess.trainer_package_id AND train_pack.status IS NULL
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
        
        LEFT JOIN (SELECT mbr_reg.member_id AS mbr_reg_member_id, ld_continue_view.submission_date_continue, ld_continue_view.total_days_continue from
        (SELECT ld.id, ld.member_registration_id as member_registration_id_continue, ld.submission_date as submission_date_continue, 
        ld_view.total_days as total_days_continue FROM  leave_days ld 
        INNER JOIN 
        (SELECT leave_day_continue_id, sum(days) AS total_days 
        FROM (SELECT id,ifnull(leave_day_continue_id, id) AS leave_day_continue_id,days FROM leave_days) AS view_1
        GROUP BY leave_day_continue_id) AS ld_view ON ld.id=ld_view.leave_day_continue_id 
        WHERE NOW() BETWEEN ld.submission_date AND DATE_ADD(ld.submission_date, INTERVAL (ifnull(total_days,0)) DAY))
        AS ld_continue_view
        INNER JOIN member_registrations AS mbr_reg ON mbr_reg.id = ld_continue_view.member_registration_id_continue)
        AS leave_days_view ON mbr.id = leave_days_view.mbr_reg_member_id
        
        WHERE
            -- IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) = 0
        -- AND NOW() < DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue,0)) DAY)
        NOW() < train_sess.start_date"
            . ($card_number ? " and mbr.card_number='$card_number' " : '') . ($trainner_session_id ? " and train_sess.id='$trainner_session_id' " : '') . "
            order by cits_view.updated_at_check_in desc";
        $activeTrainerSessions = DB::select($sql);

        return $activeTrainerSessions;
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
        DATE_ADD(leave_days_view.submission_date_continue, INTERVAL leave_days_view.total_days_continue DAY) AS expired_leave_days,

        CASE WHEN mbr_reg_member_id IS NULL THEN 'No Leave Days' ELSE 'Freeze' END AS leave_day_status,

        IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) AS remaining_sessions,
        
        CASE WHEN NOW() > DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY) THEN 'Over'
        WHEN NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY) THEN 'Running'
        ELSE 'Not Started'
        END as STATUS
    
        FROM members AS mbr
        
        INNER JOIN trainer_sessions AS train_sess ON mbr.id = train_sess.member_id
        INNER JOIN trainer_packages AS train_pack ON train_pack.id = train_sess.trainer_package_id AND train_pack.status IS NULL
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

        LEFT JOIN (SELECT mbr_reg.member_id AS mbr_reg_member_id, ld_continue_view.submission_date_continue, ld_continue_view.total_days_continue, ld_continue_view.total_price_continue from
        (SELECT ld.id, ld.member_registration_id as member_registration_id_continue, ld.submission_date as submission_date_continue,
        ld_view.total_days as total_days_continue, ld_view.total_price as total_price_continue FROM  leave_days ld 
        INNER JOIN 
        (SELECT leave_day_continue_id, sum(days) AS total_days, SUM(price) AS total_price
        FROM (SELECT id,ifnull(leave_day_continue_id, id) AS leave_day_continue_id, days, price FROM leave_days) AS view_1
        GROUP BY leave_day_continue_id) AS ld_view ON ld.id=ld_view.leave_day_continue_id 
        WHERE NOW() BETWEEN ld.submission_date AND DATE_ADD(ld.submission_date, INTERVAL (ifnull(total_days,0)) DAY))
        AS ld_continue_view
        INNER JOIN member_registrations AS mbr_reg ON mbr_reg.id = ld_continue_view.member_registration_id_continue)
        AS leave_days_view ON mbr.id = leave_days_view.mbr_reg_member_id
        
        WHERE
            IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) > 0
        AND NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue,0)) DAY)"
            . ($id ? " and train_sess.id = '$id' " : '') . "
            order by cits_view.updated_at_check_in desc";
        $activeTrainerSessions = DB::select($sql);

        return $activeTrainerSessions;
    }

    public static function getActiveLGTList($card_number = "", $id = "")
    {
        $sql = "SELECT mbr.full_name AS member_name, mbr.nickname, mbr.phone_number, mbr.gender, mbr.born, mbr.member_code, mbr.email, mbr.ig, mbr.emergency_contact, mbr.ec_name,
        mbr.card_number, mbr.id_code_count, mbr.photos, mbr.status, mbr.address, mbr.id AS member_id,
        train_sess.id, train_sess.start_date, train_sess.number_of_session AS ts_number_of_session, train_sess.days,
        train_pack.package_name,
        pers_train.full_name AS trainer_name,
        cits_view.current_check_in_trainer_sessions_id, cits_view.check_in_time, cits_view.check_out_time, cits_view.updated_at_check_in,
	
        DATE_ADD(train_sess.start_date, INTERVAL COALESCE(leave_days_view.total_days_continue, 0) + train_sess.days DAY) AS expired_date,
        DATE_ADD(leave_days_view.submission_date_continue, INTERVAL leave_days_view.total_days_continue DAY) AS expired_leave_days,

        CASE WHEN mbr_reg_member_id IS NULL THEN 'No Leave Days' ELSE 'Freeze' END AS leave_day_status,

        IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) AS remaining_sessions,
        
        CASE WHEN NOW() > DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY) THEN 'Over'
        WHEN NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY) THEN 'Running'
        ELSE 'Not Started'
        END as expired_date_status
    
        FROM members AS mbr
        
        INNER JOIN trainer_sessions AS train_sess ON mbr.id = train_sess.member_id
        INNER JOIN trainer_packages AS train_pack ON train_pack.id = train_sess.trainer_package_id AND train_pack.status = 'LGT'
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
        
        LEFT JOIN (SELECT mbr_reg.member_id AS mbr_reg_member_id, ld_continue_view.submission_date_continue, ld_continue_view.total_days_continue from
        (SELECT ld.id, ld.member_registration_id as member_registration_id_continue, ld.submission_date as submission_date_continue, 
        ld_view.total_days as total_days_continue FROM  leave_days ld 
        INNER JOIN 
        (SELECT leave_day_continue_id, sum(days) AS total_days 
        FROM (SELECT id,ifnull(leave_day_continue_id, id) AS leave_day_continue_id,days FROM leave_days) AS view_1
        GROUP BY leave_day_continue_id) AS ld_view ON ld.id=ld_view.leave_day_continue_id 
        WHERE NOW() BETWEEN ld.submission_date AND DATE_ADD(ld.submission_date, INTERVAL (ifnull(total_days,0)) DAY))
        AS ld_continue_view
        INNER JOIN member_registrations AS mbr_reg ON mbr_reg.id = ld_continue_view.member_registration_id_continue)
        AS leave_days_view ON mbr.id = leave_days_view.mbr_reg_member_id
        
        -- WHERE
        --     IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) > 0
        -- AND NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue,0)) DAY)
        "
            . ($card_number ? " and mbr.card_number='$card_number' " : '') . ($id ? " and train_sess.id='$id' " : '') . "
            order by cits_view.updated_at_check_in desc, train_sess.updated_at";
        $activeTrainerSessions = DB::select($sql);

        return $activeTrainerSessions;
    }

    public static function getActiveLGTListById($id = "")
    {
        $sql = "SELECT mbr.full_name AS member_name, mbr.nickname, mbr.phone_number, mbr.gender, mbr.born, mbr.member_code, mbr.email, mbr.ig, mbr.emergency_contact, mbr.ec_name,
        mbr.card_number, mbr.id_code_count, mbr.photos, mbr.status, mbr.address,
        train_sess.id, train_sess.start_date, train_sess.number_of_session AS ts_number_of_session, train_sess.days,
        train_pack.package_name, pers_train.full_name AS trainer_name, met_pay.name AS method_payment_name,
        cits_view.current_check_in_trainer_sessions_id, cits_view.check_in_time, cits_view.check_out_time, cits_view.updated_at_check_in,
	    leave_days_view.submission_date_continue, leave_days_view.total_price_continue,
    
        DATE_ADD(train_sess.start_date, INTERVAL COALESCE(leave_days_view.total_days_continue, 0) + train_sess.days DAY) AS expired_date,
        DATE_ADD(leave_days_view.submission_date_continue, INTERVAL leave_days_view.total_days_continue DAY) AS expired_leave_days,

        CASE WHEN mbr_reg_member_id IS NULL THEN 'No Leave Days' ELSE 'Freeze' END AS leave_day_status,

        IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) AS remaining_sessions,
        
        CASE WHEN NOW() > DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY) THEN 'Over'
        WHEN NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY) THEN 'Running'
        ELSE 'Not Started'
        END as STATUS,

        CASE WHEN NOW() > DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY) THEN 'Over'
        WHEN NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY) THEN 'Running'
        ELSE 'Not Started'
        END as expired_date_status
    
        FROM members AS mbr
        
        INNER JOIN trainer_sessions AS train_sess ON mbr.id = train_sess.member_id
        INNER JOIN trainer_packages AS train_pack ON train_pack.id = train_sess.trainer_package_id AND train_pack.status = 'LGT'
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
        
        LEFT JOIN (SELECT mbr_reg.member_id AS mbr_reg_member_id, ld_continue_view.submission_date_continue, ld_continue_view.total_days_continue, ld_continue_view.total_price_continue from
        (SELECT ld.id, ld.member_registration_id as member_registration_id_continue, ld.submission_date as submission_date_continue,
        ld_view.total_days as total_days_continue, ld_view.total_price as total_price_continue FROM  leave_days ld 
        INNER JOIN 
        (SELECT leave_day_continue_id, SUM(days) AS total_days, SUM(price) AS total_price
        FROM (SELECT id, ifnull(leave_day_continue_id, id) AS leave_day_continue_id, days, price FROM leave_days) AS view_1
        GROUP BY leave_day_continue_id) AS ld_view ON ld.id=ld_view.leave_day_continue_id 
        WHERE NOW() BETWEEN ld.submission_date AND DATE_ADD(ld.submission_date, INTERVAL (ifnull(total_days,0)) DAY))
        AS ld_continue_view
        INNER JOIN member_registrations AS mbr_reg ON mbr_reg.id = ld_continue_view.member_registration_id_continue)
        AS leave_days_view ON mbr.id = leave_days_view.mbr_reg_member_id
        
        WHERE
            IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) > 0"
            . ($id ? " and train_sess.id='$id' " : '') . "
            order by cits_view.updated_at_check_in desc, train_sess.updated_at";
        $activeTrainerSessions = DB::select($sql);

        return $activeTrainerSessions;
    }

    public static function getExpiredPT($memberId = "")
    {
        $sql = "SELECT mbr.id, mbr.full_name AS member_name, mbr.photos, mbr.member_code,
                train_sess.start_date, train_sess.id AS ts_id, train_sess.days AS ts_days, train_sess.days AS ts_number_of_days, train_sess.package_price AS ts_package_price, train_sess.member_id AS registered_member_id,
                pers_train.full_name AS trainer_full_name, train_sess.description,
                train_pack.package_name, met_pay.name AS method_payment_name,

                DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY) AS expired_date,
                DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY) AS max_end_date

                FROM trainer_sessions AS train_sess

                INNER JOIN (SELECT MAX(id) AS max_train_sess_id FROM trainer_sessions AS train_sess GROUP BY member_id)
                AS max_train_sess_view ON train_sess.id = max_train_sess_view.max_train_sess_id

                INNER JOIN trainer_packages AS train_pack ON train_pack.id = train_sess.trainer_package_id
                INNER JOIN members AS mbr ON mbr.id = train_sess.member_id
                INNER JOIN personal_trainers AS pers_train ON pers_train.id = train_sess.trainer_id
                INNER JOIN method_payments AS met_pay ON met_pay.id = train_sess.method_payment_id

                LEFT JOIN (SELECT trainer_session_id, COUNT(id) AS check_in_count FROM check_in_trainer_sessions WHERE check_out_time 
                IS NOT NULL GROUP BY trainer_session_id)
                AS count_check_in_view ON train_sess.id = count_check_in_view.trainer_session_id


                WHERE train_pack.status IS NULL AND NOW() > DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY)
                ORDER BY max_end_date" . ($memberId ? " and mbr.id='$memberId' " : '');

        $activeTrainerSessions = DB::select($sql);
        return $activeTrainerSessions;
    }

    public static function lgtActive($card_number = "", $trainner_session_id = "")
    {
        $sql = "SELECT mbr.full_name AS member_name, mbr.nickname, mbr.phone_number, mbr.gender, mbr.born, mbr.member_code, mbr.email, mbr.ig, mbr.emergency_contact, mbr.ec_name,
        mbr.card_number, mbr.id_code_count, mbr.photos, mbr.status, mbr.address, mbr.id AS member_id,
        train_sess.id, train_sess.start_date, train_sess.number_of_session AS ts_number_of_session, train_sess.days,
        train_pack.package_name,
        pers_train.full_name AS trainer_name,
        cits_view.current_check_in_trainer_sessions_id, cits_view.check_in_time, cits_view.check_out_time, cits_view.updated_at_check_in,
	
        DATE_ADD(train_sess.start_date, INTERVAL COALESCE(leave_days_view.total_days_continue, 0) + train_sess.days DAY) AS expired_date,
        DATE_ADD(leave_days_view.submission_date_continue, INTERVAL leave_days_view.total_days_continue DAY) AS expired_leave_days,

        CASE WHEN mbr_reg_member_id IS NULL THEN 'No Leave Days' ELSE 'Freeze' END AS leave_day_status,

        IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) AS remaining_sessions,
        
        CASE WHEN NOW() > DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY) THEN 'Over'
        WHEN NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY) THEN 'Running'
        ELSE 'Not Started'
        END as expired_date_status
    
        FROM members AS mbr
        
        INNER JOIN trainer_sessions AS train_sess ON mbr.id = train_sess.member_id
        INNER JOIN trainer_packages AS train_pack ON train_pack.id = train_sess.trainer_package_id AND train_pack.status = 'LGT'
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
        
        LEFT JOIN (SELECT mbr_reg.member_id AS mbr_reg_member_id, ld_continue_view.submission_date_continue, ld_continue_view.total_days_continue from
        (SELECT ld.id, ld.member_registration_id as member_registration_id_continue, ld.submission_date as submission_date_continue, 
        ld_view.total_days as total_days_continue FROM  leave_days ld 
        INNER JOIN 
        (SELECT leave_day_continue_id, sum(days) AS total_days 
        FROM (SELECT id,ifnull(leave_day_continue_id, id) AS leave_day_continue_id,days FROM leave_days) AS view_1
        GROUP BY leave_day_continue_id) AS ld_view ON ld.id=ld_view.leave_day_continue_id 
        WHERE NOW() BETWEEN ld.submission_date AND DATE_ADD(ld.submission_date, INTERVAL (ifnull(total_days,0)) DAY))
        AS ld_continue_view
        INNER JOIN member_registrations AS mbr_reg ON mbr_reg.id = ld_continue_view.member_registration_id_continue)
        AS leave_days_view ON mbr.id = leave_days_view.mbr_reg_member_id
        
        -- WHERE
            -- IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) > 0
        -- AND NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue,0)) DAY)
        "
            . ($card_number ? " and mbr.card_number='$card_number' " : '') . ($trainner_session_id ? " and train_sess.id='$trainner_session_id' " : '') . "
            order by cits_view.updated_at_check_in desc";
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
        DATE_ADD(leave_days_view.submission_date_continue, INTERVAL leave_days_view.total_days_continue DAY) AS expired_leave_days,

        CASE WHEN mbr_reg_member_id IS NULL THEN 'No Leave Days' ELSE 'Freeze' END AS leave_day_status,

        IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) AS remaining_sessions,
        
        CASE WHEN NOW() > DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY) THEN 'Over'
        WHEN NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY) THEN 'Running'
        ELSE 'Not Started'
        END as STATUS
    
        FROM members AS mbr
        
        INNER JOIN trainer_sessions AS train_sess ON mbr.id = train_sess.member_id
        INNER JOIN trainer_packages AS train_pack ON train_pack.id = train_sess.trainer_package_id AND train_pack.status IS NULL
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
        
        LEFT JOIN (SELECT mbr_reg.member_id AS mbr_reg_member_id, ld_continue_view.submission_date_continue, ld_continue_view.total_days_continue from
        (SELECT ld.id, ld.member_registration_id as member_registration_id_continue, ld.submission_date as submission_date_continue, 
        ld_view.total_days as total_days_continue FROM  leave_days ld 
        INNER JOIN 
        (SELECT leave_day_continue_id, sum(days) AS total_days 
        FROM (SELECT id,ifnull(leave_day_continue_id, id) AS leave_day_continue_id,days FROM leave_days) AS view_1
        GROUP BY leave_day_continue_id) AS ld_view ON ld.id=ld_view.leave_day_continue_id 
        WHERE NOW() BETWEEN ld.submission_date AND DATE_ADD(ld.submission_date, INTERVAL (ifnull(total_days,0)) DAY))
        AS ld_continue_view
        INNER JOIN member_registrations AS mbr_reg ON mbr_reg.id = ld_continue_view.member_registration_id_continue)
        AS leave_days_view ON mbr.id = leave_days_view.mbr_reg_member_id
        
        WHERE
            -- IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) = 0
        -- AND NOW() < DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue,0)) DAY)
        NOW() < train_sess.start_date"
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
        DATE_ADD(leave_days_view.submission_date_continue, INTERVAL leave_days_view.total_days_continue DAY) AS expired_leave_days,

        CASE WHEN mbr_reg_member_id IS NULL THEN 'No Leave Days' ELSE 'Freeze' END AS leave_day_status,

        IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) AS remaining_sessions,
        
        CASE WHEN NOW() > DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY) THEN 'Over'
        WHEN NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY) THEN 'Running'
        ELSE 'Not Started'
        END as STATUS
    
        FROM members AS mbr
        
        INNER JOIN trainer_sessions AS train_sess ON mbr.id = train_sess.member_id
        INNER JOIN trainer_packages AS train_pack ON train_pack.id = train_sess.trainer_package_id AND train_pack.status IS NULL
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
        
        LEFT JOIN (SELECT mbr_reg.member_id AS mbr_reg_member_id, ld_continue_view.submission_date_continue, ld_continue_view.total_days_continue from
        (SELECT ld.id, ld.member_registration_id as member_registration_id_continue, ld.submission_date as submission_date_continue, 
        ld_view.total_days as total_days_continue FROM  leave_days ld 
        INNER JOIN 
        (SELECT leave_day_continue_id, sum(days) AS total_days 
        FROM (SELECT id,ifnull(leave_day_continue_id, id) AS leave_day_continue_id,days FROM leave_days) AS view_1
        GROUP BY leave_day_continue_id) AS ld_view ON ld.id=ld_view.leave_day_continue_id 
        WHERE NOW() BETWEEN ld.submission_date AND DATE_ADD(ld.submission_date, INTERVAL (ifnull(total_days,0)) DAY))
        AS ld_continue_view
        INNER JOIN member_registrations AS mbr_reg ON mbr_reg.id = ld_continue_view.member_registration_id_continue)
        AS leave_days_view ON mbr.id = leave_days_view.mbr_reg_member_id
        
        WHERE
            -- IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) = 0
        -- AND NOW() < DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue,0)) DAY)
        NOW() > DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY)"
             . ($memberId ? " and mbr.id='$memberId' " : '') . "
            order by cits_view.updated_at_check_in desc";
        $pendingTrainerSessions = DB::select($sql);

        return $pendingTrainerSessions;
    }

    public static function checkInPT($card_number = "", $trainner_session_id = "")
    {
        $sql = "SELECT mbr.full_name AS member_name, mbr.nickname, mbr.phone_number, mbr.gender, mbr.born, mbr.member_code, mbr.email, mbr.ig, mbr.emergency_contact, mbr.ec_name,
        mbr.card_number, mbr.id_code_count, mbr.photos, mbr.status, mbr.address, mbr.id AS member_id,
        train_sess.id, train_sess.start_date, train_sess.number_of_session AS ts_number_of_session, train_sess.days,
        train_pack.package_name,
        pers_train.full_name AS trainer_name, pers_train.id AS trainer_id,
        cits_view.current_check_in_trainer_sessions_id, cits_view.check_in_time, cits_view.check_out_time, cits_view.updated_at_check_in,
	
        DATE_ADD(train_sess.start_date, INTERVAL COALESCE(leave_days_view.total_days_continue, 0) + train_sess.days DAY) AS expired_date,
        DATE_ADD(leave_days_view.submission_date_continue, INTERVAL leave_days_view.total_days_continue DAY) AS expired_leave_days,

        CASE WHEN mbr_reg_member_id IS NULL THEN 'No Leave Days' ELSE 'Freeze' END AS leave_day_status,

        IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) AS remaining_sessions,
        
        CASE WHEN NOW() > DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY) THEN 'Over'
        WHEN NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY) THEN 'Running'
        ELSE 'Not Started'
        END as STATUS
    
        FROM members AS mbr
        
        INNER JOIN trainer_sessions AS train_sess ON mbr.id = train_sess.member_id
        INNER JOIN trainer_packages AS train_pack ON train_pack.id = train_sess.trainer_package_id AND train_pack.status IS NULL
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
        
        LEFT JOIN (SELECT mbr_reg.member_id AS mbr_reg_member_id, ld_continue_view.submission_date_continue, ld_continue_view.total_days_continue from
        (SELECT ld.id, ld.member_registration_id as member_registration_id_continue, ld.submission_date as submission_date_continue, 
        ld_view.total_days as total_days_continue FROM  leave_days ld 
        INNER JOIN 
        (SELECT leave_day_continue_id, sum(days) AS total_days 
        FROM (SELECT id,ifnull(leave_day_continue_id, id) AS leave_day_continue_id,days FROM leave_days) AS view_1
        GROUP BY leave_day_continue_id) AS ld_view ON ld.id=ld_view.leave_day_continue_id 
        WHERE NOW() BETWEEN ld.submission_date AND DATE_ADD(ld.submission_date, INTERVAL (ifnull(total_days,0)) DAY))
        AS ld_continue_view
        INNER JOIN member_registrations AS mbr_reg ON mbr_reg.id = ld_continue_view.member_registration_id_continue)
        AS leave_days_view ON mbr.id = leave_days_view.mbr_reg_member_id
        
        WHERE
            NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue,0)) DAY) AND
            IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) > 0"
            . ($card_number ? " and mbr.card_number='$card_number' " : '') . ($trainner_session_id ? " and train_sess.id='$trainner_session_id' " : '') . " 
            order by cits_view.updated_at_check_in desc";
        $activeTrainerSessions = DB::select($sql);

        return $activeTrainerSessions;
    }

    public static function checkInLGT($card_number = "", $trainner_session_id = "")
    {
        $sql = "SELECT mbr.full_name AS member_name, mbr.nickname, mbr.phone_number, mbr.gender, mbr.born, mbr.member_code, mbr.email, mbr.ig, mbr.emergency_contact, mbr.ec_name,
        mbr.card_number, mbr.id_code_count, mbr.photos, mbr.status, mbr.address, mbr.id AS member_id,
        train_sess.id, train_sess.start_date, train_sess.number_of_session AS ts_number_of_session, train_sess.days,
        train_pack.package_name,
        pers_train.full_name AS trainer_name,
        cits_view.current_check_in_trainer_sessions_id, cits_view.check_in_time, cits_view.check_out_time, cits_view.updated_at_check_in,
	
        DATE_ADD(train_sess.start_date, INTERVAL COALESCE(leave_days_view.total_days_continue, 0) + train_sess.days DAY) AS expired_date,
        DATE_ADD(leave_days_view.submission_date_continue, INTERVAL leave_days_view.total_days_continue DAY) AS expired_leave_days,

        CASE WHEN mbr_reg_member_id IS NULL THEN 'No Leave Days' ELSE 'Freeze' END AS leave_day_status,

        IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) AS remaining_sessions,
        
        CASE WHEN NOW() > DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY) THEN 'Over'
        WHEN NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY) THEN 'Running'
        ELSE 'Not Started'
        END as expired_date_status
    
        FROM members AS mbr
        
        INNER JOIN trainer_sessions AS train_sess ON mbr.id = train_sess.member_id
        INNER JOIN trainer_packages AS train_pack ON train_pack.id = train_sess.trainer_package_id AND train_pack.status = 'LGT'
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
        
        LEFT JOIN (SELECT mbr_reg.member_id AS mbr_reg_member_id, ld_continue_view.submission_date_continue, ld_continue_view.total_days_continue from
        (SELECT ld.id, ld.member_registration_id as member_registration_id_continue, ld.submission_date as submission_date_continue, 
        ld_view.total_days as total_days_continue FROM  leave_days ld 
        INNER JOIN 
        (SELECT leave_day_continue_id, sum(days) AS total_days 
        FROM (SELECT id,ifnull(leave_day_continue_id, id) AS leave_day_continue_id,days FROM leave_days) AS view_1
        GROUP BY leave_day_continue_id) AS ld_view ON ld.id=ld_view.leave_day_continue_id 
        WHERE NOW() BETWEEN ld.submission_date AND DATE_ADD(ld.submission_date, INTERVAL (ifnull(total_days,0)) DAY))
        AS ld_continue_view
        INNER JOIN member_registrations AS mbr_reg ON mbr_reg.id = ld_continue_view.member_registration_id_continue)
        AS leave_days_view ON mbr.id = leave_days_view.mbr_reg_member_id
        
        -- WHERE
            -- IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) > 0
        -- AND NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue,0)) DAY)

        WHERE
            NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL (train_sess.days + IFNULL(leave_days_view.total_days_continue,0)) DAY) AND
            IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) > 0
        "
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
        DATE_ADD(leave_days_view.submission_date_continue, INTERVAL leave_days_view.total_days_continue DAY) AS expired_leave_days,

        CASE WHEN mbr_reg_member_id IS NULL THEN 'No Leave Days' ELSE 'Freeze' END AS leave_day_status,

        IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) AS remaining_sessions,
        
        CASE WHEN NOW() > DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY) THEN 'Over'
        WHEN NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY) THEN 'Running'
        ELSE 'Not Started'
        END as STATUS
    
        FROM members AS mbr
        
        INNER JOIN trainer_sessions AS train_sess ON mbr.id = train_sess.member_id
        INNER JOIN trainer_packages AS train_pack ON train_pack.id = train_sess.trainer_package_id AND train_pack.status IS NULL
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
        
        LEFT JOIN (SELECT mbr_reg.member_id AS mbr_reg_member_id, ld_continue_view.submission_date_continue, ld_continue_view.total_days_continue from
        (SELECT ld.id, ld.member_registration_id as member_registration_id_continue, ld.submission_date as submission_date_continue, 
        ld_view.total_days as total_days_continue FROM  leave_days ld 
        INNER JOIN 
        (SELECT leave_day_continue_id, sum(days) AS total_days 
        FROM (SELECT id,ifnull(leave_day_continue_id, id) AS leave_day_continue_id,days FROM leave_days) AS view_1
        GROUP BY leave_day_continue_id) AS ld_view ON ld.id=ld_view.leave_day_continue_id 
        WHERE NOW() BETWEEN ld.submission_date AND DATE_ADD(ld.submission_date, INTERVAL (ifnull(total_days,0)) DAY))
        AS ld_continue_view
        INNER JOIN member_registrations AS mbr_reg ON mbr_reg.id = ld_continue_view.member_registration_id_continue)
        AS leave_days_view ON mbr.id = leave_days_view.mbr_reg_member_id 
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
        DATE_ADD(leave_days_view.submission_date_continue, INTERVAL leave_days_view.total_days_continue DAY) AS expired_leave_days,

        CASE WHEN mbr_reg_member_id IS NULL THEN 'No Leave Days' ELSE 'Freeze' END AS leave_day_status,

        IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) AS remaining_sessions,
        
        CASE WHEN NOW() > DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY) THEN 'Over'
        WHEN NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY) THEN 'Running'
        ELSE 'Not Started'
        END as STATUS
    
        FROM members AS mbr
        
        INNER JOIN trainer_sessions AS train_sess ON mbr.id = train_sess.member_id
        INNER JOIN trainer_packages AS train_pack ON train_pack.id = train_sess.trainer_package_id AND train_pack.status IS NULL
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
        
        LEFT JOIN (SELECT mbr_reg.member_id AS mbr_reg_member_id, ld_continue_view.submission_date_continue, ld_continue_view.total_days_continue from
        (SELECT ld.id, ld.member_registration_id as member_registration_id_continue, ld.submission_date as submission_date_continue, 
        ld_view.total_days as total_days_continue FROM  leave_days ld 
        INNER JOIN 
        (SELECT leave_day_continue_id, sum(days) AS total_days 
        FROM (SELECT id,ifnull(leave_day_continue_id, id) AS leave_day_continue_id,days FROM leave_days) AS view_1
        GROUP BY leave_day_continue_id) AS ld_view ON ld.id=ld_view.leave_day_continue_id 
        WHERE NOW() BETWEEN ld.submission_date AND DATE_ADD(ld.submission_date, INTERVAL (ifnull(total_days,0)) DAY))
        AS ld_continue_view
        INNER JOIN member_registrations AS mbr_reg ON mbr_reg.id = ld_continue_view.member_registration_id_continue)
        AS leave_days_view ON mbr.id = leave_days_view.mbr_reg_member_id
        
        where train_sess.start_date >= '$fromDate' AND train_sess.start_date <= '$toDate'
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
        DATE_ADD(leave_days_view.submission_date_continue, INTERVAL leave_days_view.total_days_continue DAY) AS expired_leave_days,

        CASE WHEN mbr_reg_member_id IS NULL THEN 'No Leave Days' ELSE 'Freeze' END AS leave_day_status,

        IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) AS remaining_sessions,
        
        CASE WHEN NOW() > DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY) THEN 'Over'
        WHEN NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY) THEN 'Running'
        ELSE 'Not Started'
        END as STATUS
    
        FROM members AS mbr
        
        INNER JOIN trainer_sessions AS train_sess ON mbr.id = train_sess.member_id
        INNER JOIN trainer_packages AS train_pack ON train_pack.id = train_sess.trainer_package_id AND train_pack.status IS NULL
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

        LEFT JOIN (SELECT mbr_reg.member_id AS mbr_reg_member_id, ld_continue_view.submission_date_continue, ld_continue_view.total_days_continue, ld_continue_view.total_price_continue from
        (SELECT ld.id, ld.member_registration_id as member_registration_id_continue, ld.submission_date as submission_date_continue,
        ld_view.total_days as total_days_continue, ld_view.total_price as total_price_continue FROM  leave_days ld 
        INNER JOIN 
        (SELECT leave_day_continue_id, sum(days) AS total_days, SUM(price) AS total_price
        FROM (SELECT id,ifnull(leave_day_continue_id, id) AS leave_day_continue_id, days, price FROM leave_days) AS view_1
        GROUP BY leave_day_continue_id) AS ld_view ON ld.id=ld_view.leave_day_continue_id 
        WHERE NOW() BETWEEN ld.submission_date AND DATE_ADD(ld.submission_date, INTERVAL (ifnull(total_days,0)) DAY))
        AS ld_continue_view
        INNER JOIN member_registrations AS mbr_reg ON mbr_reg.id = ld_continue_view.member_registration_id_continue)
        AS leave_days_view ON mbr.id = leave_days_view.mbr_reg_member_id
        where train_sess.id = '$id'";
        $activeTrainerSessions = DB::select($sql);

        return $activeTrainerSessions;
    }
}