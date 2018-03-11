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
 * This is the external API for this profile field.
 *
 * @package    profilefield_brregservice
 * @category   profilefield
 * @copyright  2017 Ventsislav Vangelov
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace profilefield_brregservice;
defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

/**
 * This is the external API for this profile field.
 *
 * @copyright  2016 Shamim Rezaie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends \external_api
{
    /**
     * Brønnøysund Register Centre API url
     */
    const SERVICE_ENDPOINT = 'http://data.brreg.no/enhetsregisteret/enhet/';

    /**
     * API return type format
     * @var string
     */
    public static $format = 'json';


    /**
     * Returns description of get_other_fields() parameters.
     *
     * @return \external_function_parameters
     */
    public static function brreg_service_parameters() {
        return new \external_function_parameters(
            [
                'organizationnumber' => new \external_value(PARAM_ALPHA, 'Brreg organization number', VALUE_REQUIRED)
            ]
        );
    }

    /**
     * Get custom profile fields.
     *
     * @param int $fieldid The field ID
     * @return array Field records
     */
    public static function brreg_service($organizationnumber) {

        $params = self::validate_parameters(self::brreg_service_parameters(),
            array(
                'organizationnumber' => $organizationnumber,
            )
        );
        $context = \context_system::instance();
        self::validate_context($context);

        $response = self::call_brreg_api($params['organizationnumber']);
        $brregdata = self::handle_brreg_response($response);

        if ($brregdata) {
            $result = self::save_brreg_data($brregdata);
            if (!$result) {
                $errors = get_string('internal-problem', 'profilefield_brregservice');
            }
        } else {
            $errors = get_string('organization-number-missing', 'profilefield_brregservice');
        }

        return json_encode([
            'data' => $brregdata,
            'errors' => $errors,
        ]);
    }

    /**
     * Returns description of brreg_service_returns() result value.
     *
     * @return \external_description
     */
    public static function brreg_service_returns() {
        return new \external_value(PARAM_RAW, 'Brreg organization data');
    }


    public static function validate_parameters(\external_description $description, $params) {
        return $params;
    }


    /**
     * Check if organization number exists and return needed data.
     *
     * @param $response
     * @return bool|\stdClass
     */
    private static function handle_brreg_response($response) {
        $response = json_decode($response);

        if (isset($response->status) && $response->status == 400) {
            return false;
        }

        $record = new \stdClass();
        $record->number       = $response->organisasjonsnummer;
        $record->name         = $response->navn;
        $record->address      = sprintf('%s %s %s %s',
            isset($response->forretningsadresse->adresse) ? $response->forretningsadresse->adresse : '',
            isset($response->forretningsadresse->kommunenummer) ? $response->forretningsadresse->kommunenummer : '',
            isset($response->forretningsadresse->kommune) ? $response->forretningsadresse->kommune : '',
            isset($response->forretningsadresse->land) ? $response->forretningsadresse->land : ''
        );
        $record->postnummer   = $response->forretningsadresse->postnummer;

        return $record;
    }

    /**
     * Executes API request and returns data
     * @param $organizationnumber
     * @return array
     */
    private static function call_brreg_api($organizationnumber) {

        $url = self::SERVICE_ENDPOINT . $organizationnumber . '.' . self::$format;

        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);

            $response = curl_exec($ch);

            curl_close($ch);

        } else {
            $response = file_get_contents($url);
        }

        return $response;
    }


    /**
     * Inserts or updates brreg data in db. If the organization number exists the method will overwrite existing data,
     * otherwise will insert the new organization data in the table.
     *
     * @param \stdClass $data brreg api data
     * @return bool true on succes, otherwise false
     */
    private static function save_brreg_data(\stdClass $data) {
        global $DB;
        $result = false;

        try {
            $organizationid = $DB->get_records_select(
                "brreg_number",
                "number = ?",
                [$data->number],
                '',
                'id'
            );

            if (empty($organizationid)) {
                $result = $DB->insert_record('brreg_number', $data);
            } else {
                $data->id = current($organizationid)->id;
                $result = $DB->update_record('brreg_number', $data);
            }
        } catch (\Exception $e) {
            // TODO add some log for sql errors.
            return false;
        }

        return (bool) $result;
    }
}
