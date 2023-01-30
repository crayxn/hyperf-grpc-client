<?php /** @noinspection PhpMultipleClassDeclarationsInspection */
declare(strict_types=1);
/**
 * @author   crayoon
 * @contact  so.wo@foxmail.com
 */

namespace Crayoon\HyperfGrpcClient;

use Google\Protobuf\Internal\Message;
use Hyperf\GrpcClient\BaseClient;

class GrpcClient extends BaseClient {
    private string $hostname;

    public function __construct(string $hostname, array $options = []) {
        parent::__construct($hostname, $options);
        $this->hostname = $hostname;
    }

    public function invoke(
        string  $method,
        Message $argument,
                $deserialize,
        array   $metadata = [],
        array   $options = []
    ): array {
        return $this->_simpleRequest($method, $argument, $deserialize, $metadata, $options);
    }

    public function getHostname(): string {
        return $this->hostname;
    }
}