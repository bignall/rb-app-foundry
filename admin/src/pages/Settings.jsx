/**
 * Settings Page
 *
 * General plugin settings + dynamic add-on settings.
 */
import { useState } from '@wordpress/element';
import {
  Card,
  CardBody,
  CardHeader,
  ToggleControl,
  Button,
  Notice,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

const Settings = ({ settings, addons }) => {
  const [generalSettings, setGeneralSettings] = useState(
    settings?.general?.general || {}
  );
  const [saving, setSaving] = useState(false);
  const [notice, setNotice] = useState(null);

  const saveSettings = async () => {
    setSaving(true);
    try {
      await apiFetch({
        path: '/pluginforge/v1/settings',
        method: 'POST',
        data: {
          general: {
            general: generalSettings,
          },
        },
      });
      setNotice({ status: 'success', message: __('Settings saved.', 'pluginforge') });
    } catch (err) {
      setNotice({
        status: 'error',
        message: err.message || __('Failed to save settings.', 'pluginforge'),
      });
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="pluginforge-settings">
      {notice && (
        <Notice
          status={notice.status}
          isDismissible
          onDismiss={() => setNotice(null)}
        >
          {notice.message}
        </Notice>
      )}

      <Card>
        <CardHeader>
          <h2>{__('General Settings', 'pluginforge')}</h2>
        </CardHeader>
        <CardBody>
          <ToggleControl
            label={__('Delete all data on uninstall', 'pluginforge')}
            help={__(
              'When enabled, all plugin data (settings, posts, tables) will be removed when the plugin is deleted.',
              'pluginforge'
            )}
            checked={generalSettings.delete_data_on_uninstall || false}
            onChange={(value) =>
              setGeneralSettings((prev) => ({
                ...prev,
                delete_data_on_uninstall: value,
              }))
            }
          />

          <div className="pluginforge-settings-actions">
            <Button variant="primary" onClick={saveSettings} isBusy={saving}>
              {__('Save Settings', 'pluginforge')}
            </Button>
          </div>
        </CardBody>
      </Card>

      {/* Dynamic add-on settings sections */}
      {settings?.addons &&
        Object.entries(settings.addons).map(([addonId, addonData]) => (
          <Card key={addonId} className="pluginforge-addon-settings">
            <CardHeader>
              <h2>{addonData.name} {__('Settings', 'pluginforge')}</h2>
            </CardHeader>
            <CardBody>
              <p className="description">
                {__(
                  'Settings for this add-on will appear here when the add-on provides a settings schema.',
                  'pluginforge'
                )}
              </p>
            </CardBody>
          </Card>
        ))}
    </div>
  );
};

export default Settings;
