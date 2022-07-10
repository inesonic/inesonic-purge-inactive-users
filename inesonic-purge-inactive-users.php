<?php
/**
 * Plugin Name:       Inesonic Purge Inactive Users
 * Description:       A small plugin that will purge users with specific roles for more than a specified time.
 * Version:           1.0.0
 * Author:            Inesonic,  LLC
 * Author URI:        https://inesonic.com
 * License:           GPLv3
 * License URI:
 * Requires at least: 5.7
 * Requires PHP:      7.4
 * Text Domain:       inesonic-purge-inactive-users
 * Domain Path:       /locale
 ***********************************************************************************************************************
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
 * \file inesonic-purge-inactive-users.php
 *
 * Main plug-in file.
 */

require_once __DIR__ . "/include/options.php";
require_once __DIR__ . "/include/plugin-page.php";

/**
 * Inesonic WordPress plug-in that will purge users with specific roles older than a specified period.
 */
class InesonicPurgeInactiveUsers {
    const VERSION = '1.0.0';
    const SLUG    = 'inesonic-purge-inactive-users';
    const NAME    = 'Inesonic Purge Inactive Users';
    const AUTHOR  = 'Inesonic, LLC';
    const PREFIX  = 'InesonicPurgeInactiveUsers';

    /**
     * Options prefix.
     */
    const OPTIONS_PREFIX = 'inesonic_pia';

    /**
     * The singleton class instance.
     */
    private static $instance;  /* Plug-in instance */

    /**
     * Method that is called to initialize a single instance of the plug-in
     */
    public static function instance() {
        if (!isset(self::$instance) && !(self::$instance instanceof InesonicPurgeInactiveUsers)) {
            self::$instance = new InesonicPurgeInactiveUsers();
        }
    }

    /**
     * Static method that is triggered when the plug-in is activated.
     */
    public static function plugin_activated() {
        if (defined('ABSPATH') && current_user_can('activate_plugins')) {
            $plugin = isset($_REQUEST['plugin']) ? sanitize_text_field($_REQUEST['plugin']) : '';
            if (check_admin_referer('activate-plugin_' . $plugin)) {
                global $wpdb;
                $wpdb->query(
                    'CREATE TABLE ' . $wpdb->prefix . 'inesonic_purge_user_list' . ' (' .
                        'user_id BIGINT UNSIGNED NOT NULL,' .
                        'changed_timestamp BIGINT UNSIGNED NOT NULL, ' .
                        'PRIMARY KEY (user_id),' .
                        'FOREIGN KEY (user_id) REFERENCES ' . $wpdb->prefix . 'users (ID) ' .
                            'ON DELETE CASCADE' .
                    ')'
                );
            }
        }
    }

    /**
     * Static method that is triggered when the plug-in is deactivated.
     */
    public static function plugin_uninstalled() {
        if (defined('ABSPATH') && current_user_can('activate_plugins')) {
            $plugin = isset($_REQUEST['plugin']) ? sanitize_text_field($_REQUEST['plugin']) : '';
            if (check_admin_referer('deactivate-plugin_' . $plugin)) {
                global $wpdb;
                $wpdb->query('DROP TABLE ' . $wpdb->prefix . 'inesonic_purge_list_list');
            }
        }
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->loader = null;
        $this->twig_template_environment = null;
        $this->redmine_data = null;

        $this->options = new Inesonic\PurgeInactiveUsers\Options(self::OPTIONS_PREFIX);
        $this->plugin_page = new Inesonic\PurgeInactiveUsers\PlugInsPage(
            plugin_basename(__FILE__),
            self::NAME,
            $this->options
        );

        add_action('init', array($this, 'customize_on_initialization'));
        add_action('set_user_role', array($this, 'user_role_changed'), 10, 3);
    }

    /**
     * Method that performs various initialization tasks during WordPress init phase.
     */
    public function customize_on_initialization() {
        add_filter('cron_schedules', array($this, 'add_custom_cron_interval'));
        add_action('inesonic-purge-users', array($this, 'purge_users'));
        if (!wp_next_scheduled('inesonic-purge-users')) {
            $time = time() + 20;
            wp_schedule_event($time, 'inesonic-every-other-day', 'inesonic-purge-users');
        }
    }

    /**
     * Method that adds custom CRON intervals for testing.
     *
     * \param[in] $schedules The current list of CRON intervals.
     *
     * \return Returns updated schedules with new CRON entries added.
     */
    public function add_custom_cron_interval($schedules) {
        $schedules['inesonic-every-other-day'] = array(
            'interval' => 60 * 60 * 24 * 2,
            'display' => esc_html__('Every other day')
        );

        return $schedules;
    }

    /**
     * Method that is triggered periodically to purge users that have been inactive too long.
     */
    public function purge_users() {
        // When we get larger, we will probably want to farm this work out as a distinct microservice.  For now, we do
        // this here.

        $timestamp_threshold = time() - ($this->options->inactive_time_days() * 24 * 60 * 60);

        global $wpdb;
        $query_result = $wpdb->get_results(
            'SELECT user_id FROM ' . $wpdb->prefix . 'inesonic_purge_user_list' . ' WHERE ' .
                'changed_timestamp != 0 AND ' . // changed_timestamp == 0 allows us to force preserve users.
                'changed_timestamp < ' . $timestamp_threshold
        );

        if ($wpdb->num_rows > 0) {
            foreach ($query_result as $result) {
                $user_id = $result->user_id;
                wp_delete_user($user_id);
            }
        }
    }

    /**
     * Method that is triggered when a user's role is changed.
     *
     * \param[in] $user_id   The ID of the user that just changed.
     *
     * \param[in] $new_role  The new user role.
     *
     * \param[in] $old_roles The list of old roles tied to this user.
     */
    public function user_role_changed($user_id, $new_role, $old_roles) {
        $inactive_roles = $this->options->inactive_user_roles();
        if (in_array($new_role, $inactive_roles)) {
            global $wpdb;
            $wpdb->replace(
                $wpdb->prefix . 'inesonic_purge_user_list',
                array('user_id' => $user_id, 'changed_timestamp' => time()),
                array('%d', '%d')
            );
        } else {
            global $wpdb;
            $wpdb->delete(
                $wpdb->prefix . 'inesonic_purge_user_list',
                array('user_id' => $user_id),
                array('%d')
            );
        }
    }
}

/* Instatiate our plug-in. */
InesonicPurgeInactiveUsers::instance();

/* Define critical global hooks. */
register_activation_hook(__FILE__, array('InesonicPurgeInactiveUsers', 'plugin_activated'));
register_uninstall_hook(__FILE__, array('InesonicPurgeInactiveUsers', 'plugin_uninstalled'));
