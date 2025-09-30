<?php

namespace App\Models\Member;

// use Alfa6661\AutoNumber\AutoNumberTrait;
use App\Models\MethodPayment;
use App\Models\Staff\FitnessConsultant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MemberRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'member_package_id',
        'package_price',
        'admin_price',
        'start_date',
        'days',
        'old_days',
        'method_payment_id',
        'description',
        'fc_id',
        'user_id',
    ];

    protected $hidden = [];

    //jangan ditiru, nando sesat, harusnya member
    public function members()
    {
        return $this->belongsTo(Member::class, 'member_id', 'id');
    }

    public function memberPackage()
    {
        return $this->belongsTo(MemberPackage::class, 'member_package_id', 'id');
    }

    public function methodPayment()
    {
        return $this->belongsTo(MethodPayment::class, 'method_payment_id', 'id');
    }

    public function fitnessConsultant()
    {
        return $this->belongsTo(User::class, 'fc_id', 'id');
    }

    public function users()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function memberRegistrationCheckIn()
    {
        return $this->hasMany(CheckInMember::class);
    }

    public function leaveDays()
    {
        return $this->hasMany(LeaveDay::class);
    }

    public static function getActiveList($card_number = "", $member_id = "")
    {
        $sql = "SELECT mbr_reg.id, mbr_reg.start_date, mbr_reg.days as member_registration_days,
            mbr_reg.package_price as mr_package_price,  mbr_reg.admin_price as mr_admin_price, bs.id as 'store_branch_id', bs.name as 'branch_store_name',
            mbr.id as member_id, mbr.full_name as member_name, mbr.nickname, mbr.email, mbr.ig, mbr.emergency_contact, mbr.ec_name,
            mbr.address, mbr.member_code, mbr_reg.days,
            mbr.phone_number, mbr.born, mbr.photos, mbr.gender, mbr.id_code_count,
            mbr_pkg.package_name, mbr_pkg.days, mbr_pkg.package_price,
            mtd_pay.name as method_payment_name, usr.full_name as staff_name,
            -- fit_cons.full_name as fc_name, fit_cons.phone_number as fc_phone_number,
            cim_view.current_check_in_members_id, cim_view.check_in_time, cim_view.check_out_time, cim_view.updated_at_check_in,
            lds_view.submission_date, lds_continue_view.submission_date_continue, lds_continue_view.id as lds_continue_id,  lds_view.days as number_of_leave_days, lds_view.total_days,
            'bg-dark' as birthdayCelebrating,
            
            DATE_ADD(mbr_reg.start_date, INTERVAL COALESCE(lds_view.total_days, 0) + mbr_reg.days DAY) as expired_date,
            DATE_ADD(lds_view.submission_date, INTERVAL lds_view.days DAY) as expired_leave_days,

            CASE WHEN NOW() > DATE_ADD(mbr_reg.start_date, INTERVAL mbr_reg.days DAY) THEN 'Over'
                WHEN NOW() BETWEEN mbr_reg.start_date AND DATE_ADD(mbr_reg.start_date, INTERVAL mbr_reg.days DAY) THEN 'Running'
                ELSE 'Not Started'
                END as status,
            CASE when member_registration_id_continue is null then 'No Leave Days' else 'Freeze' end as leave_day_status,

            CONCAT(YEAR(CURDATE()), ' - ', MONTH(mbr.born), ' - ', DAY(mbr.born)) as member_birthday,
            DATEDIFF(CONCAT(YEAR(CURDATE()), ' - ', MONTH(mbr.born), ' - ', DAY(mbr.born)), CURDATE()) as days_until_birthday,
            ifnull((select sum(value) from member_registration_payments mrp where mbr_reg.id=mrp.member_registration_id),0) as payment_summary
            from members as mbr
            inner join branch_stores bs on mbr.branch_store_id = bs.id
            inner join member_registrations mbr_reg on mbr.id = mbr_reg.member_id
            inner join member_packages mbr_pkg on mbr_pkg.id = mbr_reg.member_package_id
            inner join method_payments mtd_pay on mtd_pay.id = mbr_reg.method_payment_id
            inner join users usr on usr.id=mbr_reg.user_id
            -- left join fitness_consultants fit_cons on fit_cons.id = mbr_reg.fc_id

            left join (select cim1.id as current_check_in_members_id, cim1.updated_at as updated_at_check_in, cim1.member_registration_id, cim1.check_in_time, cim1.check_out_time from check_in_members cim1
            inner join (SELECT max(id) as max_id FROM check_in_members group by member_registration_id) as cim2 on cim1.id=cim2.max_id) as cim_view on cim_view.member_registration_id = mbr_reg.id

            left join 
                (select lds.member_registration_id, lds.submission_date, price, days, ifnull(total_days,0) as total_days, max_id from leave_days as lds
                inner join (select max(id) as max_id, SUM(days) as total_days from leave_days
                group by member_registration_id) as view_max_id on view_max_id.max_id =lds.id) as lds_view
            on mbr_reg.id = lds_view.member_registration_id 

            left join 
                (SELECT ld.id, ld.member_registration_id as member_registration_id_continue, ld.submission_date as submission_date_continue, 
                ld_view.total_days as total_days_continue FROM  leave_days ld 
                INNER JOIN 
                (SELECT leave_day_continue_id, sum(days) AS total_days 
                FROM (SELECT id,ifnull(leave_day_continue_id, id) AS leave_day_continue_id,days FROM leave_days) AS view_1
                GROUP BY leave_day_continue_id) AS ld_view ON ld.id=ld_view.leave_day_continue_id 
                WHERE NOW() BETWEEN ld.submission_date AND DATE_ADD(ld.submission_date, INTERVAL (ifnull(total_days,0)) DAY)) as lds_continue_view
            on mbr_reg.id = lds_continue_view.member_registration_id_continue

            where NOW() BETWEEN mbr_reg.start_date AND DATE_ADD(mbr_reg.start_date, INTERVAL (mbr_reg.days + ifnull(total_days,0)) DAY) AND mbr_reg.days > 1"
            . ($card_number ? " and mbr.card_number='$card_number' " : '') . ($member_id ? " and mbr.id='$member_id' " : '') .  "
            order by cim_view.updated_at_check_in desc";
        $activeMemberRegistrations = DB::select($sql);

        return $activeMemberRegistrations;
    }

    public static function getExpiredList($memberId = "")
    {
        $sql = "SELECT mbr_reg.id, mbr_reg.start_date, mbr_reg.days as member_registration_days,
            mbr_reg.package_price as mr_package_price,  mbr_reg.admin_price as mr_admin_price,
            mbr.id as member_id, mbr.full_name as member_name, mbr.nickname, mbr.email, mbr.ig, mbr.emergency_contact, mbr.ec_name,
            mbr.address, mbr.member_code, mbr_reg.days,
            mbr.phone_number, mbr.born, mbr.photos, mbr.gender, mbr.id_code_count,
            mbr_pkg.package_name, mbr_pkg.days, mbr_pkg.package_price, mbr_pkg.description,
            mtd_pay.name as method_payment_name, usr.full_name as staff_name,
            -- fit_cons.full_name as fc_name, fit_cons.phone_number as fc_phone_number,
            cim_view.current_check_in_members_id, cim_view.check_in_time, cim_view.check_out_time, cim_view.updated_at_check_in,
            lds_view.submission_date, lds_continue_view.submission_date_continue,  lds_view.days as number_of_leave_days, lds_view.total_days,
            'bg-dark' as birthdayCelebrating,
            
            DATE_ADD(mbr_reg.start_date, INTERVAL COALESCE(lds_view.total_days, 0) + mbr_reg.days DAY) as expired_date,
            DATE_ADD(lds_view.submission_date, INTERVAL lds_view.days DAY) as expired_leave_days,

            CASE WHEN NOW() > DATE_ADD(mbr_reg.start_date, INTERVAL mbr_reg.days DAY) THEN 'Over'
                WHEN NOW() BETWEEN mbr_reg.start_date AND DATE_ADD(mbr_reg.start_date, INTERVAL mbr_reg.days DAY) THEN 'Running'
                ELSE 'Not Started'
                END as status,
            CASE when member_registration_id_continue is null then 'No Leave Days' else 'Freeze' end as leave_day_status, 
            CONCAT(YEAR(CURDATE()), ' - ', MONTH(mbr.born), ' - ', DAY(mbr.born)) as member_birthday,
            DATEDIFF(CONCAT(YEAR(CURDATE()), ' - ', MONTH(mbr.born), ' - ', DAY(mbr.born)), CURDATE()) as days_until_birthday
            from members as mbr

            inner join member_registrations mbr_reg on mbr.id = mbr_reg.member_id
            inner join member_packages mbr_pkg on mbr_pkg.id = mbr_reg.member_package_id
            inner join method_payments mtd_pay on mtd_pay.id = mbr_reg.method_payment_id
            inner join users usr on usr.id=mbr_reg.user_id
            -- left join fitness_consultants fit_cons on fit_cons.id = mbr_reg.fc_id

            left join (select cim1.id as current_check_in_members_id, cim1.updated_at as updated_at_check_in, cim1.member_registration_id, cim1.check_in_time, cim1.check_out_time from check_in_members cim1
            inner join (SELECT max(id) as max_id FROM check_in_members group by member_registration_id) as cim2 on cim1.id=cim2.max_id) as cim_view on cim_view.member_registration_id = mbr_reg.id

            left join (select lds.member_registration_id, lds.submission_date, price, days, ifnull(total_days,0) as total_days, max_id from leave_days as lds
            inner join (select max(id) as max_id, SUM(days) as total_days from leave_days
            group by member_registration_id) as view_max_id on view_max_id.max_id =lds.id) as lds_view
            on mbr_reg.id = lds_view.member_registration_id 

            left join (SELECT ld.id, ld.member_registration_id as member_registration_id_continue, ld.submission_date as submission_date_continue, 
                ld_view.total_days as total_days_continue FROM  leave_days ld 
                INNER JOIN 
                (SELECT leave_day_continue_id, sum(days) AS total_days 
                FROM (SELECT id,ifnull(leave_day_continue_id, id) AS leave_day_continue_id,days FROM leave_days) AS view_1
                GROUP BY leave_day_continue_id) AS ld_view ON ld.id=ld_view.leave_day_continue_id 
                WHERE NOW() BETWEEN ld.submission_date AND DATE_ADD(ld.submission_date, INTERVAL (ifnull(total_days,0)) DAY)) as lds_continue_view
                on mbr_reg.id = lds_continue_view.member_registration_id_continue

            where NOW() > DATE_ADD(mbr_reg.start_date, INTERVAL (mbr_reg.days + ifnull(total_days,0)) DAY)"
            . ($memberId ? " and mbr.id=$memberId " : "") .
            " order by cim_view.updated_at_check_in desc";
        $activeMemberRegistrations = DB::select($sql);
        // dd($sql);

        return $activeMemberRegistrations;
    }

    public static function getActiveListById($cardNumber = "", $memberRegistrationId = "")
    {
        $sql = "SELECT mbr_reg.id, mbr_reg.start_date, mbr_reg.days as member_registration_days,
            mbr_reg.package_price as mr_package_price,  mbr_reg.admin_price as mr_admin_price,
            mbr.id as member_id, mbr.full_name as member_name, mbr.nickname, mbr.email, mbr.ig, mbr.emergency_contact, mbr.ec_name,
            mbr.address, mbr.member_code, mbr_reg.days, mbr.phone_number, mbr.born, mbr.photos, mbr.gender, mbr.id_code_count, mbr.card_number,
            mbr_pkg.package_name, mbr_pkg.days, mbr_pkg.package_price, mbr_pkg.description, mbr_pkg.id AS member_package_id,
            mtd_pay.name as method_payment_name, usr.full_name as staff_name, usr.id as fc_id, usr.full_name AS fc_name, mtd_pay.id AS method_payment_id,
            cim_view.current_check_in_members_id, cim_view.check_in_time, cim_view.check_out_time, cim_view.updated_at_check_in,
            lds_view.submission_date, lds_continue_view.submission_date_continue,  lds_view.days as number_of_leave_days, lds_view.total_days, lds_continue_view.total_price_continue,
            'bg-dark' as birthdayCelebrating,
            
            DATE_ADD(mbr_reg.start_date, INTERVAL COALESCE(lds_view.total_days, 0) + mbr_reg.days DAY) as expired_date,
            DATE_ADD(lds_view.submission_date, INTERVAL lds_view.days DAY) as expired_leave_days,

            CASE WHEN NOW() > DATE_ADD(mbr_reg.start_date, INTERVAL mbr_reg.days DAY) THEN 'Over'
                WHEN NOW() BETWEEN mbr_reg.start_date AND DATE_ADD(mbr_reg.start_date, INTERVAL mbr_reg.days DAY) THEN 'Running'
                ELSE 'Not Started'
                END as status,
            CASE when member_registration_id_continue is null then 'No Leave Days' else 'Freeze' end as leave_day_status, 
            CONCAT(YEAR(CURDATE()), ' - ', MONTH(mbr.born), ' - ', DAY(mbr.born)) as member_birthday,
            DATEDIFF(CONCAT(YEAR(CURDATE()), ' - ', MONTH(mbr.born), ' - ', DAY(mbr.born)), CURDATE()) as days_until_birthday
            from members as mbr

            inner join member_registrations mbr_reg on mbr.id = mbr_reg.member_id
            inner join member_packages mbr_pkg on mbr_pkg.id = mbr_reg.member_package_id
            inner join method_payments mtd_pay on mtd_pay.id = mbr_reg.method_payment_id
            inner join users usr on usr.id=mbr_reg.user_id
            -- WHERE usr.role = 'FC'

            left join (select cim1.id as current_check_in_members_id, cim1.updated_at as updated_at_check_in, cim1.member_registration_id, cim1.check_in_time, cim1.check_out_time from check_in_members cim1
            inner join (SELECT max(id) as max_id FROM check_in_members group by member_registration_id) as cim2 on cim1.id=cim2.max_id) as cim_view on cim_view.member_registration_id = mbr_reg.id

            left join (select lds.member_registration_id, lds.submission_date, price, days, ifnull(total_days,0) as total_days, max_id from leave_days as lds
            inner join (select max(id) as max_id, SUM(days) as total_days from leave_days
            group by member_registration_id) as view_max_id on view_max_id.max_id =lds.id) as lds_view
            on mbr_reg.id = lds_view.member_registration_id 

            left join (SELECT ld.id, ld.member_registration_id as member_registration_id_continue, ld.submission_date as submission_date_continue, 
            ld_view.total_days as total_days_continue, ld_view.total_price as total_price_continue FROM  leave_days ld 
            INNER JOIN 
            (SELECT leave_day_continue_id, sum(days) AS total_days, SUM(price) AS total_price
            FROM (SELECT id,ifnull(leave_day_continue_id, id) AS leave_day_continue_id, days, price FROM leave_days) AS view_1
            GROUP BY leave_day_continue_id) AS ld_view ON ld.id=ld_view.leave_day_continue_id 
            WHERE NOW() BETWEEN ld.submission_date AND DATE_ADD(ld.submission_date, INTERVAL (ifnull(total_days,0)) DAY)) as lds_continue_view
            on mbr_reg.id = lds_continue_view.member_registration_id_continue

            where NOW() BETWEEN mbr_reg.start_date AND DATE_ADD(mbr_reg.start_date, INTERVAL (mbr_reg.days + ifnull(total_days,0)) DAY)"
            . ($cardNumber ? " and mbr.card_number='$cardNumber' " : '') . ($memberRegistrationId ? " and mbr_reg.id='$memberRegistrationId' " : '') .  "
            order by cim_view.updated_at_check_in desc";
        $activeMemberRegistrations = DB::select($sql);

        return $activeMemberRegistrations;
    }

    public static function getPendingList($memberId = "")
    {
        $sql = "SELECT mbr_reg.id, mbr_reg.start_date, mbr_reg.days as member_registration_days,
            mbr_reg.package_price as mr_package_price,  mbr_reg.admin_price as mr_admin_price, mbr_pkg.description,
            mbr.id as member_id, mbr.full_name as member_name, mbr.nickname, mbr.email, mbr.ig, mbr.emergency_contact, mbr.ec_name,
            mbr.address, mbr.member_code, mbr_reg.days,
            mbr.phone_number, mbr.born, mbr.photos, mbr.gender, mbr.id_code_count,
            mbr_pkg.package_name, mbr_pkg.days, mbr_pkg.package_price,
            mtd_pay.name as method_payment_name, usr.full_name as staff_name,
            -- fit_cons.full_name as fc_name, fit_cons.phone_number as fc_phone_number,
            cim_view.current_check_in_members_id, cim_view.check_in_time, cim_view.check_out_time, cim_view.updated_at_check_in,
            lds_view.submission_date, lds_continue_view.submission_date_continue, lds_continue_view.id as lds_continue_id,  lds_view.days as number_of_leave_days, lds_view.total_days,
            'bg-dark' as birthdayCelebrating,
            
            DATE_ADD(mbr_reg.start_date, INTERVAL COALESCE(lds_view.total_days, 0) + mbr_reg.days DAY) as expired_date,
            DATE_ADD(lds_view.submission_date, INTERVAL lds_view.days DAY) as expired_leave_days,

            CASE WHEN NOW() > DATE_ADD(mbr_reg.start_date, INTERVAL mbr_reg.days DAY) THEN 'Over'
                WHEN NOW() BETWEEN mbr_reg.start_date AND DATE_ADD(mbr_reg.start_date, INTERVAL mbr_reg.days DAY) THEN 'Running'
                ELSE 'Not Started'
                END as status,
            CASE when member_registration_id_continue is null then 'No Leave Days' else 'Freeze' end as leave_day_status, 
            CONCAT(YEAR(CURDATE()), ' - ', MONTH(mbr.born), ' - ', DAY(mbr.born)) as member_birthday,
            DATEDIFF(CONCAT(YEAR(CURDATE()), ' - ', MONTH(mbr.born), ' - ', DAY(mbr.born)), CURDATE()) as days_until_birthday
            from members as mbr

            inner join member_registrations mbr_reg on mbr.id = mbr_reg.member_id
            inner join member_packages mbr_pkg on mbr_pkg.id = mbr_reg.member_package_id
            inner join method_payments mtd_pay on mtd_pay.id = mbr_reg.method_payment_id
            inner join users usr on usr.id=mbr_reg.user_id
            -- left join fitness_consultants fit_cons on fit_cons.id = mbr_reg.fc_id

            left join (select cim1.id as current_check_in_members_id, cim1.updated_at as updated_at_check_in, cim1.member_registration_id, cim1.check_in_time, cim1.check_out_time from check_in_members cim1
            inner join (SELECT max(id) as max_id FROM check_in_members group by member_registration_id) as cim2 on cim1.id=cim2.max_id) as cim_view on cim_view.member_registration_id = mbr_reg.id

            left join 
                (select lds.member_registration_id, lds.submission_date, price, days, ifnull(total_days,0) as total_days, max_id from leave_days as lds
                inner join (select max(id) as max_id, SUM(days) as total_days from leave_days
                group by member_registration_id) as view_max_id on view_max_id.max_id =lds.id) as lds_view
            on mbr_reg.id = lds_view.member_registration_id 

            left join 
                (SELECT ld.id, ld.member_registration_id as member_registration_id_continue, ld.submission_date as submission_date_continue, 
                ld_view.total_days as total_days_continue FROM  leave_days ld 
                INNER JOIN 
                (SELECT leave_day_continue_id, sum(days) AS total_days 
                FROM (SELECT id,ifnull(leave_day_continue_id, id) AS leave_day_continue_id,days FROM leave_days) AS view_1
                GROUP BY leave_day_continue_id) AS ld_view ON ld.id=ld_view.leave_day_continue_id 
                WHERE NOW() BETWEEN ld.submission_date AND DATE_ADD(ld.submission_date, INTERVAL (ifnull(total_days,0)) DAY)) as lds_continue_view
            on mbr_reg.id = lds_continue_view.member_registration_id_continue

            where NOW() < (mbr_reg.start_date)" . ($memberId ? " and mbr.id=$memberId " : "")
            .  "order by cim_view.updated_at_check_in desc";
        $activeMemberRegistrations = DB::select($sql);

        return $activeMemberRegistrations;
    }

    public static function getNewPendingListById($cardNumber = "", $memberRegistrationId = "")
    {
        $sql = "SELECT mbr_reg.id, mbr_reg.start_date, mbr_reg.days as member_registration_days,
            mbr_reg.package_price as mr_package_price,  mbr_reg.admin_price as mr_admin_price,
            mbr.id as member_id, mbr.full_name as member_name, mbr.nickname, mbr.email, mbr.ig, mbr.emergency_contact, mbr.ec_name,
            mbr.address, mbr.member_code, mbr_reg.days, mbr.phone_number, mbr.born, mbr.photos, mbr.gender, mbr.id_code_count, mbr.card_number,
            mbr_pkg.package_name, mbr_pkg.days, mbr_pkg.package_price, mbr_pkg.description, mbr_pkg.id AS member_package_id,
            mtd_pay.name as method_payment_name, usr.full_name as staff_name, usr.id as fc_id, usr.full_name AS fc_name, mtd_pay.id AS method_payment_id,
            cim_view.current_check_in_members_id, cim_view.check_in_time, cim_view.check_out_time, cim_view.updated_at_check_in,
            lds_view.submission_date, lds_continue_view.submission_date_continue,  lds_view.days as number_of_leave_days, lds_view.total_days, lds_continue_view.total_price_continue,
            'bg-dark' as birthdayCelebrating,
            
            DATE_ADD(mbr_reg.start_date, INTERVAL COALESCE(lds_view.total_days, 0) + mbr_reg.days DAY) as expired_date,
            DATE_ADD(lds_view.submission_date, INTERVAL lds_view.days DAY) as expired_leave_days,

            CASE WHEN NOW() > DATE_ADD(mbr_reg.start_date, INTERVAL mbr_reg.days DAY) THEN 'Over'
                WHEN NOW() BETWEEN mbr_reg.start_date AND DATE_ADD(mbr_reg.start_date, INTERVAL mbr_reg.days DAY) THEN 'Running'
                ELSE 'Not Started'
                END as status,
            CASE when member_registration_id_continue is null then 'No Leave Days' else 'Freeze' end as leave_day_status, 
            CONCAT(YEAR(CURDATE()), ' - ', MONTH(mbr.born), ' - ', DAY(mbr.born)) as member_birthday,
            DATEDIFF(CONCAT(YEAR(CURDATE()), ' - ', MONTH(mbr.born), ' - ', DAY(mbr.born)), CURDATE()) as days_until_birthday
            from members as mbr

            inner join member_registrations mbr_reg on mbr.id = mbr_reg.member_id
            inner join member_packages mbr_pkg on mbr_pkg.id = mbr_reg.member_package_id
            inner join method_payments mtd_pay on mtd_pay.id = mbr_reg.method_payment_id
            inner join users usr on usr.id=mbr_reg.user_id
            -- WHERE usr.role = 'FC'

            left join (select cim1.id as current_check_in_members_id, cim1.updated_at as updated_at_check_in, cim1.member_registration_id, cim1.check_in_time, cim1.check_out_time from check_in_members cim1
            inner join (SELECT max(id) as max_id FROM check_in_members group by member_registration_id) as cim2 on cim1.id=cim2.max_id) as cim_view on cim_view.member_registration_id = mbr_reg.id

            left join (select lds.member_registration_id, lds.submission_date, price, days, ifnull(total_days,0) as total_days, max_id from leave_days as lds
            inner join (select max(id) as max_id, SUM(days) as total_days from leave_days
            group by member_registration_id) as view_max_id on view_max_id.max_id =lds.id) as lds_view
            on mbr_reg.id = lds_view.member_registration_id 

            left join (SELECT ld.id, ld.member_registration_id as member_registration_id_continue, ld.submission_date as submission_date_continue, 
            ld_view.total_days as total_days_continue, ld_view.total_price as total_price_continue FROM  leave_days ld 
            INNER JOIN 
            (SELECT leave_day_continue_id, sum(days) AS total_days, SUM(price) AS total_price
            FROM (SELECT id,ifnull(leave_day_continue_id, id) AS leave_day_continue_id, days, price FROM leave_days) AS view_1
            GROUP BY leave_day_continue_id) AS ld_view ON ld.id=ld_view.leave_day_continue_id 
            WHERE NOW() BETWEEN ld.submission_date AND DATE_ADD(ld.submission_date, INTERVAL (ifnull(total_days,0)) DAY)) as lds_continue_view
            on mbr_reg.id = lds_continue_view.member_registration_id_continue

            where NOW() < (mbr_reg.start_date)"
            . ($cardNumber ? " and mbr.card_number='$cardNumber' " : '') . ($memberRegistrationId ? " and mbr_reg.id='$memberRegistrationId' " : '') .  "
            order by cim_view.updated_at_check_in desc";
        $activeMemberRegistrations = DB::select($sql);

        return $activeMemberRegistrations;
    }

    // public static function getCutiAgreementOld1Agustus($card_number = "", $id = "")
    // {
    //     $sql = "SELECT mbr_reg.id, mbr_reg.start_date, mbr_reg.days as member_registration_days,
    //         mbr_reg.package_price as mr_package_price,  mbr_reg.admin_price as mr_admin_price,
    //         mbr.id as member_id, mbr.full_name as member_name, mbr.nickname, mbr.email, mbr.ig, mbr.emergency_contact, mbr.ec_name,
    //         mbr.address, mbr.member_code, mbr_reg.days, mbr.phone_number, mbr.born, mbr.photos, mbr.gender, mbr.id_code_count, mbr.card_number,
    //         mbr_pkg.package_name, mbr_pkg.days, mbr_pkg.package_price, mbr_pkg.description, mbr_pkg.id AS member_package_id,
    //         mtd_pay.name as method_payment_name, usr.full_name as staff_name, usr.id as fc_id, usr.full_name AS fc_name, mtd_pay.id AS method_payment_id,
    //         cim_view.current_check_in_members_id, cim_view.check_in_time, cim_view.check_out_time, cim_view.updated_at_check_in,
    //         lds_view.submission_date, lds_continue_view.submission_date_continue,  lds_view.days as number_of_leave_days, lds_view.total_days, lds_continue_view.total_price_continue,
    //         'bg-dark' as birthdayCelebrating,

    //         DATE_ADD(mbr_reg.start_date, INTERVAL COALESCE(lds_view.total_days, 0) + mbr_reg.days DAY) as expired_date,
    //         DATE_ADD(lds_view.submission_date, INTERVAL lds_view.days DAY) as expired_leave_days,

    //         CASE WHEN NOW() > DATE_ADD(mbr_reg.start_date, INTERVAL mbr_reg.days DAY) THEN 'Over'
    //             WHEN NOW() BETWEEN mbr_reg.start_date AND DATE_ADD(mbr_reg.start_date, INTERVAL mbr_reg.days DAY) THEN 'Running'
    //             ELSE 'Not Started'
    //             END as status,
    //         CASE when member_registration_id_continue is null then 'No Leave Days' else 'Freeze' end as leave_day_status,
    //         CONCAT(YEAR(CURDATE()), ' - ', MONTH(mbr.born), ' - ', DAY(mbr.born)) as member_birthday,
    //         DATEDIFF(CONCAT(YEAR(CURDATE()), ' - ', MONTH(mbr.born), ' - ', DAY(mbr.born)), CURDATE()) as days_until_birthday
    //         from members as mbr

    //         inner join member_registrations mbr_reg on mbr.id = mbr_reg.member_id
    //         inner join member_packages mbr_pkg on mbr_pkg.id = mbr_reg.member_package_id
    //         inner join method_payments mtd_pay on mtd_pay.id = mbr_reg.method_payment_id
    //         inner join users usr on usr.id=mbr_reg.fc_id

    //         left join (select cim1.id as current_check_in_members_id, cim1.updated_at as updated_at_check_in, cim1.member_registration_id, cim1.check_in_time, cim1.check_out_time from check_in_members cim1
    //         inner join (SELECT max(id) as max_id FROM check_in_members group by member_registration_id) as cim2 on cim1.id=cim2.max_id) as cim_view on cim_view.member_registration_id = mbr_reg.id

    //         left join (select lds.member_registration_id, lds.submission_date, price, days, ifnull(total_days,0) as total_days, max_id from leave_days as lds
    //         inner join (select max(id) as max_id, SUM(days) as total_days from leave_days
    //         group by member_registration_id) as view_max_id on view_max_id.max_id =lds.id) as lds_view
    //         on mbr_reg.id = lds_view.member_registration_id

    //         left join (SELECT ld.id, ld.member_registration_id as member_registration_id_continue, ld.submission_date as submission_date_continue,
    //         ld_view.total_days as total_days_continue, ld_view.total_price as total_price_continue FROM  leave_days ld
    //         INNER JOIN
    //         (SELECT leave_day_continue_id, sum(days) AS total_days, SUM(price) AS total_price
    //         FROM (SELECT id,ifnull(leave_day_continue_id, id) AS leave_day_continue_id, days, price FROM leave_days) AS view_1
    //         GROUP BY leave_day_continue_id) AS ld_view ON ld.id=ld_view.leave_day_continue_id
    //         WHERE NOW() BETWEEN ld.submission_date AND DATE_ADD(ld.submission_date, INTERVAL (ifnull(total_days,0)) DAY)) as lds_continue_view
    //         on mbr_reg.id = lds_continue_view.member_registration_id_continue

    //         where NOW() BETWEEN mbr_reg.start_date AND DATE_ADD(mbr_reg.start_date, INTERVAL (mbr_reg.days + ifnull(total_days,0)) DAY) AND usr.role = 'FC'"
    //         . ($card_number ? " and mbr.card_number='$card_number' " : '') . ($id ? " and mbr_reg.id='$id' " : '') .  "
    //         order by cim_view.updated_at_check_in desc";
    //     $activeMemberRegistrations = DB::select($sql);

    //     return $activeMemberRegistrations;
    // }

    public static function getCutiAgreement($cardNumber = "", $memberRegistrationId = "")
    {
        $sql = "SELECT mbr_reg.id, mbr_reg.start_date, mbr_reg.days as member_registration_days,
            mbr_reg.package_price as mr_package_price,  mbr_reg.admin_price as mr_admin_price,
            mbr.id as member_id, mbr.full_name as member_name, mbr.nickname, mbr.email, mbr.ig, mbr.emergency_contact, mbr.ec_name,
            mbr.address, mbr.member_code, mbr_reg.days, mbr.phone_number, mbr.born, mbr.photos, mbr.gender, mbr.id_code_count, mbr.card_number,
            mbr_pkg.package_name, mbr_pkg.days, mbr_pkg.package_price, mbr_pkg.description, mbr_pkg.id AS member_package_id,
            mtd_pay.name as method_payment_name, usr.full_name as staff_name, usr.id as fc_id, usr.full_name AS fc_name, mtd_pay.id AS method_payment_id,
            cim_view.current_check_in_members_id, cim_view.check_in_time, cim_view.check_out_time, cim_view.updated_at_check_in,
            lds_view.submission_date, lds_continue_view.submission_date_continue,  lds_view.days as number_of_leave_days, lds_view.total_days, lds_continue_view.total_price_continue,
            'bg-dark' as birthdayCelebrating,
            
            DATE_ADD(mbr_reg.start_date, INTERVAL COALESCE(lds_view.total_days, 0) + mbr_reg.days DAY) as expired_date,
            DATE_ADD(lds_view.submission_date, INTERVAL lds_view.days DAY) as expired_leave_days,

            CASE WHEN NOW() > DATE_ADD(mbr_reg.start_date, INTERVAL mbr_reg.days DAY) THEN 'Over'
                WHEN NOW() BETWEEN mbr_reg.start_date AND DATE_ADD(mbr_reg.start_date, INTERVAL mbr_reg.days DAY) THEN 'Running'
                ELSE 'Not Started'
                END as status,
            CASE when member_registration_id_continue is null then 'No Leave Days' else 'Freeze' end as leave_day_status, 
            CONCAT(YEAR(CURDATE()), ' - ', MONTH(mbr.born), ' - ', DAY(mbr.born)) as member_birthday,
            DATEDIFF(CONCAT(YEAR(CURDATE()), ' - ', MONTH(mbr.born), ' - ', DAY(mbr.born)), CURDATE()) as days_until_birthday
            from members as mbr

            inner join member_registrations mbr_reg on mbr.id = mbr_reg.member_id
            inner join member_packages mbr_pkg on mbr_pkg.id = mbr_reg.member_package_id
            inner join method_payments mtd_pay on mtd_pay.id = mbr_reg.method_payment_id
            inner join users usr on usr.id=mbr_reg.user_id
            -- WHERE usr.role = 'FC'

            left join (select cim1.id as current_check_in_members_id, cim1.updated_at as updated_at_check_in, cim1.member_registration_id, cim1.check_in_time, cim1.check_out_time from check_in_members cim1
            inner join (SELECT max(id) as max_id FROM check_in_members group by member_registration_id) as cim2 on cim1.id=cim2.max_id) as cim_view on cim_view.member_registration_id = mbr_reg.id

            left join (select lds.member_registration_id, lds.submission_date, price, days, ifnull(total_days,0) as total_days, max_id from leave_days as lds
            inner join (select max(id) as max_id, SUM(days) as total_days from leave_days
            group by member_registration_id) as view_max_id on view_max_id.max_id =lds.id) as lds_view
            on mbr_reg.id = lds_view.member_registration_id 

            left join (SELECT ld.id, ld.member_registration_id as member_registration_id_continue, ld.submission_date as submission_date_continue, 
            ld_view.total_days as total_days_continue, ld_view.total_price as total_price_continue FROM  leave_days ld 
            INNER JOIN 
            (SELECT leave_day_continue_id, sum(days) AS total_days, SUM(price) AS total_price
            FROM (SELECT id,ifnull(leave_day_continue_id, id) AS leave_day_continue_id, days, price FROM leave_days) AS view_1
            GROUP BY leave_day_continue_id) AS ld_view ON ld.id=ld_view.leave_day_continue_id 
            WHERE NOW() BETWEEN ld.submission_date AND DATE_ADD(ld.submission_date, INTERVAL (ifnull(total_days,0)) DAY)) as lds_continue_view
            on mbr_reg.id = lds_continue_view.member_registration_id_continue

            where NOW() BETWEEN mbr_reg.start_date AND DATE_ADD(mbr_reg.start_date, INTERVAL (mbr_reg.days + ifnull(total_days,0)) DAY)"
            . ($cardNumber ? " and mbr.card_number='$cardNumber' " : '') . ($memberRegistrationId ? " and mbr_reg.id='$memberRegistrationId' " : '') .  "
            order by cim_view.updated_at_check_in desc";
        $activeMemberRegistrations = DB::select($sql);

        return $activeMemberRegistrations;
    }

    public static function history($card_number = "", $member_id = "", $fromDate, $toDate)
    {
        $sql = "SELECT mbr_reg.id, mbr_reg.start_date, mbr_reg.days as member_registration_days,
            mbr_reg.package_price as mr_package_price,  mbr_reg.admin_price as mr_admin_price,
            mbr.id as member_id, mbr.full_name as member_name, mbr.nickname, mbr.email, mbr.ig, mbr.emergency_contact, mbr.ec_name,
            mbr.address, mbr.member_code, mbr_reg.days,
            mbr.phone_number, mbr.born, mbr.photos, mbr.gender, mbr.id_code_count,
            mbr_pkg.package_name, mbr_pkg.days, mbr_pkg.package_price,
            mtd_pay.name as method_payment_name, usr.full_name as staff_name,
            -- fit_cons.full_name as fc_name, fit_cons.phone_number as fc_phone_number,
            cim_view.current_check_in_members_id, cim_view.check_in_time, cim_view.check_out_time, cim_view.updated_at_check_in,
            lds_view.submission_date, lds_continue_view.submission_date_continue, lds_continue_view.id as lds_continue_id,  lds_view.days as number_of_leave_days, lds_view.total_days,
            'bg-dark' as birthdayCelebrating,
            
            DATE_ADD(mbr_reg.start_date, INTERVAL COALESCE(lds_view.total_days, 0) + mbr_reg.days DAY) as expired_date,
            DATE_ADD(lds_view.submission_date, INTERVAL lds_view.days DAY) as expired_leave_days,

            -- Di bagian sini sepertinya harus tambah hari cuti juga, jadi tambah  COALESCE(lds_view.total_days, 0)
            CASE WHEN NOW() > DATE_ADD(mbr_reg.start_date, INTERVAL mbr_reg.days DAY) THEN 'Over'
                WHEN NOW() BETWEEN mbr_reg.start_date AND DATE_ADD(mbr_reg.start_date, INTERVAL mbr_reg.days DAY) THEN 'Running'
                ELSE 'Not Started'
                END as status,
            CASE when member_registration_id_continue is null then 'No Leave Days' else 'Freeze' end as leave_day_status,

            CONCAT(YEAR(CURDATE()), ' - ', MONTH(mbr.born), ' - ', DAY(mbr.born)) as member_birthday,
            DATEDIFF(CONCAT(YEAR(CURDATE()), ' - ', MONTH(mbr.born), ' - ', DAY(mbr.born)), CURDATE()) as days_until_birthday
            from members as mbr

            inner join member_registrations mbr_reg on mbr.id = mbr_reg.member_id
            inner join member_packages mbr_pkg on mbr_pkg.id = mbr_reg.member_package_id
            inner join method_payments mtd_pay on mtd_pay.id = mbr_reg.method_payment_id
            inner join users usr on usr.id=mbr_reg.user_id
            -- left join fitness_consultants fit_cons on fit_cons.id = mbr_reg.fc_id

            left join (select cim1.id as current_check_in_members_id, cim1.updated_at as updated_at_check_in, cim1.member_registration_id, cim1.check_in_time, cim1.check_out_time from check_in_members cim1
            inner join (SELECT max(id) as max_id FROM check_in_members group by member_registration_id) as cim2 on cim1.id=cim2.max_id) as cim_view on cim_view.member_registration_id = mbr_reg.id

            left join 
                (select lds.member_registration_id, lds.submission_date, price, days, ifnull(total_days,0) as total_days, max_id from leave_days as lds
                inner join (select max(id) as max_id, SUM(days) as total_days from leave_days
                group by member_registration_id) as view_max_id on view_max_id.max_id =lds.id) as lds_view
            on mbr_reg.id = lds_view.member_registration_id 

            left join 
                (SELECT ld.id, ld.member_registration_id as member_registration_id_continue, ld.submission_date as submission_date_continue, 
                ld_view.total_days as total_days_continue FROM  leave_days ld 
                INNER JOIN 
                (SELECT leave_day_continue_id, sum(days) AS total_days 
                FROM (SELECT id,ifnull(leave_day_continue_id, id) AS leave_day_continue_id,days FROM leave_days) AS view_1
                GROUP BY leave_day_continue_id) AS ld_view ON ld.id=ld_view.leave_day_continue_id 
                WHERE NOW() BETWEEN ld.submission_date AND DATE_ADD(ld.submission_date, INTERVAL (ifnull(total_days,0)) DAY)) as lds_continue_view
            on mbr_reg.id = lds_continue_view.member_registration_id_continue
            where mbr_reg.start_date >= '$fromDate' AND mbr_reg.start_date <= '$toDate'
            "
             . ($member_id ? " and mbr.id='$member_id' " : '') .  "
            order by cim_view.updated_at_check_in desc";
        $activeMemberRegistrations = DB::select($sql);

        return $activeMemberRegistrations;
    }

    public static function historyById($card_number = "", $id = "")
    {
        $sql = "SELECT mbr_reg.id, mbr_reg.start_date, mbr_reg.days as member_registration_days,
            mbr_reg.package_price as mr_package_price,  mbr_reg.admin_price as mr_admin_price,
            mbr.id as member_id, mbr.full_name as member_name, mbr.nickname, mbr.email, mbr.ig, mbr.emergency_contact, mbr.ec_name,
            mbr.address, mbr.member_code, mbr_reg.days, mbr_reg.description,
            mbr.phone_number, mbr.born, mbr.photos, mbr.gender, mbr.id_code_count,
            mbr_pkg.package_name, mbr_pkg.days, mbr_pkg.package_price,
            mtd_pay.name as method_payment_name, usr.full_name as staff_name,
            -- fit_cons.full_name as fc_name, fit_cons.phone_number as fc_phone_number,
            cim_view.current_check_in_members_id, cim_view.check_in_time, cim_view.check_out_time, cim_view.updated_at_check_in,
            lds_view.submission_date, lds_continue_view.submission_date_continue, lds_continue_view.id as lds_continue_id,  lds_view.days as number_of_leave_days, lds_view.total_days,
            'bg-dark' as birthdayCelebrating,
            
            DATE_ADD(mbr_reg.start_date, INTERVAL COALESCE(lds_view.total_days, 0) + mbr_reg.days DAY) as expired_date,
            DATE_ADD(lds_view.submission_date, INTERVAL lds_view.days DAY) as expired_leave_days,

            -- Di bagian sini sepertinya harus tambah hari cuti juga, jadi tambah  COALESCE(lds_view.total_days, 0)
            CASE WHEN NOW() > DATE_ADD(mbr_reg.start_date, INTERVAL mbr_reg.days DAY) THEN 'Over'
                WHEN NOW() BETWEEN mbr_reg.start_date AND DATE_ADD(mbr_reg.start_date, INTERVAL mbr_reg.days DAY) THEN 'Running'
                ELSE 'Not Started'
                END as status,
            CASE when member_registration_id_continue is null then 'No Leave Days' else 'Freeze' end as leave_day_status,

            CONCAT(YEAR(CURDATE()), ' - ', MONTH(mbr.born), ' - ', DAY(mbr.born)) as member_birthday,
            DATEDIFF(CONCAT(YEAR(CURDATE()), ' - ', MONTH(mbr.born), ' - ', DAY(mbr.born)), CURDATE()) as days_until_birthday
            from members as mbr

            inner join member_registrations mbr_reg on mbr.id = mbr_reg.member_id
            inner join member_packages mbr_pkg on mbr_pkg.id = mbr_reg.member_package_id
            inner join method_payments mtd_pay on mtd_pay.id = mbr_reg.method_payment_id
            inner join users usr on usr.id=mbr_reg.user_id
            -- left join fitness_consultants fit_cons on fit_cons.id = mbr_reg.fc_id

            left join (select cim1.id as current_check_in_members_id, cim1.updated_at as updated_at_check_in, cim1.member_registration_id, cim1.check_in_time, cim1.check_out_time from check_in_members cim1
            inner join (SELECT max(id) as max_id FROM check_in_members group by member_registration_id) as cim2 on cim1.id=cim2.max_id) as cim_view on cim_view.member_registration_id = mbr_reg.id

            left join 
                (select lds.member_registration_id, lds.submission_date, price, days, ifnull(total_days,0) as total_days, max_id from leave_days as lds
                inner join (select max(id) as max_id, SUM(days) as total_days from leave_days
                group by member_registration_id) as view_max_id on view_max_id.max_id =lds.id) as lds_view
            on mbr_reg.id = lds_view.member_registration_id 

            left join 
                (SELECT ld.id, ld.member_registration_id as member_registration_id_continue, ld.submission_date as submission_date_continue, 
                ld_view.total_days as total_days_continue FROM  leave_days ld 
                INNER JOIN 
                (SELECT leave_day_continue_id, sum(days) AS total_days 
                FROM (SELECT id,ifnull(leave_day_continue_id, id) AS leave_day_continue_id,days FROM leave_days) AS view_1
                GROUP BY leave_day_continue_id) AS ld_view ON ld.id=ld_view.leave_day_continue_id 
                WHERE NOW() BETWEEN ld.submission_date AND DATE_ADD(ld.submission_date, INTERVAL (ifnull(total_days,0)) DAY)) as lds_continue_view
            on mbr_reg.id = lds_continue_view.member_registration_id_continue 
            where mbr_reg.id = '$id'" .
            " order by cim_view.updated_at_check_in desc";
        $activeMemberRegistrations = DB::select($sql);

        return $activeMemberRegistrations;
    }
}
