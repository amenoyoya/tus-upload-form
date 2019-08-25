from flask import Flask, render_template, request, make_response
from pprint import pprint
from datetime import datetime, timedelta
import os, uuid, base64

# metadata -> dict
def metadata2dict(metadata):
    data = {}
    for field in metadata.split(','):
        values = field.split(' ')
        data[values[0]] = base64.b64decode(values[1]).decode('utf-8')
    return data

# save file, resumable
def save_file(file_id, content):
    with open(file_id, 'ab' if os.path.isfile(file_id) else 'wb') as f:
        f.write(content)
    # 保存済みサイズを返す
    return os.path.getsize(file_id)

# ---

# ベースURLのルーティング関数
## ベースURL: uWSGI環境変数から読み込み
url_for = lambda url: request.environ.get('ROOT_URL', 'http://localhost:3333/') + url

app = Flask(__name__)
# url_for関数を上書き
app.jinja_env.globals.update(url_for = url_for)

files = {} # uploading files

# home
@app.route('/', methods=['GET'])
def home():
    return render_template('home.jinja')

# create file upload
@app.route('/files/', methods=['POST'])
def upload():
    data = {
        'content_length': request.headers.get('Content-Length'),
        'upload_length': request.headers.get('Upload-Length'),
        'tus_resumable': request.headers.get('Tus-Resumable'),
        'upload_metadata': metadata2dict(request.headers.get('Upload-Metadata')),
        'id': str(uuid.uuid4()) # 任意のファイルID生成
    }
    pprint(data)
    if data['upload_metadata']['fileext'] != '':
        # 拡張子がある場合は付与する
        data['id'] += '.' + data['upload_metadata']['fileext']
    res = make_response('', 201)
    res.headers['Location'] = '/files/' + data['id']
    res.headers['Tus-Resumable'] = data['tus_resumable']
    files[data['id']] = int(data['upload_length']) # アップロード予定サイズを保持
    return res

# resume file upload
@app.route('/files/<string:file_id>', methods=['PATCH'])
def resume(file_id):
    data = {
        'content_type': request.headers.get('Content-Type'),
        'content_length': request.headers.get('Content-Length'), # 残りアップロードサイズ
        'upload_offset': request.headers.get('Upload-Offset'), # アップロード済みサイズ
        'tus_resumable': request.headers.get('Tus-Resumable')
    }
    # ファイル保存
    saved_size = save_file(file_id, request.get_data())
    # response
    res = make_response('', 204)
    res.headers['Upload-Expires'] = datetime.now() + timedelta(hours=1) # レジューム不可になる期限＝1時間後
    res.headers['Upload-Offset'] = saved_size # アップロード済みサイズ
    res.headers['Tus-Resumable'] = data['tus_resumable']
    return res

if __name__ == "__main__":
    # run server: http://localhost:3333
    app.run(port=3333, debug=True)
