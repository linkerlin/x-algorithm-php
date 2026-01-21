# X Algorithm PHP

[![PHP Version](https://img.shields.io/badge/php-^8.1-blue)](https://packagist.org/packages/x-algorithm-php/x-algorithm)
[![License](https://img.shields.io/badge/License-Apache--2.0-yellowgreen)](LICENSE)
[![Tests](https://img.shields.io/badge/Tests-44%20passed-green)](https://github.com/x-algorithm-php/x-algorithm-php/actions)

PHP 实现 X（Twitter）For You 信息流推荐算法，完整移植自原始的 Rust/Python 混合架构。

## 功能特性

- **完整的推荐流程编排** - 实现了 HomeMixer 服务的核心逻辑
- **多种数据源支持** - Thunder（站内内容）和 Phoenix Retrieval（站外内容）
- **灵活的内容过滤** - 年龄过滤、去重、静音词、作者黑名单等
- **多维度评分系统** - ML 预测评分、加权评分、作者多样性评分
- **批处理优化** - 支持高效的批量数据处理

## 安装

```bash
composer require x-algorithm-php/x-algorithm
```

## 快速开始

```php
<?php

require_once 'vendor/autoload.php';

use XAlgorithm\Algorithm;

// 基础推荐获取
$recommendations = Algorithm::getRecommendations(
    userId: 12345,
    countryCode: 'US',
    languageCode: 'en'
);

print_r($recommendations);
```

## 高级用法

```php
<?php

use XAlgorithm\HomeMixer\HomeMixerService;

// 自定义配置
$service = new HomeMixerService([
    'default_limit' => 50,
    'max_age_seconds' => 86400 * 7,
    'enable_diversity' => true,
    'diversity_decay_factor' => 0.9,
]);

// 获取推荐结果
$query = new \XAlgorithm\Core\DataStructures\ScoredPostsQuery([
    'user_id' => 12345,
    'client_app_id' => 1,
    'country_code' => 'US',
    'language_code' => 'en',
]);

$results = $service->getHomeMix($query, 30);
```

## 架构概览

```
用户请求 → HomeMixer (编排)
  ├─→ 查询水合 (用户特征、行为序列)
  ├─→ 候选来源
  │   ├─→ Thunder (站内内容)
  │   └─→ Phoenix Retrieval (站外内容)
  ├─→ 水合 (补充元数据)
  ├─→ 过滤 (年龄、去重、静音等)
  ├─→ 评分 (ML 预测 + 加权)
  ├─→ 多样性调整
  └─→ Top-K 选择 → 最终推荐
```

## 目录结构

```
src/
├── Core/
│   ├── DataStructures/       # 数据结构
│   │   ├── PostCandidate.php      # 帖子候选对象
│   │   ├── PhoenixScores.php      # ML 预测分数
│   │   ├── ScoredPostsQuery.php   # 用户查询请求
│   │   └── UserFeatures.php       # 用户特征
│   ├── Filters/              # 过滤器
│   │   ├── AgeFilter.php          # 年龄过滤
│   │   ├── DropDuplicatesFilter.php  # 去重过滤
│   │   ├── MutedKeywordFilter.php  # 静音词过滤
│   │   └── ...
│   ├── Scorers/              # 评分器
│   │   ├── WeightedScorer.php     # 加权评分
│   │   ├── PhoenixScorer.php      # ML 评分
│   │   └── AuthorDiversityScorer.php  # 多样性评分
│   ├── Pipeline/             # 管道
│   │   └── CandidatePipeline.php  # 候选处理管道
│   └── Sources/              # 数据源
│       ├── ThunderSource.php      # 站内内容源
│       └── PhoenixSource.php      # ML 内容源
├── HomeMixer/
│   └── HomeMixerService.php       # 主编排服务
├── ML/
│   └── Phoenix/                  # ML 客户端
│       ├── PhoenixClientInterface.php
│       └── PhoenixClient.php
└── Utility/
    └── RequestIdGenerator.php     # ID 生成工具
```

## 测试

```bash
# 运行所有测试
composer test

# 运行静态分析
composer analyze

# 代码风格检查
composer lint
```

## 版本兼容性

| PHP 版本 | 支持状态 |
|----------|----------|
| 8.1.x    | ✓ 支持   |
| 8.2.x    | ✓ 支持   |
| 8.3.x    | ✓ 支持   |

## 贡献

欢迎提交 Pull Request 和 Issue！

## 许可证

本项目采用 Apache-2.0 许可证，详见 [LICENSE](LICENSE) 文件。

## 参考

- [X For You Feed Architecture](https://blog.x.com/engineering/en_us/topics/architecture/2023/x-recommendation-architecture)
- [Home Mixer Service](https://github.com/twitter-archive/home-mixer)
- [Phoenix ML System](https://github.com/twitter-archive/phoenix)
