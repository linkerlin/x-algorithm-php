# X Algorithm PHP 移植项目

## 项目概述

本项目是 X（Twitter）For You 信息流推荐算法的 PHP 实现，完整移植自原始的 Rust/Python 混合架构。

## 原始架构

### 组件分布

| 组件 | 语言 | 功能描述 |
|------|------|----------|
| home-mixer | Rust | 编排层，协调整个推荐流程 |
| thunder | Rust | 站内内容处理，从关注的账号获取帖子 |
| candidate-pipeline | Rust | 候选管道，处理和过滤候选内容 |
| phoenix | Python | ML 推荐系统，使用 Grok 变压器模型 |

### 数据流

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

## PHP 架构

### 目录结构

```
php-implementation/
├── src/
│   ├── Core/
│   │   ├── DataStructures/
│   │   │   ├── PostCandidate.php      # 帖子候选对象
│   │   │   ├── PhoenixScores.php      # ML 预测分数
│   │   │   ├── ScoredPostsQuery.php   # 用户查询请求
│   │   │   └── UserFeatures.php       # 用户特征
│   │   ├── Pipeline/
│   │   │   ├── CandidatePipeline.php  # 候选管道
│   │   │   └── Interfaces/            # 管道接口定义
│   │   ├── Filters/                   # 过滤器实现
│   │   ├── Scorers/                   # 评分器实现
│   │   ├── Selectors/                 # 选择器实现
│   │   ├── Hydrators/                 # 水合器实现
│   │   └── Sources/                   # 数据源实现
│   ├── HomeMixer/
│   │   └── HomeMixerService.php       # 主编排服务
│   ├── ML/
│   │   └── Phoenix/                   # ML 客户端
│   ├── Utility/
│   │   └── RequestIdGenerator.php     # ID 生成工具
│   └── VisibilityFiltering/           # 可视性过滤
├── public/
│   └── index.php                      # 入口文件
├── autoload.php                        # 自动加载
├── composer.json                       # 依赖配置
└── Algorithm.php                       # 便捷入口
```

## 迁移解决的问题

### 1. 类型系统转换

**问题**: Rust 强类型系统 vs PHP 动态类型

**解决方案**: 使用 PHP 8 类型声明模拟强类型
- 严格类型声明 `declare(strict_types=1)`
- 属性类型提示
- 构造函数类型检查

### 2. 并发模型转换

**问题**: Rust async/await vs PHP 同步模型

**解决方案**: 保持同步模型，使用批处理优化
- PHP 不支持原生 async，使用同步执行
- 通过缓存和批处理提升性能
- ML 调用通过外部服务异步化

### 3. trait 和接口转换

**问题**: Rust trait 对象 vs PHP 接口

**解决方案**: 定义清晰的 PHP 接口
```php
interface FilterInterface {
    public function filter(ScoredPostsQuery $query, array $candidates): FilterResult;
}
```

### 4. 枚举和常量转换

**问题**: Rust 枚举 vs PHP 类常量

**解决方案**: 使用类常量定义
```php
class FilteredReason {
    public const NONE = 0;
    public const AGE = 2;
    // ...
}
```

### 5. 性能优化

**问题**: Rust 高性能 vs PHP 解释型

**解决方案**:
- 使用 PHP 8 JIT
- Redis 缓存热点数据
- ML 推理外置到专门服务
- 批量处理减少开销

## 使用方法

### 基本使用

```php
require_once 'autoload.php';

use XAlgorithm\Algorithm;

$recommendations = Algorithm::getRecommendations(
    userId: 12345,
    countryCode: 'US',
    languageCode: 'en'
);

print_r($recommendations);
```

### 高级配置

```php
use XAlgorithm\HomeMixer\HomeMixerService;

$service = new HomeMixerService([
    'default_limit' => 50,
    'max_age_seconds' => 86400 * 7,
    'enable_diversity' => true,
    'diversity_decay_factor' => 0.9,
    'oon_boost_factor' => 1.0,
]);

$recommendations = $service->getPersonalizedRecommendations(
    userId: 12345,
    clientAppId: 1,
    inNetworkOnly: false,
    isBottomRequest: false
);
```

### 自定义管道

```php
use XAlgorithm\Core\Pipeline\CandidatePipeline;
use XAlgorithm\Core\Filters\AgeFilter;
use XAlgorithm\Core\Scorers\WeightedScorer;

$pipeline = new CandidatePipeline();
$pipeline->addFilter(new AgeFilter(86400));
$pipeline->addScorer(new WeightedScorer());
$pipeline->setSelector(new TopKScoreSelector());

$result = $pipeline->execute($query, 50);
```

## ML 集成

### 使用真实 ML 服务

```php
use XAlgorithm\ML\Phoenix\RemotePhoenixClient;

$client = new RemotePhoenixClient('http://phoenix-service:8080');
$scorer = new PhoenixScorer($client);
```

### 使用模拟客户端（测试）

```php
use XAlgorithm\ML\Phoenix\MockPhoenixClient;

$client = new MockPhoenixClient();
$scorer = new PhoenixScorer($client);
```

## 测试

```bash
# 运行测试
composer test

# 代码分析
composer analyze
```

## 性能考量

1. **缓存策略**: 使用 Redis 缓存用户特征和候选结果
2. **批处理**: 合并多个 ML 预测请求
3. **预计算**: 离线计算用户特征和向量
4. **JIT**: 启用 PHP 8 JIT 加速

## 后续优化

1. 集成 Swoole 或 ReactPHP 实现异步处理
2. 添加请求级缓存
3. 实现特征工程流水线
4. 添加性能监控指标

## 移植过程中遇到的问题及解决方案

### 1. 命名空间声明顺序问题

**问题**: PHP 要求命名空间声明语句必须是文件的第一条语句，之前放置 `require_once` 会导致错误。

**解决方案**: 修改自动加载器（autoload.php），使用 PSR-4 标准自动加载机制，不再需要在每个文件中手动包含依赖文件。

### 2. 接口未找到错误

**问题**: 实现接口的类在接口文件加载之前被加载，导致 "Interface not found" 错误。

**解决方案**: 将包含多个接口的 PipelineInterfaces.php 文件拆分为独立的接口文件，使自动加载器能够正确找到并加载接口定义。

### 3. PHP 8 可空参数弃用警告

**问题**: 使用隐式可空参数（如 `array $param = null`）在 PHP 8 中会产生弃用警告。

**解决方案**: 使用显式可空类型声明（`?array $param = null`）。

**修复的文件**:
- RequestIdGenerator.php
- ThunderSource.php

### 4. snake_case 与 camelCase 属性名转换

**问题**: PhoenixScores 类和 WeightedScorer 使用 snake_case 字段名（如 `favorite_score`），但 PHP 类属性使用 camelCase（如 `favoriteScore`）。

**解决方案**: 在 PhoenixScores 和 WeightedScorer 类中添加 `toCamelCase()` 辅助方法，将 snake_case 转换为 camelCase 后访问属性。

### 5. 缺少依赖类

**问题**: 测试和运行时缺少一些依赖类，如 MockPhoenixClient、PhoenixClientInterface 等。

**解决方案**: 创建缺失的依赖文件：
- src/ML/Phoenix/PhoenixClientInterface.php
- src/ML/Phoenix/MockPhoenixClient.php

## 测试结果

运行测试套件结果：
- 通过: 44
- 失败: 0
- 总计: 44

所有核心功能测试通过，包括：
- RequestIdGenerator (ID 生成)
- PostCandidate (帖子候选)
- PhoenixScores (ML 评分)
- ScoredPostsQuery (查询对象)
- AgeFilter (年龄过滤)
- DropDuplicatesFilter (去重过滤)
- MutedKeywordFilter (静音词过滤)
- WeightedScorer (加权评分)
- AuthorDiversityScorer (作者多样性评分)
- HomeMixerService (主服务编排)

```