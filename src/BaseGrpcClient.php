<?php
declare(strict_types=1);
/**
 * @author   crayoon
 * @contact  so.wo@foxmail.com
 */

namespace Crayoon\HyperfGrpcClient;

use Google\Protobuf\Internal\Message;
use Hyperf\Context\Context;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Grpc\StatusCode;
use Hyperf\Tracer\SpanStarter;
use OpenTracing\Tracer;
use Psr\Container\ContainerInterface;
use Swoole\Http2\Response;
use const OpenTracing\Formats\TEXT_MAP;

class BaseGrpcClient {
    use SpanStarter;

    protected Tracer $tracer;

    protected ConfigInterface $config;

    protected string $hostname = "";
    protected bool $is_tracer = false;

    public function __construct(protected ContainerInterface $container) {
        $this->tracer = $this->container->get(Tracer::class);
        $this->config = $this->container->get(ConfigInterface::class);
    }

    public function _simpleRequest(string $method, Message $argument, array $deserialize, array $metadata = [], array $options = []): array {

        $trace_enable = $this->config->get("grpc.trace.enable", true);

        if ($trace_enable && $root = Context::get('tracer.root')) {
            $carrier = [];
            // Injects the context into the wire
            $this->tracer->inject(
                $root->getContext(),
                TEXT_MAP,
                $carrier
            );
            $metadata["tracer.carrier"] = json_encode($carrier);
        }

        $client = $this->container->get(GrpcClientManager::class);

        /**
         *
         * @var Response $resp
         */
        list($reply, $status, $resp) = $client->invoke($this->hostname, $method, $argument, $deserialize, $metadata, $options);
        // 判断客户端请求也是否需要追踪
        if ($trace_enable && $this->is_tracer) {
            $key  = "GRPC Client Response [RPC] {$method}";
            $span = $this->startSpan($key);
            $span->setTag('rpc.path', $method);
            if ($resp->headers) foreach ($resp->headers as $key => $value) {
                $span->setTag('rpc.headers' . '.' . $key, $value);
            }
            if ($status != StatusCode::OK) {
                $span->setTag('error', true);
            }
            $span->setTag('rpc.status', $status);
            $span->finish();
        }
        return [$reply, $status, $resp];
    }
}