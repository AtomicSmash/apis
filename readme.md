# Apis

## Setup Twitter API

Create a twitter app and generate API access keys from https://apps.twitter.com/. Then add these inside your environment specific wp-config file (look for these constants):

- TWITTER_CONSUMER_KEY
- TWITTER_CONSUMER_SECRET
- TWITTER_OAUTH_TOKEN
- TWITTER_OAUTH_TOKEN_SECRET

## Using API in theme

You can query the cached tweet by using:

```php
$twitterAPI->get($args);
```
Current arguments include:

- results_per_page
- order


## Todo

- Get API options into the admin interface
- Have a look at turning the classes and different classes into an 'interface'
