<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\MeetingLocation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatisticController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $stats = Cache::remember('statistics_dashboard', 600, function () {
            $totalMeetings = Meeting::count();
            $averageDuration = Meeting::avg('duration');
            $meetingsThisMonth = Meeting::where('start_time', '>=', now()->startOfMonth())->count();

            $meetingsByType = Meeting::select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->get();

            $topOrganizers = User::withCount('organizedMeetings')
                ->orderBy('organized_meetings_count', 'desc')
                ->limit(5)
                ->get()
                ->map(fn ($user) => [
                    'name' => $user->name,
                    'meetings_count' => $user->organized_meetings_count,
                ]);

            $topLocations = MeetingLocation::withCount('meetings')
                ->orderBy('meetings_count', 'desc')
                ->limit(5)
                ->get()
                ->map(fn ($location) => [
                    'name' => $location->name,
                    'meetings_count' => $location->meetings_count,
                ]);

            return [
                'overview' => [
                    'total_meetings' => $totalMeetings,
                    'average_duration_minutes' => round($averageDuration),
                    'meetings_this_month' => $meetingsThisMonth,
                ],
                'meeting_trends' => [
                    'by_type' => $meetingsByType,
                ],
                'leaderboards' => [
                    'top_organizers' => $topOrganizers,
                    'top_locations' => $topLocations,
                ],
            ];
        });

        return response()->json(['data' => $stats]);
    }
}