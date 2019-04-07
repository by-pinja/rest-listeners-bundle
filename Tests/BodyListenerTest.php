<?php
declare(strict_types = 1);

namespace Protacon\Bundle\RestListenersBundle\Tests;

use PHPUnit\Framework\TestCase;
use Protacon\Bundle\RestListenersBundle\EventListener\BodyListener;
use ReflectionException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use function sprintf;

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
        $event = new GetResponseEvent($httpKernel, clone $request, HttpKernelInterface::MASTER_REQUEST);

        $listener = new BodyListener();
        $listener->onKernelRequest($event);

        self::assertEquals($request, $event->getRequest());
    }

    /**
     * @dataProvider dataProviderTestThatListenerDoesNotDoAnythingIfRequestIsNotJson
     *
     * @param string $contentType
     * @param bool   $changed
     *
     * @throws ReflectionException
     */
    public function testThatListenerChangesRequestIfNeeded(?string $contentType, bool $changed): void
    {
        $content = '{"foo":"bar"}';

        /** @var HttpKernelInterface $httpKernel */
        $httpKernel = $this->getMockBuilder(HttpKernelInterface::class)->disableOriginalConstructor()->getMock();
        $request = new Request([], [], [], [], [], ['bar' => 'foo'], $content);
        $request->headers->set('Content-Type', $contentType);
        $event = new GetResponseEvent($httpKernel, clone $request, HttpKernelInterface::MASTER_REQUEST);

        $listener = new BodyListener();
        $listener->onKernelRequest($event);

        $message = sprintf(
            'Failed with content-type: %s - Request content type: %s',
            $contentType,
            $request->getContentType()
        );

        if ($changed) {
            self::assertNotEquals($request, $event->getRequest(), $message);
        } else {
            self::assertEquals($request, $event->getRequest(), $message);
        }
    }

    public function dataProviderTestThatListenerDoesNotDoAnythingIfRequestIsNotJson(): array
    {
        return [
            [null, true],
            ['text/javascript', false],
            ['application/json', true],
            ['text/javascript', false],
            ['text/txt', true],
            ['text/html', false],
        ];
    }
}
