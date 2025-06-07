<?php
// This file is part of My Certificates block for Moodle - http://moodle.org/
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
 * My Certificates block
 *
 * @package    block_mycertificates
 * @copyright  2020 Willian Mano - http://conecti.me
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mycertificates\output;

use moodle_url;
use renderable;
use templatable;
use renderer_base;

/**
 * My Certificates block renderable class.
 *
 * @copyright  2020 Willian Mano
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block implements renderable, templatable {

    /**
     * @var int|null $courseid
     */
    protected $courseid;

    /**
     * Constructor.
     *
     * @param int|null $courseid
     */
    public function __construct($courseid = null) {
        $this->courseid = $courseid;
    }

    /**
     * Export data for template.
     *
     * @param renderer_base $output
     * @return array
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function export_for_template(renderer_base $output) {
        global $USER;

        // Get Moodle certificates
        $certificates = new \block_mycertificates\util\certificates($USER, $this->courseid);
        $issuedcertificates = $certificates->get_all_certificates();

        // Get External certificates
        $external_certificates = $this->get_external_certificates($USER);

        return [
            'hascertificates' => (count($issuedcertificates) || count($external_certificates)) ? true : false,
            'coursescertificates' => $issuedcertificates,
            'externalcertificates' => $external_certificates,
        ];
    }

    /**
     * Get external certificates.
     *
     * @param \stdClass $user
     * @return array
     * @throws \dml_exception
     */
    protected function get_external_certificates($user) {
        global $DB;

        $sql = "
            SELECT id, name, user_id, issued_date
            FROM {hpcl_external_certificates}
            WHERE user_id = :user_id AND status = 'approved'
        ";

        $params = ['user_id' => $user->id];
        $results = $DB->get_records_sql($sql, $params);

        $external_certificates = [];
        foreach ($results as $cert) {
            list($fileUrl, $isImg) = $this->get_certificate_image($cert->id);

            $external_certificates[] = [
                'name' => $cert->name,
                'user_id' => $cert->user_id,
                'issued_date' => !empty($cert->issued_date) ? date('d-m-Y', strtotime($cert->issued_date)) : 'N/A',
                'download_url' => $fileUrl ?? '#',
                'preview' => $isImg ? $fileUrl : '/local/external_certificate/pix/certificate-default.png',
            ];
        }

        return $external_certificates;
    }

    /**
     * Get certificate image or file URL.
     *
     * @param int $certificate_id
     * @return array [$fileUrl, $isImg]
     */
    protected function get_certificate_image($certificate_id) {
        $fileUrl = null;
        $isImg = false;

        $files = get_file_storage()->get_area_files(
            1, // System context ID (hardcoded, adjust if needed)
            'local_external_certificate',
            'certificate',
            $certificate_id,
            null,
            false
        );

        if (!empty($files)) {
            foreach ($files as $file) {
                if ($file->is_directory()) {
                    continue;
                }

                $fileUrl = moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    $file->get_itemid(),
                    $file->get_filepath(),
                    $file->get_filename()
                );

                if ($file->is_valid_image()) {
                    $isImg = true;
                }

                break; // Use first valid file only
            }
        }

        return [$fileUrl, $isImg];
    }
}
