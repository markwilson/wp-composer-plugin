# WordPress Composer Plugin

To be used with `markwilson/wp-template`.

## Usage

```
{
    "require": {
        "markwilson/wp-composer-plugin": "~2.0"
    },
    "extra": {
        "wordpress": {
            "copy-paths": [
                ["../../path-relative-to-web-root", "path-in-web-root"],
                "path-used-in-web-and-project-root"
            ]
        }
    }
}
```
