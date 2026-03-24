import './bootstrap';

import Alpine from 'alpinejs';

// 📚 Cropper.js - Görsel kırpma kütüphanesi
import Cropper from 'cropperjs';

// Global olarak erişilebilir yap
window.Cropper = Cropper;

window.Alpine = Alpine;

Alpine.start();
