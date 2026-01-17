<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Custom exception for Facebook Conversion API errors.
 *
 * Handles specific error codes and messages for Facebook API integration.
 */
class FacebookConversionApiException extends Exception
{
    /**
     * Error codes for Facebook Conversion API exceptions.
     */
    public const ERROR_INVALID_ACCESS_TOKEN = 'INVALID_ACCESS_TOKEN';
    public const ERROR_INVALID_PIXEL_ID = 'INVALID_PIXEL_ID';
    public const ERROR_INVALID_EVENT_DATA = 'INVALID_EVENT_DATA';
    public const ERROR_INVALID_USER_DATA = 'INVALID_USER_DATA';
    public const ERROR_API_REQUEST_FAILED = 'API_REQUEST_FAILED';
    public const ERROR_RATE_LIMIT_EXCEEDED = 'RATE_LIMIT_EXCEEDED';
    public const ERROR_NETWORK_ERROR = 'NETWORK_ERROR';
    public const ERROR_INVALID_RESPONSE = 'INVALID_RESPONSE';
    public const ERROR_MISSING_CONFIGURATION = 'MISSING_CONFIGURATION';
    public const ERROR_INVALID_EVENT_TYPE = 'INVALID_EVENT_TYPE';

    /**
     * The error code for this exception.
     */
    protected string $errorCode;

    /**
     * Additional context data for the exception.
     */
    protected array $context;

    /**
     * Create a new Facebook Conversion API exception instance.
     *
     * @param  string  $message
     * @param  string  $errorCode
     * @param  array  $context
     * @param  int  $code
     * @param  Throwable|null  $previous
     */
    public function __construct(
        string $message = '',
        string $errorCode = self::ERROR_API_REQUEST_FAILED,
        array $context = [],
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->errorCode = $errorCode;
        $this->context = $context;
    }

    /**
     * Get the error code.
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Get the context data.
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Create an exception for invalid access token.
     */
    public static function invalidAccessToken(string $message = 'Invalid Facebook access token'): self
    {
        return new self(
            $message,
            self::ERROR_INVALID_ACCESS_TOKEN,
            [],
            401
        );
    }

    /**
     * Create an exception for invalid pixel ID.
     */
    public static function invalidPixelId(string $pixelId = ''): self
    {
        return new self(
            'Invalid Facebook Pixel ID provided',
            self::ERROR_INVALID_PIXEL_ID,
            ['pixel_id' => $pixelId],
            400
        );
    }

    /**
     * Create an exception for invalid event data.
     */
    public static function invalidEventData(string $message, array $errors = []): self
    {
        return new self(
            $message,
            self::ERROR_INVALID_EVENT_DATA,
            ['errors' => $errors],
            400
        );
    }

    /**
     * Create an exception for invalid user data.
     */
    public static function invalidUserData(string $message, array $errors = []): self
    {
        return new self(
            $message,
            self::ERROR_INVALID_USER_DATA,
            ['errors' => $errors],
            400
        );
    }

    /**
     * Create an exception for API request failure.
     */
    public static function apiRequestFailed(string $message, array $response = []): self
    {
        return new self(
            $message,
            self::ERROR_API_REQUEST_FAILED,
            ['response' => $response],
            500
        );
    }

    /**
     * Create an exception for rate limit exceeded.
     */
    public static function rateLimitExceeded(string $message = 'Facebook API rate limit exceeded'): self
    {
        return new self(
            $message,
            self::ERROR_RATE_LIMIT_EXCEEDED,
            [],
            429
        );
    }

    /**
     * Create an exception for network errors.
     */
    public static function networkError(string $message, ?Throwable $previous = null): self
    {
        return new self(
            $message,
            self::ERROR_NETWORK_ERROR,
            [],
            503,
            $previous
        );
    }

    /**
     * Create an exception for invalid API response.
     */
    public static function invalidResponse(string $message, array $response = []): self
    {
        return new self(
            $message,
            self::ERROR_INVALID_RESPONSE,
            ['response' => $response],
            500
        );
    }

    /**
     * Create an exception for missing configuration.
     */
    public static function missingConfiguration(string $configKey): self
    {
        return new self(
            "Missing Facebook Conversion API configuration: {$configKey}",
            self::ERROR_MISSING_CONFIGURATION,
            ['config_key' => $configKey],
            500
        );
    }

    /**
     * Create an exception for invalid event type.
     */
    public static function invalidEventType(string $eventType): self
    {
        return new self(
            "Invalid Facebook event type: {$eventType}",
            self::ERROR_INVALID_EVENT_TYPE,
            ['event_type' => $eventType],
            400
        );
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): JsonResponse
    {
        $statusCode = $this->code ?: 500;

        return response()->json([
            'message' => $this->message,
            'error_code' => $this->errorCode,
            'context' => $this->context,
        ], $statusCode);
    }

    /**
     * Report the exception.
     */
    public function report(): void
    {
        logger()->error('Facebook Conversion API Error', [
            'message' => $this->message,
            'error_code' => $this->errorCode,
            'context' => $this->context,
            'trace' => $this->getTraceAsString(),
        ]);
    }
}
