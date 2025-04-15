<?php

namespace App\Http\Controllers\Api\V1\Activity;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);

        // Retrieve logs performed by the authenticated user
        $activities = Activity::causedBy($request->user())
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->successResponse($activities, 'Activities retrieved successfully');
    }
}
