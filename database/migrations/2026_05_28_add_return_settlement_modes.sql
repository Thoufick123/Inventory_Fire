SET @return_settlement_table = CASE
  WHEN EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'returns'
  ) THEN 'returns'
  WHEN EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_returns'
  ) THEN 'production_returns'
  ELSE NULL
END;

SET @return_settlement_sql = IF(
  @return_settlement_table IS NULL,
  'SELECT 1',
  IF(
    EXISTS (
      SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = @return_settlement_table
        AND COLUMN_NAME = 'refund_mode'
    ),
    CONCAT(
      'ALTER TABLE `', @return_settlement_table,
      '` MODIFY COLUMN `refund_mode` ENUM(''CASH'',''UPI'',''CARD'',''ONLINE'',''BANK_TRANSFER'',''SPLIT'',''CREDIT_NOTE'',''STORE_CREDIT'',''NONE'') DEFAULT NULL'
    ),
    CONCAT(
      'ALTER TABLE `', @return_settlement_table,
      '` ADD COLUMN `refund_mode` ENUM(''CASH'',''UPI'',''CARD'',''ONLINE'',''BANK_TRANSFER'',''SPLIT'',''CREDIT_NOTE'',''STORE_CREDIT'',''NONE'') DEFAULT NULL'
    )
  )
);

PREPARE return_settlement_stmt FROM @return_settlement_sql;
EXECUTE return_settlement_stmt;
DEALLOCATE PREPARE return_settlement_stmt;
