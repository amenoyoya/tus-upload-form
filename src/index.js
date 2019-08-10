import Vue from 'vue';
import App from './App'    

new Vue({
  el: '#app', // Vueでマウントする要素
  components: { App }, // 使用するコンポーネント
  template: '<app/>', // el（#app）の中に表示する内容
});