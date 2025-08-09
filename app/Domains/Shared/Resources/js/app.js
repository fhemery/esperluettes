import './bootstrap';
import Alpine from 'alpinejs';
import { proseMirrorComponent } from './editor.js';

window.Alpine = Alpine;

// Register ProseMirror component globally
Alpine.data('proseMirrorEditor', proseMirrorComponent);

Alpine.start();
