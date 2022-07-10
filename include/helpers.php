<?php
/***********************************************************************************************************************
 * Copyright 2021 - 2022, Inesonic, LLC
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
 */

namespace Inesonic\PurgeInactiveUsers;
    /**
     * Flag indicating if we should use the un-minified versions of our JavaScript and CSS in order to perform
     * debugging.
     */
    const DEBUG_JAVASCRIPT = false;

    /**
     * Function that obtains the correct JavaScript URL based on a JavaScript module name.
     *
     * \param $module_name The name of the JavaScript module to be fetched.
     *
     * \return Returns the requested JavaScript URL.
     */
    function javascript_url(string $module_name) {
        if (DEBUG_JAVASCRIPT) {
            $unminified_file = dirname(__FILE__) . '/assets/js/' . $module_name . '.js';
            if (file_exists($unminified_file)) {
                $extension = '.js';
            }
            else {
                $extension = '.min.js';
            }
        } else {
            $minified_file = dirname(__FILE__) . '/assets/js/' . $module_name . '.min.js';
            if (file_exists($minified_file)) {
                $extension = '.min.js';
            }
            else {
                $extension = '.js';
            }
        }

        return plugin_dir_url(__FILE__) . 'assets/js/' . $module_name . $extension;
    }

    /**
     * Function that obtains the correct CSS URL based on a CSS module name.
     *
     * \param $module_name The name of the JavaScript module to be fetched.
     *
     * \return Returns the requested JavaScript URL.
     */
    function css_url(string $module_name) {
        if (DEBUG_JAVASCRIPT) {
            $extension = '.css';
        } else {
            $minified_file = dirname(__FILE__) . '/assets/css/' . $module_name . '.min.css';
            if (file_exists($minified_file)) {
                $extension = '.min.css';
            }
            else {
                $extension = '.css';
            }
        }

        return plugin_dir_url(__FILE__) . 'assets/css/' . $module_name . $extension;
    }
