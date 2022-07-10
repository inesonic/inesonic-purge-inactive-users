 /**********************************************************************************************************************
 * Copyright 2021, Inesonic, LLC
 *
 * GNU Public License, Version 3:
 *   This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 *   License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any
 *   later version.
 *
 *   This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 *   warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 *   details.
 *
 *   You should have received a copy of the GNU General Public License along with this program.  If not, see
 *   <https://www.gnu.org/licenses/>.
 ***********************************************************************************************************************
 * \file plugins-page.js
 *
 * JavaScript module that manages the purge inactive users configuration via the WordPress Plug-Ins page.
 */

/***********************************************************************************************************************
 * Constants:
 */

const inactiveTimeDaysRe = new RegExp('^[0-9]+$');

/***********************************************************************************************************************
 * Script scope locals:
 */

let lastInactiveTimeDays = null;

/***********************************************************************************************************************
 * Functions:
 */

/**
 * Function that displays the manual configuration fields.
 */
function inesonicPiuToggleConfiguration() {
    let areaRow = jQuery("#inesonic-piu-configuration-area-row");
    if (areaRow.hasClass("inesonic-row-hidden")) {
        areaRow.prop("class", "inesonic-piu-configuration-area-row inesonic-row-visible");
    } else {
        areaRow.prop("class", "inesonic-piu-configuration-area-row inesonic-row-hidden");
    }
}

/**
 * Function that updates the purge inactive users settings fields.
 *
 * \param[in] allRoles         A list of all available user roles.
 *
 * \param[in] inactiveTimeDays The Piu server URL.
 *
 * \param[in] inactiveRoles    The Piu template directory.
 */
function inesonicPiuUpdateFields(allRoles, inactiveTimeDays, inactiveRoles) {
    lastInactiveTimeDays = inactiveTimeDays;
    jQuery("#inesonic-piu-inactive-time-days").val(inactiveTimeDays);

    let selectElement = document.getElementById("inesonic-piu-inactive-roles-select");
    selectElement.options.length = 0;

    for (let roleId in allRoles) {
        let roleDescription = allRoles[roleId];

        let optionElement = document.createElement("option");
        optionElement.value = roleId;

        ('textContent' in optionElement)
            ? (optionElement.textContent = roleDescription)
            : (optionElement.innerText = roleDescription);

        if (inactiveRoles.indexOf(roleId) >= 0) {
            optionElement.setAttribute("selected", "selected");
        }

        selectElement.options.add(optionElement);
    }
}

/**
 * Function that is triggered to update the piu configuration fields.
 */
function inesonicPiuUpdateSettings() {
    jQuery.ajax(
        {
            type: "POST",
            url: ajax_object.ajax_url,
            data: { "action" : "inesonic_piu_get_settings" },
            dataType: "json",
            success: function(response) {
                if (response !== null && response.status == 'OK') {
                    let allRoles = response.all_roles;
                    let inactiveTimeDays = response.inactive_time_days;
                    let inactiveRoles = response.inactive_roles;

                    inesonicPiuUpdateFields(allRoles, inactiveTimeDays, inactiveRoles);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log("Could not get Piu settings: " + errorThrown);
            }
        }
    );
}

/**
 * Function that is triggered to update the piu settings.
 */
function inesonicPiuConfigureSubmit() {
    let inactiveTimeDays = Number(jQuery("#inesonic-piu-inactive-time-days").val());
    let selectedRoles = jQuery("#inesonic-piu-inactive-roles-select").val();

    jQuery.ajax(
        {
            type: "POST",
            url: ajax_object.ajax_url,
            data: {
                "action" : "inesonic_piu_update_settings",
                "inactive_time_days" : inactiveTimeDays,
                "inactive_roles" : selectedRoles
            },
            dataType: "json",
            success: function(response) {
                if (response !== null) {
                    if (response.status == 'OK') {
                        inesonicPiuToggleConfiguration();
                    } else {
                        alert("Failed to update inactive user data:\n" + response.status);
                    }
                } else {
                    alert("Failed to update inactive user data.");
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log("Could not update inactive user data: " + errorThrown);
            }
        }
    );
}

/**
 * Method that is triggered when the user updates the inactive time, in days.
 */
function inesonicValidateInactiveTimeDays() {
    let value = jQuery("#inesonic-piu-inactive-time-days").val();

    if (value === '') {
        lastInactiveTimeDays = '0';
        jQuery("#inesonic-piu-inactive-time-days").val('0');
    } else if (value.match(inactiveTimeDaysRe)) {
        let newValue = value;
        while (newValue.startsWith('0')) {
            newValue = newValue.substring(1);
        }

        if (newValue === '') {
            newValue = '0';
        }

        if (newValue != value) {
            lastInactiveTimeDays = newValue;
            jQuery("#inesonic-piu-inactive-time-days").val(newValue);
        } else {
            lastInactiveTimeDays = value;
        }
    } else {
        jQuery("#inesonic-piu-inactive-time-days").val(lastInactiveTimeDays);
    }
}

/***********************************************************************************************************************
 * Main:
 */

jQuery(document).ready(function($) {
    inesonicPiuUpdateSettings();
    $("#inesonic-piu-configure-link").click(inesonicPiuToggleConfiguration);
    $("#inesonic-piu-configure-submit-button").click(inesonicPiuConfigureSubmit);
    $("#inesonic-piu-inactive-time-days").change(inesonicValidateInactiveTimeDays);
    $("#inesonic-piu-inactive-time-days").on("input", inesonicValidateInactiveTimeDays);
});
