<?php

namespace App\Models\Trainer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PtLeaveDay extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'trainer_session_id',
        'submission_date',
        'price',
        'days'
    ];

    public function memberRegistrations()
    {
        return $this->belongsTo(TrainerSession::class, 'trainer_session_id', 'id');
    }

    public static function summaryQuery(): Builder
    {
        return static::query()
            ->selectRaw('trainer_session_id, SUM(days) AS total_days, SUM(price) AS total_price')
            ->selectRaw('MAX(submission_date) AS latest_submission_date')
            ->selectRaw('MAX(DATE_ADD(submission_date, INTERVAL days DAY)) AS latest_freeze_end')
            ->selectRaw('MAX(CASE WHEN NOW() BETWEEN submission_date AND DATE_ADD(submission_date, INTERVAL days DAY) THEN 1 ELSE 0 END) AS is_currently_frozen')
            ->groupBy('trainer_session_id');
    }
}
