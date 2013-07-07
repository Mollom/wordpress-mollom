<?php

/**
 * @file
 * Installation and database schema handling.
 */

/**
 * Provides the plugin database schema and related methods.
 *
 * @see http://codex.wordpress.org/Creating_Tables_with_Plugins
 */
class MollomSchema {

  protected static $version = 1;

  /**
   * Returns the current schema version.
   *
   * @return int
   */
  public static function getVersion() {
    return self::$version;
  }

  /**
   * Returns the CREATE TABLE SQL statement for the 'mollom' database table schema.
   *
   * @return string
   */
  public static function getSchema() {
    global $wpdb;
    $schema = array();

    // Note: dbDelta() requires no spaces between column names in the primary
    // key definition.
    $table = $wpdb->prefix . 'mollom';
    $schema[$table] = "
  entity_type VARCHAR(32) DEFAULT '' NOT NULL,
  entity_id BIGINT DEFAULT 0 NOT NULL,
  content_id VARCHAR(32),
  created INT,
  PRIMARY KEY (entity_type,entity_id),
  KEY content_id (content_id),
  KEY created (created)
";
    return $schema;
  }

  /**
   * Installs the plugin database schema.
   */
  public static function install() {
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $schema = self::getSchema();
    foreach ($schema as $table => $spec) {
      dbDelta("CREATE TABLE $table ($spec)");
    }

    // Do not autoload the schema version option.
    // Mind the WPWTF: No, not FALSE, but "no".
    add_option('mollom_schema_version', 0, '', 'no');

    update_option('mollom_schema_version', self::getVersion());
  }

  /**
   * Updates the plugin database schema, if outdated.
   */
  public static function update() {
    if (get_option('mollom_schema_version', 0) == self::getVersion()) {
      return;
    }
    self::install();
  }

  /**
   * Uninstalls the plugin database schema.
   */
  public static function uninstall() {
    global $wpdb;
    $schema = self::getSchema();
    foreach (array_keys($schema) as $table) {
      $wpdb->query("DROP TABLE IF EXISTS $table");
    }
  }

}
