/**
 * PluginForge Admin Panel
 *
 * Entry point for the React-based admin interface.
 */
import { createRoot } from '@wordpress/element';
import App from './App';
import './styles/main.css';

const rootElement = document.getElementById('pluginforge-admin-root');

if (rootElement) {
  const root = createRoot(rootElement);
  root.render(<App />);
}
