<?php

namespace PluboRoutes\Middleware;

use WP_Error;
use WP_REST_Request;
use WP_User;

class ApplicationPasswordValidation implements MiddlewareInterface
{
    /**
     * Handles the incoming request and validates Basic Auth application password credentials.
     *
     * @param WP_REST_Request $request The incoming request object.
     * @param callable        $next    The next middleware to execute.
     *
     * @return mixed
     */
    public function handle(WP_REST_Request $request, callable $next)
    {
        $auth_header = $this->getAuthorizationHeader($request);

        if (!$auth_header) {
            return $this->errorResponse('Authorization header missing', 401);
        }

        if (!preg_match('/^Basic\s+(.+)$/i', $auth_header, $matches)) {
            return $this->errorResponse('Invalid Authorization header format', 400);
        }

        $decoded_credentials = base64_decode(trim($matches[1]), true);

        if ($decoded_credentials === false || strpos($decoded_credentials, ':') === false) {
            return $this->errorResponse('Invalid Basic credentials encoding', 400);
        }

        list($username, $application_password) = explode(':', $decoded_credentials, 2);
        $username = sanitize_text_field($username);
        $application_password = sanitize_text_field($application_password);

        if ($username === '' || $application_password === '') {
            return $this->errorResponse('Username and application password are required', 401);
        }

        if (!function_exists('wp_authenticate_application_password')) {
            return $this->errorResponse('Application passwords are not available in this WordPress version', 500);
        }

        $user = wp_authenticate_application_password(null, $username, $application_password);

        if (is_wp_error($user) || !($user instanceof WP_User) || !$user->exists()) {
            $this->logError('Application password authentication failed for user: ' . $username);
            return $this->errorResponse('Invalid application password credentials', 401);
        }

        // Set authenticated user for downstream middleware/permission checks.
        wp_set_current_user($user->ID);
        do_action('plubo/application_password_authenticated', $user, $request);

        return $next($request);
    }

    /**
     * Retrieves the Authorization header from the request/server.
     *
     * @param WP_REST_Request $request The incoming request object.
     *
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
     * Creates a standardized WP_Error response.
     *
     * @param string $message The error message.
     * @param int    $status  The HTTP status code.
     *
     * @return WP_Error
     */
    private function errorResponse(string $message, int $status): WP_Error
    {
        $this->logError($message);
        return new WP_Error('application_password_validation_error', esc_html($message), ['status' => $status]);
    }

    /**
     * Logs errors only when WP_DEBUG is enabled.
     *
     * @param string $message Error message to log.
     */
    private function logError(string $message): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Application Password Validation Error] ' . $message);
        }
    }
}
