# hyperf-grpc-client

hyperf grpc 客户端，支持服务发现、负载均衡、Dtm通过服务发现、服务链路跟踪等

*请先阅读hyperf文档 grpc服务一节 https://hyperf.wiki/3.0/#/zh-cn/grpc*

### 引用
```
composer require crayoon/hyperf-grpc-client
```

### 生成配置文件

```
// 若有引用 crayoon/hyperf-grpc 请使用crayoon/hyperf-grpc发布配置
// php bin/hyperf.php vendor:publish crayoon/hyperf-grpc
// 否则
php bin/hyperf.php vendor:publish crayoon/hyperf-grpc-client
```
### 使用
```php
// 客户端 继承 \Crayoon\HyperfGrpcClient\BaseGrpcClient 即可
class GoodsClient extends \Crayoon\HyperfGrpcClient\BaseGrpcClient {
    ...
}
```
### DTM

兼容dtm通过服务发现负载调用服务

*请先阅读hyperf文档 DTM一节 https://hyperf.wiki/3.0/#/zh-cn/distributed-transaction*
```
// 引入
composer require dtm/dtm-client
// 发布配置
php bin/hyperf.php vendor:publish dtm/dtm-client
```

修改 config/dependencies.php

```php
return [
    // 加入下面两个映射
    \DtmClient\Api\GrpcApi::class => \Crayoon\HyperfGrpcClient\DTM\GrpcApi::class,
    \Crayoon\HyperfGrpcClient\GrpcClientManager::class => \Crayoon\HyperfGrpcClient\DTM\GrpcClientManagerWithDtmClient::class,
];
```


