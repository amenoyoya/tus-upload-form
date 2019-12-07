<?php

namespace Slim\Framework;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * HTTP_UPLOAD_METADATA を連想配列に変換
 * @return array $data
 */
function metadataToArray($metadata) {
    $data = [];
    foreach (explode(',', $metadata) as $field) {
        $values = explode(' ', $field);
        $data[$values[0]] = count($values) > 1? base64_decode($values[1]): '';
    }
    return $data;
}

/**
 * get saved file size
 * @return false if not exists
 */
function getSavedFileSize($id) {
    $path = "./static/uploaded/{$id}";
    if (!file_exists($path)) {
        return false;
    }
    return filesize($path);
}

/**
 * save file, resumable
 * @return int $savedFileSize
 */
function saveFile($id, $content) {
    $path = "./static/uploaded/{$id}";
    // staticディレクトリに配置してダウンロードできるようにする
    if (!is_dir('./static/uploaded')) {
        mkdir('./static/uploaded');
    }
    file_put_contents($path, $content, FILE_APPEND);
    // 保存済みサイズを返す
    return getSavedFileSize($id);
}

/**
 * アップロードしなければならないファイルサイズを保存
 */
function saveUploadSize($id, $size) {
    return file_put_contents("/tmp/{$id}", $size);
}

/**
 * アップロードしなければならないファイルサイズを取得
 * @return int $sizeForUpload
 */
function loadUploadSize($id) {
    $sizeForUpload = file_get_contents("/tmp/${id}");
    return $sizeForUpload === ''? 0: intval($sizeForUpload);
}

// create file upload / tell tus info
Application::api(['post', 'options'], '/api/files/', function (Request $request, Response $response, array $args, array $json) {
    // POST: create file upload
    if ($request->isPost()) {
        $headers = $request->getHeaders();
        $data = [
            'content_length'   => $headers['CONTENT_LENGTH'][0],
            'upload_length'    => $headers['HTTP_UPLOAD_LENGTH'][0],
            'tus_resumable'    => $headers['HTTP_TUS_RESUMABLE'][0],
            'upload_metadata'  => metadataToArray($headers['HTTP_UPLOAD_METADATA'][0]),
            'id'               => bin2hex(openssl_random_pseudo_bytes(16)) // 任意IDをファイル名にする
        ];
        if ($data['upload_metadata']['fileext'] !== '') {
            // 拡張子がある場合は付与する
            $data['id'] .= '.' . $data['upload_metadata']['fileext'];
        }
        // アップロードしなければならないファイルサイズを保存
        saveUploadSize($data['id'], $data['upload_length']);
        return $response->withStatus(201)
            ->withHeader('Location', "/api/files/{$data['id']}")
            ->withHeader('Tus-Resumable', $data['tus_resumable']);
    }
    // OPTIONS: tell tus info
    return $response->withStatus(204)
        ->withHeader('Tus-Resumable', '1.0.0')
        ->withHeader('Tus-Version', '1.0.0,0.2.2,0.2.1')
        ->withHeader('Tus-Max-Size', 1073741824 * 4) // Max: 4GB
        ->withHeader('Tus-Extension', 'creation,expiration,checksum')
        ->withHeader('Tus-Checksum-Algorithm', 'md5,sha1,crc32');
});

// resume file upload / tell tus info / finish file upload
Application::api(['patch', 'options', 'head'], '/api/files/{id}', function (Request $request, Response $response, array $args, array $json) {
    // PATCH: resume file upload
    if ($request->isPatch()) {
        $headers = $request->getHeaders();
        $data = [
            'content_type'   => $headers['CONTENT_TYPE'][0],
            'content_length' => $headers['CONTENT_LENGTH'][0],     // 残りアップロードサイズ
            'upload_offset'  => $headers['HTTP_UPLOAD_OFFSET'][0], // アップロード済みサイズ
            'tus_resumable'  => $headers['HTTP_TUS_RESUMABLE'][0]
        ];
        // ファイル保存
        $saved = saveFile($args['id'], $request->getBody());
        $now = new \DateTime();
        return $response->withStatus(204)
            ->withHeader('Upload-Expires', $now->modify('+1 hour')->format('Y-m-d H:i:s')) // レジューム不可になる期限＝1時間後
            ->withHeader('Upload-Offset', $saved) // アップロード済みサイズ
            ->withHeader('Tus-Resumable', $data['tus_resumable']);
    }
    // OPTIONS: tell tus info
    if ($request->isOptions()) {
        return $response->withStatus(204)
            ->withHeader('Tus-Resumable', '1.0.0')
            ->withHeader('Tus-Version', '1.0.0,0.2.2,0.2.1')
            ->withHeader('Tus-Max-Size', 1073741824 * 4) // Max: 4GB
            ->withHeader('Tus-Extension', 'creation,expiration,checksum')
            ->withHeader('Tus-Checksum-Algorithm', 'md5,sha1,crc32');
    }
    // HEAD: finish file upload
    $saved = getSavedFileSize($args['id']);
    return $response->withStatus($saved === false? 404: 200)
        ->withHeader('Upload-Offset', $saved === false? 0: $saved)
        ->withHeader('Upload-Length', loadUploadSize($args['id']))
        ->withHeader('Tus-Resumable', $request->getHeaders()['HTTP_TUS_RESUMABLE'][0]);
});
