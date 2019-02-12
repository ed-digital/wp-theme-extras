<?php

  function ensureDatabaseTable($create) {
    global $wpdb;
    include_once(__dir__."/../lib/compare-tables/dbStruct.php");
    if (!preg_match("/CREATE\s+TABLE\s+['`\"]?([0-9A-Za-z\_\.]+)['`\"]?/", $create, $match)) {
      throw new Error("Couldn't run table migration, no table name was supplied.");
    }
    $tableName = $match[1];

    // Get the current table structure
    $result = $wpdb->get_row("SHOW CREATE TABLE ".$tableName, ARRAY_A);
    if (!$result) {
      // The table doesn't exist. Just run the create.
      $wpdb->query($create);
      return;
    }

    $oldCreate = $result['Create Table'];

    $updater = new dbStructUpdater();
    $changes = $updater->getUpdates($oldCreate, $create);

    if (!$changes) {
      // No changes to make!
      return;
    }

    // Apply the changes
    foreach ($changes as $r) {
      if(!$wpdb->query($r)) {
        throw new Error("Migrating table structure failed (DB said \"".$wpdb->last_error."\")");
      }
    }
  }