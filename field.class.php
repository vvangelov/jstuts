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
 *
 * @package    profilefield_brregservice
 * @category   profilefield
 * @copyright  2017 Ventsislav Vangelov
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
class profile_field_brregservice extends profile_field_base
{

    /**
     * Constructor
     *
     * Pulls out the options for brregservice from the database and sets the
     * the corresponding key for the data if it exists
     *
     * @param int $fieldid id of user profile field
     * @param int $userid id of user
     */
    public function __construct($fieldid = 0, $userid = 0) {

        global $DB;
        parent::__construct($fieldid, $userid);

        if (!empty($this->field)) {
            $datafield = $DB->get_field('user_info_data', 'data', array('userid' => $this->userid, 'fieldid' => $this->fieldid));
            if ($datafield !== false) {
                $this->data = $datafield;
            }
        }
    }

    /**
     * Adds the profile field to the moodle form class
     *
     * @param moodleform $mform instance of the moodleform class
     */
    public function edit_field_add($mform) {
        global $PAGE;
        $organizationdata = $this->get_data_for_organization();

        $mform->addElement('text', $this->inputname, format_string($this->field->name));
        $mform->setType($this->inputname, PARAM_INT);

        $mform->addElement('text', 'name', get_string('name', 'profilefield_brregservice'),
            [
                "disabled" => "disabled",
                'value' => !empty($organizationdata) ? current($organizationdata)->name : '',
            ]
        );
        $mform->setType('name', PARAM_ALPHA);

        $mform->addElement('text', 'organization-address', get_string('address', 'profilefield_brregservice'),
            [
                "disabled" => "disabled",
                'value' => !empty($organizationdata) ? current($organizationdata)->address : '',
            ]
        );
        $mform->setType('organization-address', PARAM_ALPHA);

        $mform->addElement('text', 'postnummer', get_string('postnummer', 'profilefield_brregservice'),
            [
                "disabled" => "disabled",
                'value' => !empty($organizationdata) ? current($organizationdata)->postnummer : '',
            ]
        );
        $mform->setType('postnummer', PARAM_ALPHA);

        $PAGE->requires->js_call_amd(
            'profilefield_brregservice/brregRequest',
            'execute',
            [
                'id_' . $this->inputname
            ]
        );

        if ($this->is_required() and !has_capability('moodle/user:update', context_system::instance())) {
            $mform->addRule($this->inputname, get_string('required'), 'nonzero', null, 'client');
        }
    }

    /**
     * Validate the form field from profile page
     *
     * @param stdClass $usernew user input
     * @return string contains error message otherwise NULL
     **/
    public function edit_validate_field($formdata) {
        $errors = [];

        if (!isset($formdata->{$this->inputname})) {
            $errors[$this->inputname] = get_string('organization-number-missing', 'profilefield_brregservice');
        }
        if (!is_numeric($formdata->{$this->inputname}) || strlen($formdata->{$this->inputname}) != 9) {
            $errors[$this->inputname] = get_string('organization-number-invalid-format', 'profilefield_brregservice');
        }

        if (empty($erros)) {
            $this->process_brreg($formdata->{$this->inputname});
        }

        return $errors;
    }

    /**
     * Process the data before it gets saved in database
     *
     * @param stdClass $data from the add/edit profile field form
     * @param stdClass $datarecord The object that will be used to save the record
     * @return stdClass
     */
    public function edit_save_data_preprocess($data, $datarecord) {
        return $data;
    }

    /**
     * HardFreeze the field if locked.
     *
     * @param moodleform $mform instance of the moodleform class
     */
    public function edit_field_set_locked($mform) {
        if (!$mform->elementExists($this->inputname)) {
            return;
        }
        if ($this->is_locked() and !has_capability('moodle/user:update', get_context_instance(CONTEXT_SYSTEM))) {
            $mform->hardFreeze($this->inputname);
            $mform->setConstant($this->inputname, $this->data);
        }
    }

    protected function get_data_for_organization() {
        global $DB;

        return $DB->get_records_select(
            "brreg_number",
            "number = ?",
            [$this->data],
            '',
            '*'
        );
    }

}

