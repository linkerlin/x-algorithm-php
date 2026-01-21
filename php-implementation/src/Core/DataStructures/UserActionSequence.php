<?php

declare(strict_types=1);

namespace XAlgorithm\Core\DataStructures;

class UserActionSequence
{
    public int $userId;
    public array $actions;
    public int $createdAtMs;

    public function __construct(array $data = [])
    {
        $this->userId = $data['user_id'] ?? 0;
        $this->actions = array_map(fn($a) => UserActionItem::fromArray($a), $data['actions'] ?? []);
        $this->createdAtMs = $data['created_at_ms'] ?? 0;
    }

    public function getRecentActions(int $limit): array
    {
        usort($this->actions, fn($a, $b) => $b->timestampMs <=> $a->timestampMs);
        return array_slice($this->actions, 0, $limit);
    }

    public function getActionsByType(int $actionType): array
    {
        return array_filter($this->actions, fn($a) => $a->actionType === $actionType);
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'actions' => array_map(fn($a) => $a->toArray(), $this->actions),
            'created_at_ms' => $this->createdAtMs,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }
}
