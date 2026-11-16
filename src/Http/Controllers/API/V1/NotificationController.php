<?php

namespace App\Domain\Notification\Http\Controllers\API\V1;

use App\Domain\Identity\Models\User;
use App\Domain\Notification\Http\Requests\IndexNotificationRequest;
use App\Domain\Notification\Http\Resources\NotificationResource;
use App\Domain\Notification\Models\ActivityNotification;
use App\Domain\Notification\Services\NotificationService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Concerns\ResolvesResourceArray;
use App\Traits\ApiResponse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Role-targeted activity notifications for the authenticated viewer.
 *
 * Visibility is by ROLE: a viewer sees a notification when they hold one of its
 * target roles (resolved in NotificationRepository). The `permission:view
 * notification` middleware gates the surface; the per-row role overlap is the
 * finer-grained rule enforced by the service/repository.
 */
class NotificationController extends Controller
{
    use ApiResponse;
    use ResolvesResourceArray;

    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    /**
     * Notifications visible to the viewer, newest first.
     */
    public function index(IndexNotificationRequest $request): JsonResponse
    {
        $viewer = $this->actor();
        $limit = (int) ($request->validated()['limit'] ?? 50);

        $items = $this->notifications->listForViewer($viewer, $limit);

        return $this->successResponse(
            $this->resolveResource(NotificationResource::collection($items)),
            __('api/notifications.list_success'),
        );
    }

    /**
     * Unread count for the bell badge.
     */
    public function unreadCount(): JsonResponse
    {
        return $this->successResponse(
            ['unread' => $this->notifications->unreadCount($this->actor())],
            __('api/notifications.unread_count_success'),
        );
    }

    /**
     * Mark a single notification read. 403 when the viewer is not in scope.
     */
    public function markAsRead(ActivityNotification $notification): JsonResponse
    {
        $viewer = $this->actor();

        if (! $this->notifications->isVisibleTo($notification, $viewer)) {
            throw new HttpResponseException(
                $this->errorResponse(__('api/notifications.not_visible'), 403),
            );
        }

        $this->notifications->markAsRead($notification, $viewer);

        return $this->successResponse(null, __('api/notifications.marked_read'));
    }

    /**
     * Mark every visible notification read.
     */
    public function markAllAsRead(): JsonResponse
    {
        $count = $this->notifications->markAllAsRead($this->actor());

        return $this->successResponse(
            ['marked' => $count, 'unread' => 0],
            __('api/notifications.marked_all_read'),
        );
    }

    private function actor(): User
    {
        /** @var User $user */
        $user = Auth::guard('api')->user();

        return $user;
    }
}
