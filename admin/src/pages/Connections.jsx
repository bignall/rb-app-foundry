/**
 * Connections Page
 *
 * Handles three states per connection:
 *  1. Not configured  — show credentials form (auth_fields)
 *  2. App configured  — OAuth2 only: show "Connect with {Platform}" button
 *  3. Connected       — show status + disconnect / update credentials actions
 *
 * For non-OAuth connections (API key, etc.) there are only two states:
 * not connected → connected.
 */
import { useState, useEffect } from '@wordpress/element';
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
 * Credential form — shown when the connection needs app/API credentials.
 */
const CredentialsForm = ({ connection, onSaved, onCancel }) => {
  const [values, setValues] = useState({});
  const [saving, setSaving] = useState(false);
  const [error, setError]   = useState(null);

  const allRequiredFilled = connection.auth_fields
    .filter((f) => f.required)
    .every((f) => values[f.id]?.trim());

  const handleSave = async () => {
    setSaving(true);
    setError(null);
    try {
      await apiFetch({
        path: `/pluginforge/v1/connections/${connection.id}/credentials`,
        method: 'POST',
        data: values,
      });
      setValues({});
      await onSaved();
    } catch (err) {
      setError(err.message || __('Failed to save credentials.', 'pluginforge'));
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="pluginforge-connection-form">
      {error && (
        <Notice status="error" isDismissible onDismiss={() => setError(null)}>
          {error}
        </Notice>
      )}
      {connection.auth_fields.map((field) => (
        <TextControl
          key={field.id}
          label={field.label}
          help={field.description}
          type={field.type === 'password' ? 'password' : 'text'}
          value={values[field.id] || ''}
          onChange={(val) => setValues((prev) => ({ ...prev, [field.id]: val }))}
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
          {saving ? __('Saving…', 'pluginforge') : __('Save & Continue', 'pluginforge')}
        </Button>
        {onCancel && (
          <Button variant="tertiary" disabled={saving} onClick={onCancel}>
            {__('Cancel', 'pluginforge')}
          </Button>
        )}
      </div>
    </div>
  );
};

/**
 * Single connection card — handles all three states.
 */
const ConnectionCard = ({ connection, onRefresh }) => {
  const [mode, setMode]             = useState('view'); // 'view' | 'edit_credentials'
  const [disconnecting, setDisconnecting] = useState(false);
  const [oauthLoading, setOauthLoading]   = useState(false);
  const [notice, setNotice]         = useState(null);

  // Show oauth_success / oauth_error from URL params (after Facebook redirect).
  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const success = params.get('oauth_success');
    const error   = params.get('oauth_error');
    if (success && success === connection.name) {
      setNotice({ status: 'success', message: `${success} connected successfully.` });
      // Clean up URL params.
      const url = new URL(window.location.href);
      url.searchParams.delete('oauth_success');
      window.history.replaceState({}, '', url.toString());
    } else if (error) {
      setNotice({ status: 'error', message: `OAuth failed: ${error}` });
      const url = new URL(window.location.href);
      url.searchParams.delete('oauth_error');
      window.history.replaceState({}, '', url.toString());
    }
  }, []);

  const handleDisconnect = async () => {
    setDisconnecting(true);
    setNotice(null);
    try {
      await apiFetch({
        path: `/pluginforge/v1/connections/${connection.id}/credentials`,
        method: 'DELETE',
      });
      setNotice({ status: 'success', message: __('Disconnected.', 'pluginforge') });
      await onRefresh();
    } catch (err) {
      setNotice({ status: 'error', message: err.message || __('Failed to disconnect.', 'pluginforge') });
    } finally {
      setDisconnecting(false);
    }
  };

  const handleOAuth = async () => {
    setOauthLoading(true);
    setNotice(null);
    try {
      const { url } = await apiFetch({
        path: `/pluginforge/v1/connections/${connection.id}/oauth-url`,
      });
      window.location.href = url;
    } catch (err) {
      setNotice({ status: 'error', message: err.message || __('Failed to start OAuth flow.', 'pluginforge') });
      setOauthLoading(false);
    }
  };

  const isOAuth = connection.auth_type === 'OAuth2';

  // Determine what body to render.
  const renderBody = () => {
    // Edit credentials mode (update existing).
    if (mode === 'edit_credentials') {
      return (
        <CredentialsForm
          connection={connection}
          onSaved={async () => { setMode('view'); await onRefresh(); }}
          onCancel={() => setMode('view')}
        />
      );
    }

    // Not configured at all.
    if (!connection.connected && connection.app_configured === false) {
      return (
        <CredentialsForm
          connection={connection}
          onSaved={async () => { await onRefresh(); }}
          onCancel={null}
        />
      );
    }

    // OAuth2: app credentials saved but not yet OAuth-connected.
    if (isOAuth && !connection.connected && connection.app_configured) {
      return (
        <div className="pluginforge-connection-oauth">
          <p className="pluginforge-connection-hint">
            {__('App credentials saved. Authorize with Facebook to complete the connection.', 'pluginforge')}
          </p>
          <div className="pluginforge-connection-actions">
            <Button
              variant="primary"
              isBusy={oauthLoading}
              disabled={oauthLoading}
              onClick={handleOAuth}
            >
              {oauthLoading ? <Spinner /> : __('Connect with Facebook', 'pluginforge')}
            </Button>
            <Button variant="tertiary" isSmall onClick={() => setMode('edit_credentials')}>
              {__('Update App Credentials', 'pluginforge')}
            </Button>
          </div>
        </div>
      );
    }

    // Connected.
    return (
      <div className="pluginforge-connection-actions">
        {isOAuth && (
          <Button variant="secondary" isSmall onClick={handleOAuth} isBusy={oauthLoading} disabled={oauthLoading}>
            {__('Re-authorize', 'pluginforge')}
          </Button>
        )}
        <Button variant="secondary" isSmall onClick={() => setMode('edit_credentials')}>
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
    );
  };

  return (
    <Card className="pluginforge-connection-card">
      <CardHeader>
        <div className="pluginforge-connection-header">
          <h3>{connection.name}</h3>
          <span className={`pluginforge-connection-status ${connection.connected ? 'connected' : 'disconnected'}`}>
            <Dashicon icon={connection.connected ? 'yes-alt' : 'dismiss'} />
            {connection.connected
              ? __('Connected', 'pluginforge')
              : isOAuth && connection.app_configured
                ? __('Awaiting Authorization', 'pluginforge')
                : __('Not Connected', 'pluginforge')}
          </span>
        </div>
      </CardHeader>
      <CardBody>
        {notice && (
          <Notice status={notice.status} isDismissible onDismiss={() => setNotice(null)}>
            {notice.message}
          </Notice>
        )}
        <p className="pluginforge-connection-auth">
          <strong>{__('Auth:', 'pluginforge')}</strong> {connection.auth_type}
        </p>
        {renderBody()}
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
            <p>{__('No connections registered yet. Activate add-ons that provide platform connections.', 'pluginforge')}</p>
          </CardBody>
        </Card>
      </div>
    );
  }

  return (
    <div className="pluginforge-connections">
      <div className="pluginforge-connections-grid">
        {connectionList.map((connection) => (
          <ConnectionCard key={connection.id} connection={connection} onRefresh={onRefresh} />
        ))}
      </div>
    </div>
  );
};

export default Connections;
