import axios from 'axios';

const api = axios.create({
  baseURL: 'http://51.83.36.122:8039/api', 
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});
api.interceptors.request.use(config => {
    const token = localStorage.getItem('user_token');
    
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});


api.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 401) {
            // Le token est invalide ou expiré, on force la déconnexion
            localStorage.removeItem('user_token');
            localStorage.removeItem('user_infos');
            // Redirection vers la page de connexion
            window.location.href = '/connexion';
        }
        return Promise.reject(error);
    }
);
export default api;