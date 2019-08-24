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
  </section>
</template>

<script>
import tus from 'tus-js-client';

export default {
  data() {
    return {
      files: []
    }
  },
  methods: {
    onFileChange(e) {
      this.files = e.target.files || e.dataTransfer.files;
    },
    onSubmit() {
      if (this.files.length > 0) {
        const file = this.files[0];
        const upload = new tus.Upload(file, {
          endpoint: "http://localhost:3333/files/", // POSTできるendpointを指定する
          retryDelays: [0, 3000, 5000, 10000, 20000], // リトライ遅延: 0, 3, 5, 10, 20秒
          chunkSize: 1000000, // 1MB, 1回のアップロードで送信するファイルサイズ（bytes）
          metadata: {
            filename: file.name,
            filetype: file.type
          },
          onError: function(error) {
            console.log("Failed because: " + error)
          },
          onProgress: function(bytesUploaded, bytesTotal) {
            var percentage = (bytesUploaded / bytesTotal * 100).toFixed(2)
            console.log(bytesUploaded, bytesTotal, percentage + "%")
          },
          onSuccess: function() {
            console.log("Download %s from %s", upload.file.name, upload.url)
          }
        });
        upload.start();
      }
    }
  }
};
</script>