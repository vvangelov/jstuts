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
 * Handles brreg service.
 *
 * @package    profilefield_brregservice
 * @category   profilefield
 * @copyright  2017 Ventsislav Vangelov
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax'], function($, ajax) {

    var BrregReguest = function(fieldId) {
        $('#' + fieldId).keyup(function () {
            var $field = $(this);
            if (!$field.length) {

            }

            var value = $field.val();
            if (value.length !== 9) {

            }

            var deferred = $.Deferred();

            ajax.call([{
                methodname: 'profilefield_brregservice_brreg_service',
                args: {organizationNumber: value},
                done: function(fieldinfo){
                    var result = JSON.parse(fieldinfo);
                    if (result.errors.length !== 0) {

                    } else if (result.data) {
                        $('#id_name').val(result.data.name);
                        $('#id_organization-address').val(result.data.address);
                        $('#id_postnummer').val(result.data.postnummer);
                    }

                    deferred.resolve(fieldinfo);
                },
                fail: (deferred.reject)
            }]);

            return deferred.promise();
        });

    };

    return {
        /**
         * Main initialisation.
         *
         * @param {String} fieldId
         * @return {BrregReguest} A new instance of BrregReguest.
         */
        execute: function(fieldId) {
            return new BrregReguest(fieldId);
        }
    };
});
