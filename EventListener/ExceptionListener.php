<?php
declare(strict_types = 1);

namespace Protacon\Bundle\RestListenersBundle\EventListener;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\ORMException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Throwable;
use function class_exists;
use function get_class;
use function json_encode;

class ExceptionListener implements EventSubscriberInterface
{
    private $tokenStorage;

    private $environment;

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => [
                ['onKernelException', -100],
            ],
        ];
    }

    public function __construct(?TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
        $this->environment = $_SERVER['APP_ENV'];
    }

    public function onKernelException(GetResponseForExceptionEvent $event): void
    {
        // Get exception from current event
        $exception = $event->getException();

        // Create new response
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setStatusCode($this->getStatusCode($exception));
        $response->setContent(json_encode($this->getErrorMessage($exception, $response)));

        // Send the modified response object to the event
        $event->allowCustomResponseCode();
        $event->setResponse($response);
    }

    private function getStatusCode(Throwable $exception): int
    {
        if ($this->tokenStorage !== null) {
            // Get current token, and determine if request is made from logged in user or not
            $token = $this->tokenStorage->getToken();
            $isUser = !($token === null || $token instanceof AnonymousToken);
        }

        return $this->determineStatusCode($exception, $isUser ?? false);
    }

    private function getErrorMessage(Throwable $exception, Response $response): array
    {
        // Set base of error message
        $error = [
            'message' => $this->getExceptionMessage($exception),
            'code' => $exception->getCode(),
            'status' => $response->getStatusCode(),
        ];

        // Attach more info to error response in dev environment
        if ($this->environment === 'dev') {
            $error += [
                'debug' => [
                    'exception' => get_class($exception),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'message' => $exception->getMessage(),
                    'trace' => $exception->getTrace(),
                    'traceString' => $exception->getTraceAsString(),
                ],
            ];
        }

        return $error;
    }

    private function determineStatusCode(Throwable $exception, bool $isUser): int
    {
        // Default status code is always 500
        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;

        // HttpExceptionInterface is a special type of exception that holds status code and header details
        if ($exception instanceof AuthenticationException) {
            $statusCode = Response::HTTP_UNAUTHORIZED;
        } elseif ($exception instanceof AccessDeniedException) {
            $statusCode = $isUser ? Response::HTTP_FORBIDDEN : Response::HTTP_UNAUTHORIZED;
        } elseif ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
        }

        return $statusCode;
    }

    private function getExceptionMessage(Throwable $exception): string
    {
        return $this->environment === 'dev'
            ? $exception->getMessage()
            : $this->getMessageForProductionEnvironment($exception);
    }

    private function getMessageForProductionEnvironment(Throwable $exception): string
    {
        $message = $exception->getMessage();

        // Within AccessDeniedHttpException we need to hide actual real message from users
        if ($exception instanceof AccessDeniedHttpException || $exception instanceof AccessDeniedException) {
            $message = 'Access denied.';
        } elseif ((class_exists(DBALException::class) && $exception instanceof DBALException)
            || (class_exists(ORMException::class ) && $exception instanceof ORMException)
        ) { // Database errors
            $message = 'Database error.';
        }

        return $message;
    }
}
