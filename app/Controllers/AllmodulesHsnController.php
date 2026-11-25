<?php
/**
 * Production HSN Config Controller
 * Full CRUD for HSN codes under Production module.
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2));
}

require_once BASE_PATH . '/app/Helpers/Database.php';
require_once BASE_PATH . '/app/Helpers/Response.php';
require_once BASE_PATH . '/app/Helpers/Request.php';
require_once BASE_PATH . '/app/Helpers/Session.php';
require_once BASE_PATH . '/app/Helpers/CSRF.php';
require_once BASE_PATH . '/app/Services/AuditService.php';

class AllmodulesHsnController {

    private static bool $schemaEnsured = false;

    private static function ensureSchema(): void {
        if (self::$schemaEnsured) return;
        self::$schemaEnsured = true;
        try {
            $tableCheck = Database::fetchOne("SHOW TABLES LIKE 'production_hsn_config'");
            if (!$tableCheck) {
                Database::execute("CREATE TABLE production_hsn_config (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    hsn_code VARCHAR(8) NOT NULL,
                    gst_percentage DECIMAL(5,2) DEFAULT 0.00,
                    categories VARCHAR(255) DEFAULT NULL,
                    subcategories VARCHAR(255) DEFAULT NULL,
                    sub_subcategories VARCHAR(255) DEFAULT NULL,
                    product VARCHAR(255) DEFAULT NULL,
                    is_active TINYINT(1) NOT NULL DEFAULT 1,
                    created_by INT UNSIGNED DEFAULT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uk_hsn_code (hsn_code)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            }

            // Auto-feed standard HSN & GST master rows once if not yet seeded
            $hasMaster = Database::fetchOne("SELECT id FROM production_hsn_config WHERE hsn_code = '5201' LIMIT 1");
            if (!$hasMaster) {
                foreach (self::standardMasterRows() as $mRow) {
                    self::upsertHsnRecord($mRow['hsn_code'], $mRow['gst_rate'], $mRow['description'], $mRow['category'], 0);
                }
            }
        } catch (\Throwable $e) {
            error_log('ProductionHsnController schema error: ' . $e->getMessage());
        }
    }

    /**
     * List all HSN config records.
     */
    public function index(): void {
        self::ensureSchema();
        if (!hasPermission('production_hsn', 'view') && !hasPermission('hsn_config', 'view')) {
            Response::error(403, 'Access denied: You do not have permission to view HSN Config.');
            return;
        }

        $items = Database::fetchAll(
            "SELECT * FROM production_hsn_config ORDER BY created_at DESC"
        );
        Response::view('allmodules.hsn_config.index', [
            'items'        => $items,
            'activeModule' => 'production',
            'activeMenu'   => 'production-hsn-config',
        ]);
    }

    /**
     * Show create form.
     */
    public function create(): void {
        if (!hasPermission('production_hsn', 'create') && !hasPermission('hsn_config', 'create')) {
            Response::error(403, 'Access denied: You do not have permission to create HSN Config.');
            return;
        }

        Response::view('allmodules.hsn_config.create', [
            'activeModule' => 'production',
            'activeMenu'   => 'production-hsn-config',
        ]);
    }

    /**
     * Store a new HSN config record.
     */
    public function store(): void {
        if (!hasPermission('production_hsn', 'create') && !hasPermission('hsn_config', 'create')) {
            Response::error(403, 'Access denied: You do not have permission to create HSN Config.');
            return;
        }

        CSRF::validateRequest();

        $hsnCode         = trim($_POST['hsn_code'] ?? '');
        $gstPercentage   = $_POST['gst_percentage'] ?? null;
        $categories      = trim($_POST['categories'] ?? '') ?: null;
        $subcategories   = trim($_POST['subcategories'] ?? '') ?: null;
        $subSubcategories = trim($_POST['sub_subcategories'] ?? '') ?: null;
        $product         = trim($_POST['product'] ?? '') ?: null;
        $isActive        = isset($_POST['is_active']) ? 1 : 0;

        // Validate HSN code: required, numbers only, up to 8 digits (e.g. 3, 5, 6, 8 digits)
        if ($hsnCode === '' || !preg_match('/^\d{1,8}$/', $hsnCode)) {
            Session::flash('error', 'HSN Code must be numbers only and up to 8 digits.');
            Response::back();
            return;
        }

        // Check for duplicate
        $existing = Database::fetchOne(
            "SELECT id FROM production_hsn_config WHERE hsn_code = ?",
            [$hsnCode]
        );
        if ($existing) {
            Session::flash('error', 'HSN Code "' . htmlspecialchars($hsnCode) . '" already exists.');
            Response::back();
            return;
        }

        $gst = ($gstPercentage !== null && $gstPercentage !== '') ? (float)$gstPercentage : 0.00;

        Database::execute(
            "INSERT INTO production_hsn_config
                (hsn_code, gst_percentage, categories, subcategories, sub_subcategories, product, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$hsnCode, $gst, $categories, $subcategories, $subSubcategories, $product, $isActive]
        );

        // Sync to system-wide hsn_codes table so every dropdown dynamically has this HSN code
        $descParts = array_filter([$product, $subSubcategories, $subcategories, $categories]);
        $description = !empty($descParts) ? implode(' - ', $descParts) : ('Production HSN ' . $hsnCode);

        try {
            $existingHsn = Database::fetchOne("SELECT id FROM hsn_codes WHERE hsn_code = ?", [$hsnCode]);
            if ($existingHsn) {
                Database::execute(
                    "UPDATE hsn_codes SET description = ?, gst_rate = ?, updated_at = NOW() WHERE id = ?",
                    [$description, $gst, (int)$existingHsn['id']]
                );
            } else {
                Database::execute(
                    "INSERT INTO hsn_codes (hsn_code, description, gst_rate, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())",
                    [$hsnCode, $description, $gst]
                );
            }
        } catch (\Throwable $e) {
            error_log('HSN sync error: ' . $e->getMessage());
        }

        Session::flash('success', 'HSN Code "' . htmlspecialchars($hsnCode) . '" added successfully.');
        Response::redirect(url('production/hsn-config'));
    }

    /**
     * Show a single HSN config record (read-only).
     */
    public function view(string $id): void {
        self::ensureSchema();
        if (!hasPermission('production_hsn', 'view') && !hasPermission('hsn_config', 'view')) {
            Response::error(403, 'Access denied: You do not have permission to view HSN Config.');
            return;
        }

        $record = Database::fetchOne(
            "SELECT * FROM production_hsn_config WHERE id = ?",
            [(int)$id]
        );
        if (!$record) {
            Session::flash('error', 'HSN Config record not found.');
            Response::redirect(url('production/hsn-config'));
            return;
        }
        Response::view('allmodules.hsn_config.view', [
            'record'       => $record,
            'activeModule' => 'production',
            'activeMenu'   => 'production-hsn-config',
        ]);
    }

    /**
     * Show edit form.
     */
    public function edit(string $id): void {
        self::ensureSchema();
        if (!hasPermission('production_hsn', 'edit') && !hasPermission('hsn_config', 'edit')) {
            Response::error(403, 'Access denied: You do not have permission to edit HSN Config.');
            return;
        }

        $record = Database::fetchOne(
            "SELECT * FROM production_hsn_config WHERE id = ?",
            [(int)$id]
        );
        if (!$record) {
            Session::flash('error', 'HSN Config record not found.');
            Response::redirect(url('production/hsn-config'));
            return;
        }
        Response::view('allmodules.hsn_config.edit', [
            'record'       => $record,
            'activeModule' => 'production',
            'activeMenu'   => 'production-hsn-config',
        ]);
    }

    /**
     * Update an existing HSN config record.
     */
    public function update(string $id): void {
        if (!hasPermission('production_hsn', 'edit') && !hasPermission('hsn_config', 'edit')) {
            Response::error(403, 'Access denied: You do not have permission to edit HSN Config.');
            return;
        }

        CSRF::validateRequest();

        $record = Database::fetchOne(
            "SELECT * FROM production_hsn_config WHERE id = ?",
            [(int)$id]
        );
        if (!$record) {
            Session::flash('error', 'HSN Config record not found.');
            Response::redirect(url('production/hsn-config'));
            return;
        }

        $hsnCode          = trim($_POST['hsn_code'] ?? '');
        $gstPercentage    = $_POST['gst_percentage'] ?? null;
        $categories       = trim($_POST['categories'] ?? '') ?: null;
        $subcategories    = trim($_POST['subcategories'] ?? '') ?: null;
        $subSubcategories = trim($_POST['sub_subcategories'] ?? '') ?: null;
        $product          = trim($_POST['product'] ?? '') ?: null;
        $isActive         = isset($_POST['is_active']) ? 1 : 0;

        // Validate HSN code: required, numbers only, up to 8 digits (e.g. 3, 5, 6, 8 digits)
        if ($hsnCode === '' || !preg_match('/^\d{1,8}$/', $hsnCode)) {
            Session::flash('error', 'HSN Code must be numbers only and up to 8 digits.');
            Response::back();
            return;
        }

        // Check for duplicate (excluding current record)
        $duplicate = Database::fetchOne(
            "SELECT id FROM production_hsn_config WHERE hsn_code = ? AND id != ?",
            [$hsnCode, (int)$id]
        );
        if ($duplicate) {
            Session::flash('error', 'HSN Code "' . htmlspecialchars($hsnCode) . '" already exists.');
            Response::back();
            return;
        }

        $gst = ($gstPercentage !== null && $gstPercentage !== '') ? (float)$gstPercentage : 0.00;

        Database::execute(
            "UPDATE production_hsn_config
             SET hsn_code = ?, gst_percentage = ?, categories = ?, subcategories = ?,
                 sub_subcategories = ?, product = ?, is_active = ?
             WHERE id = ?",
            [$hsnCode, $gst, $categories, $subcategories, $subSubcategories, $product, $isActive, (int)$id]
        );

        // Sync update to system-wide hsn_codes table
        $descParts = array_filter([$product, $subSubcategories, $subcategories, $categories]);
        $description = !empty($descParts) ? implode(' - ', $descParts) : ('Production HSN ' . $hsnCode);

        try {
            $existingHsn = Database::fetchOne("SELECT id FROM hsn_codes WHERE hsn_code = ? OR hsn_code = ?", [$hsnCode, $record['hsn_code']]);
            if ($existingHsn) {
                Database::execute(
                    "UPDATE hsn_codes SET hsn_code = ?, description = ?, gst_rate = ?, updated_at = NOW() WHERE id = ?",
                    [$hsnCode, $description, $gst, (int)$existingHsn['id']]
                );
            } else {
                Database::execute(
                    "INSERT INTO hsn_codes (hsn_code, description, gst_rate, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())",
                    [$hsnCode, $description, $gst]
                );
            }
        } catch (\Throwable $e) {
            error_log('HSN sync error: ' . $e->getMessage());
        }

        Session::flash('success', 'HSN Code "' . htmlspecialchars($hsnCode) . '" updated successfully.');
        Response::redirect(url('production/hsn-config'));
    }

    /**
     * Delete a HSN config record.
     */
    public function delete(string $id): void {
        if (!hasPermission('production_hsn', 'delete') && !hasPermission('hsn_config', 'delete')) {
            Response::error(403, 'Access denied: You do not have permission to delete HSN Config.');
            return;
        }

        CSRF::validateRequest();

        $record = Database::fetchOne(
            "SELECT * FROM production_hsn_config WHERE id = ?",
            [(int)$id]
        );
        if (!$record) {
            Session::flash('error', 'HSN Config record not found.');
            Response::redirect(url('production/hsn-config'));
            return;
        }

        Database::execute(
            "DELETE FROM production_hsn_config WHERE id = ?",
            [(int)$id]
        );

        // Also clean up from hsn_codes if exists
        try {
            Database::execute("DELETE FROM hsn_codes WHERE hsn_code = ?", [$record['hsn_code']]);
        } catch (\Throwable $e) {
            error_log('HSN delete sync error: ' . $e->getMessage());
        }

        Session::flash('success', 'HSN Code "' . htmlspecialchars($record['hsn_code']) . '" deleted successfully.');
        Response::redirect(url('production/hsn-config'));
    }

    /**
     * Upsert a single HSN/SAC record into both production_hsn_config and hsn_codes.
     */
    public static function upsertHsnRecord(string $hsnCode, ?float $gstRate, string $description, string $category = '', int $userId = 0): void {
        $hsnCode = mb_strtoupper(trim($hsnCode));
        $description = mb_substr(trim($description), 0, 255);
        $category = mb_substr(trim($category), 0, 255);
        $gstVal = $gstRate !== null ? round($gstRate, 2) : null;

        Database::execute(
            "INSERT INTO production_hsn_config
                (hsn_code, gst_percentage, categories, product, is_active, created_by)
             VALUES (?, ?, ?, ?, 1, ?)
             ON DUPLICATE KEY UPDATE
                gst_percentage = COALESCE(VALUES(gst_percentage), gst_percentage),
                categories = COALESCE(NULLIF(VALUES(categories), ''), categories),
                product = COALESCE(NULLIF(VALUES(product), ''), product),
                is_active = 1,
                updated_at = NOW()",
            [
                $hsnCode,
                $gstVal ?? 0.00,
                $category !== '' ? $category : null,
                $description !== '' ? $description : null,
                $userId ?: null,
            ]
        );

        try {
            $existing = Database::fetchOne(
                "SELECT id FROM hsn_codes WHERE UPPER(TRIM(hsn_code)) = ? LIMIT 1",
                [$hsnCode]
            );
            if ($existing) {
                Database::execute(
                    "UPDATE hsn_codes SET description = ?, gst_rate = ?, updated_at = NOW() WHERE id = ?",
                    [$description !== '' ? $description : ('HSN ' . $hsnCode), $gstVal, (int)$existing['id']]
                );
            } else {
                Database::execute(
                    "INSERT INTO hsn_codes (hsn_code, description, gst_rate, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())",
                    [$hsnCode, $description !== '' ? $description : ('HSN ' . $hsnCode), $gstVal]
                );
            }
        } catch (\Throwable $e) {
            error_log('HSN hsn_codes upsert warning: ' . $e->getMessage());
        }
    }

    /**
     * Canonical HSN & GST master rows extracted from the Textile & Garment HSN/SAC Master.
     */
    public static function standardMasterRows(): array {
        $rows = [];
        $add = static function (string $code, string $category, string $description, ?float $rate) use (&$rows): void {
            $rows[] = [
                'hsn_code'    => $code,
                'category'    => $category,
                'description' => $description,
                'gst_rate'    => $rate,
            ];
        };

        foreach ([
            ['5201', 'Raw cotton, not carded or combed'],
            ['5202', 'Cotton waste, including yarn waste and garnetted stock'],
            ['5203', 'Cotton, carded or combed'],
            ['5204', 'Cotton sewing thread'],
            ['5205', 'Cotton yarn, containing 85% or more cotton, not put up for retail sale'],
            ['5206', 'Cotton yarn, containing less than 85% cotton, not put up for retail sale'],
            ['5207', 'Cotton yarn put up for retail sale'],
            ['5208', 'Woven cotton fabrics, containing 85% or more cotton, weighing not more than 200 g/m2'],
            ['5209', 'Woven cotton fabrics, containing 85% or more cotton, weighing more than 200 g/m2'],
            ['5210', 'Woven cotton fabrics, containing less than 85% cotton, mixed mainly or solely with man-made fibres'],
            ['5211', 'Woven cotton fabrics, containing less than 85% cotton, weighing more than 200 g/m2'],
            ['5212', 'Other woven cotton fabrics'],
            ['5401', 'Sewing thread of man-made filaments'],
            ['5402', 'Synthetic filament yarn, including high-tenacity yarn'],
            ['5404', 'Synthetic monofilament and strip, of an apparent width not exceeding 5 mm'],
            ['5407', 'Woven fabrics of synthetic filament yarn'],
            ['5408', 'Woven fabrics of artificial filament yarn'],
            ['5501', 'Synthetic filament tow'],
            ['5502', 'Artificial filament tow'],
            ['5503', 'Synthetic staple fibres, not carded, combed or otherwise processed for spinning'],
            ['5504', 'Artificial staple fibres, not carded, combed or otherwise processed for spinning'],
            ['5508', 'Sewing thread of man-made staple fibres'],
            ['5509', 'Yarn of synthetic staple fibres, not put up for retail sale'],
            ['5510', 'Yarn of artificial staple fibres, not put up for retail sale'],
            ['5511', 'Yarn of man-made staple fibres put up for retail sale'],
            ['5512', 'Woven fabrics containing 85% or more synthetic staple fibres'],
            ['5513', 'Woven fabrics containing less than 85% synthetic staple fibres, mixed mainly with cotton'],
            ['5514', 'Woven fabrics containing less than 85% synthetic staple fibres, mixed mainly with man-made fibres'],
            ['5515', 'Other woven fabrics of synthetic staple fibres'],
            ['5516', 'Woven fabrics of artificial staple fibres'],
            ['5101', 'Wool, not carded or combed'],
            ['5106', 'Yarn of carded wool, not put up for retail sale'],
            ['5111', 'Woven fabrics of carded wool or fine animal hair'],
            ['5112', 'Woven fabrics of combed wool or fine animal hair'],
            ['5007', 'Woven fabrics of silk or silk waste'],
        ] as [$code, $description]) {
            $add($code, 'Raw materials and fabrics', $description, 5.00);
        }

        foreach ([
            ['6101', "Men's or boys' overcoats and similar garments, knitted or crocheted"],
            ['6102', "Women's or girls' overcoats and similar garments, knitted or crocheted"],
            ['6103', "Men's or boys' suits, jackets, trousers and shorts, knitted or crocheted"],
            ['6104', "Women's or girls' suits, dresses, skirts, trousers and shorts, knitted or crocheted"],
            ['6105', "Men's or boys' shirts, knitted or crocheted"],
            ['6106', "Women's or girls' blouses and shirts, knitted or crocheted"],
            ['6109', 'T-shirts, singlets and other vests, knitted or crocheted'],
            ['6110', 'Jerseys, pullovers, cardigans and similar articles, knitted or crocheted'],
            ['6111', "Babies' garments and clothing accessories, knitted or crocheted"],
            ['6112', 'Track suits, ski suits and swimwear, knitted or crocheted'],
            ['6113', 'Garments made from knitted or crocheted coated fabrics'],
            ['6114', 'Other garments, knitted or crocheted'],
            ['6115', 'Hosiery, including socks and tights'],
            ['6116', 'Gloves, mittens and mitts, knitted or crocheted'],
            ['6117', 'Other made-up clothing accessories, knitted or crocheted'],
            ['6201', "Men's or boys' overcoats and similar garments, not knitted or crocheted"],
            ['6202', "Women's or girls' overcoats and similar garments, not knitted or crocheted"],
            ['6203', "Men's or boys' suits, jackets, trousers and shorts, not knitted or crocheted"],
            ['6204', "Women's or girls' suits, dresses, skirts, trousers and shorts, not knitted or crocheted"],
            ['6205', "Men's or boys' shirts, not knitted or crocheted"],
            ['6206', "Women's or girls' blouses and shirts, not knitted or crocheted"],
            ['6207', "Men's or boys' undergarments and nightwear"],
            ['6208', "Women's or girls' undergarments and nightwear"],
            ['6209', "Babies' garments and clothing accessories, not knitted or crocheted"],
            ['6210', 'Garments made from fabrics of headings 5602, 5603, 5903, 5906 or 5907'],
            ['6211', 'Track suits, ski suits and swimwear, not knitted or crocheted'],
            ['6212', 'Brassieres, girdles, corsets, braces, suspenders and similar articles'],
            ['6214', 'Shawls, scarves, mufflers, mantillas, veils and similar articles'],
            ['6217', 'Other made-up clothing accessories'],
        ] as [$code, $description]) {
            $add($code, 'Garments', $description, $code === '6109' ? null : 5.00);
        }

        foreach ([
            ['6301', 'Blankets and travelling rugs'],
            ['6302', 'Bed linen, table linen, toilet linen and kitchen linen, including towels and bedsheets'],
            ['6303', 'Curtains, drapes, interior blinds and bed valances'],
            ['6304', 'Other furnishing articles, including furnishing textiles'],
            ['6305', 'Sacks and bags used for packing goods, of textile materials'],
            ['6307', 'Other made-up textile articles, including certain labels and textile accessories'],
        ] as [$code, $description]) {
            $add($code, 'Home and made-up textiles', $description, 5.00);
        }

        foreach ([
            ['5607', 'Twine, cordage, ropes and cables, whether or not plaited'],
            ['5804', 'Tulles, lace and embroidery lace in the piece, in strips or motifs'],
            ['5806', 'Narrow woven fabrics, including certain woven labels'],
            ['5807', 'Woven labels, badges and similar textile articles'],
            ['5808', 'Braids in the piece; ornamental trimmings in the piece'],
            ['5810', 'Embroidery in the piece, in strips or in motifs'],
        ] as [$code, $description]) {
            $add($code, 'Garment accessories and manufacturing materials', $description, 5.00);
        }

        foreach ([
            ['9606', 'Buttons, press-fasteners, snap-fasteners and parts thereof'],
            ['9607', 'Slide fasteners (zippers) and parts thereof'],
        ] as [$code, $description]) {
            $add($code, 'Garment accessories and manufacturing materials', $description, 12.00);
        }

        foreach ([
            ['3923', 'Articles for the conveyance or packing of goods, of plastics'],
            ['3919', 'Self-adhesive plates, sheets, film, foil, tape and other flat shapes, of plastics'],
        ] as [$code, $description]) {
            $add($code, 'Packaging materials', $description, 18.00);
        }

        foreach ([
            ['4819', 'Cartons, boxes, cases, bags and other packing containers of paper or paperboard'],
            ['4820', 'Registers, notebooks, folders, labels and similar articles of paper or paperboard'],
            ['4821', 'Paper or paperboard labels of all kinds, printed or not'],
        ] as [$code, $description]) {
            $add($code, 'Packaging materials', $description, 12.00);
        }

        foreach ([
            ['8444', 'Machines for extruding, drawing, texturing or cutting man-made textile materials'],
            ['8445', 'Machines for preparing textile fibres and spinning, doubling or twisting textile yarns'],
            ['8446', 'Weaving machines (looms)'],
            ['8447', 'Knitting machines, stitch-bonding machines and machines for making net or lace'],
            ['8448', 'Auxiliary machinery and parts for machines of headings 8444 to 8447'],
            ['8451', 'Machinery for washing, bleaching, dyeing, dressing, finishing, coating or impregnating textile yarns or fabrics'],
            ['8452', 'Sewing machines and furniture, bases and covers specially designed for sewing machines'],
        ] as [$code, $description]) {
            $add($code, 'Machinery and parts', $description, 18.00);
        }

        $add('9987', 'Maintenance and repair services', 'Maintenance, repair and installation services (except construction)', 18.00);
        $add('998713', 'Maintenance and repair services', 'Maintenance and repair services of commercial and industrial machinery', 18.00);
        $add('998719', 'Maintenance and repair services', 'Maintenance and repair services of other machinery and equipment', 18.00);
        $add('9988', 'Job work and manufacturing services', 'Manufacturing services on physical inputs (goods) owned by others', null);
        $add('998821', 'Job work and manufacturing services', 'Textile and apparel job work, including cutting, stitching, embroidery and finishing', null);

        return $rows;
    }

    /**
     * Feed all standard HSN/SAC codes & GST rates into production_hsn_config and hsn_codes.
     */
    public function seedMaster(): void {
        self::ensureSchema();
        if (!hasPermission('production_hsn', 'create') && !hasPermission('hsn_config', 'create')) {
            Response::error(403, 'Access denied.');
            return;
        }
        if (!CSRF::validateRequest()) {
            Session::flash('error', 'Invalid security token.');
            Response::redirect(url('allmodules/hsn-config'));
            return;
        }

        $userId = (int)(Session::get('user_id') ?? 0);
        $rows = self::standardMasterRows();
        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            foreach ($rows as $row) {
                self::upsertHsnRecord($row['hsn_code'], $row['gst_rate'], $row['description'], $row['category'], $userId);
            }
            $db->commit();
            Session::flash('success', 'Fed ' . count($rows) . ' standard HSN/SAC & GST master records into the database.');
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            Session::flash('error', 'Failed to seed HSN master: ' . $e->getMessage());
        }
        Response::redirect(url('allmodules/hsn-config'));
    }

    /**
     * Bulk import HSN codes extracted from PDF (AJAX JSON).
     */
    public function importBulk(): void {
        self::ensureSchema();
        if (!hasPermission('production_hsn', 'create') && !hasPermission('hsn_config', 'create')) {
            Response::json(['success' => false, 'message' => 'Access denied.'], 403);
            return;
        }
        if (!CSRF::validateRequest()) {
            Response::json(['success' => false, 'message' => 'Invalid security token.'], 403);
            return;
        }

        $rawBody = file_get_contents('php://input');
        $payload = !empty($rawBody) ? json_decode($rawBody, true) : $_POST;
        $rows = $payload['rows'] ?? [];
        if (is_string($rows)) {
            $rows = json_decode($rows, true);
        }
        if (!is_array($rows) || empty($rows)) {
            Response::json(['success' => false, 'message' => 'No valid HSN rows provided to import.'], 422);
            return;
        }

        $userId = (int)(Session::get('user_id') ?? 0);
        $savedCount = 0;
        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            foreach ($rows as $r) {
                $code = preg_replace('/[^0-9A-Za-z]/', '', trim((string)($r['hsn_code'] ?? '')));
                if (strlen($code) < 2 || strlen($code) > 8) {
                    continue;
                }
                $desc = trim((string)($r['description'] ?? ''));
                $cat = trim((string)($r['category'] ?? ''));
                $gstRaw = $r['gst_rate'] ?? null;
                $gstRate = ($gstRaw !== null && $gstRaw !== '' && is_numeric($gstRaw))
                    ? max(0.0, min(100.0, (float)$gstRaw))
                    : null;

                self::upsertHsnRecord($code, $gstRate, $desc, $cat, $userId);
                $savedCount++;
            }
            if ($savedCount === 0) {
                throw new \RuntimeException('No valid HSN codes (2-8 chars) were found in the submitted rows.');
            }
            $db->commit();
            Response::json([
                'success' => true,
                'count'   => $savedCount,
                'message' => "Successfully imported {$savedCount} HSN record(s) into the database.",
            ]);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            Response::json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}

if (!class_exists('ProductionHsnController', false)) {
    class_alias('AllmodulesHsnController', 'ProductionHsnController');
}
