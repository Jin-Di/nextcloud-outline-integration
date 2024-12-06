import Vue from 'vue'
import PersonalSettings from './components/PersonalSettings.vue'
Vue.mixin({ methods: { t, n } })

const VueSettings = Vue.extend(PersonalSettings)
new VueSettings().$mount('#outline_prefs')
