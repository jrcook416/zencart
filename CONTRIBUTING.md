# Contribution Guidelines

## Ways To Contribute

There are numerous ways to contribute to [Zen Cart&reg;](https://www.zen-cart.com/):

1. Enter reproduceable bug reports as Issues on github.
2. Submit PRs containing bug fixes or new features on github
3. Build plugins as 3rd party contributions which shopowners can install to their own site
4. Post snippets of bugfixes or minor tweaks to assist with solving specific questions posted on our support forum at [https://www.zen-cart.com/forum.php](https://www.zen-cart.com/forum.php)
5. Work on a [language pack](https://docs.zen-cart.com/dev/languages/creating_a_language_pack/).

Seasoned open source contributor? Review the guidelines below, fork the repo and start contributing PRs! 

First timer?  Take a look at our [getting started](https://docs.zen-cart.com/dev/contributing/introduction/) document.

## Guidelines
When submitting bug reports, issues, plugins, or code as pull requests, please adhere to the following guidelines:

* Please search previous suggestions before making a new one, as yours may be a duplicate.
* Each suggestion/report/pull-request should be separate. Don't mix multiple issues into one. See our [git workflow](https://docs.zen-cart.com/dev/contributing/github_workflow/) for details.
* Code submissions should comply with the [Coding Standards](https://docs.zen-cart.com/dev/contributing/coding_standards/).
* If reporting a bug/issue, please provide exact steps which one can take to recreate the bug, starting from a brand new fresh install. See [Guidelines for Reporting Bugs](https://docs.zen-cart.com/dev/contributing/issues/).
* If providing a suggestion, please also **explain the business problem the suggestion will solve**.
* If publishing a plugin, be sure it complies with the [Plugin Contribution Guidelines](https://docs.zen-cart.com/dev/plugins/rules/)
* Check your spelling and grammar.

Thank you for your contributions!


## Local Feature Tests (Docker-only)

For local Store/Admin feature tests, use Docker MySQL to mirror CI.
Do not rely on local Homebrew Apache/MySQL/phpMyAdmin for test runs.

### CI-matching database container

```bash
docker rm -f zc-mysql57 2>/dev/null || true
docker run -d --name zc-mysql57 \
  -e MYSQL_ROOT_PASSWORD=root \
  -e MYSQL_DATABASE=db \
  -e MYSQL_USER=db \
  -e MYSQL_PASSWORD=root \
  -p 3306:3306 \
  --health-cmd='mysqladmin ping -h127.0.0.1 -udb -proot --silent' \
  --health-interval=10s --health-timeout=5s --health-retries=12 \
  mysql:5.7

# wait for healthy
until [ "$(docker inspect -f '{{.State.Health.Status}}' zc-mysql57)" = "healthy" ]; do sleep 2; done
```

### Start app server for tests

```bash
HTTP_SERVER=http://localhost:8000 \
ZENCART_TESTFRAMEWORK_RUNNING=1 \
ZENCART_TESTFRAMEWORK_CONFIG_USER=runner \
php -d display_errors=1 -d display_startup_errors=1 \
  -d auto_prepend_file="$(pwd)/not_for_release/testFramework/Support/php_server_prepend.php" \
  -S localhost:8000 -t .
```

### Run suites serially (fresh DB each suite)

Run **Store** and **Admin** separately, recreating the container between suites to guarantee a clean DB state.

```bash
# Store
USER=runner HTTP_SERVER=http://localhost:8000 php vendor/bin/phpunit --testsuite FeatureStore

# Recreate zc-mysql57 (fresh DB), then Admin
USER=runner HTTP_SERVER=http://localhost:8000 php vendor/bin/phpunit --testsuite FeatureAdmin
```

### Cleanup

```bash
docker rm -f zc-mysql57 2>/dev/null || true
```


&nbsp;  
   
*&copy;Copyright 2003-2026, Zen Cart&reg;. All rights reserved.*
