<?php
// This file is part of a 108design source-available software product.
//
// Copyright (C) 2026 Andreas Giesen <andreas@108design.com>
//
// Use and modification are permitted only under the Software License included
// with this distribution. See LICENSE.md for the full terms.

defined('MOODLE_INTERNAL') || die();

/** Upgrade Section Icons without changing assignment IDs or stored files. */
function xmldb_local_sectionicons_upgrade($oldversion): bool {
    global $DB;

    if ($oldversion < 2026100401) {
        $dbman = $DB->get_manager();
        $tables = [
            'local_si_icon' => 'local_sectionicons_icon',
            'local_si_course' => 'local_sectionicons_course',
        ];

        // Check both tables before changing either; never overwrite existing data.
        foreach ($tables as $oldname => $newname) {
            $oldexists = $dbman->table_exists(new xmldb_table($oldname));
            $newexists = $dbman->table_exists(new xmldb_table($newname));
            if ($oldexists === $newexists) {
                throw new coding_exception('Section Icons upgrade requires exactly one of ' . $oldname . ' or ' . $newname);
            }
        }

        foreach ($tables as $oldname => $newname) {
            $table = new xmldb_table($oldname);
            if ($dbman->table_exists($table)) {
                $dbman->rename_table($table, $newname);
            }
        }

        upgrade_plugin_savepoint(true, 2026100401, 'local', 'sectionicons');
    }

    return true;
}
