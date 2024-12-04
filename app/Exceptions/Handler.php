<?php

namespace App\Exceptions;

use Exception;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        ValidationException::class,
        NotFoundHttpException::class,
    ];

    public function __construct(Request $request, LoggerInterface $logger)
    {
        $this->request = $request;
        $this->logger = $logger;
    }

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Throwable  $e
     * @return void
     */
    public function report(Throwable $e)
    {
        if ($e instanceof HttpException) {
            $statusCode = $e->getStatusCode();
            if ($statusCode < 500 && $statusCode >= 300) {
                return;
            }
        }
        if (app()->bound('sentry') && $this->shouldReport($e)) {
            app('sentry')->captureException($e);
        }

        if (parent::shouldntReport($e)) {
            return;
        }

        if (method_exists($e, 'report')) {
            return $e->report();
        }

        try {
            $logger = app(LoggerInterface::class);
        } catch (Exception $ex) {
            throw $e; // throw the original exception
        }

        $logger->error($e, ['exception' => $e,'request'=>$this->request->getContent(),"path"=>$this->request->getPathInfo(),"ip"=>$this->request->getClientIps(), "headers"=>(array)$this->request->headers]);

        //parent::report($e);
    }


    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $e)
    {
        return parent::render($request, $e);
    }
}
