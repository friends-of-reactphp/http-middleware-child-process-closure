<?php

namespace FriendsOfReact\Http\Middleware\ChildProcess\Closure;

use React\EventLoop\LoopInterface;
use WyriHaximus\React\ChildProcess\Closure\ClosureChild;
use WyriHaximus\React\ChildProcess\Closure\MessageFactory;
use WyriHaximus\React\ChildProcess\Messenger\Factory as MessengerFactory;
use WyriHaximus\React\ChildProcess\Messenger\Messages\Payload;
use WyriHaximus\React\ChildProcess\Messenger\Messenger;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use React\Promise;
use Psr\Http\Message\ServerRequestInterface;

final class ChildProcessClosure implements MiddlewareInterface
{
    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var callable
     */
    private $callback;

    /**
     * @param LoopInterface $loop
     */
    public function __construct(LoopInterface $loop, callable $callback)
    {
        $this->loop = $loop;
        $this->callback = $callback;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $wrapperCallback = $this->callback;
        $callback = function () use ($request, $wrapperCallback) {
            return [
                'result' => $wrapperCallback($request),
            ];
        };

        return MessengerFactory::parentFromClass(
            ClosureChild::class,
            $this->loop
        )->then(function (Messenger $messenger) use ($callback) {
            $callback = $this->callback;
            return $messenger->rpc(MessageFactory::rpc($callback));
        })->then(function (Payload $payload) {
            return $payload['result'];
        });
    }
}
