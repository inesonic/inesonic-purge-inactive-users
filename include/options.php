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
     * Trivial class that provides an API to plug-in specific options.
     */
    class Options {
        /**
         * A default inactive time, in days.
         */
        const DEFAULT_INACTIVE_TIME_DAYS = 183;

        /**
         * Static method that is triggered when the plug-in is activated.
         */
        public function plugin_activated() {}

        /**
         * Static method that is triggered when the plug-in is deactivated.
         */
        public function plugin_deactivated() {}

        /**
         * Static method that is triggered when the plug-in is uninstalled.
         */
        public function plugin_uninstalled() {
            $this->delete_option('inactive_time_days');
            $this->delete_option('inactive_roles');
        }

        /**
         * Constructor
         *
         * \param[in] $options_prefix The options prefix to apply to plug-in specific options.
         */
        public function __construct(string $options_prefix) {
            $this->options_prefix = $options_prefix . '_';
        }

        /**
         * Method you can use to obtain the current plugin version.
         *
         * \return Returns the current plugin version.  Returns null if the version has not been set.
         */
        public function version() {
            return $this->get_option('version', null);
        }

        /**
         * Method you can use to set the current plugin version.
         *
         * \param $version The desired plugin version.
         *
         * \return Returns true on success.  Returns false on error.
         */
        public function set_version(string $version) {
            return $this->update_option('version', $version);
        }

        /**
         * Method you can use to obtain the inactive time, in days.
         *
         * \return Returns the inactive time, in days.
         */
        public function inactive_time_days() {
            $r = $this->get_option('inactive_time_days', self::DEFAULT_INACTIVE_TIME_DAYS);
            try {
                $result = intval($r);
            } catch (Exception $e) {
                $result = self::DEFAULT_INACTIVE_TIME_DAYS;
            }

            return $result;
        }

        /**
         * Method you can use to update the inactive time in days.
         *
         * \param[in] $new_inactive_time_days The new inactive time, in days.
         */
        public function set_inactive_time_days(int $new_inactive_time_days) {
            $this->update_option('inactive_time_days', $new_inactive_time_days);
        }

        /**
         * Method you can use to obtain a list of inactive user roles.
         *
         * \return Returns a list of inactive user roles.
         */
        public function inactive_user_roles() {
            return json_decode($this->get_option('inactive_roles', '[]'));
        }

        /**
         * Method you can use to update the list of inactive user roles.
         *
         * \param[in] $new_inactive_user_roles An array of inactive user roles.
         */
        public function set_inactive_user_roles(array $new_inactive_user_roles) {
            $this->update_option('inactive_roles', json_encode($new_inactive_user_roles));
        }

        /**
         * Method you can use to obtain a specific option.  This function is a thin wrapper on the WordPress get_option
         * function.
         *
         * \param $option  The name of the option of interest.
         *
         * \param $default The default value.
         *
         * \return Returns the option content.  A value of false is returned if the option value has not been set and
         *         the default value is not provided.
         */
        private function get_option(string $option, $default = false) {
            return \get_option($this->options_prefix . $option, $default);
        }

        /**
         * Method you can use to add a specific option.  This function is a thin wrapper on the WordPress update_option
         * function.
         *
         * \param $option The name of the option of interest.
         *
         * \param $value  The value to assign to the option.  The value must be serializable or scalar.
         *
         * \return Returns true on success.  Returns false on error.
         */
        private function update_option(string $option, $value = '') {
            return \update_option($this->options_prefix . $option, $value);
        }

        /**
         * Method you can use to delete a specific option.  This function is a thin wrapper on the WordPress
         * delete_option function.
         *
         * \param $option The name of the option of interest.
         *
         * \return Returns true on success.  Returns false on error.
         */
        private function delete_option(string $option) {
            return \delete_option($this->options_prefix . $option);
        }
    }
