<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class AdminHomeController extends Controller
{
    use ApiResponseTrait;
    /**
     * List all blocked users
     *
     * @OA\Get(
     *     path="/api/admin/blocked-users",
     *     tags={"Admin - Home"},
     *     summary="Get all blocked users",
     *     description="Returns a list of users who are currently blocked.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Blocked users list",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/User"))
     *         )
     *     ),
     *  @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     )),
     *  @OA\Response(
     *         response=403,
     *         description="User is not an admin.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما مجوز انجام این عملیات را ندارید")
     *         ),
     *     )
     * )
     */
    public function blockedUsers()
    {
        $users = User::where('is_blocked', 1)->latest()->paginate(20);
        return $this->success($users);
    }


    /**
     * Get reports filtered by status
     *
     * @OA\Get(
     *     path="/api/admin/reports-status",
     *     tags={"Admin - Home"},
     *     security={{"bearerAuth": {}}},
     *     summary="Get reports by status (approved or rejected)",
     *     description="Returns a list of reports filtered by their status (1: approved, 2: rejected).",
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=true,
     *         description="Report status (0 = pending, 1 = approved, 2 = rejected)",
     *         @OA\Schema(type="integer", enum={0, 1, 2})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Filtered reports list",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Report"))
     *         )
     *     ),
     * @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     )),
     *  @OA\Response(
     *         response=403,
     *         description="User is not an admin.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما مجوز انجام این عملیات را ندارید")
     *         ),
     *     ),
     *   @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="وارد کردن وضعیت الزامی است")
     *     )),
     * )
     */
    public function reportsByStatus(Request $request)
    {
        $request->validate([
            'status' => 'required|in:0,1,2',
        ]);

        $reports = Report::where('status', $request->status)
            ->with('reporter:id,username', 'message')
            ->latest()
            ->get()
            ->each(function ($report) {
                $report->message->makeHidden(['parent', 'created_at', 'updated_at', 'deleted_at']);
                $report->reporter->makeHidden(['activation_value', 'user_type_value', 'is_discoverable_value','is_blocked_value']);
            });

        return $this->success($reports);
    }


}
