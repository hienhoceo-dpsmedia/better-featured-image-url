## Description
Welcome to the Featured Image with URL Plugin repository on GitHub. Here you can browse the source, look at open issues and keep track of development. 

If you are not a developer, please use the [plugin page](https://wordpress.org/plugins/featured-image-with-url/) on WordPress.org.

## Better Featured Image URL additions

Version 1.1.0 adds an optional setting under Settings > Featured Image with URL to download external featured image URLs into the WordPress Media Library and set the downloaded attachment as the real featured image. Legacy `_harikrutfiwu_*` and `_knawatfibu_*` meta compatibility is preserved.

Run the lightweight CLI regression tests with:

```bash
php tests/test-download-external-images.php
```

## Deployment
We use [10up/action-wordpress-plugin-deploy](https://github.com/10up/action-wordpress-plugin-deploy) Github Action for auto deployment to WordPress.
