<?php

namespace App\Services\Api\V1\Activity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class ActivityLoggerService
{
    protected string $logName = 'default';
    protected ?Model $performedOn = null;
    protected array $properties = [];
    protected ?Model $causedBy = null;
    protected ?string $event = null;

    public static function log(): self
    {
        return new static();
    }

    public function useLog(string $logName): self
    {
        $this->logName = $logName;
        return $this;
    }

    public function performedOn(Model $model): self
    {
        $this->performedOn = $model;
        return $this;
    }

    public function withProperties(array $properties): self
    {
        $this->properties = $properties;
        return $this;
    }
    public function causedBy(?Model $user = null): self
    {
        $this->causedBy = $user ?? Auth::user();
        return $this;
    }

    public function event(string $event): self
    {
        $this->event = $event;
        return $this;
    }

    public function logMessage(string $message): Activity
    {
        $logger = activity($this->logName)
            ->withProperties($this->properties);

        if ($this->causedBy) {
            $logger->causedBy($this->causedBy);
        }

        if ($this->performedOn) {
            $logger->performedOn($this->performedOn);
        }

        if ($this->event) {
            $logger->event($this->event);
        }

        $activity = $logger->log($message);

        // ✅ Manually set user_id for convenience
        if ($this->causedBy && $activity instanceof Activity) {
            $activity->user_id = $this->causedBy->id;
            $activity->save();
        }

        return $activity;
    }
}
