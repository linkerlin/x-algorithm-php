<?php

declare(strict_types=1);

namespace XAlgorithm\Core\Hydrators;

use XAlgorithm\Core\DataStructures\PostCandidate;
use XAlgorithm\Core\DataStructures\ScoredPostsQuery;

class VideoDurationCandidateHydrator implements CandidateHydratorInterface
{
    public function hydrate(ScoredPostsQuery $query, array $candidates): array
    {
        return array_map(function (PostCandidate $candidate) {
            $videoDurationMs = null;
            
            if ($candidate->mediaEntities !== null) {
                foreach ($candidate->mediaEntities as $entity) {
                    if ($entity['type'] ?? '' === 'video' || $entity['type'] ?? '' === 'animated_gif') {
                        $videoDurationMs = $entity['video_info']['duration_millis'] ?? null;
                        break;
                    }
                }
            }

            return new PostCandidate([
                'tweet_id' => $candidate->tweetId,
                'author_id' => $candidate->authorId,
                'video_duration_ms' => $videoDurationMs,
            ]);
        }, $candidates);
    }

    public function getName(): string
    {
        return 'VideoDurationCandidateHydrator';
    }
}
