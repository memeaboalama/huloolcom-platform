<?php

namespace App\Domain\Educator\Resource\Task;

use App\Application\Project\Enums\AssignmentStatus;
use App\Domain\Educator\Resource\AssignmentResource;
use App\Domain\Educator\Resource\Task\SubTaskResource;
use App\Domain\Educator\Resource\Task\TaskFileResource;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        $completedSubTasks = $this->subTasks->where('completed', true)->count();
        $totalSubTasks = $this->subTasks->count();
        $progress = $totalSubTasks > 0 ? round(($completedSubTasks / $totalSubTasks) * 100) :
                ($this->status === 'COMPLETED' ? 100 : 0);

        return [
            'id' => $this->uuid,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status instanceof \UnitEnum ? $this->status->value : $this->status,
            'budget' => $this->budget !== null ? (float) $this->budget : null,
            'deadline' => $this->deadline ? ($this->deadline instanceof \DateTimeInterface ? $this->deadline->format('Y-m-d') : substr((string) $this->deadline, 0, 10)) : null,
            'progress' => $progress,
            'subTasks' => $this->whenLoaded('subTasks', function () {
                return SubTaskResource::collection($this->subTasks->load('attachments'));
            }),

            'assignedEducator' => $this->whenLoaded('assignments', function () {
                $educator = $this->assignments?->where('assigned_to', auth()->id())->first();
                if (!$educator) return null;
                $assignedType = $educator->assigned_type instanceof \UnitEnum ? $educator->assigned_type->value : $educator->assigned_type;
                $status = $educator->status instanceof \UnitEnum ? $educator->status->value : $educator->status;
                $assignedAt = null;
                if ($educator->assigned_at) {
                    $assignedAt = $educator->assigned_at instanceof \DateTimeInterface
                        ? $educator->assigned_at->format('Y-m-d')
                        : substr((string) $educator->assigned_at, 0, 10);
                }
                return [
                    'id' => (string) $educator->uuid,
                    'assigned_type' => $assignedType,
                    'status' => $status,
                    'amount' => $educator->amount !== null ? (float) $educator->amount : null,
                    'assigned_at' => $assignedAt,
                ];
            }),

            'subTasksCount' => $totalSubTasks,
            'completedSubTasksCount' => $completedSubTasks,
            'attachmentsCount' => $this?->attachments->count(),
            'attachments' => $this->whenLoaded('attachments', function () {
                return TaskFileResource::collection($this->attachments);
            }),

            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
            'creationDate' => $this->creation_date ?? $this->created_at?->format('Y-m-d H:i:s'),

            'notes' => $this->whenLoaded('notes', function () {
                return \Domain\Project\Resources\Task\TaskNoteResource::collection($this->notes);
            }, []),
            'activityLog' => $this->whenLoaded('activityLog', function () {
                return $this->activityLog
                    ->sortByDesc('timestamp')
                    ->map(fn($log) => [
                        'id' => (string) $log->uuid,
                        'messageKey' => $log->message_key,
                        'messagePayload' => $log->message_payload ?? [],
                        'actorName' => $log->actor_name,
                        'timestamp' => $log->timestamp?->format('Y-m-d H:i:s'),
                    ])
                    ->values();
            }, []),

            'project' => $this->whenLoaded('project', function () {
                return [
                    'id' => $this->project->uuid,
                    'title' => $this->project->title,
                ];
            }),
        ];
    }
}
