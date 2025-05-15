<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Models\Contact;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Log;

class ContactController extends Controller
{
    use ApiResponseTrait;
    /**
     * @OA\Get(
     *     path="/api/contact",
     *     summary="Get all contacts of the authenticated user",
     *     description="Returns a list of all contacts associated with the currently authenticated user, including related user information.",
     *     tags={"Contact"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of contacts retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Contact")
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
     * )
     */
    public function index()
    {
        $contacts = auth()->user()->contacts;

        return $this->success($contacts);
    }

    /**
     * @OA\Get(
     *     path="/api/contact/show/{contact}",
     *     summary="Get a specific contact of the authenticated user",
     *     description="Returns detailed information about a specific contact, including selected fields of the related user (contactUser), if the authenticated user is authorized to view it.",
     *     tags={"Contact"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="contact",
     *         in="path",
     *         required=true,
     *         description="ID of the contact to retrieve",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contact retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                    @OA\Property(
     *                 property="contactUser",
     *                 ref="#/components/schemas/Contact"  
     *             ),
     *                
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - You are not allowed to view this contact",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما اجازه مشاهده اطلاعات این مخاطب را ندارید")
     *         )
     *     ),
     *  @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     )),
     * )
     */
    public function show(Contact $contact)
    {
        if (Gate::denies('view', $contact)) {
            return $this->error('شما اجازه مشاهده اطلاعات این مخاطب را ندارید');
        }
        return $this->success($contact);

    }

    /**
     * @OA\Delete(
     *     path="/api/contact/bulk-destroy",
     *     summary="Delete multiple contacts of the authenticated user",
     *     description="Deletes a list of contacts belonging to the authenticated user. Authorization is checked for each contact before deletion.",
     *     tags={"Contact"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="List of contact IDs to delete",
     *         @OA\JsonContent(
     *             type="object",
     *             required={"ids"},
     *             @OA\Property(
     *                 property="ids",
     *                 type="array",
     *                 @OA\Items(type="integer", example=5)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contacts deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="مخاطب با موفقیت حذف شد"),
     *             @OA\Property(property="data", type="string", nullable=true, example=null)
     *         )
     *     ),
     *    
     *   @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     )),
     *  @OA\Response(
     *         response=404,
     *         description="route not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="مخاطبی یافت نشد")
     *     )),
     *  @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *             @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید.")
     *           )
     *     )
     * )
     */
    public function bulkDestroy(ContactRequest $request)
    {
        try {
            $data = $request->all();

            $authUser = auth()->user();

            $contacts = Contact::whereIn('id', $data['ids'])
                ->where('user_id', $authUser->id)
                ->get();
            if(count($contacts)=== 0){
                return $this->error('مخاطبی یافت نشد',404);
            }
            Contact::destroy($contacts->pluck('id')->toArray());
            return $this->success(null, "مخاطب با موفقیت حذف شد");
        } catch (Exception $e) {
            return $this->error();
        }
    }

    /**
     * @OA\Post(
     *     path="/api/contact/store",
     *     summary="Add a new contact to the authenticated user's contact list",
     *     description="Creates a new contact for the authenticated user, given the ID of another user. Prevents duplicates by checking if the contact already exists.",
     *     tags={"Contact"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Contact creation payload",
     *         @OA\JsonContent(
     *             type="object",
     *             required={"contact_user_id"},
     *             @OA\Property(
     *                 property="contact_user_id",
     *                 type="integer",
     *                 description="The ID of the user to add as a contact",
     *                 example=5
     *             ),
     *            @OA\Property(
     *                 property="contact_name",
     *                 type="string",
     *                 description="The name of the user to add as a contact",
     *                 example="ایمان مدائنی"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Contact created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="مخاطب با موفقیت اضافه شد"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                 property="contactUser",
     *                 ref="#/components/schemas/Contact"  
     *             ),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict - Contact already exists",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="این کاربر قبلاً به لیست مخاطبان شما اضافه شده است."),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/Contact")
     *         )
     *     ),
     *  @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     )),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *             @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید.")
     *           )
     *     )
     * )
     */
    public function store(ContactRequest $request)
    {
        try {
            $data = $request->all();
            $exists = Contact::where('user_id', $request->user()->id)
                ->where('contact_user_id', $data['contact_user_id'])
                ->exists();

            if ($exists) {
                return $this->error('این کاربر قبلاً به لیست مخاطبان شما اضافه شده است.', 409);
            }
            $contact = auth()->user()->contacts()->create($data);
            return $this->success($contact, 'مخاطب با موفقیت اضافه شد', 201);
        } catch (Exception $e) {
            return $this->error();
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/contact/delete/{contact}",
     *     summary="Delete a specific contact",
     *     description="Deletes a single contact belonging to the authenticated user. Authorization is checked before deletion.",
     *     tags={"Contact"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="contact",
     *         in="path",
     *         description="ID of the contact to delete",
     *         required=true,
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contact deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="مخاطب با موفقیت حذف شد"),
     *             @OA\Property(property="data", type="string", nullable=true, example=null)
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
     *     )),
     *      @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *             @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید.")
     *           )
     *     )
     * )
     */
    public function delete(Contact $contact)
    {
        if(Gate::denies('delete', $contact)){
            return $this->error('شما اجازه حذف این مخاطب را ندارید');
        }
        try {
            $contact->delete();
            return $this->success(null, 'مخاطب با موفقیت حذف شد');
        } catch (Exception $e) {
            return $this->error();
        }
    }

    /**
     * @OA\Post(
     *     path="/api/contact/search",
     *     summary="Search for users to add as contacts",
     *     description="Searches for discoverable users based on the given query. It matches against username, phone number, first name, last name, or full name.",
     *     tags={"Contact"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         required=true,
     *         description="The search term. It can be a username, phone number, first name, last name, or full name.",
     *         @OA\Schema(type="string", example="john")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Search results returned successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example=null),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=12),
     *                         @OA\Property(property="username", type="string", example="john_doe"),
     *                         @OA\Property(property="first_name", type="string", example="John"),
     *                         @OA\Property(property="last_name", type="string", example="Doe"),
     *                         @OA\Property(property="mobile", type="string", example="09121234567"),
     *                         @OA\Property(property="profile_photo_path", type="string", example="path/avatar.jpg"),
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No users found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="کاربری یافت نشد"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *         @OA\Property(property="status", type="boolean", example=false),
     *         @OA\Property(property="message", type="string", example="وارد کردن عبارت جستجو الزامی است")
     *     )),
     *   @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     )),
     * )
     */
    public function search(ContactRequest $request)
    {
        $query = $request->input('query');
    
        $isMobile = preg_match('/^(\+98|0098|98|0)?9\d{9}$/', $query);
        $mobile = $isMobile ? normalizeMobile($query) : null;
    
        $usersQuery = User::query()
            ->whereKeyNot(auth()->id())
            ->where('is_discoverable', 1)
            ->where(function ($q) use ($query, $mobile) {
                $q->where('username', $query)
                  ->orWhere('username', 'LIKE', "$query%")
                  ->orWhere('first_name', 'LIKE', "$query%")
                  ->orWhere('last_name', 'LIKE', "$query%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%$query%"]);
    
                if ($mobile) {
                    $q->orWhere('mobile', $mobile)
                      ->orWhere('mobile', 'LIKE', "$mobile%");
                }
            })
            ->select('id', 'first_name', 'last_name', 'username', 'mobile', 'profile_photo_path');
    
        // ساخت دینامیک orderByRaw
        $orderByRaw = "CASE WHEN username = ? THEN 0";
        $bindings = [$query];
    
        $index = 1;
    
        if ($mobile) {
            $orderByRaw .= " WHEN mobile = ? THEN {$index}";
            $bindings[] = $mobile;
            $index++;
    
            $orderByRaw .= " WHEN username LIKE ? THEN {$index}";
            $bindings[] = "$query%";
            $index++;
    
            $orderByRaw .= " WHEN mobile LIKE ? THEN {$index}";
            $bindings[] = "$mobile%";
            $index++;
        } else {
            $orderByRaw .= " WHEN username LIKE ? THEN {$index}";
            $bindings[] = "$query%";
            $index++;
        }
    
        $orderByRaw .= " WHEN first_name LIKE ? THEN {$index}";
        $bindings[] = "$query%";
        $index++;
    
        $orderByRaw .= " WHEN last_name LIKE ? THEN {$index}";
        $bindings[] = "$query%";
        $index++;
    
        $orderByRaw .= " WHEN CONCAT(first_name, ' ', last_name) LIKE ? THEN {$index}";
        $bindings[] = "%$query%";
    
        $orderByRaw .= " ELSE 99 END";
    
        $users = $usersQuery
            ->orderByRaw($orderByRaw, $bindings)
            ->simplePaginate(15)
            ->each(function($user){
                $user->makeHidden(['activation_value','user_type_value','is_discoverable_value']);
            });
    
        if ($users->isEmpty()) {
            return $this->error('کاربری یافت نشد', 404);
        }
    
        return $this->success($users);
    }
    


    // upload phone numbers in device where registered in chat app
    /**
     * @OA\Post(
     *     path="/api/contact/sync",
     *     summary="Sync phone contacts with registered users",
     *     description="Accepts a list of mobile numbers, normalizes and filters them, then returns matching discoverable users from the database.",
     *     tags={"Contact"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"mobiles"},
     *             @OA\Property(
     *                 property="mobiles",
     *                 type="array",
     *                 @OA\Items(type="string", example="+989123456789")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Matching users returned successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example=null),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=10),
     *                     @OA\Property(property="first_name", type="string", example="Ali"),
     *                     @OA\Property(property="last_name", type="string", example="Rezaei"),
     *                     @OA\Property(property="username", type="string", example="ali_reza"),
     *                     @OA\Property(property="mobile", type="string", example="09123456789"),
     *                     @OA\Property(property="profile_photo_path", type="string", example="path/avatar.jpg"),
     *                 )
     *             )
     *         )
     *     ),
     *    
     *      @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *         @OA\Property(property="status", type="boolean", example=false),
     *         @OA\Property(property="message", type="string", example="وارد کردن عبارت جستجو الزامی است")
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
    public function syncContacts(ContactRequest $request)
    {
        $data = $request->all();

        // the given numbers have 0 or +98. so we must be filter them
        $normalizedMobiles = collect($data['mobiles'])
            ->map(function ($mobile) {
                return normalizeMobile($mobile);
            })
            ->filter() // just valid numbers
            ->unique() // just unique numbers
            ->values() // sort indexs of array
            ->toArray();

        // search in db
        $users = User::whereIn('mobile', $normalizedMobiles)
            ->whereKeyNot(auth()->id())
            ->where('is_discoverable', 1)
            ->select('id', 'first_name', 'last_name', 'username', 'mobile', 'profile_photo_path')
            ->get()
            ->each(function($user){
                $user->makeHidden(['activation_value','user_type_value','is_discoverable_value']);
            });
           
        return $this->success($users);
    }

    /**
     * @OA\Post(
     *     path="/api/contact/block/{user}",
     *     summary="Block a user",
     *     description="Blocks the specified user, preventing further interactions. User cannot block themselves or block the same user more than once.",
     *     tags={"Contact"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         description="The ID of the user to block",
     *         required=true,
     *         @OA\Schema(type="integer", example=12)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User successfully blocked",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="کاربر با موفقیت بلاک شد"),
     *             @OA\Property(property="data", type="string", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Cannot block self or already blocked user",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="شما نمی‌توانید خودتان را بلاک کنید")
     *         )
     *     ),
     *  @OA\Response(
     *         response=404,
     *         description="route not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="مسیر مورد نظر پیدا نشد")
     *     )),
     *   @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     )),
     *      @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *             @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید.")
     *           )
     *     )
     * )
     */
    public function blockUser(User $user)
    {
        try {
            $authUser = auth()->user();

            if ($authUser->id === $user->id) {
                return $this->error('شما نمی‌توانید خودتان را بلاک کنید', 400);
            }

            $alreadyBlocked = $authUser->blocks()
                ->where('blockable_id', $user->id)
                ->where('blockable_type', User::class)
                ->exists();

            if ($alreadyBlocked) {
                return $this->error('این کاربر قبلاً بلاک شده است', 400);
            }

            $authUser->blocks()->create([
                'blockable_id' => $user->id,
                'blockable_type' => User::class,
                'blocker_id' => $authUser->id,
            ]);

            return $this->success(null, 'کاربر با موفقیت بلاک شد');
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/contact/unblock/{user}",
     *     summary="Unblock a user",
     *     description="Removes a user from the block list if they were previously blocked by the authenticated user.",
     *     tags={"Contact"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         description="The ID of the user to unblock",
     *         required=true,
     *         @OA\Schema(type="integer", example=12)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User successfully unblocked",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="کاربر با موفقیت از حالت بلاک خارج شد"),
     *             @OA\Property(property="data", type="string", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="User is not blocked",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="این کاربر در لیست بلاک‌های شما نیست")
     *         )
     *     ),
     *    @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="جهت انجام عملیات ابتدا وارد حساب کاربری خود شوید")
     *     )),
     *  @OA\Response(
     *         response=404,
     *         description="route not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="مسیر مورد نظر پیدا نشد")
     *     )),
     *    @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *             @OA\JsonContent(
     *             @OA\Property(property="status", type="bool", example="false"),
     *             @OA\Property(property="message", type="string", example="خطای غیرمنتظره در سرور رخ داده است. لطفاً دوباره تلاش کنید.")
     *           )
     *     )
     * )
     */
    public function unblockUser(User $user)
    {
        try {
            $authUser = auth()->user();

            $blocked = $authUser->blocks()
                ->where('blockable_id', $user->id)
                ->where('blockable_type', User::class)
                ->first();

            if (!$blocked) {
                return $this->error('این کاربر در لیست بلاک‌های شما نیست', 400);
            }

            $blocked->delete();

            return $this->success(null, 'کاربر با موفقیت از حالت بلاک خارج شد');
        } catch (Exception $e) {
            return $this->error(); 
        }
    }


}
