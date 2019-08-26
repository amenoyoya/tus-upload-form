import Vue from 'vue';
import App from './App';

// IE11/Safari9用のpolyfill
import 'babel-polyfill';
import 'es6-promise/auto';

new Vue({
  el: '#app', // Vueでマウントする要素
  components: { App }, // 使用するコンポーネント
  template: '<app/>', // el（#app）の中に表示する内容
});