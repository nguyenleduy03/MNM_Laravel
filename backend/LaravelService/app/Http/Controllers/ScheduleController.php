<?php

namespace App\Http\Controllers;

use App\Models\UserSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScheduleController extends Controller
{
    /**
     * Get user's schedule (all)
     * GET /api/schedules or GET /api/schedules/all
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $schedules = UserSchedule::where('user_id', $user->id)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return response()->json($schedules->map(fn($s) => $this->formatScheduleResponse($s)));
    }

    /**
     * Get today's schedule
     * GET /api/schedules/today
     */
    public function today(Request $request)
    {
        $user = $request->user();
        
        // Get day of week (1=Monday, 7=Sunday)
        $dayOfWeek = now()->dayOfWeekIso;

        $schedules = UserSchedule::where('user_id', $user->id)
            ->where('day_of_week', $dayOfWeek)
            ->orderBy('start_time')
            ->get();

        return response()->json($schedules->map(fn($s) => $this->formatScheduleResponse($s)));
    }

    /**
     * Get schedule by day
     * GET /api/schedules/day/{dayOfWeek}
     */
    public function byDay(Request $request, $dayOfWeek)
    {
        $user = $request->user();

        // Support both number (1-7) and string (MONDAY, TUESDAY, etc.)
        if (is_numeric($dayOfWeek)) {
            $day = (int) $dayOfWeek;
        } else {
            $days = [
                'MONDAY' => 1, 'TUESDAY' => 2, 'WEDNESDAY' => 3,
                'THURSDAY' => 4, 'FRIDAY' => 5, 'SATURDAY' => 6, 'SUNDAY' => 7
            ];
            $day = $days[strtoupper($dayOfWeek)] ?? 1;
        }

        $schedules = UserSchedule::where('user_id', $user->id)
            ->where('day_of_week', $day)
            ->orderBy('start_time')
            ->get();

        return response()->json($schedules->map(fn($s) => $this->formatScheduleResponse($s)));
    }

    /**
     * Create schedule entry
     * POST /api/schedules
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userId' => 'required|integer|exists:users,id', // Accept userId from request
            'dayOfWeek' => 'required|integer|min:1|max:7',
            'startTime' => 'required|date_format:H:i',
            'endTime' => 'required|date_format:H:i|after:startTime',
            'subject' => 'required|string|max:255',
            'room' => 'nullable|string|max:100',
            'teacher' => 'nullable|string|max:100',
            'note' => 'nullable|string',
            'semester' => 'nullable|string|max:20',
            'schoolYear' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'details' => $validator->errors()], 422);
        }

        // Use userId from request (for internal API) or authenticated user
        $userId = $request->input('userId') ?? $request->user()?->id;
        
        if (!$userId) {
            return response()->json(['error' => 'User ID required'], 400);
        }

        $schedule = UserSchedule::create([
            'user_id' => $userId,
            'day_of_week' => $request->dayOfWeek,
            'start_time' => $request->startTime,
            'end_time' => $request->endTime,
            'subject' => $request->subject,
            'room' => $request->room,
            'teacher' => $request->teacher,
            'note' => $request->note,
            'semester' => $request->semester,
            'school_year' => $request->schoolYear,
        ]);

        return response()->json($this->formatScheduleResponse($schedule), 201);
    }

    /**
     * Update schedule entry
     * PUT /api/schedules/{id}
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();

        $schedule = UserSchedule::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'dayOfWeek' => 'nullable|integer|min:1|max:7',
            'startTime' => 'nullable|date_format:H:i',
            'endTime' => 'nullable|date_format:H:i',
            'subject' => 'nullable|string|max:255',
            'room' => 'nullable|string|max:100',
            'teacher' => 'nullable|string|max:100',
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        if ($request->has('dayOfWeek')) $schedule->day_of_week = $request->dayOfWeek;
        if ($request->has('startTime')) $schedule->start_time = $request->startTime;
        if ($request->has('endTime')) $schedule->end_time = $request->endTime;
        if ($request->has('subject')) $schedule->subject = $request->subject;
        if ($request->has('room')) $schedule->room = $request->room;
        if ($request->has('teacher')) $schedule->teacher = $request->teacher;
        if ($request->has('note')) $schedule->note = $request->note;

        $schedule->save();

        return response()->json($this->formatScheduleResponse($schedule));
    }

    /**
     * Delete schedule entry
     * DELETE /api/schedules/{id}
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $schedule = UserSchedule::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $schedule->delete();

        return response()->json(['message' => 'Đã xóa lịch học']);
    }

    /**
     * Bulk create/update schedules (for sync from TVU)
     * POST /api/schedules/bulk
     */
    public function bulk(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'schedules' => 'required|array',
            'schedules.*.dayOfWeek' => 'required|integer|min:1|max:7',
            'schedules.*.startTime' => 'required|date_format:H:i',
            'schedules.*.endTime' => 'required|date_format:H:i',
            'schedules.*.subject' => 'required|string|max:255',
            'clearExisting' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = $request->user();

        // Clear existing if requested
        if ($request->clearExisting) {
            UserSchedule::where('user_id', $user->id)->delete();
        }

        $created = [];
        foreach ($request->schedules as $s) {
            $schedule = UserSchedule::create([
                'user_id' => $user->id,
                'day_of_week' => $s['dayOfWeek'],
                'start_time' => $s['startTime'],
                'end_time' => $s['endTime'],
                'subject' => $s['subject'],
                'room' => $s['room'] ?? null,
                'teacher' => $s['teacher'] ?? null,
                'note' => $s['note'] ?? null,
                'semester' => $s['semester'] ?? null,
                'school_year' => $s['schoolYear'] ?? null,
            ]);
            $created[] = $this->formatScheduleResponse($schedule);
        }

        return response()->json([
            'message' => 'Created ' . count($created) . ' schedule entries',
            'schedules' => $created,
        ], 201);
    }

    /**
     * Delete all schedules
     * DELETE /api/schedules/all
     */
    public function deleteAll(Request $request)
    {
        $user = $request->user();
        UserSchedule::where('user_id', $user->id)->delete();

        return response()->json(['message' => 'Đã xóa toàn bộ lịch học']);
    }

    private function formatScheduleResponse(UserSchedule $schedule): array
    {
        return [
            'id' => $schedule->id,
            'dayOfWeek' => $schedule->day_of_week,
            'startTime' => $schedule->start_time,
            'endTime' => $schedule->end_time,
            'subject' => $schedule->subject,
            'room' => $schedule->room,
            'teacher' => $schedule->teacher,
            'note' => $schedule->note,
            'semester' => $schedule->semester,
            'schoolYear' => $schedule->school_year,
            'createdAt' => $schedule->created_at?->toISOString(),
        ];
    }
}
