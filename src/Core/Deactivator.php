<?php

declare(strict_types=1);

namespace RBCS\AppForge\Core;

/**
 * Handles plugin deactivation tasks.
 *
 * Cleans up scheduled events and transients but preserves
 * data (options, tables) for potential reactivation.
 *
 * @package RBCS\AppForge\Core
 */
class Deactivator
{
    /**
     * Run deactivation tasks.
     */
    public static function deactivate(): void
    {
        // Clear any scheduled cron events.
        self::clearScheduledEvents();

        // Flush rewrite rules.
        flush_rewrite_rules();
    }

    /**
     * Clear all scheduled cron events belonging to the plugin.
     */
    private static function clearScheduledEvents(): void
    {
        $events = [
            'appforge_daily_maintenance',
            'appforge_hourly_check',
        ];

        foreach ($events as $event) {
            $timestamp = wp_next_scheduled($event);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $event);
            }
        }

        /**
         * Fires when clearing scheduled events during deactivation.
         *
         * Add-ons should hook into this to clear their own cron events.
         */
        do_action('appforge_clear_scheduled_events');
    }
}
