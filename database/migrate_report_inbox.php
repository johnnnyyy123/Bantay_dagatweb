<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$pdo = db();
foreach (['admins', 'reports', 'report_history', 'users'] as $requiredTable) {
    $statement = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $statement->execute([$requiredTable]);
    if (!(int) $statement->fetchColumn()) {
        throw new RuntimeException('Required table is missing: ' . $requiredTable . '. Import database\\database.sql first.');
    }
}

$lock = $pdo->query("SELECT GET_LOCK('bantay_dagat_report_inbox_migration', 10)")->fetchColumn();
if ((int) $lock !== 1) {
    throw new RuntimeException('Could not acquire the report Inbox migration lock.');
}

try {
    $columns = [];
    foreach ($pdo->query('SHOW COLUMNS FROM reports')->fetchAll(PDO::FETCH_ASSOC) as $column) {
        $columns[$column['Field']] = $column;
    }
    if (!isset($columns['reference_number'])) {
        throw new RuntimeException('The reports.reference_number column is required.');
    }
    if ($columns['reference_number']['Null'] !== 'YES') {
        $pdo->exec('ALTER TABLE reports MODIFY reference_number VARCHAR(30) NULL');
    }
    if (!isset($columns['reference_sent_at'])) {
        $pdo->exec('ALTER TABLE reports ADD COLUMN reference_sent_at DATETIME NULL AFTER reference_number');
    }
    if (!isset($columns['reference_sent_by'])) {
        $pdo->exec('ALTER TABLE reports ADD COLUMN reference_sent_by BIGINT UNSIGNED NULL AFTER reference_sent_at');
    }

    $constraint = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?'
    );
    $constraint->execute(['reports', 'fk_reports_reference_admin']);
    if (!(int) $constraint->fetchColumn()) {
        $pdo->exec(
            'ALTER TABLE reports ADD CONSTRAINT fk_reports_reference_admin
             FOREIGN KEY (reference_sent_by) REFERENCES admins(admin_id)
             ON DELETE SET NULL ON UPDATE CASCADE'
        );
    }

    $referenceIndexes = [];
    foreach ($pdo->query('SHOW INDEX FROM reports')->fetchAll(PDO::FETCH_ASSOC) as $index) {
        $referenceIndexes[$index['Key_name']][] = $index;
    }
    $hasUniqueReferenceIndex = false;
    foreach ($referenceIndexes as $indexColumns) {
        if (
            count($indexColumns) === 1
            && $indexColumns[0]['Column_name'] === 'reference_number'
            && (int) $indexColumns[0]['Non_unique'] === 0
        ) {
            $hasUniqueReferenceIndex = true;
            break;
        }
    }
    if (!$hasUniqueReferenceIndex) {
        $duplicate = $pdo->query(
            "SELECT reference_number FROM reports
             WHERE reference_number IS NOT NULL
             GROUP BY reference_number HAVING COUNT(*) > 1 LIMIT 1"
        )->fetchColumn();
        if ($duplicate !== false) {
            throw new RuntimeException('Duplicate report reference numbers must be resolved before adding the unique index.');
        }
        if (isset($referenceIndexes['uq_reports_reference_number'])) {
            throw new RuntimeException('Index name uq_reports_reference_number is already used by another index.');
        }
        $pdo->exec('ALTER TABLE reports ADD UNIQUE KEY uq_reports_reference_number (reference_number)');
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS user_inbox_messages (
            inbox_message_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            recipient_user_id BIGINT UNSIGNED NOT NULL,
            report_id BIGINT UNSIGNED NOT NULL,
            reference_number VARCHAR(30) NOT NULL,
            subject VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            admin_id BIGINT UNSIGNED NULL,
            read_at DATETIME NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_user_inbox_recipient_unread (recipient_user_id, read_at, created_at),
            KEY idx_user_inbox_report (report_id, created_at),
            CONSTRAINT fk_user_inbox_recipient FOREIGN KEY (recipient_user_id)
                REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_user_inbox_report FOREIGN KEY (report_id)
                REFERENCES reports(report_id) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_user_inbox_admin FOREIGN KEY (admin_id)
                REFERENCES admins(admin_id) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB'
    );

    echo "Report reference and user Inbox schema is ready. Existing reports and messages were preserved.\n";
} finally {
    $pdo->query("SELECT RELEASE_LOCK('bantay_dagat_report_inbox_migration')");
}
