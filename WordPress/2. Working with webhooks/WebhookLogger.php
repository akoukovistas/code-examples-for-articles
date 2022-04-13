<?php
declare(strict_types=1);

namespace PinkCrab\FgPosSync\Webhook;

use wpdb;

class WebhookLogger
{
    /**
     * @var WPDB
     */
    private $wpdb;

    private const TABLE_NAME = 'webhook_event_log';

    public function __construct(WPDB $wpdb)
    {
        $this->wpdb = $wpdb;
        $this->add_table_if_doesnt_exist();
    }

    private function add_table_if_doesnt_exist(): void
    {
        $table_name = $this->wpdb->prefix . self::TABLE_NAME;
        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      payload text,
      logged_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY  (id)
    ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * @param string $payload
     */
    public function log_payload(string $payload)
    {
        $this->wpdb->insert($this->wpdb->prefix . self::TABLE_NAME, ['payload' => $payload]);
    }
}