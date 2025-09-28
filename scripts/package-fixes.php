#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$fixSourceDir = $root . '/includes/classes/Fixes/Fix';
$outputRoot = $root . '/dist/fixes';

if (! is_dir($fixSourceDir)) {
        fwrite(STDERR, "Unable to locate fixes directory: {$fixSourceDir}\n");
        exit(1);
}

if (! is_dir($outputRoot)) {
        if (! mkdir($outputRoot, 0775, true) && ! is_dir($outputRoot)) {
                fwrite(STDERR, "Unable to create output directory: {$outputRoot}\n");
                exit(1);
        }
}

$fixFiles = glob($fixSourceDir . '/*.php');

if (! $fixFiles) {
        fwrite(STDERR, "No fix files found in {$fixSourceDir}\n");
        exit(0);
}

/**
 * Recursively delete directory contents.
 */
$clearDir = function (string $dir): void {
        if (! is_dir($dir)) {
                return;
        }

        $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $fileInfo) {
                /** @var SplFileInfo $fileInfo */
                if ($fileInfo->isDir()) {
                        rmdir($fileInfo->getPathname());
                } else {
                        unlink($fileInfo->getPathname());
                }
        }
};

$zipPlugin = function (string $pluginDir, string $pluginSlug) use ($outputRoot): void {
        $zipPath = $outputRoot . '/' . $pluginSlug . '.zip';
        if (file_exists($zipPath)) {
                unlink($zipPath);
        }

        $zip = new ZipArchive();
        if (true !== $zip->open($zipPath, ZipArchive::CREATE)) {
                throw new RuntimeException('Unable to create zip archive at ' . $zipPath);
        }

        $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($pluginDir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
                /** @var SplFileInfo $file */
                if ($file->isDir()) {
                        continue;
                }
                $filePath = $file->getRealPath();
                if (false === $filePath) {
                        continue;
                }
                $relativePath = substr($filePath, strlen($pluginDir) + 1);
                $zip->addFile($filePath, $pluginSlug . '/' . $relativePath);
        }

        $zip->close();
};

$normalizeWhitespace = function (string $value): string {
        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
};

foreach ($fixFiles as $fixFile) {
        $originalContents = file_get_contents($fixFile);
        if (false === $originalContents) {
                fwrite(STDERR, "Skipping {$fixFile}: unable to read file.\n");
                continue;
        }

        $className = basename($fixFile, '.php');

        $slug = null;
        if (preg_match('/function\s+get_slug\s*\(\)\s*:?\s*string\s*\{\s*return\s+[\'\"]([^\'\"]+)[\'\"];?/m', $originalContents, $matches)) {
                $slug = $matches[1];
        }

        if (! $slug) {
                $slug = strtolower(preg_replace('/Fix$/', '', $className));
                $slug = $slug ? str_replace('_', '-', preg_replace('/([a-z])([A-Z])/', '$1-$2', $slug)) : $className;
        }

        $nicename = null;
        if (preg_match('/function\s+get_nicename\s*\(\)\s*:?\s*string\s*\{\s*return\s+(?:esc_html__|__|_x)\(\s*[\'\"]([^\'\"]+)[\'\"]/m', $originalContents, $matches)) {
                $nicename = $matches[1];
        }

        if (! $nicename) {
                $nicename = ucwords(str_replace(['-', '_'], ' ', $slug));
        }

        $pluginSlug = 'accessibility-fix-' . $slug;
        $pluginDir = $outputRoot . '/' . $pluginSlug;

        if (is_dir($pluginDir)) {
                $clearDir($pluginDir);
        } else {
                mkdir($pluginDir, 0775, true);
        }

        $coreDir = $pluginDir . '/includes/Core';
        $fixDestDir = $pluginDir . '/includes/Fixes';

        mkdir($coreDir, 0775, true);
        mkdir($fixDestDir, 0775, true);

        $textDomain  = str_replace('_', '-', $pluginSlug);
        $description = $normalizeWhitespace($nicename);

        $pluginHeaderTemplate = <<<'PHP'
<?php
/**
 * Plugin Name: Accessibility Fix – %1$s
 * Description: Packages the "%2$s" fix from Accessibility Checker as an individual plugin.
 * Version: 1.0.0
 * Author: Equalize Digital
 * License: GPL-2.0-or-later
 * Text Domain: %3$s
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

require_once __DIR__ . '/includes/Core/PluginContext.php';
require_once __DIR__ . '/includes/Core/FixInterface.php';
require_once __DIR__ . '/includes/Core/FixesManager.php';
require_once __DIR__ . '/includes/Core/SingleFixBootstrap.php';
require_once __DIR__ . '/includes/Fixes/%4$s.php';

\AccessibilityFixes\Core\PluginContext::bootstrap( __FILE__ );

$fix = new \AccessibilityFixes\%4$s\%4$s();

\AccessibilityFixes\Core\SingleFixBootstrap::create( __FILE__, $fix )->bootstrap();
PHP;

        $pluginHeader = sprintf(
                $pluginHeaderTemplate,
                $nicename,
                $description,
                $textDomain,
                $className
        );

        file_put_contents($pluginDir . '/accessibility-fix-' . $slug . '.php', $pluginHeader);

        $fixNamespace = 'AccessibilityFixes\\' . $className;

        $adjustedContents = preg_replace(
                '/^namespace\s+EqualizeDigital\\\\AccessibilityChecker\\\\Fixes\\\\Fix;$/m',
                'namespace ' . $fixNamespace . ';',
                $originalContents
        );

        $adjustedContents = preg_replace(
                '/^use\s+EqualizeDigital\\\\AccessibilityChecker\\\\Fixes\\\\FixInterface;$/m',
                'use AccessibilityFixes\\Core\\FixInterface;',
                $adjustedContents
        );

        if (str_contains($adjustedContents, 'FixesManager')) {
                $adjustedContents = preg_replace(
                        '/^use\s+EqualizeDigital\\\\AccessibilityChecker\\\\Fixes\\\\FixesManager;$/m',
                        'use AccessibilityFixes\\Core\\FixesManager;',
                        $adjustedContents
                );
        }

        if (str_contains($adjustedContents, 'EDAC_PLUGIN_URL')) {
                $adjustedContents = str_replace('EDAC_PLUGIN_URL', '\\AccessibilityFixes\\Core\\PluginContext::plugin_url()', $adjustedContents);
        }

        file_put_contents($fixDestDir . '/' . $className . '.php', $adjustedContents);

        $fixInterface = <<<'PHP'
<?php
/**
 * Interface for the generated single-fix plugins.
 */

namespace AccessibilityFixes\Core;

interface FixInterface {
        public static function get_slug(): string;

        public static function get_nicename(): string;

        public static function get_type(): string;

        public function register(): void;

        public function get_fields_array( array $fields = [] ): array;

        public function run();
}
PHP;

        file_put_contents($coreDir . '/FixInterface.php', $fixInterface);

        $fixesManager = <<<'PHP'
<?php
/**
 * Minimal FixesManager helpers required by certain fixes.
 */

namespace AccessibilityFixes\Core;

class FixesManager {
        private static ?bool $theme_is_accessibility_ready = null;

        public static function is_theme_accessibility_ready(): bool {
                if ( null !== self::$theme_is_accessibility_ready ) {
                        return self::$theme_is_accessibility_ready;
                }

                $theme = function_exists( 'wp_get_theme' ) ? wp_get_theme() : null;
                $tags  = $theme ? $theme->get( 'Tags' ) : [];

                self::$theme_is_accessibility_ready = is_array( $tags ) && in_array( 'accessibility-ready', $tags, true );

                return self::$theme_is_accessibility_ready ?? false;
        }

        public static function maybe_show_accessibility_ready_conflict_notice(): void {
                if ( self::is_theme_accessibility_ready() ) {
                        ?>
                        <span class="edac-notice--accessibility-ready-conflict">
                                <?php esc_html_e( 'Note: This setting is not recommended for themes that are already accessibility-ready.', 'accessibility-checker' ); ?>
                        </span>
                        <?php
                }
        }
}
PHP;

        file_put_contents($coreDir . '/FixesManager.php', $fixesManager);

        $pluginContext = <<<'PHP'
<?php
/**
 * Provides runtime context for generated plugins.
 */

namespace AccessibilityFixes\Core;

class PluginContext {
        private static string $plugin_file;

        public static function bootstrap( string $plugin_file ): void {
                self::$plugin_file = $plugin_file;
        }

        public static function plugin_file(): string {
                return self::$plugin_file;
        }

        public static function plugin_dir(): string {
                return plugin_dir_path( self::$plugin_file );
        }

        public static function plugin_url(): string {
                return plugin_dir_url( self::$plugin_file );
        }
}
PHP;

        file_put_contents($coreDir . '/PluginContext.php', $pluginContext);

        $singleFixBootstrap = <<<'PHP'
<?php
/**
 * Boots an individual accessibility fix plugin.
 */

namespace AccessibilityFixes\Core;

class SingleFixBootstrap {
        private string $plugin_file;

        private FixInterface $fix;

        /**
         * @var array<string, array<string, mixed>>
         */
        private array $fields = [];

        private string $settings_page;

        private string $option_group;

        private function __construct( string $plugin_file, FixInterface $fix ) {
                $this->plugin_file = $plugin_file;
                $this->fix         = $fix;
                $this->settings_page = $fix::get_slug() . '-settings';
                $this->option_group  = 'accessibility_fix_' . $fix::get_slug();
        }

        public static function create( string $plugin_file, FixInterface $fix ): self {
                return new self( $plugin_file, $fix );
        }

        public function bootstrap(): void {
                register_activation_hook( $this->plugin_file, [ $this, 'activate' ] );

                add_action( 'plugins_loaded', [ $this, 'init' ] );
                add_action( 'admin_menu', [ $this, 'register_menu' ] );
                add_action( 'admin_init', [ $this, 'register_settings' ] );
        }

        public function activate(): void {
                if ( ! function_exists( 'get_option' ) ) {
                        return;
                }

                foreach ( $this->fix->get_fields_array() as $option => $field ) {
                        if ( false === get_option( $option, false ) ) {
                                $default = $field['default'] ?? ( isset( $field['type'] ) && 'checkbox' === $field['type'] ? 0 : '' );
                                add_option( $option, $default );
                        }
                }
        }

        public function init(): void {
                $this->fields = $this->fix->get_fields_array();
                $this->fix->register();

                $type = $this->fix::get_type();

                if ( 'backend' === $type ) {
                        add_action( 'admin_init', [ $this->fix, 'run' ] );
                } elseif ( 'frontend' === $type ) {
                        add_action(
                                'init',
                                function (): void {
                                        if ( ! is_admin() ) {
                                                $this->fix->run();
                                        }
                                }
                        );
                } else {
                        add_action( 'init', [ $this->fix, 'run' ] );
                }
        }

        public function register_menu(): void {
                add_options_page(
                        $this->fix::get_nicename(),
                        $this->fix::get_nicename(),
                        'manage_options',
                        $this->settings_page,
                        [ $this, 'render_settings_page' ]
                );
        }

        public function register_settings(): void {
                if ( empty( $this->fields ) ) {
                        $this->fields = $this->fix->get_fields_array();
                }

                if ( empty( $this->fields ) ) {
                        return;
                }

                add_settings_section( 'accessibility_fix', '', '__return_false', $this->settings_page );

                foreach ( $this->fields as $option => $field ) {
                        register_setting(
                                $this->option_group,
                                $option,
                                [
                                        'sanitize_callback' => $this->resolve_sanitizer( $field ),
                                        'default'           => $field['default'] ?? null,
                                ]
                        );

                        add_settings_field(
                                $option,
                                $field['label'] ?? $option,
                                [ $this, 'render_field' ],
                                $this->settings_page,
                                'accessibility_fix',
                                [
                                        'option' => $option,
                                        'field'  => $field,
                                ]
                        );
                }
        }

        public function render_settings_page(): void {
                ?>
                <div class="wrap">
                        <h1><?php echo esc_html( $this->fix::get_nicename() ); ?></h1>
                        <form method="post" action="options.php">
                                <?php
                                        settings_fields( $this->option_group );
                                        do_settings_sections( $this->settings_page );
                                        submit_button();
                                ?>
                        </form>
                </div>
                <?php
        }

        /**
         * @param array<string, mixed> $args Field configuration.
         */
        public function render_field( array $args ): void {
                $option = $args['option'];
                $field  = $args['field'];
                $value  = get_option( $option, $field['default'] ?? ( isset( $field['type'] ) && 'checkbox' === $field['type'] ? 0 : '' ) );

                $description = $field['description'] ?? '';
                $id          = $field['labelledby'] ?? $option;

                if ( isset( $field['type'] ) && 'checkbox' === $field['type'] ) {
                        ?>
                        <label for="<?php echo esc_attr( $id ); ?>">
                                <input type="hidden" name="<?php echo esc_attr( $option ); ?>" value="0" />
                                <input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $option ); ?>" value="1" <?php checked( (bool) $value ); ?> />
                                <?php echo esc_html( $field['label'] ?? '' ); ?>
                        </label>
                        <?php
                } else {
                        ?>
                        <input type="text" class="regular-text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $option ); ?>" value="<?php echo esc_attr( (string) $value ); ?>" />
                        <?php
                }

                if ( $description ) {
                        ?>
                        <p class="description"><?php echo wp_kses_post( $description ); ?></p>
                        <?php
                }
        }

        /**
         * @param array<string, mixed> $field
         * @return callable|string|null
         */
        private function resolve_sanitizer( array $field ) {
                if ( isset( $field['sanitize_callback'] ) && is_callable( $field['sanitize_callback'] ) ) {
                        return $field['sanitize_callback'];
                }

                if ( isset( $field['type'] ) && 'checkbox' === $field['type'] ) {
                        return [ $this, 'sanitize_checkbox' ];
                }

                return 'sanitize_text_field';
        }

        public function sanitize_checkbox( $value ): int {
                return ! empty( $value ) ? 1 : 0;
        }
}
PHP;

        file_put_contents($coreDir . '/SingleFixBootstrap.php', $singleFixBootstrap);

        if (str_contains($adjustedContents, 'assets/')) {
                $assetsSource = $root . '/assets';
                if (is_dir($assetsSource)) {
                        $assetsDestination = $pluginDir . '/assets';
                        $iterator = new RecursiveIteratorIterator(
                                new RecursiveDirectoryIterator($assetsSource, FilesystemIterator::SKIP_DOTS),
                                RecursiveIteratorIterator::SELF_FIRST
                        );

                        foreach ($iterator as $item) {
                                $targetPath = $assetsDestination . '/' . $iterator->getSubPathName();
                                if ($item->isDir()) {
                                        if (! is_dir($targetPath)) {
                                                mkdir($targetPath, 0775, true);
                                        }
                                } else {
                                        copy($item->getPathname(), $targetPath);
                                }
                        }
                }
        }

        $zipPlugin($pluginDir, $pluginSlug);
}

fwrite(STDOUT, "Generated " . count($fixFiles) . " fix plugin packages in {$outputRoot}\n");
