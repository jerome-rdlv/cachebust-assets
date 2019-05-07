# Cachebust Assets

This plugin add a cache busting fragment to assets URL.

Once loaded, this plugin can still be disabled using the `cachebust_assets` filter:

```php
add_filter('cachebust_assets', '__return_false');
```


The fragment is based on resource last modification time (`mtime`) so its URL changes each time
the resource changes, forcing browser to load the new version.

That cache busting technique allows to set long time caching on resources, by means of an `Expires`
HTTP header. For example, on Apache:

```apacheconfig
Set Header…
```

## If path cache busting fragment

Rewrite rule needed.

## If query string cache busting fragment

htaccess 