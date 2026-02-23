/**
 * Connections Page
 *
 * Shows all registered platform connections and their status.
 */
import { Card, CardBody, CardHeader, Dashicon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const Connections = ({ connections, onRefresh }) => {
  const connectionList = Object.values(connections);

  if (connectionList.length === 0) {
    return (
      <div className="pluginforge-connections">
        <Card>
          <CardBody>
            <p>
              {__(
                'No connections registered yet. Activate add-ons that provide platform connections.',
                'pluginforge'
              )}
            </p>
          </CardBody>
        </Card>
      </div>
    );
  }

  return (
    <div className="pluginforge-connections">
      <div className="pluginforge-connections-grid">
        {connectionList.map((connection) => (
          <Card key={connection.id} className="pluginforge-connection-card">
            <CardHeader>
              <div className="pluginforge-connection-header">
                <h3>{connection.name}</h3>
                <span
                  className={`pluginforge-connection-status ${
                    connection.connected ? 'connected' : 'disconnected'
                  }`}
                >
                  <Dashicon icon={connection.connected ? 'yes-alt' : 'dismiss'} />
                  {connection.connected
                    ? __('Connected', 'pluginforge')
                    : __('Not Connected', 'pluginforge')}
                </span>
              </div>
            </CardHeader>
            <CardBody>
              <p className="pluginforge-connection-auth">
                <strong>{__('Auth Type:', 'pluginforge')}</strong> {connection.auth_type}
              </p>
            </CardBody>
          </Card>
        ))}
      </div>
    </div>
  );
};

export default Connections;
