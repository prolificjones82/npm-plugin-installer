# npm-plugin-installer

A Composer plugin that automatically installs npm packages and patches `package.json` during Composer install/update operations.

## Features

- Automatically creates `package.json` if it doesn't exist
- Installs npm/yarn packages configured in `composer.json`
- Automatically updates `package.json` with custom configuration
- Supports both npm and yarn package managers
- Separates production and development dependencies
- Runs after every Composer install/update operation

## Requirements

- PHP >= 8.1
- Composer >= 2.0
- Node.js and npm (or yarn)

## Installation

Install the plugin via Composer:

```bash
composer require prolificjones/npm-plugin-installer
```

The plugin will be activated automatically when added to your project.

## Configuration

Configure the plugin in your `composer.json` file under the `extra` section:

```json
{
    "extra": {
        "npm-installer": {
            "manager": "npm",
            "packages": {
                "package-name": "^1.0.0",
                "another-package": "latest"
            },
            "dev-packages": {
                "dev-tool": "^2.0.0"
            },
            "package-json": {
                "scripts": {
                    "build": "webpack",
                    "dev": "webpack --watch"
                },
                "engines": {
                    "node": ">=16.0.0"
                }
            }
        }
    }
}
```

### Configuration Options

- **manager** (string, default: `npm`): The package manager to use. Options: `npm` or `yarn`
- **packages** (object|array): Production dependencies to install
  - As object: `{ "package-name": "version" }`
  - As array: `["package-name", "another-package"]`
- **dev-packages** (object|array): Development dependencies to install (uses `--save-dev` or `--dev`)
  - As object: `{ "package-name": "version" }`
  - As array: `["package-name"]`
- **package-json** (object): Custom configuration to merge into `package.json` (e.g., scripts, engines, keywords)

## Usage

### Basic Setup

Add packages to install in your `composer.json`:

```json
{
    "extra": {
        "npm-installer": {
            "packages": ["lodash", "axios"],
            "dev-packages": ["webpack", "webpack-cli"]
        }
    }
}
```

Then run:

```bash
composer install
```

### With Custom Package Manager

To use yarn instead of npm:

```json
{
    "extra": {
        "npm-installer": {
            "manager": "yarn",
            "packages": {
                "vue": "^3.0.0"
            }
        }
    }
}
```

### With Custom package.json Configuration

Merge additional configuration into `package.json`:

```json
{
    "extra": {
        "npm-installer": {
            "packages": ["react", "react-dom"],
            "package-json": {
                "scripts": {
                    "build": "webpack",
                    "dev": "webpack serve"
                },
                "browserslist": {
                    "production": ["> 1%", "last 2 versions"],
                    "development": ["last 1 chrome version"]
                }
            }
        }
    }
}
```

## How It Works

1. When `composer install` or `composer update` is executed, the plugin hooks into the post-install/post-update events
2. If no `package.json` exists, one is created with minimal default configuration
3. Packages specified in the configuration are installed using the configured package manager
4. If `package-json` configuration is provided, it's recursively merged into the existing `package.json`
5. All changes are output to the console for visibility

## Troubleshooting

### "npm-installer: creating package.json"

This message appears when no `package.json` exists. The plugin creates a basic one automatically.

### "npm-installer: ... failed"

This error occurs when the package manager command fails. Ensure:
- The package manager (npm/yarn) is properly installed
- Package names and versions are correct
- You have internet connectivity to download packages
- The working directory has write permissions

### No packages installed

Verify your `composer.json` configuration:
- The `extra.npm-installer` section exists
- `packages` or `dev-packages` arrays/objects are not empty
- Package names are valid

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! Please feel free to submit pull requests or open issues.

## Author

Lee Jones (prolificjones82)