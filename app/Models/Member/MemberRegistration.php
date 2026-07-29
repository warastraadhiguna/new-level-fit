<?php

namespace App\Models\Member;

// use Alfa6661\AutoNumber\AutoNumberTrait;
use App\Models\MethodPayment;
use App\Models\Staff\FitnessConsultant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
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
        'payment_deadline',
        'is_installment_plan',
        'installment_monthly_amount',
        'installment_status',
        'installment_deposit_status',
        'installment_grace_days',
        'installment_cancel_days',
        'installment_cancelled_at',
        'start_date',
        'days',
        'old_days',
        'method_payment_id',
        'description',
        'fc_id',
        'user_id',
    ];

    protected $casts = [
        'payment_deadline' => 'integer',
        'is_installment_plan' => 'boolean',
        'installment_cancelled_at' => 'datetime',
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

    public static function getActiveList($card_number = "", $member_id = "", $isUnpaidMember = "no", $branchStoreId = "")
    {
        $defaultSql = "NOW() BETWEEN mbr_reg.start_date AND DATE_ADD(mbr_reg.start_date, INTERVAL (mbr_reg.days + ifnull(total_days,0)) DAY) AND mbr_reg.days > 1";
        //ini karena nando sudah ngaco. jadi aku juga mau pakai untuk yang belum bayar. tapi masalahnya ternyata ini untuk
        //yang aktif, padahal yang belum bayar juga mungkin pending. sehingga aku akali di tanggalnya

        $sql = "SELECT mbr_reg.id, mbr_reg.start_date, mbr_reg.created_at as registration_created_at,
            mbr_reg.payment_deadline, mbr_reg.is_installment_plan, mbr_reg.installment_status,
            CASE WHEN mbr_reg.payment_deadline > 0
                THEN DATE_ADD(mbr_reg.created_at, INTERVAL mbr_reg.payment_deadline DAY)
                ELSE NULL
            END as payment_deadline_date,
            mbr_reg.days as member_registration_days,
            mbr_reg.package_price as mr_package_price,  mbr_reg.admin_price as mr_admin_price, bs.id as 'branch_store_id', bs.name as 'branch_store_name',
            mbr.id as member_id, mbr.full_name as member_name, mbr.nickname, mbr.email, mbr.ig, mbr.emergency_contact, mbr.ec_name,
            mbr.address, mbr.member_code, mbr_reg.days,
            mbr.phone_number, mbr.born, mbr.photos, mbr.gender, mbr.id_code_count,
            mbr_pkg.package_name, mbr_pkg.days, mbr_pkg.package_price, mbr_pkg.is_all_club,mbr_pkg.branch_store_id as member_package_branch_store_id,
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

            where mbr_reg.id > 0 AND "
            //--maaf ini akal2an untuk kasus nando di atas
            . ($isUnpaidMember == "yes"? " ((mbr_reg.is_installment_plan = 1 AND COALESCE(mbr_reg.installment_status, 'pending') NOT IN ('active','completed')) OR (mbr_reg.is_installment_plan = 0 AND IFNULL((SELECT SUM(value) FROM member_registration_payments mrp WHERE mbr_reg.id = mrp.member_registration_id), 0) < (mbr_reg.package_price + mbr_reg.admin_price)))" :
            ($isUnpaidMember == "no"? " $defaultSql AND ((mbr_reg.is_installment_plan = 1 AND mbr_reg.installment_status IN ('active','completed')) OR (mbr_reg.is_installment_plan = 0 AND IFNULL((SELECT SUM(value) FROM member_registration_payments mrp WHERE mbr_reg.id = mrp.member_registration_id), 0) >= (mbr_reg.package_price + mbr_reg.admin_price)))" :  $defaultSql))

            . ($card_number ? " and mbr.card_number='$card_number' " : '')
            . ($member_id ? " and mbr.id='$member_id' " : '')
            . ($branchStoreId ? " and mbr.branch_store_id=" . (int) $branchStoreId . " " : '') .  "
            order by cim_view.updated_at_check_in desc";
        $activeMemberRegistrations = DB::select($sql);

        return $activeMemberRegistrations;
    }

    public function payments()
    {
        return $this->hasMany(MemberRegistrationPayment::class);
    }

    public function installments()
    {
        return $this->hasMany(MemberRegistrationInstallment::class);
    }

    public static function getActiveListPaginated($search = "", $perPage = 10, $sort = "updated_at_check_in", $direction = "desc", $branchStoreId = "")
    {
        $direction = strtolower($direction) == "asc" ? "asc" : "desc";
        $sortableColumns = [
            "member_name" => "member_name",
            "branch_store_name" => "branch_store_name",
            "check_in_time" => "updated_at_check_in",
            "start_date" => "start_date",
            "expired_date" => "expired_date",
            "payment_summary" => "payment_summary",
            "leave_day_status" => "leave_day_status",
            "staff_name" => "staff_name",
            "updated_at_check_in" => "updated_at_check_in",
        ];
        $sortColumn = $sortableColumns[$sort] ?? "updated_at_check_in";

        $baseSql = "SELECT mbr_reg.id, mbr_reg.start_date, mbr_reg.days as member_registration_days, mbr_reg.is_installment_plan, mbr_reg.installment_status,
            mbr_reg.package_price as mr_package_price,  mbr_reg.admin_price as mr_admin_price, bs.id as branch_store_id, bs.name as branch_store_name,
            mbr.id as member_id, mbr.full_name as member_name, mbr.nickname, mbr.email, mbr.ig, mbr.emergency_contact, mbr.ec_name,
            mbr.address, mbr.member_code, mbr_reg.days,
            mbr.phone_number, mbr.born, mbr.photos, mbr.gender, mbr.id_code_count,
            mbr_pkg.package_name, mbr_pkg.days as member_package_days, mbr_pkg.package_price, mbr_pkg.is_all_club,mbr_pkg.branch_store_id as member_package_branch_store_id,
            mtd_pay.name as method_payment_name, usr.full_name as staff_name,
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
            where mbr_reg.id > 0
            AND NOW() BETWEEN mbr_reg.start_date AND DATE_ADD(mbr_reg.start_date, INTERVAL (mbr_reg.days + ifnull(total_days,0)) DAY)
            AND mbr_reg.days > 1
            AND ((mbr_reg.is_installment_plan = 1 AND mbr_reg.installment_status IN ('active','completed'))
                OR (mbr_reg.is_installment_plan = 0 AND IFNULL((SELECT SUM(value) FROM member_registration_payments mrp WHERE mbr_reg.id = mrp.member_registration_id), 0) >= (mbr_reg.package_price + mbr_reg.admin_price)))"
            . ($branchStoreId ? " and mbr.branch_store_id=" . (int) $branchStoreId . " " : '');

        $query = DB::query()->fromSub($baseSql, "active_members");

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where("member_name", "like", "%{$search}%")
                    ->orWhere("nickname", "like", "%{$search}%")
                    ->orWhere("member_code", "like", "%{$search}%")
                    ->orWhere("phone_number", "like", "%{$search}%")
                    ->orWhere("package_name", "like", "%{$search}%")
                    ->orWhere("branch_store_name", "like", "%{$search}%")
                    ->orWhere("staff_name", "like", "%{$search}%");
            });
        }

        return $query
            ->orderBy($sortColumn, $direction)
            ->paginate($perPage);
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

    public static function getNonExpiredList()
    {
        $sql = "SELECT
                m.id,
                m.full_name,
                m.member_code,
                m.phone_number
            FROM members m
            JOIN (
                SELECT r.member_id, r.start_date, r.days
                FROM member_registrations r
                JOIN (
                    SELECT
                        member_id,
                        MAX(start_date) AS max_start_date
                    FROM member_registrations
                    GROUP BY member_id
                ) latest ON latest.member_id = r.member_id
                        AND latest.max_start_date = r.start_date
            ) last_reg ON last_reg.member_id = m.id
            WHERE DATE_ADD(last_reg.start_date, INTERVAL last_reg.days DAY) >= NOW() order by m.full_name";
        
        return DB::select($sql);
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

    public function scopeExpiredRegistrations(Builder $query, $cardNumber = "")
    {
        $result = $query
            ->from('members as a')
            ->select(
                'b.id as mr_id',
                'a.id',
                'a.full_name',
                'a.member_code',
                'a.photos',
                'b.days',
                'b.start_date',
                'max_end_date',
                'total_package_price',
                'total_admin_price',
                'c.registered_member_id'
            )
            ->join(DB::raw('(
                select 
                    a.id as id_max,
                    b.id,
                    b.days,
                    b.start_date,
                    max(DATE_ADD(b.start_date, INTERVAL b.days DAY)) as max_end_date,
                    sum(package_price) as total_package_price,
                    DATE_ADD(b.start_date, INTERVAL b.days DAY) as expired_date_date,
                    sum(admin_price) as total_admin_price
                from members a
                inner join member_registrations b on a.id = b.member_id
                where DATE_ADD(b.start_date, INTERVAL b.days DAY) < now()
                group by a.id, b.id, b.days, b.start_date
            ) as b'), function ($join) {
                $join->on('a.id', '=', 'b.id_max');
            })
            ->leftJoin(DB::raw('(
                select distinct member_id as registered_member_id
                from member_registrations
                where DATE_ADD(start_date, INTERVAL days DAY) >= now()
            ) as c'), function ($join) {
                $join->on('a.id', '=', 'c.registered_member_id');
            })
            ->whereNull('c.registered_member_id')
            ->where('b.days', '>', 1);
        return $cardNumber? $result->where('a.card_number', $cardNumber) : $result;
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
