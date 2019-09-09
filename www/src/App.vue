<template>
  <section class="section">
    <div class="container">
      <div class="field">
        <div class="control">
          <input class="input" type="file" placeholder="upload file" @change="onFileChange">
        </div>
      </div>
      <div class="field">
        <div class="control">
          <button class="button is-link" @click.prevent="onSubmit">送信</button>
        </div>
      </div>
    </div>
    <div v-if="progress" class="field">
      <div class="notification is-info" v-html="progress"></div>
    </div>
    <div v-if="error" class="field">
      <div class="notification is-danger" v-html="error"></div>
    </div>
  </section>
</template>

<script>
import tus from 'tus-js-client';
import { extname } from 'path';

export default {
  data() {
    return {
      files: [],
      progress: false,
      error: false,
    }
  },
  methods: {
    onFileChange(e) {
      this.files = e.target.files || e.dataTransfer.files;
    },
    onSubmit() {
      if (this.files.length > 0) {
        const self = this;
        const file = self.files[0];
        const extNames = file.name.split('.');

        // clear messages
        self.progress = false, self.error = false;

        const upload = new tus.Upload(file, {
          endpoint: '/api/files/', // POSTできるendpointを指定する
          retryDelays: [0, 3000, 5000, 10000, 20000], // リトライ遅延: 0, 3, 5, 10, 20秒
          chunkSize: 1000000, // 1MB, 1回のアップロードで送信するファイルサイズ（bytes）
          metadata: {
            filename: file.name,
            fileext:  extNames.length > 1? extNames[extNames.length-1]: '',
            filetype: file.type
          },
          onError(error) {
            self.error = '<p>Failed because: ' + error + '</p>';
          },
          onProgress(bytesUploaded, bytesTotal) {
            var percentage = (bytesUploaded / bytesTotal * 100).toFixed(2)
            self.progress = '<p>Uploaded: ' + bytesUploaded + ' / ' + bytesTotal + ' bytes</p><p>' + percentage + ' %</p>';
          },
          onSuccess() {
            // ファイル名取得
            const paths = upload.url.split('/');
            const filename = paths[paths.length -1];
            // アップロード先（ダウンロード可能URL）を表示
            const link = '/static/uploaded/' + filename;
            self.progress = '<p>Download ' + upload.file.name + ' from <a target="_blank" href="' + link + '">' + link + '</a></p>';
          }
        });
        upload.start();
      }
    }
  }
};
</script>