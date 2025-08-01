<?php

namespace App\Rules;

use App\Models\Meeting;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\InvokableRule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class NoTimeConflict implements InvokableRule, DataAwareRule
{
    /**
     * All of the data under validation.
     *
     * @var array<string, mixed>
     */
    protected $data = [];

    /**
     * Set the data under validation.
     *
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function __invoke(string $attribute, mixed $value, Closure $fail): void
    {
        $startTime = Carbon::parse($value)->utc();
        $duration = $this->data['duration'] ?? 0;
        $endTime = $startTime->copy()->addMinutes($duration);
        $type = $this->data['type'] ?? null;
        $locationId = $this->data['location_id'] ?? null;
        $zoomMeetingId = $this->data['zoom_meeting_id'] ?? null;

        // Location conflict check (for offline and hybrid meetings)
        if (in_array($type, ['offline', 'hybrid']) && $locationId) {
            if ($this->isLocationConflict($locationId, $startTime, $endTime)) {
                $fail('The selected time slot conflicts with another meeting at the same location.');
            }
        }

        // Zoom conflict check (for online and hybrid meetings)
        if (in_array($type, ['online', 'hybrid']) && $zoomMeetingId) {
            if ($this->isZoomConflict($zoomMeetingId, $startTime, $endTime)) {
                $fail('The selected time slot conflicts with another meeting in the same Zoom account.');
            }
        }
    }

    /**
     * Get the database-specific expression for calculating a meeting's end time.
     */
    private function getEndTimeExpression(): string
    {
        $driver = DB::connection()->getDriverName();

        switch ($driver) {
            case 'sqlite':
                return "datetime(start_time, '+' || duration || ' minutes')";
            case 'mysql':
                return 'DATE_ADD(start_time, INTERVAL duration MINUTE)';
            case 'pgsql':
                return "(start_time + (duration * interval '1 minute'))";
            default:
                // Fallback for other SQL dialects, may need adjustment
                return 'DATE_ADD(start_time, INTERVAL duration MINUTE)';
        }
    }

    /**
     * Check for a location conflict.
     */
    private function isLocationConflict(int $locationId, Carbon $startTime, Carbon $endTime): bool
    {
        $endTimeExpression = $this->getEndTimeExpression();

        return Meeting::where('location_id', $locationId)
            ->where(function ($query) use ($startTime, $endTime, $endTimeExpression) {
                $query->where('start_time', '<', $endTime)
                      ->where(DB::raw($endTimeExpression), '>', $startTime);
            })
            ->exists();
    }

    /**
     * Check for a Zoom meeting conflict.
     */
    private function isZoomConflict(int $zoomMeetingId, Carbon $startTime, Carbon $endTime): bool
    {
        $endTimeExpression = $this->getEndTimeExpression();

        return Meeting::whereHas('zoomMeeting', function ($query) use ($zoomMeetingId) {
            $query->where('setting_id', $zoomMeetingId);
        })
            ->where(function ($query) use ($startTime, $endTime, $endTimeExpression) {
                $query->where('start_time', '<', $endTime)
                    ->where(DB::raw($endTimeExpression), '>', $startTime);
            })
            ->exists();
    }
}