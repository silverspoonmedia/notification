<?php

namespace App\Domain\Notification\Http\Controllers\API\V1;

use App\Domain\Identity\Models\User;
use App\Domain\Notification\Http\Requests\UpdateNotificationAudienceRequest;
use App\Domain\Notification\Services\NotificationAudienceService;
use App\Domain\Notification\Support\ListActions\NotificationAudienceQueryRowActions;
use App\Http\Controllers\Controller;
use App\Support\ListActions\QueryRowActionAttacher;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminNotificationAudienceController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly NotificationAudienceService $audiences,
        private readonly NotificationAudienceQueryRowActions $queryRowActions,
    ) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('view settings'), 403);

        $items = $this->audiences->listPresented()->values();
        $actor = $request->user();

        return $this->successResponse(
            [
                'items' => QueryRowActionAttacher::attach(
                    $items->all(),
                    $items->all(),
                    $this->queryRowActions,
                    $actor instanceof User ? $actor : null,
                ),
                'assignable_roles' => $this->audiences->assignableRoles(),
            ],
            __('api/notifications.audiences.list_success'),
        );
    }

    public function show(Request $request, string $type): JsonResponse
    {
        abort_unless($request->user()?->can('view settings'), 403);

        return $this->successResponse(
            $this->withActions($this->audiences->presentType($type), $request->user()),
            __('api/notifications.audiences.show_success'),
        );
    }

    public function update(UpdateNotificationAudienceRequest $request, string $type): JsonResponse
    {
        $updated = $this->audiences->update(
            $type,
            $request->notifySubjectUser(),
            $request->roleNames(),
            $request->userIds(),
        );

        return $this->successResponse(
            $this->withActions($updated, $request->user()),
            __('api/notifications.audiences.updated'),
        );
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function withActions(array $row, mixed $actor): array
    {
        return QueryRowActionAttacher::attach(
            [$row],
            [$row],
            $this->queryRowActions,
            $actor instanceof User ? $actor : null,
        )[0];
    }
}
