<?php

declare(strict_types=1);

namespace RBCS\PluginForge\Connection;

/**
 * Registry and manager for platform connections.
 *
 * Connections register themselves here. The manager provides
 * a central place to look up, test, and manage all connections.
 *
 * @package RBCS\PluginForge\Connection
 */
class ConnectionManager
{
    /**
     * Registered connections.
     *
     * @var array<string, ConnectionInterface>
     */
    private array $connections = [];

    /**
     * Register a connection.
     *
     * Typically called by add-ons during their boot() method.
     */
    public function register(ConnectionInterface $connection): void
    {
        $this->connections[$connection->getId()] = $connection;

        /**
         * Fires after a connection is registered.
         *
         * @param string              $id         The connection ID.
         * @param ConnectionInterface $connection The connection instance.
         */
        do_action('pluginforge_connection_registered', $connection->getId(), $connection);
    }

    /**
     * Get a connection by ID.
     */
    public function get(string $id): ?ConnectionInterface
    {
        return $this->connections[$id] ?? null;
    }

    /**
     * Get all registered connections.
     *
     * @return array<string, ConnectionInterface>
     */
    public function getAll(): array
    {
        return $this->connections;
    }

    /**
     * Get all connected (authenticated) connections.
     *
     * @return array<string, ConnectionInterface>
     */
    public function getConnected(): array
    {
        return array_filter(
            $this->connections,
            fn(ConnectionInterface $conn) => $conn->isConnected()
        );
    }

    /**
     * Check if a specific connection is registered.
     */
    public function has(string $id): bool
    {
        return isset($this->connections[$id]);
    }

    /**
     * Check if a specific connection is connected.
     */
    public function isConnected(string $id): bool
    {
        return isset($this->connections[$id]) && $this->connections[$id]->isConnected();
    }

    /**
     * Get connections grouped by auth type.
     *
     * @return array<string, ConnectionInterface[]>
     */
    public function getByAuthType(): array
    {
        $grouped = [];
        foreach ($this->connections as $connection) {
            $type = $connection->getAuthType()->value;
            $grouped[$type][] = $connection;
        }
        return $grouped;
    }

    /**
     * Get a summary of all connections and their status.
     *
     * Useful for the admin dashboard.
     *
     * @return array<string, array{id: string, name: string, connected: bool, auth_type: string}>
     */
    public function getSummary(): array
    {
        $summary = [];
        foreach ($this->connections as $id => $connection) {
            $summary[$id] = [
                'id'             => $id,
                'name'           => $connection->getName(),
                'connected'      => $connection->isConnected(),
                'auth_type'      => $connection->getAuthType()->value,
                'auth_fields'    => $connection->getAuthFields(),
                'app_configured' => method_exists($connection, 'isAppConfigured')
                    ? $connection->isAppConfigured()
                    : null,
                'accounts'       => method_exists($connection, 'getAccountsSummary')
                    ? $connection->getAccountsSummary()
                    : null,
            ];
        }
        return $summary;
    }
}
