# Cachebust Assets

[![pipeline status](https://gitlab.rue-de-la-vieille.fr/jerome/cachebust-assets/badges/master/pipeline.svg)](https://gitlab.rue-de-la-vieille.fr/jerome/cachebust-assets/commits/master)
[![coverage report](https://gitlab.rue-de-la-vieille.fr/jerome/cachebust-assets/badges/master/coverage.svg)](https://gitlab.rue-de-la-vieille.fr/jerome/cachebust-assets/commits/master)

This WordPress plugin add a cache busting fragment to assets URL.

Cache busting fragment is based on resource last modification time (`mtime`)
so its URL changes each time the resource changes, forcing browser to load the new version.

That cache busting technique allows to set long time caching on resources, by means of an `Expires`
HTTP header. For example, on Apache:

```apacheconfig
ExpiresActive On
ExpiresDefault "access plus 1 year"
Header append Cache-Control "public"
```

## Installation

### Composer

```bash
composer require jerome-rdlv/cachebust-assets
```

This will install the plugin as a mu-plugin.

You can then load the plugin from another one or from a theme’s `functions.php`.

### Manually

In case of manual installation, either as plugin or mu-plugin,
don’t forget to run `composer install` inside the plugin directory
to install its depedenncies (actually merely the autoloader).

## Usage

### Query string mode

By default, the cache busting fragment is added as a query string parameter to the asset URL. For
example `http://example.org/main.js` becomes `http://example.org/main.js?v=1557260694` with
`1557260694` being the last modification timestamp of the `main.js` file.

Using this query string parameter, the URL still resolve correctly to the corresponding file
and no further configuration is needed.

You can now add cache directives in your server configuration to enable long time caching
of resources. On Apache for example:

```apacheconfig
<IfModule mod_expires.c>
    # expires depending on query string
    <If "%{QUERY_STRING} =~ m#(^|&)v=\d+($|&)#">
        ExpiresActive On
        ExpiresDefault "access plus 1 year"
        Header append Cache-Control "public"
    </If>
</IfModule>
```

### Path mode

Query string parameter may cause issues in some environment like CDN or with some 
web server configurations. In this case you may switch to `path` mode:

```php
add_filter('cachebust_assets_mode', function () {
    return 'path';
});
```

In that mode, our URL `http://example.org/main.js` becomes `http://example.org/main.v1557260694.js`.
You then need to add rewriting rules to your server configuration for it to be able to
correctly resolve that URL to the file.

For apache:

```apacheconfig
# rewrite static resources which have a cachebust fragment
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.+)\.v(\d+)\.([a-z0-9]+)$ $1.$3 [L,E=LT_CACHE:$1]
</IfModule>

<IfModule mod_expires.c>
    # available with apache 2.4 and above only
    <If "-n %{ENV:REDIRECT_LT_CACHE}">
        ExpiresActive On
        ExpiresDefault "access plus 1 year"
        Header append Cache-Control "public"
    </If>
</IfModule>
```

### Disabling

Once loaded, this plugin can still be disabled using the `cachebust_assets` filter:

```php
add_filter('cachebust_assets_enabled', '__return_false');
```
