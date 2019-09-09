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
# webコンテナの www-dataユーザーと Docker実行ユーザーのIDを揃えて Dockerコンテナ起動
$ export UID && docker-compose up -d

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

***

## Memo

### 共有ディレクトリのパーミッション問題
- 本プロジェクトは `./wwww/html` と `docker://web///var/www/html` が共有ディレクトリとなっている
- アップロードファイル保存のため webコンテナ内のApache実行ユーザー（`www-data`）が共有ディレクトリに書き込みできる必要がある
- 普通にDockerを起動してしまうとパーミッションの問題で `www-data` ユーザーが共有ディレクトリにファイルを書き込みできない

#### 解決策
- 試したこと
    1. `/etc/passwd` と `/etc/group` を READ-ONLY でマウント
        ```yaml
        web:
            volumes:
                - ./www/html:/var/www/html # ドキュメントルート
                - ./web/000-default.conf:/etc/apache2/sites-available/000-default.conf
                - ./web/php.ini:/usr/local/etc/php/php.ini
                - /etc/passwd:/etc/passwd:ro # read_only(ro)で passwd を共有
                - /etc/group:/etc/group:ro # read_only(ro)で group を共有
        ```
        - 参考: https://qiita.com/yohm/items/047b2e68d008ebb0f001
        - 手っ取り早くユーザー情報を共有できる利点がある
        - ホストに `www-data` ユーザーがいないと、webコンテナを起動できない
    2. webコンテナ起動時に `wwww-data` ユーザーのIDを Docker実行ユーザーのIDに変更する
        ```yaml
        web:
            command: bash -c 'usermod -o -u ${UID} www-data; groupmod -o -g ${UID} www-data; apachectl -D FOREGROUND'
        ```
        - 参考: https://qiita.com/reflet/items/3516400c37c4f5b0cd6d
        - 今回の場合、この方法でうまく行った
        - Dockerコンテナ起動時、UIDを環境変数にexportしなければならない
            ```bash
            $ export UID && docker-compose up -d
            ```
- 解決策: 上記 2. の方法でうまく行った
