# tus-upload-form

## What's this?
tusプロトコルを用いた大サイズファイルのアップロードフォーム

- **tusプロトコル**
    - 公式サイト: https://tus.io/
    - アップロードファイルを小さいチャンクに分割してアップロードするため、サーバーのアップロード制限に引っかからない
    - レジューム機能があるため、アップロード途中でエラーが起きても再開できる

***

## Setup

### Environment
- OS: Windows10 Pro
- CommandLine Tools: https://github.com/amenoyoya/win-dev-tools
    - nodejs: `10.15.3`
    - yarn: `1.15.2`


### Preparation
```bash
# create minimal vue project
$ curl https://raw.githubusercontent.com/amenoyoya/node-projects/master/vue.js | node -
$ yarn install

# test run
$ yarn start
# -> start webpack-dev-server at http://localhost:3000
```


### Installation
```bash
# install tus-js-client
$ yarn add -D tus-js-client
```
