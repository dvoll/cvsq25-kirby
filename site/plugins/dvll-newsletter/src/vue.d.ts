// vue.d.ts
import Vue from 'vue';

declare module 'vue/types/vue' {
  interface Vue {
    $panel: any;
    $store: any;
    $api: any;
    load: () => Promise<any>;
  }
}
