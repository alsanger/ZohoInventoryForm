import axios from 'axios';

const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api',
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Функция для получения CSRF токена из cookie
function getCSRFToken() {
  const cookies = document.cookie.split(';');
  for (let cookie of cookies) {
    const [name, value] = cookie.trim().split('=');
    if (name === 'XSRF-TOKEN') {
      return decodeURIComponent(value);
    }
  }
  return null;
}

apiClient.interceptors.request.use(async (config) => {
  if (config.method !== 'get' && config.method !== 'head') {
    const token = getCSRFToken();
    if (token) {
      config.headers['X-XSRF-TOKEN'] = token;
    }
  }

  return config;
}, (error) => {
  return Promise.reject(error);
});

export default apiClient;
