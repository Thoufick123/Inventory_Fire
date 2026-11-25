<?php

namespace App\Services;

require_once __DIR__ . '/ExportService.php';

/**
 * ProductionReportService
 * 
 * Legacy proxy maintaining backward compatibility for existing controllers.
 * Forwards calls directly to the high-performance ExportService.
 */
class AllmodulesReportService extends ExportService
{
}

if (!class_exists('ProductionReportService', false)) {
    class_alias(AllmodulesReportService::class, 'ProductionReportService');
}
