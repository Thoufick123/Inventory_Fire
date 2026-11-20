import re

with open('app/Services/ExportService.php', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Update getReportData signature
old_sig = """    public static function getReportData(
        string $startDate,
        string $endDate,
        array $selectedModules,
        ?int $productionUnitId = null,
        array $warehouseIds = []
    ): array"""

new_sig = """    public static function getReportData(
        string $startDate,
        string $endDate,
        array $selectedModules,
        ?int $productionUnitId = null,
        array $warehouseIds = [],
        array $concernIds = []
    ): array"""

assert old_sig in content, "old_sig not found"
content = content.replace(old_sig, new_sig, 1)

# 2. Add concern filter variables right after warehouseIds normalization
old_setup = """        $warehouseIds = array_values(array_unique(array_filter(array_map('intval', $warehouseIds), static function (int $id): bool {
            return $id > 0;
        })));"""

new_setup = """        $warehouseIds = array_values(array_unique(array_filter(array_map('intval', $warehouseIds), static function (int $id): bool {
            return $id > 0;
        })));
        $concernIds = array_values(array_unique(array_filter(array_map('intval', $concernIds), static function (int $id): bool {
            return $id > 0;
        })));
        $hasConcerns = !empty($concernIds);
        $concernPlaceholders = $hasConcerns ? implode(',', array_fill(0, count($concernIds), '?')) : '';
        $cWhere = static function (string $col) use ($hasConcerns, $concernPlaceholders): string {
            return $hasConcerns ? " AND {$col} IN ({$concernPlaceholders})" : '';
        };
        $cParams = static function (array $base) use ($hasConcerns, $concernIds): array {
            return $hasConcerns ? array_merge($base, $concernIds) : $base;
        };"""

assert old_setup in content, "old_setup not found"
content = content.replace(old_setup, new_setup, 1)

print("Signatures & setup replaced successfully.")
with open('app/Services/ExportService.php', 'w', encoding='utf-8') as f:
    f.write(content)
