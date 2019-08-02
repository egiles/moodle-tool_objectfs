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
 * Object client class.
 *
 * @package   tool_objectfs
 * @author    Kenneth Hendricks <kennethhendricks@catalyst-au.net>
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_objectfs\client;

defined('MOODLE_INTERNAL') || die();

class object_client {

    public function get_availability() {
        return false;
    }

    public function register_stream_wrapper() {
        return false;
    }

    /**
     * Does the storage support pre-signed URLs.
     *
     * @return bool.
     */
    public function support_presigned_urls() {
        return false;
    }

    /**
     * Generates pre-signed URL to storage file from its hash.
     *
     * @param string $contenthash File content hash.
     * @param array $headers request headers.
     *
     * @throws \coding_exception
     */
    public function generate_presigned_url($contenthash, $headers) {
        throw new \coding_exception("Pre-signed URLs not supported");
    }

    /**
     * Checks that Content-Disposition represented in headers.
     *
     * @param array $headers request headers.
     *
     * @return bool
     */
    public function content_disposition_exists_in_headers($headers) {
        foreach ($headers as $header) {
            $found = strpos($header, 'Content-Disposition');

            if ($found !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns Content-Disposition header from headers.
     *
     * @param array $headers request headers.
     *
     * @return string file name.
     * @throws \coding_exception
     */
    public function get_content_disposition_header($headers) {
        foreach ($headers as $header) {
            $found = strpos($header, 'Content-Disposition');

            if ($found !== false) {
                return substr($header, 21);
            }
        }
        throw new \coding_exception("Couldn't find Content-Disposition in headers");
    }

}
