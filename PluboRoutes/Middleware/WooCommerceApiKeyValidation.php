<?php

namespace PluboRoutes\Middleware;

use WP_Error;
use WP_REST_Request;
use WP_User;

class WooCommerceApiKeyValidation implements MiddlewareInterface
{
    /**
     * Optional forced permission: read|write|read_write.
     *
     * @var string|null
     */
    private $requiredPermission;

    /**
     * @param string|null $requiredPermission Optional fixed permission.
     */
    public function __construct(?string $requiredPermission = null)
    {
        $normalized = is_string($requiredPermission) ? strtolower(trim($requiredPermission)) : null;
        $this->requiredPermission = in_array($normalized, ['read', 'write', 'read_write'], true)
            ? $normalized
            : null;
    }

    /**
     * Handles the incoming request and validates WooCommerce API credentials.
     *
     * @param WP_REST_Request $request The incoming request object.
     * @param callable        $next    The next middleware to execute.
     *
     * @return mixed
     */
    public function handle(WP_REST_Request $request, callable $next)
    {
        if (!$this->isWooCommerceAvailable()) {
            return $this->errorResponse('WooCommerce is not available', 500);
        }

        $credentials = $this->getCredentials($request);
        if (is_wp_error($credentials)) {
            return $credentials;
        }

        $apiKey = $this->getApiKey($credentials['consumer_key']);
        if (!$apiKey) {
            return $this->errorResponse('Invalid WooCommerce API credentials', 401);
        }

        if (!hash_equals((string) $apiKey->consumer_secret, $credentials['consumer_secret'])) {
            return $this->errorResponse('Invalid WooCommerce API credentials', 401);
        }

        if (!$this->hasPermission((string) $apiKey->permissions, $request->get_method())) {
            return $this->errorResponse('WooCommerce API key lacks required permissions', 403);
        }

        $user = get_user_by('id', (int) $apiKey->user_id);
        if (!($user instanceof WP_User) || !$user->exists()) {
            return $this->errorResponse('API key user is invalid', 401);
        }

        wp_set_current_user((int) $user->ID);
        do_action('plubo/woocommerce_api_key_authenticated', $user, $apiKey, $request);

        return $next($request);
    }

    /**
     * Checks if WooCommerce and related auth helpers are available.
     */
    private function isWooCommerceAvailable(): bool
    {
        return class_exists('WooCommerce') && function_exists('wc_api_hash');
    }

    /**
     * Extract credentials from Authorization header or request params.
     *
     * @param WP_REST_Request $request
     * @return array|WP_Error
     */
    private function getCredentials(WP_REST_Request $request)
    {
        $authHeader = $this->getAuthorizationHeader($request);

        if ($authHeader && preg_match('/^Basic\s+(.+)$/i', $authHeader, $matches)) {
            $decoded = base64_decode(trim($matches[1]), true);

            if ($decoded === false || strpos($decoded, ':') === false) {
                return $this->errorResponse('Invalid Basic credentials encoding', 400);
            }

            list($consumerKey, $consumerSecret) = explode(':', $decoded, 2);
            $consumerKey = sanitize_text_field($consumerKey);
            $consumerSecret = sanitize_text_field($consumerSecret);

            if ($consumerKey === '' || $consumerSecret === '') {
                return $this->errorResponse('Consumer key and secret are required', 401);
            }

            return [
                'consumer_key' => $consumerKey,
                'consumer_secret' => $consumerSecret,
            ];
        }

        $consumerKey = sanitize_text_field((string) $request->get_param('consumer_key'));
        $consumerSecret = sanitize_text_field((string) $request->get_param('consumer_secret'));

        if ($consumerKey === '' || $consumerSecret === '') {
            return $this->errorResponse('WooCommerce credentials are required', 401);
        }

        return [
            'consumer_key' => $consumerKey,
            'consumer_secret' => $consumerSecret,
        ];
    }

    /**
     * Retrieves the Authorization header from request/server.
     *
     * @param WP_REST_Request $request
     * @return string|null
     */
    private function getAuthorizationHeader(WP_REST_Request $request): ?string
    {
        $header = $request->get_header('authorization');
        if (is_string($header) && $header !== '') {
            return sanitize_text_field($header);
        }

        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return sanitize_text_field(wp_unslash($_SERVER['HTTP_AUTHORIZATION']));
        }

        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return sanitize_text_field(wp_unslash($_SERVER['REDIRECT_HTTP_AUTHORIZATION']));
        }

        return null;
    }

    /**
     * Finds an API key record by consumer key.
     *
     * @param string $consumerKey
     * @return object|null
     */
    private function getApiKey(string $consumerKey)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'woocommerce_api_keys';
        $hashedKey = \wc_api_hash($consumerKey);

        $sql = "SELECT key_id, user_id, permissions, consumer_secret FROM {$table} WHERE consumer_key = %s LIMIT 1";
        $record = $wpdb->get_row($wpdb->prepare($sql, $hashedKey));

        return $record ?: null;
    }

    /**
     * Validates permissions against required level.
     *
     * @param string $grantedPermission
     * @param string $method
     * @return bool
     */
    private function hasPermission(string $grantedPermission, string $method): bool
    {
        $granted = strtolower($grantedPermission);
        $required = $this->requiredPermission ?: $this->requiredPermissionForMethod($method);

        if ($required === 'read') {
            return in_array($granted, ['read', 'write', 'read_write'], true);
        }

        if ($required === 'write') {
            return in_array($granted, ['write', 'read_write'], true);
        }

        if ($required === 'read_write') {
            return $granted === 'read_write';
        }

        return false;
    }

    /**
     * Maps request method to required permission.
     */
    private function requiredPermissionForMethod(string $method): string
    {
        $method = strtoupper($method);
        return in_array($method, ['GET', 'HEAD', 'OPTIONS'], true) ? 'read' : 'write';
    }

    /**
     * Creates a standardized WP_Error response.
     *
     * @param string $message
     * @param int    $status
     *
     * @return WP_Error
     */
    private function errorResponse(string $message, int $status): WP_Error
    {
        $this->logError($message);
        return new WP_Error('woocommerce_api_key_validation_error', esc_html($message), ['status' => $status]);
    }

    /**
     * Logs errors only when WP_DEBUG is enabled.
     *
     * @param string $message
     */
    private function logError(string $message): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[WooCommerce API Key Validation Error] ' . $message);
        }
    }
}
