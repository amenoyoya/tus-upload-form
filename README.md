# tus-upload-form

## What's this?
tusプロトコルを用いた大サイズファイルのアップロードフォーム

- **tusプロトコル**
    - 公式サイト: https://tus.io/
    - アップロードファイルを小さいチャンクに分割してアップロードするため、サーバーのアップロード制限に引っかからない
    - レジューム機能があるため、アップロード途中でエラーが起きても再開できる

***

## Environment
- OS:
    - Windows10 Pro
    - Ubuntu 18.04 LTS
- CommandLine Tools:
    - for windows: https://github.com/amenoyoya/win-dev-tools
    - nodejs: `10.15.3`
    - yarn: `1.15.2`
- Server Side:
    - slim-admin: https://github.com/amenoyoya/slim-admin
        - PHP: `7.2`
        - Slim Framework: `3.12`

***

## Development

### Structure
- Docker:
    - `web`コンテナ: http://tus-upload-form.localhost
        - PHP 7.2 + Apache 2.4
        - ドキュメントルート: `./www/html/` => `/var/www/html/`
        - Backend:
            - PHP + Slim Framework
        - Frontend:
            - Node.js + Vue.js + Webpack
    - `mailhog`コンテナ: http://mail.tus-upload-form.localhost

### Execution
```bash
# --- Docker ---
$ docker-compose build
$ docker-compose up -d

# --- Backend ---
$ docker-compose exec web bash

---
# install composer libraries
% composer install

% exit
---

# --- Frontend ---
$ cd www/

# install nodejs packages
$ yarn install

# run webpack: watch mode
$ yarn watch
```
