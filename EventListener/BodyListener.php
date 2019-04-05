<?php
declare(strict_types = 1);

namespace Protacon\Bundle\RestListenersBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use function in_array;
use function json_decode;

class BodyListener
{
    public function onKernelRequest(GetResponseEvent $event): void
    {
        // Get current request
        $request = $event->getRequest();

        // Request content is empty so assume that it's ok - probably DELETE or OPTION request
        if (empty($request->getContent())) {
            return;
        }

        // If request is JSON type convert it to request parameters
        if (in_array($request->getContentType(), [null, 'json', 'txt'], true)) {
            $request->request->replace(json_decode($request->getContent(), true));
        }
    }
}
