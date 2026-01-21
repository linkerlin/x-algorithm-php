<?php

declare(strict_types=1);

namespace XAlgorithm\Core\DataStructures;

class CandidateFeatures
{
    public float $weightedScore;
    public float $diversityScore;
    public float $oonScore;
    public float $finalScore;
    public ?int $rank;
    public int $filterCount;
    public int $sourceIndex;
    public array $scorerNames;

    public function __construct(array $data = [])
    {
        $this->weightedScore = $data['weighted_score'] ?? 0.0;
        $this->diversityScore = $data['diversity_score'] ?? 0.0;
        $this->oonScore = $data['oon_score'] ?? 0.0;
        $this->finalScore = $data['final_score'] ?? 0.0;
        $this->rank = $data['rank'] ?? null;
        $this->filterCount = $data['filter_count'] ?? 0;
        $this->sourceIndex = $data['source_index'] ?? 0;
        $this->scorerNames = $data['scorer_names'] ?? [];
    }

    public function toArray(): array
    {
        return [
            'weighted_score' => $this->weightedScore,
            'diversity_score' => $this->diversityScore,
            'oon_score' => $this->oonScore,
            'final_score' => $this->finalScore,
            'rank' => $this->rank,
            'filter_count' => $this->filterCount,
            'source_index' => $this->sourceIndex,
            'scorer_names' => $this->scorerNames,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }
}
