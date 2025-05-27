<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class BankTransaction extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'bank_transactions';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['user_id', 'order_id', 'amount', 'status', 'due_date'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Spatie Activitylog config
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('bank_transaction')
            ->setDescriptionForEvent(fn(string $eventName) => "Bank transaction was {$eventName}");
    }
}

