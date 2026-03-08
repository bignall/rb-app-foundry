/**
 * Main App Component
 *
 * Renders the admin panel with tab-based navigation.
 * Tabs are dynamic — active add-ons can register their own tabs.
 */
import { useState, useEffect } from '@wordpress/element';
import { TabPanel, Spinner, Notice } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

import Dashboard from './pages/Dashboard';
import Addons from './pages/Addons';
import Settings from './pages/Settings';
import Connections from './pages/Connections';

const App = () => {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [addons, setAddons] = useState([]);
  const [connections, setConnections] = useState({});
  const [settings, setSettings] = useState({});

  // Fetch initial data.
  useEffect(() => {
    const fetchData = async () => {
      try {
        const [addonsData, connectionsData, settingsData] = await Promise.all([
          apiFetch({ path: '/pluginforge/v1/addons' }),
          apiFetch({ path: '/pluginforge/v1/connections' }),
          apiFetch({ path: '/pluginforge/v1/settings' }),
        ]);

        setAddons(addonsData);
        setConnections(connectionsData);
        setSettings(settingsData);
      } catch (err) {
        setError(err.message || __('Failed to load plugin data.', 'pluginforge'));
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, []);

  // Refresh add-ons data after activation/deactivation.
  const refreshAddons = async () => {
    try {
      const addonsData = await apiFetch({ path: '/pluginforge/v1/addons' });
      setAddons(addonsData);
    } catch (err) {
      setError(err.message);
    }
  };

  // Refresh connections data.
  const refreshConnections = async () => {
    try {
      const connectionsData = await apiFetch({ path: '/pluginforge/v1/connections' });
      setConnections(connectionsData);
    } catch (err) {
      setError(err.message);
    }
  };

  if (loading) {
    return (
      <div className="pluginforge-loading">
        <Spinner />
        <p>{__('Loading PluginForge...', 'pluginforge')}</p>
      </div>
    );
  }

  // Build tabs — core tabs + dynamic tabs from active add-ons.
  const coreTabs = [
    {
      name: 'dashboard',
      title: __('Dashboard', 'pluginforge'),
      className: 'pluginforge-tab-dashboard',
    },
    {
      name: 'addons',
      title: __('Add-ons', 'pluginforge'),
      className: 'pluginforge-tab-addons',
    },
    {
      name: 'connections',
      title: __('Connections', 'pluginforge'),
      className: 'pluginforge-tab-connections',
    },
    {
      name: 'settings',
      title: __('Settings', 'pluginforge'),
      className: 'pluginforge-tab-settings',
    },
  ];

  // Add-on tabs will be dynamically added here when add-ons
  // provide settings schemas. For now, their settings appear
  // under the Settings tab grouped by add-on.

  const renderTab = (tab) => {
    switch (tab.name) {
      case 'dashboard':
        return (
          <Dashboard
            addons={addons}
            connections={connections}
          />
        );
      case 'addons':
        return (
          <Addons
            addons={addons}
            onRefresh={async () => {
              await refreshAddons();
              await refreshConnections();
            }}
          />
        );
      case 'connections':
        return (
          <Connections
            connections={connections}
            onRefresh={refreshConnections}
          />
        );
      case 'settings':
        return (
          <Settings
            settings={settings}
            addons={addons}
          />
        );
      default:
        return null;
    }
  };

  return (
    <div className="pluginforge-admin">
      <div className="pluginforge-header">
        <h1>{__('PluginForge', 'pluginforge')}</h1>
        <span className="pluginforge-version">
          v{window.pluginForgeData?.version || '1.0.0'}
        </span>
      </div>

      {error && (
        <Notice status="error" isDismissible onDismiss={() => setError(null)}>
          {error}
        </Notice>
      )}

      <TabPanel
        className="pluginforge-tabs"
        tabs={coreTabs}
      >
        {renderTab}
      </TabPanel>
    </div>
  );
};

export default App;
