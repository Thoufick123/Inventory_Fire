<?php
/**
 * Settings Service — Central Configuration Repository
 * Provides unified getter/setter interface for system settings.
 */

require_once BASE_PATH . '/app/Services/AuditService.php';

class SettingsService {
    private static ?array $cache = null;

    /**
     * Load all settings into static memory cache.
     */
    private static function loadCache(): void {
        if (self::$cache === null) {
            self::$cache = [];
            try {
                $rows = Database::fetchAll("SELECT setting_key, setting_value, setting_group FROM settings");
                foreach ($rows as $row) {
                    self::$cache[$row['setting_key']] = [
                        'value' => $row['setting_value'],
                        'group' => $row['setting_group'],
                    ];
                }
            } catch (Exception $e) {
                error_log("SettingsService::loadCache failed: " . $e->getMessage());
            }
        }
    }

    /**
     * Get a setting value by key with optional default.
     */
    public static function get(string $key, $default = null) {
        self::loadCache();
        if (array_key_exists($key, self::$cache)) {
            $val = self::$cache[$key]['value'];
            // Auto-decode JSON if valid JSON structure
            if ($val !== null && is_string($val) && ($val === 'true' || $val === 'false')) {
                return $val === 'true';
            }
            return $val;
        }
        return $default;
    }

    /**
     * Set/update a setting value by key.
     */
    public static function set(string $key, $value, string $group = 'general'): bool {
        self::loadCache();

        $strVal = is_array($value) || is_object($value) ? json_encode($value) : (string)$value;
        $prevVal = self::$cache[$key]['value'] ?? null;

        Database::execute(
            "INSERT INTO settings (setting_key, setting_value, setting_group, created_at, updated_at) 
             VALUES (?, ?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_group = VALUES(setting_group), updated_at = NOW()",
            [$key, $strVal, $group]
        );

        self::$cache[$key] = [
            'value' => $strVal,
            'group' => $group,
        ];

        AuditService::log('system_settings', 'UPDATE', 'setting', null, [$key => $prevVal], [$key => $strVal]);
        return true;
    }

    /**
     * Get all settings in a specific group.
     */
    public static function getGroup(string $group): array {
        self::loadCache();
        $groupData = [];
        foreach (self::$cache as $key => $data) {
            if ($data['group'] === $group) {
                $groupData[$key] = $data['value'];
            }
        }
        return $groupData;
    }

    /**
     * Update an entire group section of settings.
     */
    public static function updateGroup(string $group, array $settings): bool {
        Database::beginTransaction();
        try {
            foreach ($settings as $key => $val) {
                self::set($key, $val, $group);
            }
            Database::commit();
            return true;
        } catch (Exception $e) {
            Database::rollback();
            error_log("SettingsService::updateGroup failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get all settings formatted by group.
     */
    public static function getAllGrouped(): array {
        self::loadCache();
        $grouped = [
            'company'    => [],
            'switches'   => [],
            'inventory'  => [],
            'security'   => [],
            'backup'     => [],
            'theme'      => [],
            'general'    => [],
        ];

        foreach (self::$cache as $key => $data) {
            $grp = $data['group'] ?? 'general';
            if (!isset($grouped[$grp])) {
                $grouped[$grp] = [];
            }
            $grouped[$grp][$key] = $data['value'];
        }

        // Merge defaults for missing keys
        return array_replace_recursive(self::getDefaults(), $grouped);
    }

    /**
     * Pre-defined default settings schema.
     */
    public static function getDefaults(): array {
        return [
            'company' => [
                'company_name'       => 'GRM Textiles & Apparel Ltd',
                'company_tagline'    => 'Quality Garment Manufacturing & Retail',
                'company_email'      => 'support@grminventory.com',
                'company_phone'      => '+91 98765 43210',
                'company_gstin'      => '33AAAAA0000A1Z5',
                'company_pan'        => 'AAAAA0000A',
                'company_address'    => '123 Textile Park Road, Tirupur, Tamil Nadu 641601',
                'currency_symbol'    => '₹',
            ],
            'switches' => [
                'maintenance_mode'      => 'false',
                'disable_pos_guest'     => 'false',
                'auto_approve_program'  => 'false',
                'allow_negative_stock'  => 'false',
            ],
            'inventory' => [
                'low_stock_threshold'   => '10',
                'default_warehouse_id'  => '1',
                'audit_stock_movement' => 'true',
            ],
            'security' => [
                'session_timeout_mins'  => '60',
                'max_login_attempts'   => '5',
                'lockout_duration_mins' => '15',
                'force_password_change' => 'false',
            ],
            'backup' => [
                'auto_backup_enabled'   => 'true',
                'auto_backup_frequency' => 'daily',
                'backup_retention_days' => '30',
                'alert_recipients'      => 'admin@grminventory.com',
            ],
            'theme' => [
                'theme_preset'       => 'indigo',
                'theme_primary'      => '#4f46e5',
                'theme_sidebar_bg'   => '#1e293b',
                'theme_sidebar_text' => '#94a3b8',
                'theme_navbar_bg'    => '#ffffff',
                'theme_page_bg'      => '#f8fafc',
            ],
        ];
    }

    /**
     * Built-in default color presets.
     */
    public static function getDefaultThemePresets(): array {
        return [
            'indigo' => [
                'label'        => 'Indigo Classic',
                'primary'      => '#4f46e5',
                'sidebar_bg'   => '#1e293b',
                'sidebar_text' => '#94a3b8',
                'navbar_bg'    => '#ffffff',
                'page_bg'      => '#f8fafc',
                'is_custom'    => false,
            ],
            'ocean' => [
                'label'        => 'Royal Ocean Blue',
                'primary'      => '#0284c7',
                'sidebar_bg'   => '#0f172a',
                'sidebar_text' => '#94a3b8',
                'navbar_bg'    => '#ffffff',
                'page_bg'      => '#f0f9ff',
                'is_custom'    => false,
            ],
            'navy_teal' => [
                'label'        => 'Deep Navy & Teal',
                'primary'      => '#0d9488',
                'sidebar_bg'   => '#111827',
                'sidebar_text' => '#9ca3af',
                'navbar_bg'    => '#ffffff',
                'page_bg'      => '#f8fafc',
                'is_custom'    => false,
            ],
            'charcoal_amber' => [
                'label'        => 'Charcoal & Amber',
                'primary'      => '#d97706',
                'sidebar_bg'   => '#18181b',
                'sidebar_text' => '#a1a1aa',
                'navbar_bg'    => '#ffffff',
                'page_bg'      => '#fafaf9',
                'is_custom'    => false,
            ],
            'forest' => [
                'label'        => 'Forest Emerald',
                'primary'      => '#059669',
                'sidebar_bg'   => '#14261f',
                'sidebar_text' => '#9ca3af',
                'navbar_bg'    => '#ffffff',
                'page_bg'      => '#f4faf7',
                'is_custom'    => false,
            ],
            'plum' => [
                'label'        => 'Modern Crimson Plum',
                'primary'      => '#9333ea',
                'sidebar_bg'   => '#1f1630',
                'sidebar_text' => '#b8accf',
                'navbar_bg'    => '#ffffff',
                'page_bg'      => '#faf8ff',
                'is_custom'    => false,
            ],
        ];
    }

    public static function getCustomThemePresets(): array {
        $raw = (string)self::get('theme_custom_presets', '[]');
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }
        $defaults = self::getDefaults()['theme'];
        $clean = [];
        foreach ($decoded as $id => $item) {
            if (!is_array($item)) {
                continue;
            }
            $presetId = preg_replace('/[^a-z0-9_-]/i', '', (string)$id);
            $label = trim((string)($item['label'] ?? ''));
            if ($presetId === '' || $label === '') {
                continue;
            }
            $clean[$presetId] = [
                'label'        => mb_substr($label, 0, 50),
                'primary'      => self::sanitizeHexColor($item['primary'] ?? null, $defaults['theme_primary']),
                'sidebar_bg'   => self::sanitizeHexColor($item['sidebar_bg'] ?? null, $defaults['theme_sidebar_bg']),
                'sidebar_text' => self::sanitizeHexColor($item['sidebar_text'] ?? null, $defaults['theme_sidebar_text']),
                'navbar_bg'    => self::sanitizeHexColor($item['navbar_bg'] ?? null, $defaults['theme_navbar_bg']),
                'page_bg'      => self::sanitizeHexColor($item['page_bg'] ?? null, $defaults['theme_page_bg']),
                'is_custom'    => true,
            ];
        }
        return $clean;
    }

    public static function getHiddenThemePresets(): array {
        $raw = (string)self::get('theme_hidden_presets', '[]');
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }
        return array_values(array_unique(array_filter(array_map('strval', $decoded), static fn(string $v): bool => $v !== '')));
    }

    /**
     * Active quick color presets (visible built-ins + customer-saved custom presets).
     */
    public static function getThemePresets(): array {
        $hidden = self::getHiddenThemePresets();
        $visibleDefaults = array_filter(
            self::getDefaultThemePresets(),
            static fn(array $preset, string $id): bool => !in_array($id, $hidden, true),
            ARRAY_FILTER_USE_BOTH
        );
        return array_merge($visibleDefaults, self::getCustomThemePresets());
    }

    public static function saveCustomThemePreset(string $label, array $colors, bool $applyNow = true): string {
        $cleanLabel = trim(preg_replace('/\s+/', ' ', $label) ?? '');
        if ($cleanLabel === '') {
            throw new InvalidArgumentException('Preset name is required.');
        }
        $cleanLabel = mb_substr($cleanLabel, 0, 50);

        $defaults = self::getDefaults()['theme'];
        $sanitized = [
            'label'        => $cleanLabel,
            'primary'      => self::sanitizeHexColor($colors['primary'] ?? null, $defaults['theme_primary']),
            'sidebar_bg'   => self::sanitizeHexColor($colors['sidebar_bg'] ?? null, $defaults['theme_sidebar_bg']),
            'sidebar_text' => self::sanitizeHexColor($colors['sidebar_text'] ?? null, $defaults['theme_sidebar_text']),
            'navbar_bg'    => self::sanitizeHexColor($colors['navbar_bg'] ?? null, $defaults['theme_navbar_bg']),
            'page_bg'      => self::sanitizeHexColor($colors['page_bg'] ?? null, $defaults['theme_page_bg']),
            'is_custom'    => true,
        ];

        $customPresets = self::getCustomThemePresets();
        $slugBase = strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '_', $cleanLabel), '_'));
        if ($slugBase === '') {
            $slugBase = 'palette';
        }
        $presetId = 'custom_' . $slugBase;
        $counter = 2;
        while (isset($customPresets[$presetId]) || isset(self::getDefaultThemePresets()[$presetId])) {
            $presetId = 'custom_' . $slugBase . '_' . ($counter++);
        }

        $customPresets[$presetId] = $sanitized;
        $payload = ['theme_custom_presets' => json_encode($customPresets)];

        if ($applyNow) {
            $payload['theme_preset']       = $presetId;
            $payload['theme_primary']      = $sanitized['primary'];
            $payload['theme_sidebar_bg']   = $sanitized['sidebar_bg'];
            $payload['theme_sidebar_text'] = $sanitized['sidebar_text'];
            $payload['theme_navbar_bg']    = $sanitized['navbar_bg'];
            $payload['theme_page_bg']      = $sanitized['page_bg'];
        }

        self::updateGroup('theme', $payload);
        return $presetId;
    }

    public static function deleteThemePreset(string $presetId): bool {
        $cleanId = preg_replace('/[^a-z0-9_-]/i', '', trim($presetId));
        if ($cleanId === '') {
            return false;
        }

        $customPresets = self::getCustomThemePresets();
        $defaultPresets = self::getDefaultThemePresets();
        $payload = [];
        $deleted = false;

        if (isset($customPresets[$cleanId])) {
            unset($customPresets[$cleanId]);
            $payload['theme_custom_presets'] = json_encode($customPresets);
            $deleted = true;
        } elseif (isset($defaultPresets[$cleanId])) {
            $hidden = self::getHiddenThemePresets();
            if (!in_array($cleanId, $hidden, true)) {
                $hidden[] = $cleanId;
            }
            $payload['theme_hidden_presets'] = json_encode(array_values($hidden));
            $deleted = true;
        }

        if (!$deleted) {
            return false;
        }

        if ((string)self::get('theme_preset', 'indigo') === $cleanId) {
            $payload['theme_preset'] = 'custom';
        }

        return self::updateGroup('theme', $payload);
    }

    public static function sanitizeHexColor(?string $value, string $fallback): string {
        $candidate = trim((string)$value);
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $candidate)) {
            return strtolower($candidate);
        }
        return strtolower($fallback);
    }

    public static function hexToRgbString(string $hex): string {
        $hex = ltrim(self::sanitizeHexColor($hex, '#4f46e5'), '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return "{$r}, {$g}, {$b}";
    }

    public static function adjustHexBrightness(string $hex, int $steps): string {
        $hex = ltrim(self::sanitizeHexColor($hex, '#4f46e5'), '#');
        $r = max(0, min(255, hexdec(substr($hex, 0, 2)) + $steps));
        $g = max(0, min(255, hexdec(substr($hex, 2, 2)) + $steps));
        $b = max(0, min(255, hexdec(substr($hex, 4, 2)) + $steps));
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    public static function mixWithWhite(string $hex, float $whiteRatio = 0.90): string {
        $hex = ltrim(self::sanitizeHexColor($hex, '#4f46e5'), '#');
        $ratio = max(0.0, min(1.0, $whiteRatio));
        $r = (int)round(hexdec(substr($hex, 0, 2)) * (1 - $ratio) + 255 * $ratio);
        $g = (int)round(hexdec(substr($hex, 2, 2)) * (1 - $ratio) + 255 * $ratio);
        $b = (int)round(hexdec(substr($hex, 4, 2)) * (1 - $ratio) + 255 * $ratio);
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Resolve active theme colors and derived CSS variables safely.
     */
    public static function getThemeConfig(): array {
        $defaults = self::getDefaults()['theme'];
        $presets = self::getThemePresets();
        $presetKey = trim((string)self::get('theme_preset', $defaults['theme_preset']));
        if ($presetKey !== 'custom' && !isset($presets[$presetKey])) {
            $presetKey = $defaults['theme_preset'];
        }

        $primary     = self::sanitizeHexColor((string)self::get('theme_primary', $defaults['theme_primary']), $defaults['theme_primary']);
        $sidebarBg   = self::sanitizeHexColor((string)self::get('theme_sidebar_bg', $defaults['theme_sidebar_bg']), $defaults['theme_sidebar_bg']);
        $sidebarText = self::sanitizeHexColor((string)self::get('theme_sidebar_text', $defaults['theme_sidebar_text']), $defaults['theme_sidebar_text']);
        $navbarBg    = self::sanitizeHexColor((string)self::get('theme_navbar_bg', $defaults['theme_navbar_bg']), $defaults['theme_navbar_bg']);
        $pageBg      = self::sanitizeHexColor((string)self::get('theme_page_bg', $defaults['theme_page_bg']), $defaults['theme_page_bg']);

        return [
            'preset'        => $presetKey,
            'primary'       => $primary,
            'primary_hover' => self::adjustHexBrightness($primary, -18),
            'primary_light' => self::mixWithWhite($primary, 0.90),
            'primary_tint'  => self::mixWithWhite($primary, 0.45),
            'primary_rgb'   => self::hexToRgbString($primary),
            'sidebar_bg'    => $sidebarBg,
            'sidebar_text'  => $sidebarText,
            'navbar_bg'     => $navbarBg,
            'page_bg'       => $pageBg,
        ];
    }
}
