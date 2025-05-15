<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportRequest;
use App\Models\Message;
use App\Models\Report;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Get(
     *     path="/api/admin/report",
     *     summary="List all reported messages",
     *     description="Retrieve a list of all message reports including reporter info and the reported message.",
     *     tags={"Admin - Report"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of reports",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(type="object", ref="#/components/schemas/Report"
     *                  
     *                 )
     *             )
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
     *         description="Forbidden - You are not authorized to delete this contact",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="شما اجازه حذف این مخاطب را ندارید")
     *     ))
     * )
     */
    public function index()
    {
        $reports = Report::with('reporter:id,username', 'message')
            ->get()
            ->each(function ($report) {
                $report->message->makeHidden(['parent', 'created_at', 'updated_at', 'deleted_at']);
                $report->reporter->makeHidden(['activation_value', 'user_type_value', 'is_discoverable_value','is_blocked_value']);
            });
        return $this->success($reports);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/report/message/{message}",
     *     summary="Get reports for a specific message",
     *     description="Retrieve all reports that were submitted for a specific message by its ID.",
     *     tags={"Admin - Report"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="message",
     *         in="path",
     *         description="ID of the message to retrieve reports for",
     *         required=true,
     *         @OA\Schema(type="integer", example=17)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of reports for the message",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(type="object",
     *                  ref="#/components/schemas/Report"
     *                 )
     *             )
     *         )
     *     ),
     *    @OA\Response(
     *         response=403,
     *         description="Forbidden - You are not authorized to delete this contact",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="شما اجازه حذف این مخاطب را ندارید")
     *     )),
     *  @OA\Response(
     *         response=404,
     *         description="route not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="مسیر مورد نظر پیدا نشد")
     *     )),
     *  @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     ))
     * )
     */
    public function messageReports(Message $message)
    {
        $reports = Report::where('message_id', $message->id)->with('message', 'reporter:id,username')
            ->simplePaginate(15)
            ->each(function ($report) {
                $report->message->makeHidden(['parent', 'created_at', 'updated_at', 'deleted_at']);
                $report->reporter->makeHidden(['activation_value', 'user_type_value', 'is_discoverable_value','is_blocked_value']);
            });

        return $this->success($reports);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/report/user/{user}",
     *     summary="Get reports submitted by a specific user",
     *     description="Retrieve all reports that have been submitted by a specific user based on their user ID.",
     *     tags={"Admin - Report"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         description="ID of the user who submitted the reports",
     *         required=true,
     *         @OA\Schema(type="integer", example=4)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of reports submitted by the user",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(type="object",
     *                  ref="#/components/schemas/Report"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - You are not authorized to delete this contact",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="شما اجازه حذف این مخاطب را ندارید")
     *     )),
     *  @OA\Response(
     *         response=404,
     *         description="route not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="مسیر مورد نظر پیدا نشد")
     *     )),
     *  @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     ))
     * )
     */
    public function reports(User $user)
    {
        $reports = Report::where('user_id', $user->id)->with('message', 'reporter:id,username')
            ->simplePaginate(15)
            ->each(function ($report) {
                $report->message->makeHidden(['parent', 'created_at', 'updated_at', 'deleted_at']);
                $report->reporter->makeHidden(['activation_value', 'user_type_value', 'is_discoverable_value','is_blocked_value']);
            });
        return $this->success($reports);
    }



    /**
     * Respond to a report by approving or rejecting it
     *
     * @OA\Patch(
     *     path="/api/admin/report/respond/{report}",
     *     tags={"Admin - Report"},
     *     summary="Respond to a user report (approve or reject)",
     *     description="Update the status of a report (1: approved, 2: rejected), optionally with a comment.",
     *     @OA\Parameter(
     *         name="report",
     *         in="path",
     *         description="ID of the report",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="integer", enum={1, 2}, description="1 for approved, 2 for rejected"),
     *             @OA\Property(property="comment", type="string", maxLength=1000, description="Optional comment from admin")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Report responded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="true"),
     *             @OA\Property(property="message", type="string", example="گزارش با موفقیت بررسی شد"),
     *             @OA\Property(property="data", type="string", example="null")
     *         )
     *     ),
     *   @OA\Response(
     *         response=403,
     *         description="Forbidden - You are not authorized to delete this contact",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="شما اجازه حذف این مخاطب را ندارید")
     *     )),
     *  @OA\Response(
     *         response=404,
     *         description="route not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="مسیر مورد نظر پیدا نشد")
     *     )),
     *  @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     )),
     * @OA\Response(
     *         response=500,
     *         description="Unexpected server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید")
     *         )
     *     )
     * )
     */
    public function respondToReport(ReportRequest $request, Report $report)
    {
        try {
            $report->update([
                'status' => $request->status,
                'admin_comment' => $request->comment,
            ]);
           // TODO: Check admin policy to decide whether to delete the message or block the user upon approval.

            return $this->success(null, 'گزارش با موفقیت بررسی شد');
        } catch (Exception $e) {
            return $this->error();
        }
    }

}
