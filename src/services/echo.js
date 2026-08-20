// src/services/echo.js
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY || 'your-reverb-key',
    wsHost: import.meta.env.VITE_REVERB_HOST || 'localhost',
    wsPort: import.meta.env.VITE_REVERB_PORT || 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT || 8080,
    forceTLS: false, // اجعلها true في بيئة الإنتاج (Production)
    enabledTransports: ['ws', 'wss'],
    authEndpoint: 'http://localhost:8000/api/broadcasting/auth',
    auth: {
        headers: {
            Authorization: `Bearer ${localStorage.getItem('merchant_token')}`,
            Accept: 'application/json',
        },
    },
});

export default echo;
