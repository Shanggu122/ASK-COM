<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AdminAnalyticsController extends Controller
{
    private const TZ = 'Asia/Manila';

    public function index()
    {
        return view('admin-analytics');
    }

    public function data(Request $request)
    {
        [$startDate, $endDate] = $this->resolveDateRange($request);
        $months = $this->generateMonthLabels($startDate, $endDate);

        $bookings = DB::table('t_consultation_bookings as b')
            ->join('professors as p', 'p.Prof_ID', '=', 'b.Prof_ID')
            ->join('t_student as s', 's.Stud_ID', '=', 'b.Stud_ID')
            ->leftJoin('t_consultation_types as ct', 'ct.Consult_type_ID', '=', 'b.Consult_type_ID')
            ->select([
                'b.Booking_ID',
                'b.Booking_Date',
                'b.completion_reviewed_at',
                'b.Status',
                DB::raw("COALESCE(b.Custom_Type, ct.Consult_Type) as Type"),
                'p.Dept_ID',
                'p.Name as Professor_Name',
                's.Name as Student_Name'
            ])
            ->whereRaw('LOWER(b.Status) = ?', ['completed'])
            ->get();

        $bookings = $bookings->filter(function ($booking) use ($startDate, $endDate) {
            $sourceDate = $booking->completion_reviewed_at ?? $booking->Booking_Date;
            $parsed = $this->parseBookingDate($sourceDate);
            if (!$parsed) {
                return false;
            }

            $booking->parsed_date = $parsed;
            $booking->effective_date_source = $sourceDate;
            return $parsed->betweenIncluded($startDate, $endDate);
        })
        ->map(function ($booking) {
            // Ensure department comparisons work even if the DB column stores descriptive strings
            $booking->Dept_ID = $this->normalizeDeptId($booking->Dept_ID);
            return $booking;
        })
        ->values();

        if ($bookings->isEmpty()) {
            return response()->json([
                'comsci' => $this->getEmptyDepartmentData($months),
            ]);
        }

        $comSciBookings = $bookings->where('Dept_ID', 2);

        return response()->json([
            'comsci' => $this->processDepartmentData($comSciBookings, $months),
        ]);
    }

    private function getEmptyDepartmentData(array $months): array
    {
        if (empty($months)) {
            $months = [Carbon::now(self::TZ)->format('M Y')];
        }

        return [
            'totalConsultations' => 0,
            'activeProfessors' => 0,
            'activeStudents' => 0,
            'topics' => [
                'topics' => [],
                'professors' => [],
                'data' => []
            ],
            'activity' => [
                'months' => $months,
                'series' => [[
                    'name' => 'Consultations',
                    'data' => array_fill(0, count($months), 0)
                ]]
            ],
            'peak_days' => [],
            'weekend_days' => []
        ];
    }

    private function processDepartmentData($bookings, array $months): array
    {
        if ($bookings->isEmpty()) {
            return $this->getEmptyDepartmentData($months);
        }

    $totalConsultations = $bookings->count();
    $activeProfessors = $bookings->unique('Professor_Name')->count();
    $activeStudents = $bookings->unique('Student_Name')->count();

        $topicsData = $this->processTopicsData($bookings);
        $activityData = $this->processActivityData($bookings, $months);
        $peakDaysData = $this->processPeakDaysData($bookings);

        return [
            'totalConsultations' => $totalConsultations,
            'activeProfessors' => $activeProfessors,
            'activeStudents' => $activeStudents,
            'topics' => $topicsData,
            'activity' => $activityData,
            'peak_days' => $peakDaysData['weekdays'],
            'weekend_days' => $peakDaysData['weekend']
        ];
    }

    private function processTopicsData($bookings): array
    {
        // Group bookings by professor first
        $bookingsByProf = $bookings->groupBy('Professor_Name');
        $professors = $bookingsByProf->keys()->values();
        
        // Get unique topics and initialize counters
        $topics = $bookings->pluck('Type')->unique()->values();
        $topicsByProf = [];
        
        // Process each professor's bookings
        foreach ($bookingsByProf as $profName => $profBookings) {
            foreach ($profBookings->groupBy('Type') as $topic => $topicBookings) {
                $topicsByProf[$topic][$profName] = $topicBookings->count();
            }
        }

        // Format data for chart
        $data = [];
        foreach ($topics as $topic) {
            $data[$topic] = [];
            foreach ($professors as $prof) {
                $data[$topic][] = $topicsByProf[$topic][$prof] ?? 0;
            }
        }

        return [
            'topics' => $topics,
            'professors' => $professors,
            'data' => $data
        ];
    }

    private function processActivityData($bookings, array $months): array
    {
        // Initialize monthly data with zeros
        $monthlyData = array_fill_keys($months, 0);

        // Group bookings by month
        $bookingsByMonth = $bookings->groupBy(function ($booking) {
            return ($booking->parsed_date ?? $this->parseBookingDate($booking->Booking_Date))?->format('M Y');
        })->filter(function ($group, $key) {
            return !is_null($key);
        });

        // Count bookings for each month
        foreach ($bookingsByMonth as $month => $monthBookings) {
            if (isset($monthlyData[$month])) {
                $monthlyData[$month] = $monthBookings->count();
            }
        }

        return [
            'months' => $months,
            'series' => [[
                'name' => 'Consultations',
                'data' => array_values($monthlyData)
            ]]
        ];
    }

    private function processPeakDaysData($bookings): array
    {
        $allDays = [
            'Monday' => 0, 'Tuesday' => 0, 'Wednesday' => 0, 
            'Thursday' => 0, 'Friday' => 0, 'Saturday' => 0, 'Sunday' => 0
        ];

        // Group bookings by day of week
        $bookingsByDay = $bookings->groupBy(function ($booking) {
            return ($booking->parsed_date ?? $this->parseBookingDate($booking->Booking_Date))?->format('l');
        })->filter(function ($group, $key) {
            return !is_null($key);
        });

        // Count bookings for each day
        foreach ($bookingsByDay as $day => $dayBookings) {
            if (isset($allDays[$day])) {
                $allDays[$day] = $dayBookings->count();
            }
        }

        // Split into weekdays and weekend
        $weekdays = array_filter(array_intersect_key($allDays, array_flip(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'])));
        $weekend = array_filter(array_intersect_key($allDays, array_flip(['Saturday', 'Sunday'])));

        return [
            'weekdays' => $weekdays,
            'weekend' => $weekend
        ];
    }

    private function resolveDateRange(Request $request): array
    {
        $tz = self::TZ;

        try {
            $end = $request->filled('end_date')
                ? Carbon::parse($request->input('end_date'), $tz)->endOfDay()
                : Carbon::now($tz)->endOfDay();
        } catch (\Exception $e) {
            $end = Carbon::now($tz)->endOfDay();
        }

        try {
            $start = $request->filled('start_date')
                ? Carbon::parse($request->input('start_date'), $tz)->startOfDay()
                : $end->copy()->subMonths(5)->startOfMonth();
        } catch (\Exception $e) {
            $start = $end->copy()->subMonths(5)->startOfMonth();
        }

        if ($start->greaterThan($end)) {
            $start = $end->copy()->startOfDay();
        }

        return [$start, $end];
    }

    private function generateMonthLabels(Carbon $start, Carbon $end): array
    {
        $period = CarbonPeriod::create(
            $start->copy()->startOfMonth(),
            '1 month',
            $end->copy()->startOfMonth()
        );

        $labels = [];
        foreach ($period as $month) {
            $labels[] = $month->format('M Y');
        }

        if (empty($labels)) {
            $labels[] = $start->format('M Y');
        }

        return $labels;
    }

    private function parseBookingDate($dateString): ?Carbon
    {
        if (!$dateString) {
            return null;
        }

        try {
            return Carbon::parse($dateString, self::TZ);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function normalizeDeptId($dept): ?int
    {
        if (is_null($dept)) {
            return null;
        }

        if (is_numeric($dept)) {
            $asInt = (int) $dept;
            return $asInt > 0 ? $asInt : null;
        }

        $normalized = strtoupper(trim((string) $dept));
        $identifier = preg_replace('/[^A-Z]/', '', $normalized);

        if ($identifier === '') {
            return null;
        }

        $itisTags = ['IT', 'ITIS', 'INFORMATIONTECHNOLOGY'];
        $comSciTags = ['CS', 'COMSCI', 'COMPUTERSCIENCE'];

        if (in_array($identifier, $itisTags, true)) {
            return 1;
        }

        if (in_array($identifier, $comSciTags, true)) {
            return 2;
        }

        if (
            substr($identifier, 0, 2) === 'IT' ||
            str_contains($identifier, 'ITIS') ||
            str_contains($identifier, 'INFORMATIONTECH')
        ) {
            return 1;
        }

        if (
            substr($identifier, 0, 2) === 'CS' ||
            str_contains($identifier, 'COMSCI') ||
            str_contains($identifier, 'COMPUTERSCIENCE')
        ) {
            return 2;
        }

        return null;
    }
}
