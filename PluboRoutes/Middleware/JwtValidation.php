<?php

namespace PluboRoutes\Middleware;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class JwtValidation implements MiddlewareInterface
{
    /**
     * Secret key used for signing the JWT.
     *
     * @var string
     */
    private $secret_key;
    private ?string $expected_issuer;
    private ?string $expected_audience;
    private int $leeway;

    /**
     * Constructor to initialize the secret key and optional claims.
     *
     * @param string      $secret_key           The secret key used to validate the token.
     * @param string|null $expected_issuer      (Optional) The expected issuer ('iss') claim.
     * @param string|null $expected_audience    (Optional) The expected audience ('aud') claim.
     * @param int         $leeway               (Optional) Leeway in seconds for time-based claims.
     */
    public function __construct(
        string $secret_key,
        ?string $expected_issuer = null,
        ?string $expected_audience = null,
        int $leeway = 0
    ) {
        $this->secret_key = $secret_key;
        $this->expected_issuer = $expected_issuer;
        $this->expected_audience = $expected_audience;
        $this->leeway = $leeway;
    }

    /**
     * Handles the incoming request and validates the JWT token.
     *
     * @param mixed $request The incoming request object.
     * @param callable $next The next middleware to execute.
     * 
     * @return WP_REST_Response|WP_Error The response after validation.
     */
    public function handle(WP_REST_Request $request, callable $next)
    {
        $authHeader = $this->get_authorization_header();

        if (!$authHeader) {
            return $this->error_response('Authorization token missing', 401);
        }

        if (preg_match('/Bearer\s(\S+)/i', $authHeader, $matches)) {
            $token = sanitize_text_field($matches[1]);

            // Validate the token
            $validationResult = $this->validateToken($token);

            if (is_wp_error($validationResult)) {
                return $validationResult;
            }

            // Token is valid; proceed to the next middleware or request handler
            return $next($request);
        } else {
            return $this->error_response('Invalid Authorization header format', 400);
        }
    }

    /**
     * Retrieves the Authorization header from the request.
     *
     * @return string|null The Authorization header if present, null otherwise.
     */
    private function get_authorization_header(): ?string
    {
        // Check $_SERVER superglobal.
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return sanitize_text_field(wp_unslash($_SERVER['HTTP_AUTHORIZATION']));
        }

        // Some servers use REDIRECT_HTTP_AUTHORIZATION.
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return sanitize_text_field(wp_unslash($_SERVER['REDIRECT_HTTP_AUTHORIZATION']));
        }

        return null;
    }

    /**
     * Validates the JWT token.
     *
     * @param string $token The JWT token to validate.
     * @return true|WP_Error Returns true if valid, WP_Error otherwise.
     */
    private function validateToken(string $token)
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return $this->error_response('Invalid token format', 400);
        }

        list($header, $payload, $signature) = $parts;

        // Decode the JWT segments safely
        $decodedHeader = $this->base64UrlDecode($header);
        $decodedPayload = $this->base64UrlDecode($payload);

        if (!$decodedHeader || !$decodedPayload) {
            return $this->error_response('Invalid token encoding', 400);
        }

        $headerArray = json_decode($decodedHeader, true);
        $payloadArray = json_decode($decodedPayload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->error_response('Invalid JSON in token', 400);
        }

        // Validate the algorithm
        if (empty($headerArray['alg']) || $headerArray['alg'] !== 'HS256') {
            return $this->error_response('Unsupported token algorithm', 400);
        }

        // Recreate the signature
        $dataToSign = "$header.$payload";
        $expectedSignature = $this->base64UrlEncode(hash_hmac('sha256', $dataToSign, $this->secret_key, true));

        if (!hash_equals($expectedSignature, $signature)) {
            return $this->error_response('Signature verification failed', 403);
        }

        // 1. Expiration time (exp)
        if (isset($payloadArray['exp'])) {
            if (!is_numeric($payloadArray['exp'])) {
                return $this->error_response('Invalid expiration claim', 400);
            }
            if (($payloadArray['exp'] + $this->leeway) < time()) {
                return $this->error_response('Token has expired', 401);
            }
        }

        // 2. Not Before (nbf)
        if (isset($payloadArray['nbf'])) {
            if (!is_numeric($payloadArray['nbf'])) {
                return $this->error_response('Invalid not before claim', 400);
            }
            if (($payloadArray['nbf'] - $this->leeway) > time()) {
                return $this->error_response('Token is not yet valid', 401);
            }
        }

        // 3. Issued At (iat)
        if (isset($payloadArray['iat'])) {
            if (!is_numeric($payloadArray['iat'])) {
                return $this->error_response('Invalid issued at claim', 400);
            }
            if (($payloadArray['iat'] - $this->leeway) > time()) {
                return $this->error_response('Token was issued in the future', 400);
            }
        }

        // 4. Issuer (iss)
        if ($this->expected_issuer !== null) {
            if (!isset($payloadArray['iss']) || !is_string($payloadArray['iss'])) {
                return $this->error_response('Issuer claim missing or invalid', 403);
            }
            if ($payloadArray['iss'] !== $this->expected_issuer) {
                return $this->error_response('Invalid issuer', 403);
            }
        }

        // 5. Audience (aud)
        if ($this->expected_audience !== null) {
            if (!isset($payloadArray['aud'])) {
                return $this->error_response('Audience claim missing', 403);
            }

            // 'aud' can be a string or an array of strings
            $audience = $payloadArray['aud'];
            $isValidAudience = false;

            if (is_string($audience) && $audience === $this->expected_audience) {
                $isValidAudience = true;
            } elseif (is_array($audience) && in_array($this->expected_audience, $audience, true)) {
                $isValidAudience = true;
            }

            if (!$isValidAudience) {
                return $this->error_response('Invalid audience', 403);
            }
        }

        return true;
    }

    /**
     * Creates a standardized WP_Error response.
     *
     * @param string $message The error message.
     * @param int $status The HTTP status code.
     * 
     * @return WP_Error The WP_Error object.
     */
    private function error_response(string $message, int $status): WP_Error
    {
        $this->log_error($message);
        return new WP_Error('jwt_validation_error', esc_html($message), ['status' => $status]);
    }

    private function log_error(string $message): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[JWT Validation Error] ' . $message);
        }
    }

    /**
     * Encodes data using Base64 URL encoding.
     *
     * @param string $data The data to encode.
     * 
     * @return string The Base64 URL encoded data.
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Decodes Base64 URL encoded data.
     *
     * @param string $data The Base64 URL encoded data.
     * 
     * @return string|false The decoded data or false on failure.
     */
    private function base64UrlDecode(string $data)
    {
        $padding = 4 - (strlen($data) % 4);
        if ($padding < 4) {
            $data .= str_repeat('=', $padding);
        }
        $decoded = base64_decode(strtr($data, '-_', '+/'), true);
        return $decoded;
    }
}
