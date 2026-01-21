<?php

declare(strict_types=1);

namespace XAlgorithm\Core\DataStructures;

/**
 * 凤凰评分数据结构
 * 包含各种用户参与度预测分数
 */
class PhoenixScores
{
    public ?float $favoriteScore = null;
    public ?float $replyScore = null;
    public ?float $retweetScore = null;
    public ?float $photoExpandScore = null;
    public ?float $clickScore = null;
    public ?float $profileClickScore = null;
    public ?float $vqvScore = null;
    public ?float $shareScore = null;
    public ?float $shareViaDmScore = null;
    public ?float $shareViaCopyLinkScore = null;
    public ?float $dwellScore = null;
    public ?float $quoteScore = null;
    public ?float $quotedClickScore = null;
    public ?float $followAuthorScore = null;
    public ?float $notInterestedScore = null;
    public ?float $blockAuthorScore = null;
    public ?float $muteAuthorScore = null;
    public ?float $reportScore = null;
    public ?float $dwellTime = null;

    public function __construct(array $scores = [])
    {
        foreach ($scores as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public function toArray(): array
    {
        return [
            'favorite_score' => $this->favoriteScore,
            'reply_score' => $this->replyScore,
            'retweet_score' => $this->retweetScore,
            'photo_expand_score' => $this->photoExpandScore,
            'click_score' => $this->clickScore,
            'profile_click_score' => $this->profileClickScore,
            'vqv_score' => $this->vqvScore,
            'share_score' => $this->shareScore,
            'share_via_dm_score' => $this->shareViaDmScore,
            'share_via_copy_link_score' => $this->shareViaCopyLinkScore,
            'dwell_score' => $this->dwellScore,
            'quote_score' => $this->quoteScore,
            'quoted_click_score' => $this->quotedClickScore,
            'follow_author_score' => $this->followAuthorScore,
            'not_interested_score' => $this->notInterestedScore,
            'block_author_score' => $this->blockAuthorScore,
            'mute_author_score' => $this->muteAuthorScore,
            'report_score' => $this->reportScore,
            'dwell_time' => $this->dwellTime,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self([
            'favoriteScore' => $data['favorite_score'] ?? null,
            'replyScore' => $data['reply_score'] ?? null,
            'retweetScore' => $data['retweet_score'] ?? null,
            'photoExpandScore' => $data['photo_expand_score'] ?? null,
            'clickScore' => $data['click_score'] ?? null,
            'profileClickScore' => $data['profile_click_score'] ?? null,
            'vqvScore' => $data['vqv_score'] ?? null,
            'shareScore' => $data['share_score'] ?? null,
            'shareViaDmScore' => $data['share_via_dm_score'] ?? null,
            'shareViaCopyLinkScore' => $data['share_via_copy_link_score'] ?? null,
            'dwellScore' => $data['dwell_score'] ?? null,
            'quoteScore' => $data['quote_score'] ?? null,
            'quotedClickScore' => $data['quoted_click_score'] ?? null,
            'followAuthorScore' => $data['follow_author_score'] ?? null,
            'notInterestedScore' => $data['not_interested_score'] ?? null,
            'blockAuthorScore' => $data['block_author_score'] ?? null,
            'muteAuthorScore' => $data['mute_author_score'] ?? null,
            'reportScore' => $data['report_score'] ?? null,
            'dwellTime' => $data['dwell_time'] ?? null,
        ]);
    }

    public function getWeightedEngagementScore(array $weights = []): float
    {
        $defaultWeights = [
            'favorite_score' => 0.05,
            'reply_score' => 0.5,
            'retweet_score' => 0.3,
            'share_score' => 0.4,
            'dwell_score' => 0.1,
            'quote_score' => 0.35,
            'click_score' => 0.15,
            'profile_click_score' => 0.1,
        ];

        $weights = array_merge($defaultWeights, $weights);
        $score = 0.0;

        foreach ($weights as $field => $weight) {
            $camelField = $this->toCamelCase($field);
            $value = $this->$camelField ?? 0;
            $score += $value * $weight;
        }

        return $score;
    }

    private function toCamelCase(string $snakeCase): string
    {
        $parts = explode('_', $snakeCase);
        return $parts[0] . implode('', array_map('ucfirst', array_slice($parts, 1)));
    }
}
