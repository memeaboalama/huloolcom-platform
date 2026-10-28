<?php

namespace Domain\Project\Actions\Task;

use App\Application\Project\Enums\ProjectStatus;
use Domain\Project\Models\Task;
use Domain\Project\Repositories\TaskRepository;

class TaskChangeStatusAction
{
    public function __construct(protected TaskRepository $taskRepository){}

    public function handle(Task $task, array $taskData): Task
    {
        $data = ['status' => $taskData['status']];

        // First time the task becomes completed, stamp the completion date.
        // Mirrors UpdateAssignmentStatusAction for assignments.
        if ($this->isCompleted($taskData['status'] ?? null)) {
            $data['completed_at'] = $task->completed_at ?? now();
        }

        $this->taskRepository->update($task->id, $data);
        // event(new TaskUpdated($task));
        return $task->fresh();
    }

    private function isCompleted(mixed $status): bool
    {
        if ($status instanceof \BackedEnum) {
            $status = $status->value;
        }

        return $status === ProjectStatus::COMPLETED->value;
    }
}
