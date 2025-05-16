<?php

namespace App\Models\Trainer;

use App\Models\Member\Member;
use App\Models\MethodPayment;
use App\Models\Staff\FitnessConsultant;
use App\Models\Staff\PersonalTrainer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LGT extends Model
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
        return $this->belongsTo(FitnessConsultant::class, 'fc_id', 'id');
    }

    public function trainerSessionCheckIn()
    {
        return $this->hasMany(CheckInTrainerSession::class);
    }

    public function methodPayment()
    {
        return $this->belongsTo(MethodPayment::class, 'method_payment_id', 'id');
    }

    public static function getActivePTList($card_number = "")
    {
        $sql = "SELECT mbr.full_name AS member_name, mbr.nickname, mbr.phone_number, mbr.gender, mbr.born, mbr.member_code, mbr.email, mbr.ig, mbr.emergency_contact, mbr.ec_name,
        mbr.card_number, mbr.id_code_count, mbr.photos, mbr.status, mbr.address,
        train_sess.id, train_sess.start_date, train_sess.number_of_session AS ts_number_of_session, train_sess.days,
        train_pack.package_name,
        pers_train.full_name AS trainer_name,
        cits_view.current_check_in_trainer_sessions_id, cits_view.check_in_time, cits_view.check_out_time, cits_view.updated_at_check_in,
	
        DATE_ADD(train_sess.start_date, INTERVAL COALESCE(leave_days_view.total_days_continue, 0) + train_sess.days DAY) AS expired_date,
        DATE_ADD(leave_days_view.submission_date_continue, INTERVAL leave_days_view.total_days_continue DAY) AS expired_leave_days,

        CASE WHEN mbr_reg_member_id IS NULL THEN 'No Leave Days' ELSE 'Freeze' END AS leave_day_status,

        IFNULL(train_sess.number_of_session - count_check_in_view.check_in_count, train_sess.number_of_session) AS remaining_sessions,
        
        -- CASE WHEN NOW() > DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY) THEN 'Over'
        -- WHEN NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY) THEN 'Running'
        -- ELSE 'Not Started'
        -- END as STATUS,

        CASE WHEN NOW() > DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY) THEN 'Over'
        WHEN NOW() BETWEEN train_sess.start_date AND DATE_ADD(train_sess.start_date, INTERVAL train_sess.days DAY) THEN 'Running'
        ELSE 'Not Started'
        END as expired_date_status
    
        FROM members AS mbr
        
        INNER JOIN trainer_sessions AS train_sess ON mbr.id = train_sess.member_id
        INNER JOIN trainer_packages AS train_pack ON train_pack.id = train_sess.trainer_package_id AND train_pack.status = 'LGT'
        INNER JOIN personal_trainers AS pers_train ON pers_train.id = train_sess.trainer_id
        INNER JOIN fitness_consultants AS fit_cons ON fit_cons.id= train_sess.fc_id
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
        AS leave_days_view ON mbr.id = leave_days_view.mbr_reg_member_id"
            . ($card_number ? " and mbr.card_number='$card_number' " : '') . "
            order by cits_view.updated_at_check_in desc, train_sess.updated_at";
        $activeTrainerSessions = DB::select($sql);

        return $activeTrainerSessions;
    }
}
