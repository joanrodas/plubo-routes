<?php

namespace PluboRoutes\Middleware;

trait MiddlewareHelpersTrait
{
    /**
     * Add multiple middleware entries in order.
     *
     * @param array $middlewares
     * @return $this
     */
    public function useMiddlewares(array $middlewares)
    {
        foreach ($middlewares as $middleware) {
            $this->useMiddleware($middleware);
        }

        return $this;
    }

    /**
     * Add JWT validation middleware.
     *
     * @return $this
     */
    public function useJwtValidation(
        string $secret_key,
        ?string $expected_issuer = null,
        ?string $expected_audience = null,
        int $leeway = 0
    ) {
        $this->useMiddleware(new JwtValidation($secret_key, $expected_issuer, $expected_audience, $leeway));
        return $this;
    }

    /**
     * Add Application Password validation middleware.
     *
     * @return $this
     */
    public function useApplicationPasswordValidation()
    {
        $this->useMiddleware(new ApplicationPasswordValidation());
        return $this;
    }

    /**
     * Add WooCommerce API key validation middleware.
     *
     * @return $this
     */
    public function useWooCommerceApiKeyValidation(?string $requiredPermission = null)
    {
        $this->useMiddleware(new WooCommerceApiKeyValidation($requiredPermission));
        return $this;
    }

    /**
     * Add CORS middleware.
     *
     * @param string|array $allowedOrigins
     * @return $this
     */
    public function useCors($allowedOrigins = '*', array $allowedMethods = ['GET', 'POST', 'OPTIONS'], array $allowedHeaders = ['Content-Type', 'Authorization'])
    {
        $this->useMiddleware(new Cors($allowedOrigins, $allowedMethods, $allowedHeaders));
        return $this;
    }

    /**
     * Add cache middleware.
     *
     * @return $this
     */
    public function useCache(int $cacheTime = 300)
    {
        $this->useMiddleware(new Cache($cacheTime));
        return $this;
    }

    /**
     * Add rate-limit middleware.
     *
     * @return $this
     */
    public function useRateLimit(int $maxRequests = 10, int $windowTime = 60, string $type = 'ip')
    {
        $this->useMiddleware(new RateLimit($maxRequests, $windowTime, $type));
        return $this;
    }

    /**
     * Add permission middleware.
     *
     * @return $this
     */
    public function usePermissions(
        string $type = '',
        array $allowed_roles = [],
        array $allowed_capabilities = [],
        array $disallowed_roles = [],
        array $disallowed_capabilities = []
    ) {
        $this->useMiddleware(new Permissions(
            $type,
            $allowed_roles,
            $allowed_capabilities,
            $disallowed_roles,
            $disallowed_capabilities
        ));

        return $this;
    }

    /**
     * Add JSON schema validation middleware.
     *
     * @param mixed $schema
     * @return $this
     */
    public function useSchemaValidator($schema)
    {
        $this->useMiddleware(new SchemaValidator($schema));
        return $this;
    }
}
