import { createApp } from "vue"
import { createPinia } from "pinia"
import router from "./router"
import Toast from "vue-toastification"
import "vue-toastification/dist/index.css"

import App from "./App.vue"
import "./style.css"

const app = createApp(App)

// Pinia store
app.use(createPinia())

// Router
app.use(router)

// Toast notifications
app.use(Toast, {
  position: "top-right",
  timeout: 5000,
  closeOnClick: true,
  pauseOnFocusLoss: true,
  pauseOnHover: true,
  draggable: true,
  draggablePercent: 0.6,
  showCloseButtonOnHover: false,
  hideProgressBar: false,
  closeButton: "button",
  icon: true,
  rtl: false,
})

app.mount("#app")
