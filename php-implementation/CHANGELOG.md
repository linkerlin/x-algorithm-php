# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-01-20

### Added

- Core data structures:
  - `PostCandidate` - 帖子候选对象
  - `PhoenixScores` - ML 预测评分
  - `ScoredPostsQuery` - 用户查询请求
  - `UserFeatures` - 用户特征

- Filter implementations:
  - `AgeFilter` - 年龄过滤
  - `DropDuplicatesFilter` - 去重过滤
  - `MutedKeywordFilter` - 静音词过滤
  - `AuthorSocialgraphFilter` - 作者社交图过滤
  - `IneligibleSubscriptionFilter` - 订阅资格过滤
  - `RetweetDeduplicationFilter` - 转发去重
  - `SelfTweetFilter` - 自身推文过滤
  - `PreviouslySeenPostsFilter` - 之前浏览过滤
  - `PreviouslyServedPostsFilter` - 之前服务过滤
  - `DedupConversationFilter` - 会话去重

- Scorer implementations:
  - `WeightedScorer` - 加权评分
  - `PhoenixScorer` - ML 预测评分
  - `OONScorer` - 出站评分
  - `AuthorDiversityScorer` - 作者多样性评分

- Pipeline components:
  - `CandidatePipeline` - 候选处理管道
  - `TopKScoreSelector` - Top-K 选择器
  - `CoreDataCandidateHydrator` - 核心数据水合器

- Data sources:
  - `ThunderSource` - 站内内容源
  - `PhoenixSource` - ML 内容源

- Main services:
  - `HomeMixerService` - 主编排服务
  - `Algorithm` - 便捷入口类

- ML integration:
  - `PhoenixClientInterface` - Phoenix 客户端接口
  - `PhoenixClient` - Phoenix 客户端实现
  - `MockPhoenixClient` - 测试用模拟客户端

- Utilities:
  - `RequestIdGenerator` - Snowflake ID 生成器
  - `FilterResult` - 过滤结果封装

### Changed

- Migrated from custom autoloader to Composer's PSR-4 autoloading
- Improved type safety with PHP 8 strict types
- Refactored interfaces to separate files for better compatibility

### Fixed

- Fixed snake_case/camelCase property name conversion
- Fixed nullable parameter deprecation warnings in PHP 8
- Fixed interface loading order issues

### Performance

- Optimized batch processing for candidates
- Improved memory usage for large result sets

## [0.1.0] - 2024-01-15

### Added

- Initial project structure
- Basic data structures
- Initial filter implementations

[1.0.0]: https://github.com/x-algorithm-php/x-algorithm-php/releases/tag/v1.0.0
[0.1.0]: https://github.com/x-algorithm-php/x-algorithm-php/releases/tag/v0.1.0
