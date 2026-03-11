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
    <div className="appfoundry-dashboard">
      <div className="appfoundry-dashboard-grid">
        <Card>
          <CardHeader>
            <h2>{__('Add-ons', 'rb-app-foundry')}</h2>
          </CardHeader>
          <CardBody>
            <div className="appfoundry-stat">
              <span className="appfoundry-stat-number">{activeAddons.length}</span>
              <span className="appfoundry-stat-label">
                {__('Active', 'rb-app-foundry')}
              </span>
            </div>
            <p className="appfoundry-stat-detail">
              {addons.length} {__('total available', 'rb-app-foundry')}
            </p>
          </CardBody>
        </Card>

        <Card>
          <CardHeader>
            <h2>{__('Connections', 'rb-app-foundry')}</h2>
          </CardHeader>
          <CardBody>
            <div className="appfoundry-stat">
              <span className="appfoundry-stat-number">{connectedCount}</span>
              <span className="appfoundry-stat-label">
                {__('Connected', 'rb-app-foundry')}
              </span>
            </div>
            <p className="appfoundry-stat-detail">
              {totalConnections} {__('total registered', 'rb-app-foundry')}
            </p>
          </CardBody>
        </Card>

        <Card>
          <CardHeader>
            <h2>{__('Quick Start', 'rb-app-foundry')}</h2>
          </CardHeader>
          <CardBody>
            <ol className="appfoundry-quickstart">
              <li>{__('Activate the add-ons you need', 'rb-app-foundry')}</li>
              <li>{__('Configure your platform connections', 'rb-app-foundry')}</li>
              <li>{__('Adjust settings for each add-on', 'rb-app-foundry')}</li>
            </ol>
          </CardBody>
        </Card>
      </div>

      {activeAddons.length === 0 && (
        <Card className="appfoundry-empty-state">
          <CardBody>
            <p>
              {__(
                'No add-ons are active yet. Head over to the Add-ons tab to get started!',
                'rb-app-foundry'
              )}
            </p>
          </CardBody>
        </Card>
      )}
    </div>
  );
};

export default Dashboard;
