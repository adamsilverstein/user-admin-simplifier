<?php
/**
 * PHPStan bootstrap file for WordPress stub functions
 * 
 * This file provides stub functions and constants that PHPStan needs to analyze
 * WordPress plugin code without having WordPress installed.
 */

// WordPress constants
if (!defined('ABSPATH')) {
    define('ABSPATH', '/wp/');
}
if (!defined('WP_UNINSTALL_PLUGIN')) {
    define('WP_UNINSTALL_PLUGIN', true);
}
if (!defined('JSON_ERROR_NONE')) {
    define('JSON_ERROR_NONE', 0);
}

// WordPress stub functions
if (!function_exists('add_action')) {
    function add_action(string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1): bool { return true; }
}
if (!function_exists('add_filter')) {
    function add_filter(string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1): bool { return true; }
}
if (!function_exists('add_management_page')) {
    function add_management_page(string $page_title, string $menu_title, string $capability, string $menu_slug, callable $callback = null): string|false { return 'hook'; }
}
if (!function_exists('remove_menu_page')) {
    function remove_menu_page(string $menu_slug): array|false { return []; }
}
if (!function_exists('remove_submenu_page')) {
    function remove_submenu_page(string $menu_slug, string $submenu_slug): array|false { return []; }
}
if (!function_exists('get_admin_url')) {
    function get_admin_url(?int $blog_id = null, string $path = '', string $scheme = 'admin'): string { return 'http://example.com/wp-admin/'; }
}
if (!function_exists('admin_url')) {
    function admin_url(string $path = '', string $scheme = 'admin'): string { return 'http://example.com/wp-admin/'; }
}
if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url(string $file): string { return 'http://example.com/wp-content/plugins/'; }
}
if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path(string $file): string { return '/path/to/plugin/'; }
}
if (!function_exists('plugin_basename')) {
    function plugin_basename(string $file): string { return 'plugin/file.php'; }
}
if (!function_exists('get_option')) {
    function get_option(string $option, mixed $default = false): mixed { return $default; }
}
if (!function_exists('update_option')) {
    function update_option(string $option, mixed $value, bool|string $autoload = null): bool { return true; }
}
if (!function_exists('delete_option')) {
    function delete_option(string $option): bool { return true; }
}
if (!function_exists('get_users')) {
    function get_users(array|string $args = []): array { return []; }
}
if (!function_exists('current_user_can')) {
    function current_user_can(string $capability, mixed ...$args): bool { return true; }
}
if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce(string $nonce, string|int $action = -1): int|false { return 1; }
}
if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce(string|int $action = -1): string { return 'nonce'; }
}
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field(string $str): string { return $str; }
}
if (!function_exists('sanitize_key')) {
    function sanitize_key(string $key): string { return $key; }
}
if (!function_exists('wp_unslash')) {
    function wp_unslash(string|array $value): string|array { return $value; }
}
if (!function_exists('esc_html__')) {
    function esc_html__(string $text, string $domain = 'default'): string { return $text; }
}
if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error(mixed $data = null, int $status_code = null, int $options = 0): void { exit; }
}
if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success(mixed $data = null, int $status_code = null, int $options = 0): void { exit; }
}
if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script(string $handle, string|false $src = '', array $deps = [], string|bool|null $ver = false, bool|array $args = []): void {}
}
if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style(string $handle, string|false $src = '', array $deps = [], string|bool|null $ver = false, string $media = 'all'): void {}
}
if (!function_exists('wp_localize_script')) {
    function wp_localize_script(string $handle, string $object_name, array $l10n): bool { return true; }
}
if (!function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags(string $text, bool $remove_breaks = false): string { return $text; }
}
if (!function_exists('__return_false')) {
    function __return_false(): bool { return false; }
}

// Global variables
global $menu, $submenu, $current_user, $storedmenu, $storedsubmenu, $wp_admin_bar_menu_items;
$menu = [];
$submenu = [];
$storedmenu = [];
$storedsubmenu = [];
$wp_admin_bar_menu_items = [];
$current_user = (object)['user_nicename' => 'test'];
