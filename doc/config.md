# Configuration File

NoctalysFramework uses a configuration file to set up the environment and parameters for the application. This file named `config.json` is located in the root directory of the project. The configuration file is in JSON format and contains various settings that control the behavior of the framework.

# Configuration File Structure
The configuration file is structured as a JSON object with the following
keys:

**Example of a configuration file**
```json
{
    "app": {
        "name": "My Noctalys App",
        "timezone": "auto"
    },
    "env": {
        "extended_compat": false
    },
    "router": {
        "page_scan": [
            {
                "folder_name": "pages",
                "path": "src/Frontend"
            }
        ],
        "error_page": "src/Frontend/pages/error",
        "api": {
            "controller_scan": [
                {
                    "folder_name": "controllers",
                    "path": "src/Backend"
                }
            ],
            "api_url": "/api"
        }
    },
    "layouts": {
        "default": "default",
        "sources": [
            {
                "folder_name": "layouts",
                "path": "src/Frontend"
            }
        ]
    },
    "components": {
        "sources": [
            {
                "folder_name": "components",
                "path": "src/Frontend"
            }
        ]
    },
    "assets": {
        "sources": [
            {
                "folder_name": "assets",
                "path": "src/Frontend"
            }
        ]   
    }
}
```
# Configuration File Keys
- **app**: Contains application-level settings.
  - **name**: The name of the application.
  - **timezone**: The timezone setting, can be "auto" or a specific timezone string.
- **env**: Contains environment settings.
  - **extended_compat**: When true, sets environment variables into `$_ENV` and `put_env()`.
- **router**: Controls routing configuration.
  - **page_scan**: Array of locations to scan for page files.
    - **folder_name**: Name(s) of folders containing page files (string or array).
    - **path**: Base path where these folders can be found.
  - **overrides**: Array of route overrides, mapping one URL to another.
  - **error_page**: Path to the error page file.
  - **api**: API routing settings.
    - **controller_scan**: Array of locations to scan for API controller files.
      - **folder_name**: Name(s) of folders containing controller files (string or array).
      - **path**: Base path where these folders can be found.
    - **api_url**: URL prefix for API routes (e.g., "/api").

- **layouts**: Layout configuration.
  - **default**: Name of the default layout to use.
  - **sources**: Array of locations to scan for layout files.
    - **folder_name**: Name(s) of folders containing layout files (string or array).
    - **path**: Base path where these folders can be found.

- **components**: Component configuration.
  - **sources**: Array of locations to scan for component files.
    - **folder_name**: Name(s) of folders containing component files (string or array).
    - **path**: Base path where these folders can be found.

- **assets**: Asset configuration.
  - **sources**: Array of locations to scan for asset files.
    - **folder_name**: Name(s) of folders containing asset files (string or array).
    - **path**: Base path where these folders can be found.
