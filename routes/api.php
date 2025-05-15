<?php
use App\Http\Controllers\Admin\ConversationController as AdminConversationController;
use App\Http\Controllers\Admin\MessageController as AdminMessageController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\EmailVerificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\OTPController;
use App\Http\Controllers\Chat\MessageController;
use App\Http\Controllers\Chat\ConversationController;
use App\Http\Controllers\Chat\GroupConversationController;
use App\Http\Controllers\Chat\JoinRequestController;
use App\Http\Controllers\Chat\ContactController;
use App\Http\Controllers\Chat\FavoriteMessageController;
use App\Http\Controllers\Chat\PinnedMessageController;
use App\Http\Controllers\Admin\PinnedMessageController as AdminPinnedMessageController;
use App\Http\Controllers\Chat\MessageReactionController;
use App\Http\Controllers\Admin\MessageReactionController as AdminMessageReactionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\AdminHomeController;


Route::
        namespace('Admin')->prefix('admin')->middleware(['auth:api', 'role:superadmin|admin'])->group(function () {

            // Home
            Route::get('/blocked-users', [AdminHomeController::class, 'blockedUsers'])->name('admin.blocked-users');
            Route::get('/reports-status', [AdminHomeController::class, 'reportsByStatus'])->name('admin.reports-status');
            // user
            Route::prefix('user')->group(function () {
                Route::get('/', [UserController::class, 'index'])->name('admin.user');
                Route::get('/show/{user}', [UserController::class, 'show'])->name('admin.user.show');
                Route::patch('/block/{user}', [UserController::class, 'block'])->name('admin.user.block');
                Route::patch('/unblock/{user}', [UserController::class, 'unblock'])->name('admin.user.unblock');
                Route::patch('/unblock/{user}', [UserController::class, 'unblock'])->name('admin.user.unblock');
                Route::patch('/update-roles/{user}', [UserController::class, 'updateRoles'])->name('admin.user.update-roles');
                Route::patch('/update-permissions/{user}', [UserController::class, 'updatePermissions'])->name('admin.user.update-permissions');
            });
            // role
            Route::prefix('role')->group(function () {
                Route::get('/', [RoleController::class, 'index'])->name('admin.role');
                Route::get('/show/{role}', [RoleController::class, 'show'])->name('admin.role.show');
                Route::post('/store', [RoleController::class, 'store'])->name('admin.role.store');
                Route::patch('/update/{role}', [RoleController::class, 'update'])->name('admin.role.update');
                Route::post('/sync-permissions/{role}', [RoleController::class, 'syncPermissionsToRole'])->name('admin.role.sync-permissions');
                Route::delete('/delete/{role}', [RoleController::class, 'delete'])->name('admin.role.delete');
            });
            // permission
            Route::prefix('permission')->group(function () {
                Route::get('/', [PermissionController::class, 'index'])->name('admin.permission');
                Route::get('/show/{permission}', [PermissionController::class, 'show'])->name('admin.permission.show');
                Route::post('/store', [PermissionController::class, 'store'])->name('admin.permission.store');
                Route::patch('/update/{permission}', [PermissionController::class, 'update'])->name('admin.permission.update');
                Route::post('/sync-roles/{permission}', [PermissionController::class, 'syncPermissionToRoles'])->name('admin.permission.sync-roles');
                Route::delete('/delete/{permission}', [PermissionController::class, 'delete'])->name('admin.permission.delete');
            });
            // report
            Route::prefix('report')->group(function () {
                Route::get('/', [ReportController::class, 'index'])->name('admin.report');
                Route::get('/user/{user}', [ReportController::class, 'reports'])->name('admin.user.reports');
                Route::get('/message/{message}', [ReportController::class, 'messageReports'])->name('admin.messageReports');
                Route::patch('/respond/{report}', [ReportController::class, 'respondToReport'])->name('admin.respondToReport');
            });

            // conversation
            Route::prefix('conversation')->group(function () {
                Route::get('/show/{conversation}', [AdminConversationController::class, 'show']);
                Route::post('/store/{targetUser}', [AdminConversationController::class, 'store']);
                Route::post('/join/{conversation}', [AdminConversationController::class, 'joinConversationAsAdmin']);
                Route::get('/search/{conversation}', [AdminConversationController::class, 'search'])->name('admin.conversation.search');
                Route::get('/last-seen-message/{conversation}', [AdminConversationController::class, 'getLastSeenMessage'])->name('admin.conversation.last-seen-message');
                Route::patch('/{conversation}/update-last-seen-message/{message}', [AdminConversationController::class, 'updateLastSeen'])->middleware('EnsureNotBlocked')->name('admin.conversation.last-seen-message.update');
            });

            // message
            Route::prefix('message')->group(function () {
                Route::post('/send/{conversation}', [AdminMessageController::class, 'sendMessage'])->name('admin.message.send');
                Route::get('/get/{conversation}', [AdminMessageController::class, 'getMessages'])->name('admin.messages');
                Route::get('/media/download/{media}', [AdminMessageController::class, 'downloadMedia'])->name('admin.message.media.download');
                Route::patch('/mark-as-read/{message}', [AdminMessageController::class, 'markAsRead'])->name('admin.message.mark-as-read');
                Route::post('/reply-to-message/{message}', [AdminMessageController::class, 'replyToMessage'])->name('admin.message.reply');
                Route::post('/forward/{message}', [AdminMessageController::class, 'forwardMessage'])->name('admin.message.forward');
                Route::post('/reply-privately/{message}', [AdminMessageController::class, 'sendPrivateMessage'])->name('admin.message.reply-privately');
                Route::patch('/update/{message}', [AdminMessageController::class, 'updateMessage'])->name('admin.message.update');
                Route::delete('/delete/{message}', [AdminMessageController::class, 'deleteMessage'])->name('admin.message.delete');
                Route::delete('/delete-for-user/{message}', [AdminMessageController::class, 'deleteMessageForUser'])->name('admin.message.delete-for-user');
                Route::get('/get-pinned/{conversation}', [AdminPinnedMessageController::class, 'pinnedMessage'])->name('admin.message.get-pinned');
                Route::post('/toggle-pin/{message}', [AdminPinnedMessageController::class, 'togglePin'])->name('admin.message.toggle-pin');
                Route::get('/get-reactions/{message}', [AdminMessageReactionController::class, 'messageReactions'])->name('admin.message.get-reactions');
                Route::post('/toggle-react/{message}', [AdminMessageReactionController::class, 'toggleReaction'])->name('admin.message.toggle-react');
            });

        });
// Auth



Route::
        namespace('Auth')->group(function () {

            Route::post('/register', [AuthController::class, 'register'])->name('register');
            Route::post('/login', [AuthController::class, 'login'])->name('login');
            Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('jwt.refresh'); // use post method to more safety
            Route::middleware(['auth:api', 'systemicBlock'])->group(function () {
                Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
                Route::get('/resend-otp', [OTPController::class, 'resendOtp'])->name('resend-otp');
                Route::get('/email/verify', [EmailVerificationController::class, 'checkVerificationStatus']);
            });
            Route::post('/email/verification-notification', [EmailVerificationController::class, 'resendVerificationEmail'])->middleware('throttle:6,1');
            Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verifyEmail'])->middleware('signed', 'throttle:6,1')->name('verification.verify');
        });
// profile
Route::
        namespace('Profile')->prefix('profile')->group(function () {
            Route::middleware(['auth:api', 'systemicBlock'])->group(function () {
                Route::get('/', [ProfileController::class, 'index'])->name('profile');
                Route::put('/update', [ProfileController::class, 'updateProfileInfo'])->name('profile.update.info');
                Route::post('/update-contact', [ProfileController::class, 'updateProfileContact'])->name('profile.update.contact');
                Route::put('/confirm-contact/{token}', [ProfileController::class, 'profileCantactConfirm'])->name('profile.contact.confirm');
                Route::post('/update-avatar', [ProfileController::class, 'updateProfileAvatar'])->name('profile.update.avatar');
                Route::patch('/change-password', [ProfileController::class, 'changePassword'])->name('profile.update.password');
                Route::patch('/toggle-discoverable', [ProfileController::class, 'toggleDiscoverable'])->name('profile.toggle-discoverable');
            });
        });
Route::
        namespace('Message')->prefix('message')->group(function () {
            Route::middleware(['auth:api', 'systemicBlock'])->group(function () {
                Route::get('/get-favorites', [FavoriteMessageController::class, 'favoriteMessages'])->name('message.get-favorites');

            });
            Route::middleware(['auth:api', 'systemicBlock', 'EnsureNotBlocked'])->group(function () {
                Route::post('/send/{conversation}', [MessageController::class, 'sendMessage'])->name('message.send');
                Route::patch('/update/{message}', [MessageController::class, 'updateMessage'])->name('message.update');
                Route::get('/get/{conversation}', [MessageController::class, 'getMessages'])->name('messages');
                Route::get('/media/download/{media}', [MessageController::class, 'downloadMedia'])->name('message.media.download');
                Route::patch('/mark-as-read/{message}', [MessageController::class, 'markAsRead'])->name('message.mark-as-read');
                Route::delete('/delete/{message}', [MessageController::class, 'deleteMessage'])->name('message.delete');
                Route::delete('/delete-for-user/{message}', [MessageController::class, 'deleteMessageForUser'])->name('message.delete-fo-user');
                Route::post('/report/{message}', [MessageController::class, 'reportMessage'])->name('message.report');
                Route::post('/toggle-favorite/{message}', [FavoriteMessageController::class, 'toggleFavorite'])->name('message.toggle-favorite');
                Route::get('/get-pinned/{conversation}', [PinnedMessageController::class, 'pinnedMessage'])->name('message.get-pinned');
                Route::post('/toggle-pin/{message}', [PinnedMessageController::class, 'togglePin'])->name('message.toggle-pin');
                Route::get('/get-reactions/{message}', [MessageReactionController::class, 'messageReactions'])->name('message.get-reactions');
                Route::post('/toggle-react/{message}', [MessageReactionController::class, 'toggleReaction'])->name('message.toggle-react');
                Route::post('/reply-privately/{message}', [MessageController::class, 'sendPrivateMessageFromGroupMessage'])->name('message.reply-privately');
                Route::post('/reply-to-message/{message}', [MessageController::class, 'replyToMessage'])->name('message.reply-to-message');
                Route::post('/forward/{message}', [MessageController::class, 'forwardMessage'])->name('message.forward');
            });
        });

// conversation
Route::
        namespace('Conversation')->group(function () {
            Route::middleware(['auth:api', 'systemicBlock'])->group(function () {
                Route::prefix('conversation')->group(function () {
                    Route::get('/', [ConversationController::class, 'index'])->name('conversation');
                    Route::post('/store/{targetUser}', [ConversationController::class, 'store'])->name('conversation.store');
                    Route::get('/show/{conversation}', [ConversationController::class, 'show'])->middleware('EnsureNotBlocked')->name('conversation.show');
                    Route::delete('/delete/{conversation}', [ConversationController::class, 'destroy'])->name('conversation.delete');
                    Route::get('/search/{conversation}', [ConversationController::class, 'search'])->name('conversation.search');
                    Route::patch('/toggle-archive/{conversation}', [ConversationController::class, 'toggleArchive'])->name('conversation.toggle-archive');
                    Route::patch('/toggle-mute/{conversation}', [ConversationController::class, 'toggleMute'])->name('conversation.toggle-mute');
                    Route::patch('/toggle-favorite/{conversation}', [ConversationController::class, 'toggleFavorite'])->name('conversation.toggle-facorite');
                    Route::patch('/toggle-pin/{conversation}', [ConversationController::class, 'togglePin'])->name('conversation.toggle-pin');
                    Route::get('/last-seen-message/{conversation}', [ConversationController::class, 'getLastSeenMessage'])->name('conversation.last-seen-message');
                    Route::patch('/{conversation}/update-last-seen-message/{message}', [ConversationController::class, 'updateLastSeen'])->middleware('EnsureNotBlocked')->name('conversation.last-seen-message.update');
                    Route::post('/block/{conversation}', [ConversationController::class, 'blockConversation'])->name('conversation.block');
                    Route::delete('/unblock/{conversation}', [ConversationController::class, 'unblockConversation'])->name('conversation.unblock');
                    // group
                    Route::prefix('group')->group(function () {

                        Route::middleware('EnsureNotBlocked')->group(function () {
                            Route::get('/show/{groupConversation}', [GroupConversationController::class, 'showGroup'])->name('conversation.group.show');
                            Route::get('/get-members/{conversation}', [GroupConversationController::class, 'members'])->name('conversation.group.get-members');
                            Route::post('/add-member/{groupConversation}', [GroupConversationController::class, 'addToGroup'])->name('conversation.group.add-member');
                        });
                        Route::post('/store', [GroupConversationController::class, 'storeGroup'])->name('conversation.group.store');
                        Route::patch('/{conversation}/update-member-role/{user}', [GroupConversationController::class, 'updateMemberRole'])->name('conversation.group.update-member-role');
                        Route::delete('/{conversation}/remove-member/{user}', [GroupConversationController::class, 'removeMember'])->name('conversation.group.remove-member');
                        Route::delete('/delete/{groupConversation}', [GroupConversationController::class, 'deleteGroup'])->name('conversation.group.delete');
                        Route::patch('/leave/{groupConversation}', [GroupConversationController::class, 'leaveGroup'])->name('conversation.group.leave');
                        Route::patch('/transfer-ownership/{groupConversation}', [GroupConversationController::class, 'transferOwnership'])->name('conversation.group.transfer-ownership');
                        Route::patch('/remove-avatar/{groupConversation}', [GroupConversationController::class, 'removeGroupAvatar'])->name('conversation.group.remove-avatar');
                        Route::post('/change-avatar/{groupConversation}', [GroupConversationController::class, 'changeGroupAvatar'])->name('conversation.group.change-avatar');

                        //    join request
        
                    });
                    Route::prefix('join')->group(function () {
                        Route::post('/{groupConversation}', [JoinRequestController::class, 'joinConversation'])->name('conversation.group.join');
                        Route::patch('/respond-to-request/{joinRequest}', [JoinRequestController::class, 'respondToJoinRequest'])->name('conversation.group.join.respond-torequest');
                        Route::get('/pending-requests/{groupConversation}', [JoinRequestController::class, 'pendingJoinRequests'])->name('conversation.group.join.pending-requests');

                    });
                });
            });
        });

Route::middleware(['auth:api', 'systemicBlock'])->group(function () {
    Route::prefix('contact')->group(function () {
        Route::get('/', [ContactController::class, 'index']);
        Route::get('/show/{contact}', [ContactController::class, 'show']);
        Route::post('/search', [ContactController::class, 'search'])->name('contact.search');
        Route::post('/store', [ContactController::class, 'store'])->name('contact.store');
        Route::post('/sync', [ContactController::class, 'syncContacts'])->name('contact.sync');
        Route::post('/block/{user}', [ContactController::class, 'blockUser'])->name('contact.block');
        Route::delete('/unblock/{user}', [ContactController::class, 'unblockUser'])->name('contact.unblock');
        Route::delete('/delete/{contact}', [ContactController::class, 'delete'])->name('contact.delete');
        Route::delete('/bulk-destroy', [ContactController::class, 'bulkDestroy'])->name('contact.bulkDestroy');
    });
});
Route::post('/broadcasting/auth', function (Request $request) {
    return Broadcast::auth($request);
})->middleware(['auth:api', 'systemicBlock']);


