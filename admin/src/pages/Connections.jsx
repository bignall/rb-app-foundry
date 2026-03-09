/**
 * Connections Page
 *
 * Shows all registered platform connections with inline credential forms.
 * Form fields are driven by each connection's auth_fields schema, so no
 * connection-specific logic lives here.
 */
import { useState } from '@wordpress/element';
import {
  Card,
  CardBody,
  CardHeader,
  Button,
  TextControl,
  Notice,
  Spinner,
  Dashicon,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Renders the credential form and status actions for a single connection.
 */
const ConnectionCard = ({ connection, onRefresh }) => {
  const [expanded, setExpanded]           = useState(!connection.connected);
  const [values, setValues]               = useState({});
  const [saving, setSaving]               = useState(false);
  const [disconnecting, setDisconnecting] = useState(false);
  const [notice, setNotice]               = useState(null);

  const handleSave = async () => {
    setSaving(true);
    setNotice(null);
    try {
      await apiFetch({
        path: `/pluginforge/v1/connections/${connection.id}/credentials`,
        method: 'POST',
        data: values,
      });
      setNotice({ status: 'success', message: __('Connected successfully.', 'pluginforge') });
      setExpanded(false);
      setValues({});
      await onRefresh();
    } catch (err) {
      setNotice({
        status: 'error',
        message: err.message || __('Failed to save credentials.', 'pluginforge'),
      });
    } finally {
      setSaving(false);
    }
  };

  const handleDisconnect = async () => {
    setDisconnecting(true);
    setNotice(null);
    try {
      await apiFetch({
        path: `/pluginforge/v1/connections/${connection.id}/credentials`,
        method: 'DELETE',
      });
      setNotice({ status: 'success', message: __('Disconnected.', 'pluginforge') });
      setExpanded(true);
      await onRefresh();
    } catch (err) {
      setNotice({
        status: 'error',
        message: err.message || __('Failed to disconnect.', 'pluginforge'),
      });
    } finally {
      setDisconnecting(false);
    }
  };

  const allRequiredFilled = connection.auth_fields
    .filter((f) => f.required)
    .every((f) => values[f.id]?.trim());

  return (
    <Card className="pluginforge-connection-card">
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
        {notice && (
          <Notice
            status={notice.status}
            isDismissible
            onDismiss={() => setNotice(null)}
          >
            {notice.message}
          </Notice>
        )}

        <p className="pluginforge-connection-auth">
          <strong>{__('Auth:', 'pluginforge')}</strong> {connection.auth_type}
        </p>

        {connection.connected && !expanded && (
          <div className="pluginforge-connection-actions">
            <Button variant="secondary" isSmall onClick={() => setExpanded(true)}>
              {__('Update Credentials', 'pluginforge')}
            </Button>
            <Button
              variant="link"
              isDestructive
              isSmall
              isBusy={disconnecting}
              disabled={disconnecting}
              onClick={handleDisconnect}
            >
              {disconnecting ? <Spinner /> : __('Disconnect', 'pluginforge')}
            </Button>
          </div>
        )}

        {expanded && (
          <div className="pluginforge-connection-form">
            {connection.auth_fields.map((field) => (
              <TextControl
                key={field.id}
                label={field.label}
                help={field.description}
                type={field.type === 'password' ? 'password' : 'text'}
                value={values[field.id] || ''}
                onChange={(val) =>
                  setValues((prev) => ({ ...prev, [field.id]: val }))
                }
                autoComplete={field.type === 'password' ? 'new-password' : 'off'}
              />
            ))}

            <div className="pluginforge-connection-form-actions">
              <Button
                variant="primary"
                isBusy={saving}
                disabled={saving || !allRequiredFilled}
                onClick={handleSave}
              >
                {saving ? __('Saving…', 'pluginforge') : __('Save & Connect', 'pluginforge')}
              </Button>
              {connection.connected && (
                <Button
                  variant="tertiary"
                  disabled={saving}
                  onClick={() => { setExpanded(false); setValues({}); setNotice(null); }}
                >
                  {__('Cancel', 'pluginforge')}
                </Button>
              )}
            </div>
          </div>
        )}
      </CardBody>
    </Card>
  );
};

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
          <ConnectionCard
            key={connection.id}
            connection={connection}
            onRefresh={onRefresh}
          />
        ))}
      </div>
    </div>
  );
};

export default Connections;
