# WP-CLI Staging

## About 

WP-CLI Staging plugin extends WP-CLI with staging management tools for a local WordPress instance. Currently, it provides the ability to import and synchronize remote database (production) into a local database (staging) as well as provides other utilities such as quick backups, restorations, rollbacks and rollforwards.

## Commands

WP-CLI Staging provides a suite of commands accessible via WP-CLI, enabling developers to efficiently manage their local staging environment:

- `wp staging backup`: Creates a backup of the current local database.
- `wp staging rollback`: Reverts the local database to the last backup, facilitating easy undo of recent changes.
- `wp staging rollforward`: Advances the local database to a more recent backup if available, useful after performing a rollback.
- `wp staging import`: Imports the database from the remote server, updating the local environment with production data.
- `wp staging restore`: Restores the local database from a specified backup file, offering flexibility in managing local data states.

## Inspiration

The development of WPCLI Staging was inspired by our use of WP Engine's [Local WP](https://localwp.com/) for managing local WordPress instances and [Cloudways](Cloudways) for hosting. Recognizing the strengths of both platforms and their support for WP-CLI, the plugin aimed to bridge the gap between local and production environments. This helped quickly sync live sites with local clones. 

## Current Limitations and Requirements

WP-CLI Staging focuses on enhancing local development workflows with specific considerations/limitations in mind:

- **Database-centric**: The tool currently supports only pulling the database from the production environment; it does not push changes from staging to production. This probably will be added through future iterations. 
- **Cloudways Optimized**: While designed with Cloudways hosting in mind, the plugin settings may require adjustments to work seamlessly with other hosting providers.
- **SSH Requirement**: The remote host must be accessible via SSH to enable secure communication and data transfer.
- **Local (By WP Engine) Optimized**: While designed with Local by WP Engine in mind, the plugin should work with other local WordPress management systems. 

## Disclaimer and Safety

Cloning a production environment to a local environment could cause issues with processes like e-commerce subscriptions. Please consider taking additional cautions like:

Adding the following to your wp-config.php to signal to plugins that the local environment is a testing environment and disabling the cronjob to protect against unwanted upkeep routines:

```
define( 'DISABLE_WP_CRON', true );
define( 'WP_ENVIRONMENT_TYPE', 'local' );
define( 'WP_LOCAL_DEV', true );
```

Also consider using [Automattic's Safety-Net Plugin](https://github.com/a8cteam51/safety-net) for scrubbing/anonymizing/managing sensitive data.

## What is WP-CLI?

WP-CLI is the official command-line tool for interacting with and managing WordPress sites. It provides a set of command-line tools to manage WordPress installations, allowing developers to update plugins, configure multisite installations, and much more, without using a web browser.

For more information and documentation, visit the [WP-CLI Official Website](https://wp-cli.org/).

## Setup

### Downloading the Plugin

To get started with WPCLI Staging, clone the repository from GitHub into your WordPress plugins directory:

```shell
git clone https://github.com/atwellpub/wpcli-local-staging.git
```

### Configuration

After downloading, activate the plugin through the WordPress dashboard or using WP-CLI:

```shell
wp plugin activate wpcli-local-staging
```

Next, head into wp-admin->Settings->WP-CLI Staging and proceed to fill out your remote SSH details:

![image](https://github.com/atwellpub/wpcli-staging/assets/2002207/3e87c4d0-71b2-4108-98f2-31e74ee31c31)

## Usage 

Once the plugin has been installed, activated, and setup, the command line with wpcli enabled can be opened from your Local UI by accessing the site inside and clicking the "Open site shell" button. Once the terminal is launched then the commands can be ran:

![image](https://github.com/atwellpub/wpcli-staging/assets/2002207/17b36eeb-2980-4886-bfdc-28aaa987e587)


## Troubleshooting

### Issue with terminal saying WP instance not installed (When in Local Site Shell)

```
Warning: No WordPress installation found. If the command 'staging' is in a plugin or theme, pass --path=`path/to/wordpress`.
Error: 'staging' is not a registered wp command. See 'wp help' for available commands.
```

We found that the terminal setting might need to be set to Git Bash for the correct WP CLI path to load. 

![image](https://github.com/atwellpub/wpcli-staging/assets/2002207/dc0d72ea-004c-4b8c-a71c-0079525263ae)

### Issue with Connecting to the Database

If you encounter issues connecting to your local database while using WP-CLI Staging, a possible solution involves updating your `wp-config.php` file to specify the correct MySQL port used by Local.

**Solution**:
1. Open your `wp-config.php` file located in the root of your WordPress installation.
2. Locate the line that defines `DB_HOST`. It typically looks like this:

   ```php
   define( 'DB_HOST', 'localhost' );
   ```

3. Modify the `DB_HOST` value to include the MySQL port listed in Local's Database tab. For example, if Local lists the MySQL port as `10023`, change the line to:

   ```php
   define( 'DB_HOST', 'localhost:10023' );
   ```

This adjustment tells WordPress to connect to the MySQL server using the specific port that Local's MySQL server is listening on, which can resolve connection issues stemming from port conflicts or non-standard configurations.

**Note**: The port number (`10023` in the example above) may vary based on your Local setup. Ensure you use the port number specified within your Local application.

## How to Contribute

WP-CLI Staging is currently in a prototype phase, and I definitely welcome contributions from the community:

- **Report Issues**: Encounter a bug or have a suggestion? Open an issue on our [GitHub repository](https://github.com/atwellpub/wpcli-local-staging/issues).
- **Fork and Pull Request**: Interested in adding a feature or fixing a bug? Fork the repository, make your changes, and submit a pull request.

Your contributions and feedback are invaluable in making WP-CLI Staging a robust tool for WordPress developers. Contributors whose merge requests are accepted will be listed as a contributor so please feel free to participate.


## Connect and Share Your Support

If you find WP-CLI Staging helpful, here's how you can support the asset's development:

- **Star on GitHub**: If you appreciate this plugin, please give this repo a star on GitHub!
    
    -  [Star WPCLI Staging](https://github.com/atwellpub/wpcli-local-staging)

- **Share Your Thoughts**: Found WP-CLI Staging useful? Share your experience on Twitter and LinkedIn.

   - Twitter: Tag [@atwellpub](https://twitter.com/atwellpub) and use the hashtag #WPCLIStaging to share how the plugin has helped you or your team.

   - LinkedIn: Connect and share your thoughts with [Hudson Atwell](https://www.linkedin.com/in/hudsonatwell/) on LinkedIn. 

- **Hire from Codeable**: [Codeable.io](https://codeable.io/?ref=4yHGV) has pre-vetted senior WordPress experts ready to estimate your next project.

Your feedback, support, and contributions are greatly appreciated. 
