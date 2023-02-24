<?php
declare(strict_types=1);
/**
 * @author   crayoon
 * @contact  so.wo@foxmail.com
 */

namespace Crayoon\HyperfGrpcClient;

use Exception;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Grpc\StatusCode;
use Hyperf\LoadBalancer\LoadBalancerManager;
use Hyperf\LoadBalancer\Node;
use Hyperf\ServiceGovernance\DriverManager;
use Psr\Container\ContainerInterface;

class GrpcClientManager
{
    private string|null $credentials = null;
    private array $pools = [];

    protected DriverManager $governanceManager;
    protected ConfigInterface $config;
    protected StdoutLoggerInterface $logger;

    protected string $algo = '';
    protected array $server_alias = [];

    protected string $driver = "nacos";

    protected string $consul_host = '';

    public function __construct(protected ContainerInterface $container)
    {
        $this->config = $this->container->get(ConfigInterface::class);
        $this->governanceManager = $this->container->get(DriverManager::class);
        $this->logger = $this->container->get(StdoutLoggerInterface::class);
        // 获取配置
        $this->algo = $this->config->get("grpc.register.algo", "round-robin");
        $this->server_alias = $this->config->get("grpc.server_alias", []);
        $this->driver = $this->config->get("grpc.register.driver", "nacos");
        $this->consul_host = $this->config->get("services.drivers.consul.uri", "");
    }

    private function getDriverHost()
    {
        return $this->driver == "consul" ? $this->consul_host : '';
    }

    public function getNode(string $server): string
    {
        $server = $this->server_alias[$server] ?? $server;

        if ($governance = $this->governanceManager->get($this->driver)) {
            try {
                /**
                 * @var LoadBalancerManager $loadBalancerManager
                 */
                $loadBalancerManager = $this->container->get(LoadBalancerManager::class);
                $serverLB = $loadBalancerManager->getInstance($server, $this->algo);
                if (!$serverLB->isAutoRefresh()) {
                    $fun = function () use ($governance, $server) {
                        $nodes = [];
                        foreach ($governance->getNodes($this->getDriverHost(), $server, ['protocol' => 'grpc']) as $node) {
                            $nodes[] = new Node($node['host'], $node['port'], $node['weight'] ?? 1);
                        }
                        return $nodes;
                    };
                    $serverLB->setNodes($fun())->refresh($fun);
                }
                $node = $serverLB->select();
                return sprintf("%s:%d", $node->host, $node->port);
            } catch (\Throwable $throwable) {
                $this->logger->error(sprintf("Get Node[%s] From %s[%s] Fail! Because:%s", $server, $this->driver, $this->algo, $throwable->getMessage()));
            }
        }
        return "";
    }

    /**
     * get client
     * @param string $hostname
     * @param string $method
     * @return GrpcClient
     * @throws Exception
     */
    public function getClient(string $hostname, string $method): GrpcClient
    {
        if (empty($hostname)) {
            //获取服务名称
            $server = trim(current(explode(".", $method)), "/");
            //获取节点地址
            $hostname = $this->getNode($server);
        }

        if (empty($hostname)) {
            throw new Exception("hostname not found!");
        }
        if (!isset($this->pools[$hostname])) {
            $this->pools[$hostname] = new GrpcClient($hostname, [
                'credentials' => $this->credentials,
            ]);
        }
        return $this->pools[$hostname];
    }

    public function addClient(string $hostname, GrpcClient $client): void
    {
        $this->pools[$hostname] = $client;
    }

    public function removeClient(string $hostname, GrpcClient $client): void
    {
        if (isset($this->pools[$hostname])) {
            unset($this->pools[$hostname]);
        }
    }

    public function invoke(string $hostname, string $method, $argument, $deserialize, array $metadata = [], array $options = []): array
    {
        //响应
        try {
            return $this->getClient($hostname, $method)->invoke($method, $argument, $deserialize, $metadata, $options);
        } catch (Exception $e) {
            $this->logger->error(sprintf("Client[%s %s] Error:%s", $hostname, $method, $e->getMessage()));
            return [null, StatusCode::ABORTED];
        }
    }
}