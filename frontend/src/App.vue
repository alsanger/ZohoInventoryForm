<template>
  <div id="app">
    <router-view></router-view>

    <div v-if="authMessage" :class="['auth-status-message', authStatus]">
      {{ authMessage }}
      <button @click="clearAuthMessage" class="close-button">X</button>
    </div>

  </div>
</template>

<script>
export default {
  name: 'App',
  data() {
    return {
      authStatus: null,
      authMessage: null,
    };
  },
  mounted() {
    // Получаем параметры запроса из текущего URL
    const params = new URLSearchParams(window.location.search);
    const status = params.get('auth_status');
    const message = params.get('message');

    if (status && message) {
      this.authStatus = status;
      // Декодируем сообщение, так как оно было закодировано в URL
      this.authMessage = decodeURIComponent(message);

      // Очищаем параметры из URL, чтобы они не оставались при обновлении страницы
      // Используем replaceState, чтобы не добавлять запись в историю браузера
      history.replaceState({}, document.title, window.location.pathname);
    }
  },
  methods: {
    clearAuthMessage() {
      this.authStatus = null;
      this.authMessage = null;
    }
  }
};
</script>

<style>
.auth-status-message {
  padding: 10px 20px;
  margin: 10px 0;
  border-radius: 5px;
  position: relative;
  font-family: sans-serif;
  color: white;
}

.auth-status-message.success {
  background-color: #4CAF50;
}

.auth-status-message.error {
  background-color: #f44336;
}

.close-button {
  background: none;
  border: none;
  color: white;
  font-weight: bold;
  font-size: 16px;
  cursor: pointer;
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
}
</style>
