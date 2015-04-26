# Knectar Release Manager

As we gain more and more projects to manage it becomes increasingly important to enact some best practices across environments.
Each release may be different but here we can define some commonality.

### Default Settings

- System log cleaning is enabled.
- Symlinks are allowed for templates.  Useful for [composer installer](https://github.com/Cotya/magento-composer-installer).
- Catalog URL suffixes are removed.  Contrary to popular belief, suffixes do not help SEO.
- Category canonical tags are enabled.  This actually is good for SEO.
- Product canonical tags are not enabled because default canonical URLs do not include category names.

### Deployment Webhooks

Automated deployments like [dploy.io](http://dploy.io/) or [ftploy.com](http://ftploy.com/) can trigger `/deployhook.php` on your site.
This script must be requested over HTTPS and with basic authentication.
If authentication is already in place the script will attempt to detect that from the `.htaccess` file.
For public sites or non-Apache servers a `.htpasswd` file is still required.
With no parameters it should display brief instructions.
In pre-deployment the site is put into maintenance mode.
In post-deployment the site is recompiled if necessary, pending updates are applied, the config cache is cleared and then maintenance mode is unset.
