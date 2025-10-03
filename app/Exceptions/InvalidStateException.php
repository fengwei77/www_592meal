<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when LINE OAuth state parameter validation fails
 *
 * This exception is used to prevent CSRF attacks during LINE Login flow
 */
class InvalidStateException extends Exception
{
    /**
     * Create a new InvalidStateException instance
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message = 'Invalid state parameter',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Report the exception
     *
     * @return bool|null
     */
    public function report(): ?bool
    {
        // Log the exception for security monitoring
        return true;
    }

    /**
     * Render the exception as an HTTP response
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        return redirect()->route('login')
            ->with('error', '安全驗證失敗，請重新登入');
    }
}
