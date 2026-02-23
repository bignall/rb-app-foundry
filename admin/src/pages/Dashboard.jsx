/**
 * Dashboard Page
 *
 * Overview of the plugin status: active add-ons, connections, etc.
 */
import { Card, CardBody, CardHeader } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const Dashboard = ({ addons, connections }) => {
  const activeAddons = addons.filter((a) => a.active);
  const connectedCount = Object.values(connections).filter((c) => c.connected).length;
  const totalConnections = Object.keys(connections).length;

  return (
    <div className="pluginforge-dashboard">
      <div className="pluginforge-dashboard-grid">
        <Card>
          <CardHeader>
            <h2>{__('Add-ons', 'pluginforge')}</h2>
          </CardHeader>
          <CardBody>
            <div className="pluginforge-stat">
              <span className="pluginforge-stat-number">{activeAddons.length}</span>
              <span className="pluginforge-stat-label">
                {__('Active', 'pluginforge')}
              </span>
            </div>
            <p className="pluginforge-stat-detail">
              {addons.length} {__('total available', 'pluginforge')}
            </p>
          </CardBody>
        </Card>

        <Card>
          <CardHeader>
            <h2>{__('Connections', 'pluginforge')}</h2>
          </CardHeader>
          <CardBody>
            <div className="pluginforge-stat">
              <span className="pluginforge-stat-number">{connectedCount}</span>
              <span className="pluginforge-stat-label">
                {__('Connected', 'pluginforge')}
              </span>
            </div>
            <p className="pluginforge-stat-detail">
              {totalConnections} {__('total registered', 'pluginforge')}
            </p>
          </CardBody>
        </Card>

        <Card>
          <CardHeader>
            <h2>{__('Quick Start', 'pluginforge')}</h2>
          </CardHeader>
          <CardBody>
            <ol className="pluginforge-quickstart">
              <li>{__('Activate the add-ons you need', 'pluginforge')}</li>
              <li>{__('Configure your platform connections', 'pluginforge')}</li>
              <li>{__('Adjust settings for each add-on', 'pluginforge')}</li>
            </ol>
          </CardBody>
        </Card>
      </div>

      {activeAddons.length === 0 && (
        <Card className="pluginforge-empty-state">
          <CardBody>
            <p>
              {__(
                'No add-ons are active yet. Head over to the Add-ons tab to get started!',
                'pluginforge'
              )}
            </p>
          </CardBody>
        </Card>
      )}
    </div>
  );
};

export default Dashboard;
