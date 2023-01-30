<?php
declare(strict_types=1);
/**
 * @author   crayoon
 * @contact  so.wo@foxmail.com
 */

namespace Crayoon\HyperfGrpcClient\DTM;

use Crayoon\HyperfGrpcClient\GrpcClient;
use DtmClient\Grpc\Message\DtmGidReply;
use Google\Protobuf\GPBEmpty;
use Google\Protobuf\Internal\Message;

class Client extends GrpcClient {
    protected const SERVICE = '/dtmgimp.Dtm/';

    public function newGid(): DtmGidReply {
        [$reply] = $this->_simpleRequest(
            self::SERVICE . 'NewGid',
            new GPBEmpty(),
            [DtmGidReply::class, 'decode']
        );
        return $reply;
    }

    public function transCallDtm(Message $argument, string $operation, string $replyClass = ''): array {
        list($reply, $status) = $this->_simpleRequest(
            self::SERVICE . ucfirst($operation),
            $argument,
            [$replyClass ?: GPBEmpty::class, 'decode']
        );
        return compact("status", "reply");
    }
}