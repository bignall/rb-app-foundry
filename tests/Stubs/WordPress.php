<?php

/**
 * Lightweight WordPress class stubs for PHPUnit.
 *
 * These replace the real WP classes so tests can run without a WordPress
 * install. Only the methods actually used in production code or tests are
 * implemented.
 */

if (!class_exists('WP_Error')) {
    class WP_Error
    {
        private string $code;
        private string $message;
        private mixed $data;

        public function __construct(string $code = '', string $message = '', mixed $data = '')
        {
            $this->code    = $code;
            $this->message = $message;
            $this->data    = $data;
        }

        public function get_error_code(): string
        {
            return $this->code;
        }

        public function get_error_message(): string
        {
            return $this->message;
        }

        public function get_error_data(): mixed
        {
            return $this->data;
        }
    }
}
