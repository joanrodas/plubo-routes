<?php

namespace PluboRoutes\Middleware;

class JsonTokenValidationMiddleware implements MiddlewareInterface
{
    private $secretKey;

    public function __construct($secretKey)
    {
        $this->secretKey = $secretKey;
    }

    public function handle($request, $next)
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];

            // Validate the token without external libraries
            if ($this->validateToken($token)) {
                return $next($request);
            } else {
                return new \WP_REST_Response(['error' => 'Invalid token'], 403);
            }
        } else {
            return new \WP_REST_Response(['error' => 'Authorization token missing'], 401);
        }
    }

    private function validateToken($token)
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return false; // Invalid token format
        }

        list($header, $payload, $signature) = $parts;

        // Decode the JWT segments
        $decodedHeader = json_decode(base64_decode($header), true);
        $decodedPayload = json_decode(base64_decode($payload), true);

        // Check the algorithm in the header
        if ($decodedHeader['alg'] !== 'HS256') {
            return false; // Only HS256 is supported
        }

        // Recreate the signature and compare it to the one provided
        $validSignature = $this->base64UrlEncode(hash_hmac('sha256', "$header.$payload", $this->secretKey, true));

        if ($validSignature !== $signature) {
            return false; // Signature does not match
        }

        // Additional claim validations (optional, e.g., exp claim)
        if (isset($decodedPayload['exp']) && $decodedPayload['exp'] < time()) {
            return false; // Token is expired
        }

        return true; // Token is valid
    }

    private function base64UrlEncode($data)
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }
}
