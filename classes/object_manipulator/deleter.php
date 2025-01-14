<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Deletes files that are old enough and are in S3.
 *
 * @package   tool_objectfs
 * @author    Kenneth Hendricks <kennethhendricks@catalyst-au.net>
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_objectfs\object_manipulator;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/admin/tool/objectfs/lib.php');

class deleter extends manipulator {

    /**
     * How long file must exist after
     * duplication before it can be deleted.
     *
     * @var int
     */
    private $consistencydelay;

    /**
     * Whether to delete local files
     * once they are in remote.
     *
     * @var bool
     */
    private $deletelocal;

    /**
     * Size threshold for pushing files to remote in bytes.
     *
     * @var int
     */
    private $sizethreshold;

    /**
     * deleter constructor.
     *
     * @param sss_client $client S3 client
     * @param object_file_system $filesystem S3 file system
     * @param object $config sssfs config.
     */
    public function __construct($filesystem, $config, $logger) {
        parent::__construct($filesystem, $config);
        $this->consistencydelay = $config->consistencydelay;
        $this->deletelocal = $config->deletelocal;
        $this->sizethreshold = $config->sizethreshold;
        $this->logger = $logger;
        // Inject our logger into the filesystem.
        $this->filesystem->set_logger($this->logger);
    }

    protected function get_query_name() {
        return 'get_delete_candidates';
    }

    protected function get_candidates_sql() {

        $sql = 'SELECT MAX(f.id),
                       f.contenthash,
                       MAX(f.filesize) AS filesize
                  FROM {files} f
             LEFT JOIN {tool_objectfs_objects} o ON f.contenthash = o.contenthash
                 WHERE o.timeduplicated <= ?
                       AND o.location = ?
              GROUP BY f.contenthash,
                       f.filesize,
                       o.location
                HAVING MAX(f.filesize) > ?';

        return $sql;
    }

    protected function get_candidates_sql_params() {
        $consistancythrehold = time() - $this->consistencydelay;
        $params = array($consistancythrehold, OBJECT_LOCATION_DUPLICATED, $this->sizethreshold);

        return $params;
    }

    protected function manipulate_object($objectrecord) {
        $newlocation = $this->filesystem->delete_object_from_local_by_hash($objectrecord->contenthash, $objectrecord->filesize);
        return $newlocation;
    }

    protected function manipulator_can_execute() {
        if ($this->deletelocal == 0) {
            mtrace("Delete local disabled \n");
            return false;
        }

        return true;
    }

}
