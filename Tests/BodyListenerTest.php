<?php

namespace Protacon\Bundle\RestListenersBundle\Tests;

use PHPUnit\Framework\TestCase;
use Protacon\Bundle\RestListenersBundle\EventListener\BodyListener;
use ReflectionException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class BodyListenerTest extends TestCase
{
    /**
     * @throws ReflectionException
     */
    public function testListenerDoesNotDoAnythingWithoutContent(): void
    {
        /** @var HttpKernelInterface $httpKernel */
        $httpKernel = $this->getMockBuilder(HttpKernelInterface::class)->disableOriginalConstructor()->getMock();
        $request = new Request();
        $event = new GetResponseEvent($httpKernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $listener = new BodyListener();
        $listener->onKernelRequest($event);

        $this->assertSame($request, $event->getRequest());
    }
}
