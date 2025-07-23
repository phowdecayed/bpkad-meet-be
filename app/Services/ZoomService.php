<?php

namespace App\Services;

use App\Models\ZoomMeeting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class ZoomService
{
    protected $clientId;
    protected $clientSecret;
    protected $accountId;
    protected $baseUrl = 'https://api.zoom.us/v2';

    public function __construct()
    {
        $this->clientId = config('zoom.client_id');
        $this->clientSecret = config('zoom.client_secret');
        $this->accountId = config('zoom.account_id');
    }

    /**
     * Get the access token from cache or fetch a new one.
     */
    protected function getAccessToken()
    {
        return Cache::remember('zoom_access_token', 3500, function () {
            return $this->fetchNewAccessToken();
        });
    }

    /**
     * Fetch a new access token from the Zoom API.
     */
    protected function fetchNewAccessToken()
    {
        if (!$this->clientId || !$this->clientSecret || !$this->accountId) {
            throw new \Exception('Zoom credentials not configured.');
        }

        $credentials = base64_encode($this->clientId . ':' . $this->clientSecret);

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $credentials,
        ])->asForm()->post('https://zoom.us/oauth/token', [
            'grant_type' => 'account_credentials',
            'account_id' => $this->accountId,
        ]);

        $response->throw(); // Throw an exception if the request fails

        return $response->json()['access_token'];
    }

    /**
     * Make an authenticated request to the Zoom API.
     */
    protected function makeRequest(string $method, string $endpoint, array $data = [])
    {
        $sendRequest = function () use ($method, $endpoint, $data) {
            $accessToken = $this->getAccessToken();
            $pendingRequest = Http::withToken($accessToken)
                ->baseUrl($this->baseUrl)
                ->timeout(30);

            return match (strtoupper($method)) {
                'POST' => $pendingRequest->post($endpoint, $data),
                'PATCH' => $pendingRequest->patch($endpoint, $data),
                'DELETE' => $pendingRequest->delete($endpoint, $data),
                default => $pendingRequest->get($endpoint, $data),
            };
        };

        $response = $sendRequest();

        // If token is expired, fetch a new one and retry the request once.
        if ($response->status() === 401) {
            Cache::forget('zoom_access_token');
            $response = $sendRequest();
        }

        return $response;
    }

    /**
     * Force a new authentication and return the response.
     */
    public function authenticate()
    {
        Cache::forget('zoom_access_token');
        $this->fetchNewAccessToken();
        return response()->json(['message' => 'Zoom authentication successful.']);
    }

    /**
     * Create a new Zoom meeting and save it to the database.
     */
    public function createMeeting(array $meetingData, int $parentMeetingId): Response
    {
        // Default meeting data
        $defaults = [
            'topic' => 'New BPKAD Meeting',
            'type' => 2, // Scheduled meeting
            'start_time' => now()->addHour()->toIso8601String(),
            'duration' => 60, // minutes
            'settings' => [
                'host_video' => true,
                'participant_video' => true,
                'join_before_host' => false,
                'mute_upon_entry' => true,
                'enforce_login' => true,
            ],
        ];

        // Recursively merge the arrays to handle nested settings correctly.
        $data = array_replace_recursive($defaults, $meetingData);

        $response = $this->makeRequest('POST', '/users/me/meetings', $data);

        if ($response->successful()) {
            $this->saveMeeting($response->json(), $parentMeetingId);
        }

        return $response;
    }

    /**
     * Save the meeting details to the database.
     */
    protected function saveMeeting(array $data, int $parentMeetingId = null): void
    {
        // Find the existing zoom meeting or create a new one
        $zoomMeeting = ZoomMeeting::firstOrNew(['zoom_id' => $data['id']]);

        // Fill the model with data from the Zoom API response
        $zoomMeeting->fill([
            'uuid' => $data['uuid'],
            'host_id' => $data['host_id'],
            'host_email' => $data['host_email'],
            'type' => $data['type'],
            'status' => $data['status'],
            'start_time' => $data['start_time'],
            'duration' => $data['duration'],
            'timezone' => $data['timezone'],
            'created_at_zoom' => $data['created_at'],
            'start_url' => $data['start_url'],
            'join_url' => $data['join_url'],
            'password' => $data['password'] ?? null,
            'settings' => $data['settings'],
        ]);

        // If a parent meeting ID is provided (on create), associate it.
        if ($parentMeetingId) {
            $zoomMeeting->meeting_id = $parentMeetingId;
        }

        $zoomMeeting->save();
    }

    /**
     * Delete a Zoom meeting.
     */
    public function deleteMeeting(string $meetingId): Response
    {
        $response = $this->makeRequest('DELETE', "/meetings/{$meetingId}");

        if ($response->successful()) {
            // Also delete from our local database
            ZoomMeeting::where('zoom_id', $meetingId)->delete();
        }

        return $response;
    }

    /**
     * Get a specific Zoom meeting.
     */
    public function getMeeting(string $meetingId): Response
    {
        return $this->makeRequest('GET', "/meetings/{$meetingId}");
    }

    /**
     * Get the summary for a specific Zoom meeting.
     */
    public function getMeetingSummary(string $meetingUuid): Response
    {
        // Double-encode the UUID to prevent premature decoding of special characters
        // like '+' by any intermediate systems.
        $encodedUuid = urlencode(urlencode($meetingUuid));

        return $this->makeRequest('GET', "/meetings/{$encodedUuid}/summary");
    }

    /**
     * Get details for a past Zoom meeting.
     */
    public function getPastMeetingDetails(string $meetingId): Response
    {
        return $this->makeRequest('GET', "/past_meetings/{$meetingId}");
    }

    /**
     * Update a specific Zoom meeting.
     */
    public function updateMeeting(string $meetingId, array $data): Response
    {
        $response = $this->makeRequest('PATCH', "/meetings/{$meetingId}", $data);

        // If the update was successful, fetch the updated meeting details and save them.
        if ($response->successful()) {
            $updatedMeetingResponse = $this->getMeeting($meetingId);
            if ($updatedMeetingResponse->successful()) {
                $this->saveMeeting($updatedMeetingResponse->json());
            }
        }

        return $response;
    }

    /**
     * List all meetings for the current user.
     */
    public function listMeetings(): Response
    {
        return $this->makeRequest('GET', '/users/me/meetings');
    }
}