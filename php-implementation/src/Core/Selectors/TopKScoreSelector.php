<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Selectors;

use XAlgorithm\Core\DataStructures\ScoredPostsQuery;
use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\Pipeline\Interfaces\SelectorInterface;

/**
 * 顶部K分数选择器
 * 选择分数最高的K个候选
 */
class TopKScoreSelector implements SelectorInterface
{
    private string $name;
    private string $scoreField;

    public function __construct(string $scoreField = 'weightedScore')
    {
        $this->name = 'TopKScoreSelector';
        $this->scoreField = $scoreField;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function select(ScoredPostsQuery $query, array $candidates, int $limit): array
    {
        if (empty($candidates)) {
            return [];
        }

        $scoreField = $this->scoreField;
        usort($candidates, function (PostCandidate $a, PostCandidate $b) use ($scoreField) {
            $scoreA = $a->$scoreField ?? $a->score ?? 0;
            $scoreB = $b->$scoreField ?? $b->score ?? 0;
            return $scoreB <=> $scoreA;
        });

        return array_slice($candidates, 0, $limit);
    }

    public function setScoreField(string $field): self
    {
        $this->scoreField = $field;
        return $this;
    }
}
