<?php

namespace PluboRoutes\Middleware;

use JsonSchema\Validator;
use JsonSchema\Constraints\Constraint;
use WP_REST_Request;

class SchemaValidator implements MiddlewareInterface
{
    private $schema;

    public function __construct($schema)
    {
        $this->schema = $schema;
    }

    public function handle(WP_REST_Request $request, callable $next)
    {
        $params = $request->get_params();
        $paramsObject = json_decode(json_encode($params));

        // Validate against the schema
        $validator = new Validator();
        $validator->validate($paramsObject, $this->schema, Constraint::CHECK_MODE_APPLY_DEFAULTS);

        if (!$validator->isValid()) {
            $errors = array_map(function ($error) {
                return sprintf("[%s] %s", $error['property'], $error['message']);
            }, $validator->getErrors());

            return new \WP_REST_Response(['error' => 'Input validation failed', 'details' => $errors], 400);
        }

        // Sanitize data
        $sanitizedData = $this->sanitize($paramsObject, $this->schema);

        // Replace request parameters with sanitized and validated data
        foreach ($sanitizedData as $key => $value) {
            $request->set_param($key, $value);
        }

        return $next($request);
    }

    private function sanitize($data, $schema)
    {
        if (!is_object($schema)) {
            $schema = json_decode(json_encode($schema));
        }

        $sanitizedData = [];

        foreach ($schema->properties as $key => $property) {
            if (isset($data->$key)) {
                $sanitizedData[$key] = $this->sanitizeValue($data->$key, $property);
            }
        }

        return (object)$sanitizedData;
    }

    private function sanitizeValue($value, $propertySchema)
    {
        $type = $propertySchema->type ?? 'string';

        switch ($type) {
            case 'string':
                if (isset($propertySchema->format)) {
                    switch ($propertySchema->format) {
                        case 'email':
                            return filter_var($value, FILTER_VALIDATE_EMAIL) ? sanitize_email($value) : null;
                        case 'uri':
                            return filter_var($value, FILTER_VALIDATE_URL) ? esc_url($value) : null;
                        case 'date-time':
                            try {
                                $dateTime = new \DateTime($value);
                                return $dateTime->format(\DateTime::ATOM); // Sanitized ISO 8601 date-time string
                            } catch (\Exception $e) {
                                return null; // Invalid date-time
                            }
                        default:
                            return sanitize_text_field($value); // Default sanitization for unknown formats
                    }
                }
                return sanitize_text_field($value);
            case 'integer':
                return intval($value);
            case 'number':
                return floatval($value);
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            case 'array':
                if (!is_array($value)) return [];
            
                // If a schema for items is provided, sanitize each element with it.
                if (isset($propertySchema->items)) {
                    return array_map(function ($item) use ($propertySchema) {
                        return $this->sanitizeValue($item, $propertySchema->items);
                    }, $value);
                }
            
                // Deep-sanitize unknown array contents
                return array_map([$this, 'sanitizeLoose'], $value);
            case 'object':
                if (is_object($value)) {
                    $sanitizedObject = [];
    
                    // Process defined properties
                    if (isset($propertySchema->properties)) {
                        foreach ($propertySchema->properties as $subKey => $subSchema) {
                            if (isset($value->$subKey)) {
                                $sanitizedObject[$subKey] = $this->sanitizeValue($value->$subKey, $subSchema);
                            }
                        }
                    }
    
                    // Handle additional properties if allowed (boolean true => sanitize strings as text)
                    if (isset($propertySchema->additionalProperties) && $propertySchema->additionalProperties === true) {
                        $definedProperties = isset($propertySchema->properties) ? array_keys((array)$propertySchema->properties) : [];
                        foreach ($value as $subKey => $subValue) {
                            if (!in_array($subKey, $definedProperties, true)) {
                                $sanitizedObject[$subKey] = is_string($subValue) ? sanitize_text_field($subValue) : $subValue;
                            }
                        }
                    }
    
                    return (object)$sanitizedObject;
                }
                return new \stdClass();
            case 'null':
                return null;
            default:
                // Handle multiple types like ["string","null"]
                if (is_array($type)) {
                    foreach ($type as $t) {
                        $sanitized = $this->sanitizeValue($value, (object)['type' => $t]);
                        if ($sanitized !== null) return $sanitized;
                    }
                }
                return sanitize_text_field($value);
        }
    }

    /**
     * Deep, schema-agnostic sanitizer used as a safe fallback.
     * - Strings: sanitize_text_field
     * - Numbers/bools: cast
     * - Arrays/objects: recurse
     */
    private function sanitizeLoose($data)
    {
        if (is_string($data)) {
            return sanitize_text_field($data);
        }
        if (is_int($data)) return (int)$data;
        if (is_float($data)) return (float)$data;
        if (is_bool($data)) return (bool)$data;
        if (is_null($data)) return null;
    
        if (is_array($data)) {
            $out = [];
            foreach ($data as $k => $v) {
                $safeKey = is_string($k) ? sanitize_key($k) : $k;
                $out[$safeKey] = $this->sanitizeLoose($v);
            }
            return $out;
        }
    
        if (is_object($data)) {
            $out = [];
            foreach (get_object_vars($data) as $k => $v) {
                $safeKey = sanitize_key($k);
                $out[$safeKey] = $this->sanitizeLoose($v);
            }
            return (object)$out;
        }
    
        // Fallback for unexpected types/resources
        return null;
    }
}
