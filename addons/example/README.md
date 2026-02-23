# Example Add-on

This is a reference implementation showing how to create a PluginForge add-on. It's safe to delete.

## What it demonstrates

- Implementing `AddonAbstract` with all required methods
- The `addon.json` metadata file format
- Registering hooks and filters in `boot()`
- Setting defaults in `activate()`
- Defining a settings schema for the admin UI
- Registering custom REST API endpoints
- Creating shortcodes
- Using the `getSetting()` / `updateSetting()` helpers

## Files

```
example/
├── addon.json           # Add-on metadata (required)
├── README.md            # This file
└── src/
    └── ExampleAddon.php # Main add-on class (required)
```

## Usage

1. Go to **PluginForge → Add-ons** in the WordPress admin
2. Toggle "Example Add-on" to active
3. Visit any post to see the example notice (if enabled in settings)
4. Use the `[pluginforge_example]` shortcode in any post/page
5. Hit the REST endpoint at `/wp-json/pluginforge/v1/example/hello`
