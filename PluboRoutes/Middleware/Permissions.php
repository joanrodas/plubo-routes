<?php

namespace PluboRoutes\Middleware;

use WP_REST_Request;
use WP_Error;

class Permissions implements MiddlewareInterface
{
    private $type;
    private $allowed_roles;
    private $allowed_capabilities;
    private $disallowed_roles;
    private $disallowed_capabilities;

    public function __construct(
        string $type = '',
        array $allowed_roles = [],
        array $allowed_capabilities = [],
        array $disallowed_roles = [],
        array $disallowed_capabilities = []
    ) {
        $this->type = $type;
        $this->allowed_roles = $allowed_roles;
        $this->allowed_capabilities = $allowed_capabilities;
        $this->disallowed_roles = $disallowed_roles;
        $this->disallowed_capabilities = $disallowed_capabilities;
    }

    public function handle(WP_REST_Request $request, callable $next)
    {
        $user = wp_get_current_user();

        // Check guest, registered, or open
        if ($this->type) {
            $is_guest = !$user->exists();
            if (($this->type === 'registered' && $is_guest) || ($this->type === 'guest' && !$is_guest)) {
                return $this->forbiddenResponse('Access restricted');
            }
        }

        // Check disallowed roles
        if (!empty($this->disallowed_roles) && array_intersect($user->roles, $this->disallowed_roles)) {
            return $this->forbiddenResponse('User role not allowed');
        }

        // Check disallowed capabilities
        if (!empty($this->disallowed_capabilities)) {
            foreach ($this->disallowed_capabilities as $capability) {
                if ($user->has_cap($capability)) {
                    return $this->forbiddenResponse('User capability not allowed');
                }
            }
        }

        // Check allowed roles if disallowed checks passed
        if (!empty($this->allowed_roles) && !array_intersect($user->roles, $this->allowed_roles)) {
            return $this->forbiddenResponse('User role not allowed');
        }

        // Check allowed capabilities if disallowed checks passed
        if (!empty($this->allowed_capabilities)) {
            $has_cap = false;
            foreach ($this->allowed_capabilities as $capability) {
                if ($user->has_cap($capability)) {
                    $has_cap = true;
                    break;
                }
            }
            if (!$has_cap) {
                return $this->forbiddenResponse('User lacks required capabilities');
            }
        }

        return $next($request);
    }

    private function forbiddenResponse(string $message)
    {
        return new WP_Error('permission_denied', $message, ['status' => 403]);
    }
}
