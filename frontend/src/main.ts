import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'

// Bootstrap CSS and Icons
import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap-icons/font/bootstrap-icons.css'
import 'bootstrap/dist/js/bootstrap.bundle.min.js'

// Custom styles
import './style.css'

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#app')
