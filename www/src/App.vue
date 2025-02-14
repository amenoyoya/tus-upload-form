<template>
  <section class="section">
    <div class="container">
      <div class="field">
        <div :class="'control dropfile ' + (dragover? 'dragover': '')"
          @dragover.prevent="onDragOver($event, true)"
          @dragleave.prevent="onDragOver($event, false)"
          @drop.prevent="onDrop"
          @click.prevent="onClick"
        >
          <div class="background" v-if="files.length == 0">
            <i class="fas fa-plus"></i>
          </div>
          <p v-html="dropzone_text"></p>
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
      dragover: false,
      dropzone_text: 'Drop file here, or Click me.'
    }
  },
  methods: {
    onFileChange(e) {
      this.files = e.target.files || e.dataTransfer.files;
      if (this.files.length > 0) {
        this.dropzone_text = '<i class="fas fa-cube"></i>&nbsp;' + this.files[0].name;
      }
    },
    onDragOver(e, status) {
      if (e.dataTransfer.types == "text/plain") {
        // ファイルではなく、html要素をドラッグしてきた時は処理を中止
        return false;
      }
      this.dragover = status;
    },
    onDrop(e) {
      this.dragover = false;
      this.onFileChange(e);
    },
    onClick() {
      const input = document.createElement('input');
      input.type = 'file';
      input.onchange = this.onFileChange;
      input.click();
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