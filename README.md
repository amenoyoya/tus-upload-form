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
    - python: `3.7.4` (anaconda: `4.5.11`)
        - flask: `1.0.2`
        - awscli: `1.16.225`
        - boto3: `1.9.215`

***

## Frontend Setup

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

***

## Backend Setup

### Preparation
```bash
# install python modules
$ pip install awscli boto3 flask

# setup awscli
$ aws configure
AWS Access Key ID [None]:     # <- enter: AWS IAM Access Key ID
AWS Secret Access Key [None]: # <- enter: AWS IAM Secret Access Key
Default region name [None]:   # <- enter: AWS S3 region name (el. `us-east-2`)
Default output format [None]: # <- enter: `json`

## -> confirm aws settings: ~/.aws/credentials

# test upload to aws s3
## backetname: your s3 backet name
$ aws s3 cp README.md s3://backetname/README.md
```

***

## Development

### Structure
- Backend:
    - http://localhost:3333
    - Python + Flask
- Frontend:
    - Node.js + Vue.js + Webpack

### Execution
```bash
# run webpack: watch mode
$ yarn webpack --watch

# run python + flask server: http://localhost:3333
$ python server.py
```

***

## TODO

- `HEAD`リクエストが来たときのサーバーサイド処理を追加
