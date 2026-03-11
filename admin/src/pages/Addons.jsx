/**
 * Add-ons Page
 *
 * Displays all available add-ons with toggle switches
 * to activate/deactivate them.
 */
import { useState } from '@wordpress/element';
import {
  Card,
  CardBody,
  CardHeader,
  ToggleControl,
  Notice,
  Spinner,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

const Addons = ({ addons, onRefresh }) => {
  const [loading, setLoading] = useState({});
  const [notices, setNotices] = useState([]);

  const toggleAddon = async (id, currentlyActive) => {
    setLoading((prev) => ({ ...prev, [id]: true }));

    const action = currentlyActive ? 'deactivate' : 'activate';

    try {
      await apiFetch({
        path: `/rb-app-foundry/v1/addons/${id}/${action}`,
        method: 'POST',
      });

      setNotices((prev) => [
        ...prev,
        {
          id: Date.now(),
          status: 'success',
          message: `Add-on ${currentlyActive ? 'deactivated' : 'activated'} successfully.`,
        },
      ]);

      await onRefresh();
    } catch (err) {
      setNotices((prev) => [
        ...prev,
        {
          id: Date.now(),
          status: 'error',
          message: err.message || `Failed to ${action} add-on.`,
        },
      ]);
    } finally {
      setLoading((prev) => ({ ...prev, [id]: false }));
    }
  };

  const dismissNotice = (noticeId) => {
    setNotices((prev) => prev.filter((n) => n.id !== noticeId));
  };

  return (
    <div className="appfoundry-addons">
      {notices.map((notice) => (
        <Notice
          key={notice.id}
          status={notice.status}
          isDismissible
          onDismiss={() => dismissNotice(notice.id)}
        >
          {notice.message}
        </Notice>
      ))}

      {addons.length === 0 ? (
        <Card>
          <CardBody>
            <p>
              {__(
                'No add-ons found. Add add-on folders to the addons/ directory to get started.',
                'rb-app-foundry'
              )}
            </p>
          </CardBody>
        </Card>
      ) : (
        <div className="appfoundry-addons-grid">
          {addons.map((addon) => (
            <Card key={addon.id} className="appfoundry-addon-card">
              <CardHeader>
                <div className="appfoundry-addon-header">
                  <h3>{addon.name}</h3>
                  <span className="appfoundry-addon-version">v{addon.version}</span>
                </div>
              </CardHeader>
              <CardBody>
                <p className="appfoundry-addon-description">{addon.description}</p>

                {addon.dependencies.length > 0 && (
                  <p className="appfoundry-addon-deps">
                    <strong>{__('Requires:', 'rb-app-foundry')}</strong>{' '}
                    {addon.dependencies.join(', ')}
                  </p>
                )}

                <div className="appfoundry-addon-toggle">
                  {loading[addon.id] ? (
                    <Spinner />
                  ) : (
                    <ToggleControl
                      label={addon.active ? __('Active', 'rb-app-foundry') : __('Inactive', 'rb-app-foundry')}
                      checked={addon.active}
                      onChange={() => toggleAddon(addon.id, addon.active)}
                    />
                  )}
                </div>
              </CardBody>
            </Card>
          ))}
        </div>
      )}
    </div>
  );
};

export default Addons;
