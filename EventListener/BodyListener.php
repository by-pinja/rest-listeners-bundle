<?php

namespace Protacon\Bundle\RestListenerBundle;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class BodyListener
{
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
    }
}
