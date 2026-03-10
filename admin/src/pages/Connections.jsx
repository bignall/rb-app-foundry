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
  const [mode, setMode]                       = useState('view'); // 'view' | 'edit_credentials'
  const [disconnecting, setDisconnecting]     = useState(false);
  const [disconnectingAll, setDisconnectingAll] = useState(false);
  const [removingAccount, setRemovingAccount] = useState(null); // accountId being removed
  const [oauthLoading, setOauthLoading]       = useState(false);
  const [notice, setNotice]                   = useState(null);

  // Show oauth_success / oauth_error from URL params (after Facebook redirect).
  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const success         = params.get('oauth_success');
    const error           = params.get('oauth_error');
    const oauthConnection = params.get('oauth_connection');
    if (success && success === connection.name) {
      setNotice({ status: 'success', message: `${success} connected successfully.` });
      // Clean up URL params.
      const url = new URL(window.location.href);
      url.searchParams.delete('oauth_success');
      window.history.replaceState({}, '', url.toString());
    } else if (error && (!oauthConnection || oauthConnection === connection.id)) {
      setNotice({ status: 'error', message: `OAuth failed: ${error}` });
      const url = new URL(window.location.href);
      url.searchParams.delete('oauth_error');
      url.searchParams.delete('oauth_connection');
      window.history.replaceState({}, '', url.toString());
    }
  }, []);

  const handleRemoveConnection = async () => {
    setDisconnecting(true);
    setNotice(null);
    try {
      await apiFetch({
        path: `/pluginforge/v1/connections/${connection.id}/credentials`,
        method: 'DELETE',
      });
      await onRefresh();
    } catch (err) {
      setNotice({ status: 'error', message: err.message || __('Failed to remove connection.', 'pluginforge') });
    } finally {
      setDisconnecting(false);
    }
  };

  const handleDisconnectAll = async () => {
    setDisconnectingAll(true);
    setNotice(null);
    try {
      await apiFetch({
        path: `/pluginforge/v1/connections/${connection.id}/accounts`,
        method: 'DELETE',
      });
      await onRefresh();
    } catch (err) {
      setNotice({ status: 'error', message: err.message || __('Failed to disconnect accounts.', 'pluginforge') });
    } finally {
      setDisconnectingAll(false);
    }
  };

  const handleRemoveAccount = async (accountId) => {
    setRemovingAccount(accountId);
    setNotice(null);
    try {
      await apiFetch({
        path: `/pluginforge/v1/connections/${connection.id}/accounts/${accountId}`,
        method: 'DELETE',
      });
      await onRefresh();
    } catch (err) {
      setNotice({ status: 'error', message: err.message || __('Failed to remove account.', 'pluginforge') });
    } finally {
      setRemovingAccount(null);
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

  const isOAuth = connection.auth_type === 'oauth2';

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
            {__('App credentials saved. Connect a Facebook account to complete the setup.', 'pluginforge')}
          </p>
          <div className="pluginforge-connection-actions">
            <Button variant="primary" isBusy={oauthLoading} disabled={oauthLoading} onClick={handleOAuth}>
              {oauthLoading ? <Spinner /> : __('Connect Facebook Account', 'pluginforge')}
            </Button>
            <Button variant="tertiary" onClick={() => setMode('edit_credentials')}>
              {__('Update App Credentials', 'pluginforge')}
            </Button>
          </div>
        </div>
      );
    }

    // Connected — show account list.
    const accounts = connection.accounts || [];
    return (
      <div className="pluginforge-connection-connected">
        <ul className="pluginforge-accounts-list">
          {accounts.map((account) => (
            <li key={account.id} className="pluginforge-account-item">
              <span className="pluginforge-account-info">
                <strong>{account.name}</strong>
                <span className="pluginforge-account-pages">
                  {account.page_count} {account.page_count === 1
                    ? __('page', 'pluginforge')
                    : __('pages', 'pluginforge')}
                </span>
              </span>
              <Button
                variant="link"
                isDestructive
                isBusy={removingAccount === account.id}
                disabled={!!removingAccount || disconnectingAll}
                onClick={() => handleRemoveAccount(account.id)}
              >
                {__('Remove', 'pluginforge')}
              </Button>
            </li>
          ))}
        </ul>
        <div className="pluginforge-connection-actions">
          <Button variant="primary" isBusy={oauthLoading} disabled={oauthLoading || !!removingAccount} onClick={handleOAuth}>
            {oauthLoading ? <Spinner /> : __('Add Account', 'pluginforge')}
          </Button>
          <Button variant="secondary" onClick={() => setMode('edit_credentials')} disabled={!!removingAccount}>
            {__('Update App Credentials', 'pluginforge')}
          </Button>
          <Button
            variant="link"
            isDestructive
            isBusy={disconnectingAll}
            disabled={disconnectingAll || !!removingAccount}
            onClick={handleDisconnectAll}
          >
            {__('Disconnect All Accounts', 'pluginforge')}
          </Button>
          <Button
            variant="link"
            isDestructive
            isBusy={disconnecting}
            disabled={disconnecting || !!removingAccount || disconnectingAll}
            onClick={handleRemoveConnection}
          >
            {__('Remove Connection', 'pluginforge')}
          </Button>
        </div>
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
              ? connection.accounts?.length > 1
                ? `${connection.accounts.length} ${__('accounts', 'pluginforge')}`
                : __('Connected', 'pluginforge')
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
