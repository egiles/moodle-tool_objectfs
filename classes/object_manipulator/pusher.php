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
 * Pushes files to remote storage if they meet the configured criterea.
 *
 * @package   tool_objectfs
 * @author    Kenneth Hendricks <kennethhendricks@catalyst-au.net>
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_objectfs\object_manipulator;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/admin/tool/objectfs/lib.php');

class pusher extends manipulator {

    /**
     * Size threshold for pushing files to remote in bytes.
     *
     * @var int
     */
    private $sizethreshold;

    /**
     * Minimum age of a file to be pushed to remote in seconds.
     *
     * @var int
     */
    private $minimumage;

    /**
     * The maximum upload file size in bytes.
     *
     * @var int
     */
    private $maximumfilesize;

    /**
     * Pusher constructor.
     *
     * @param object_client $client remote object client
     * @param object_file_system $filesystem objectfs file system
     * @param object $config objectfs config.
     */
    public function __construct($filesystem, $config, $logger) {
        parent::__construct($filesystem, $config);
        $this->sizethreshold = $config->sizethreshold;
        $this->minimumage = $config->minimumage;
        $this->maximumfilesize = $this->filesystem->get_maximum_upload_filesize();

        $this->logger = $logger;
        // Inject our logger into the filesystem.
        $this->filesystem->set_logger($this->logger);
    }

    protected function get_query_name() {
        return 'get_push_candidates';
    }

    protected function get_candidates_sql() {
        $sql = 'SELECT MAX(f.id),
                       f.contenthash,
                       MAX(f.filesize) AS filesize
                  FROM {files} f
             LEFT JOIN {tool_objectfs_objects} o ON f.contenthash = o.contenthash
                  WHERE f.filesize > :threshold
                        AND f.filesize < :maximum_file_size
                        AND f.timecreated <= :maxcreatedtimstamp
                        AND (o.location IS NULL OR o.location = :object_location)
               GROUP BY f.contenthash, o.location';

        return $sql;
    }

    protected function get_candidates_sql_params() {
        $params = array(
            'maxcreatedtimstamp' => time() - $this->minimumage,
            'threshold' => $this->sizethreshold,
            'maximum_file_size' => $this->maximumfilesize,
            'object_location' => OBJECT_LOCATION_LOCAL,
        );

        return $params;
    }

    protected function manipulate_object($objectrecord) {
        $newlocation = $this->filesystem->copy_object_from_local_to_external_by_hash($objectrecord->contenthash, $objectrecord->filesize);
        return $newlocation;
    }

}


