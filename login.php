<?php
/**
 * Multi-Tenant Authentication View (Login & Registration)
 * Hardened with Anti-CSRF, Rate Limiting, Client/Server Validation & Glassmorphism UI
 */
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/auth.php';

Security::applySecurityHeaders(false);
$csrfToken = Security::getCsrfToken();

// If already logged in, redirect to dashboard app
if (Auth::check()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
  <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
  <title>Acceso al Sistema — XINDRO Copilot</title>
  
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>⚡</text></svg>">
  
  <!-- Google Fonts: Plus Jakarta Sans & Syne -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=Syne:wght@700;800;900&display=swap" rel="stylesheet">
  
  <style>
    :root {
      --primary: #7C3AED;
      --primary-hover: #6D28D9;
      --primary-glow: rgba(124, 58, 237, 0.35);
      --accent-blue: #3B82F6;
      --bg-dark: #0B0F19;
      --card-bg: rgba(17, 24, 39, 0.82);
      --border-subtle: rgba(255, 255, 255, 0.08);
      --border-focus: #7C3AED;
      --text-main: #F8FAFC;
      --text-muted: #94A3B8;
      --text-dim: #64748B;
      --error-red: #F43F5E;
      --error-bg: rgba(244, 63, 94, 0.12);
      --success-green: #10B981;
      --success-bg: rgba(16, 185, 129, 0.12);
      --radius-sm: 10px;
      --radius-md: 18px;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background-color: var(--bg-dark);
      color: var(--text-main);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      position: relative;
      overflow-x: hidden;
    }

    /* Ambient background glow */
    .ambient-glow-1 {
      position: fixed;
      top: -15%;
      left: 50%;
      transform: translateX(-50%);
      width: 700px;
      height: 500px;
      background: radial-gradient(circle, rgba(124, 58, 237, 0.18) 0%, rgba(59, 130, 246, 0.08) 50%, transparent 70%);
      filter: blur(80px);
      pointer-events: none;
      z-index: 0;
    }

    .ambient-glow-2 {
      position: fixed;
      bottom: -10%;
      right: 10%;
      width: 500px;
      height: 400px;
      background: radial-gradient(circle, rgba(99, 102, 241, 0.12) 0%, transparent 70%);
      filter: blur(90px);
      pointer-events: none;
      z-index: 0;
    }

    .auth-page-wrapper {
      position: relative;
      z-index: 1;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 32px 16px;
    }

    .back-home-link {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      color: var(--text-muted);
      text-decoration: none;
      font-size: 0.84rem;
      font-weight: 600;
      margin-bottom: 24px;
      padding: 6px 14px;
      border-radius: 9999px;
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid var(--border-subtle);
      transition: all 0.2s ease;
    }

    .back-home-link:hover {
      color: #fff;
      background: rgba(255, 255, 255, 0.08);
      border-color: rgba(255, 255, 255, 0.18);
      transform: translateX(-2px);
    }

    .auth-card-box {
      width: 100%;
      max-width: 450px;
      background: var(--card-bg);
      border: 1px solid var(--border-subtle);
      border-radius: var(--radius-md);
      padding: 36px 32px;
      box-shadow: 0 24px 50px -12px rgba(0, 0, 0, 0.7), 0 0 30px rgba(124, 58, 237, 0.08);
      backdrop-filter: blur(24px);
      -webkit-backdrop-filter: blur(24px);
    }

    .auth-header {
      text-align: center;
      margin-bottom: 26px;
    }

    .auth-brand-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 4px 12px;
      border-radius: 9999px;
      background: rgba(124, 58, 237, 0.15);
      border: 1px solid rgba(124, 58, 237, 0.35);
      color: #c084fc;
      font-size: 0.72rem;
      font-weight: 800;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      margin-bottom: 12px;
    }

    .auth-brand-badge span.pulse-dot {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: #c084fc;
      box-shadow: 0 0 8px #c084fc;
    }

    .auth-logo-title {
      font-family: 'Syne', sans-serif;
      font-size: 1.85rem;
      font-weight: 900;
      letter-spacing: -0.03em;
      color: #fff;
      margin-bottom: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }

    .auth-logo-title .brand-gradient {
      background: linear-gradient(135deg, #a78bfa 0%, #c084fc 50%, #f472b6 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .auth-subtitle {
      font-size: 0.86rem;
      color: var(--text-muted);
      line-height: 1.4;
    }

    /* Tabs Navigation */
    .auth-tabs-nav {
      display: flex;
      background: rgba(0, 0, 0, 0.45);
      border: 1px solid var(--border-subtle);
      border-radius: var(--radius-sm);
      padding: 4px;
      margin-bottom: 24px;
      gap: 4px;
    }

    .auth-tab-btn {
      flex: 1;
      background: transparent;
      border: none;
      color: var(--text-muted);
      font-family: inherit;
      font-size: 0.86rem;
      font-weight: 700;
      padding: 10px 14px;
      border-radius: 7px;
      cursor: pointer;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      text-align: center;
      user-select: none;
    }

    .auth-tab-btn:hover {
      color: #fff;
      background: rgba(255, 255, 255, 0.04);
    }

    .auth-tab-btn.active {
      background: var(--primary);
      color: #fff;
      box-shadow: 0 4px 14px var(--primary-glow);
    }

    /* Global Alert Box */
    .auth-alert-box {
      display: none;
      padding: 12px 14px;
      border-radius: var(--radius-sm);
      font-size: 0.82rem;
      margin-bottom: 20px;
      line-height: 1.45;
      animation: alertSlide 0.25s ease-out;
    }

    @keyframes alertSlide {
      from { opacity: 0; transform: translateY(-6px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .auth-alert-box.error {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      background: var(--error-bg);
      border: 1px solid rgba(244, 63, 94, 0.35);
      color: #fecdd3;
    }

    .auth-alert-box.success {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      background: var(--success-bg);
      border: 1px solid rgba(16, 185, 129, 0.35);
      color: #a7f3d0;
    }

    /* Form Groups */
    .auth-form-group {
      margin-bottom: 18px;
    }

    .auth-form-group label {
      display: block;
      font-size: 0.8rem;
      font-weight: 700;
      color: #CBD5E1;
      margin-bottom: 6px;
    }

    .auth-input-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }

    .auth-input-icon {
      position: absolute;
      left: 14px;
      font-size: 1rem;
      color: var(--text-dim);
      pointer-events: none;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .auth-input {
      width: 100%;
      background: rgba(0, 0, 0, 0.4);
      border: 1px solid var(--border-subtle);
      border-radius: var(--radius-sm);
      padding: 11px 42px 11px 40px;
      color: #fff;
      font-family: inherit;
      font-size: 0.92rem;
      outline: none;
      transition: all 0.2s ease;
    }

    .auth-input::placeholder {
      color: #475569;
    }

    .auth-input:focus {
      border-color: var(--border-focus);
      box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.2);
      background: rgba(0, 0, 0, 0.6);
    }

    .auth-input.has-error {
      border-color: var(--error-red) !important;
      box-shadow: 0 0 0 3px rgba(244, 63, 94, 0.2) !important;
      background: rgba(244, 63, 94, 0.05);
      animation: shakeInput 0.3s ease;
    }

    @keyframes shakeInput {
      0%, 100% { transform: translateX(0); }
      20%, 60% { transform: translateX(-4px); }
      40%, 80% { transform: translateX(4px); }
    }

    .auth-input-toggle-pw {
      position: absolute;
      right: 12px;
      background: transparent;
      border: none;
      color: var(--text-dim);
      cursor: pointer;
      font-size: 1rem;
      padding: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: color 0.15s ease;
    }

    .auth-input-toggle-pw:hover {
      color: #fff;
    }

    .field-error-msg {
      display: none;
      color: #fb7185;
      font-size: 0.74rem;
      font-weight: 600;
      margin-top: 5px;
      line-height: 1.3;
    }

    .field-error-msg.visible {
      display: block;
    }

    /* Password strength indicator */
    .pw-strength-container {
      margin-top: 6px;
      display: none;
    }

    .pw-strength-container.active {
      display: block;
    }

    .pw-strength-bar {
      height: 4px;
      background: rgba(255, 255, 255, 0.08);
      border-radius: 2px;
      overflow: hidden;
      margin-bottom: 4px;
    }

    .pw-strength-fill {
      height: 100%;
      width: 0%;
      transition: all 0.3s ease;
      border-radius: 2px;
    }

    .pw-strength-fill.weak { width: 33%; background: #F43F5E; }
    .pw-strength-fill.medium { width: 66%; background: #F59E0B; }
    .pw-strength-fill.strong { width: 100%; background: #10B981; }

    .pw-strength-text {
      font-size: 0.72rem;
      font-weight: 600;
      color: var(--text-dim);
    }

    /* Submit Button with Loader */
    .btn-auth-submit {
      width: 100%;
      background: linear-gradient(135deg, var(--primary) 0%, #6366f1 100%);
      border: none;
      color: #fff;
      font-family: inherit;
      font-size: 0.94rem;
      font-weight: 800;
      padding: 13px 20px;
      border-radius: var(--radius-sm);
      cursor: pointer;
      transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      margin-top: 24px;
      box-shadow: 0 4px 18px var(--primary-glow);
      user-select: none;
      position: relative;
    }

    .btn-auth-submit:hover:not(:disabled) {
      transform: translateY(-2px) scale(1.01);
      box-shadow: 0 8px 24px var(--primary-glow);
    }

    .btn-auth-submit:active:not(:disabled) {
      transform: translateY(1px) scale(0.98);
    }

    .btn-auth-submit:disabled {
      opacity: 0.75;
      cursor: not-allowed;
      transform: none !important;
      box-shadow: none;
    }

    .btn-spinner {
      width: 18px;
      height: 18px;
      border: 2.5px solid rgba(255, 255, 255, 0.3);
      border-top-color: #fff;
      border-radius: 50%;
      animation: spin 0.7s linear infinite;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    .auth-footer-note {
      margin-top: 24px;
      padding-top: 18px;
      border-top: 1px solid var(--border-subtle);
      text-align: center;
      font-size: 0.75rem;
      color: var(--text-dim);
      line-height: 1.5;
    }

    .auth-footer-note a {
      color: #a78bfa;
      text-decoration: none;
      font-weight: 600;
    }

    .auth-footer-note a:hover {
      text-decoration: underline;
    }

    @media (max-width: 480px) {
      .auth-card-box {
        padding: 28px 20px;
      }
      .auth-logo-title {
        font-size: 1.6rem;
      }
    }
  </style>
</head>
<body>

<div class="ambient-glow-1"></div>
<div class="ambient-glow-2"></div>

<div class="auth-page-wrapper">
  
  <a href="index.php" class="back-home-link">
    <span>←</span>
    <span>Volver al inicio</span>
  </a>

  <div class="auth-card-box">
    
    <div class="auth-header">
      <div class="auth-brand-badge">
        <span class="pulse-dot"></span>
        <span>Autenticación Segura</span>
      </div>
      <h1 class="auth-logo-title">
        <span>⚡</span>
        <span class="brand-gradient">XINDRO</span>
      </h1>
      <p class="auth-subtitle" id="auth-header-subtitle">Ingresa a tu panel de gestión inteligente de redes sociales.</p>
    </div>

    <div class="auth-tabs-nav" role="tablist">
      <button type="button" class="auth-tab-btn active" id="tab-btn-login" onclick="AuthUI.switchTab('login')" role="tab" aria-selected="true">
        Iniciar Sesión
      </button>
      <button type="button" class="auth-tab-btn" id="tab-btn-register" onclick="AuthUI.switchTab('register')" role="tab" aria-selected="false">
        Crear Cuenta
      </button>
    </div>

    <!-- Alert Box for Server / Rate-Limit Feedback -->
    <div class="auth-alert-box" id="auth-alert" role="alert">
      <span id="auth-alert-icon">⚠️</span>
      <span id="auth-alert-text"></span>
    </div>

    <!-- Form 1: Login -->
    <form id="form-login" onsubmit="AuthUI.submitLogin(event)" novalidate>
      <div class="auth-form-group">
        <label for="login-email">Correo Electrónico:</label>
        <div class="auth-input-wrapper">
          <span class="auth-input-icon">✉️</span>
          <input 
            type="email" 
            id="login-email" 
            class="auth-input" 
            placeholder="tu@correo.com" 
            autocomplete="email" 
            oninput="AuthUI.clearFieldError('login-email')"
          />
        </div>
        <p class="field-error-msg" id="error-login-email"></p>
      </div>

      <div class="auth-form-group">
        <label for="login-password">Contraseña:</label>
        <div class="auth-input-wrapper">
          <span class="auth-input-icon">🔒</span>
          <input 
            type="password" 
            id="login-password" 
            class="auth-input" 
            placeholder="••••••••" 
            autocomplete="current-password" 
            oninput="AuthUI.clearFieldError('login-password')"
          />
          <button type="button" class="auth-input-toggle-pw" onclick="AuthUI.togglePasswordVisibility('login-password', this)" title="Mostrar/Ocultar contraseña" aria-label="Mostrar/Ocultar contraseña">
            👁️
          </button>
        </div>
        <p class="field-error-msg" id="error-login-password"></p>
      </div>

      <button type="submit" class="btn-auth-submit" id="btn-submit-login">
        <span>Ingresar al Panel</span>
        <span>→</span>
      </button>
    </form>

    <!-- Form 2: Register -->
    <form id="form-register" style="display: none;" onsubmit="AuthUI.submitRegister(event)" novalidate>
      <div class="auth-form-group">
        <label for="reg-name">Nombre Completo o de Marca:</label>
        <div class="auth-input-wrapper">
          <span class="auth-input-icon">👤</span>
          <input 
            type="text" 
            id="reg-name" 
            class="auth-input" 
            placeholder="Ej: Carlos Silva o Tu Marca" 
            autocomplete="name" 
            maxlength="80"
            oninput="AuthUI.clearFieldError('reg-name')"
          />
        </div>
        <p class="field-error-msg" id="error-reg-name"></p>
      </div>

      <div class="auth-form-group">
        <label for="reg-email">Correo Electrónico:</label>
        <div class="auth-input-wrapper">
          <span class="auth-input-icon">✉️</span>
          <input 
            type="email" 
            id="reg-email" 
            class="auth-input" 
            placeholder="tu@correo.com" 
            autocomplete="email" 
            maxlength="180"
            oninput="AuthUI.clearFieldError('reg-email')"
          />
        </div>
        <p class="field-error-msg" id="error-reg-email"></p>
      </div>

      <div class="auth-form-group">
        <label for="reg-password">Contraseña (Mínimo 8 caracteres):</label>
        <div class="auth-input-wrapper">
          <span class="auth-input-icon">🔑</span>
          <input 
            type="password" 
            id="reg-password" 
            class="auth-input" 
            placeholder="Crea una contraseña segura" 
            autocomplete="new-password" 
            maxlength="256"
            oninput="AuthUI.onRegisterPasswordInput(this)"
          />
          <button type="button" class="auth-input-toggle-pw" onclick="AuthUI.togglePasswordVisibility('reg-password', this)" title="Mostrar/Ocultar contraseña" aria-label="Mostrar/Ocultar contraseña">
            👁️
          </button>
        </div>
        <div class="pw-strength-container" id="pw-strength-box">
          <div class="pw-strength-bar">
            <div class="pw-strength-fill" id="pw-strength-fill"></div>
          </div>
          <span class="pw-strength-text" id="pw-strength-label">Fuerza de la contraseña</span>
        </div>
        <p class="field-error-msg" id="error-reg-password"></p>
      </div>

      <button type="submit" class="btn-auth-submit" id="btn-submit-register">
        <span>Crear mi Cuenta Gratis 🚀</span>
      </button>
    </form>

    <div class="auth-footer-note">
      <span>Tus datos y tokens están protegidos con cifrado de grado militar AES-256-GCM y arquitectura multi-tenant aislada.</span>
    </div>

  </div>
</div>

<script>
const AuthUI = {
  activeTab: 'login',

  getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  },

  switchTab(tab) {
    this.activeTab = tab;
    this.hideAlert();
    this.clearAllFieldErrors();

    const btnLogin = document.getElementById('tab-btn-login');
    const btnReg = document.getElementById('tab-btn-register');
    const formLogin = document.getElementById('form-login');
    const formReg = document.getElementById('form-register');
    const subtitle = document.getElementById('auth-header-subtitle');

    if (tab === 'login') {
      btnLogin.classList.add('active');
      btnLogin.setAttribute('aria-selected', 'true');
      btnReg.classList.remove('active');
      btnReg.setAttribute('aria-selected', 'false');
      formLogin.style.display = 'block';
      formReg.style.display = 'none';
      subtitle.textContent = 'Ingresa a tu panel de gestión inteligente de redes sociales.';
    } else {
      btnReg.classList.add('active');
      btnReg.setAttribute('aria-selected', 'true');
      btnLogin.classList.remove('active');
      btnLogin.setAttribute('aria-selected', 'false');
      formReg.style.display = 'block';
      formLogin.style.display = 'none';
      subtitle.textContent = 'Crea tu espacio de trabajo y conecta tus redes en segundos.';
    }
  },

  togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    if (input.type === 'password') {
      input.type = 'text';
      btn.textContent = '🙈';
    } else {
      input.type = 'password';
      btn.textContent = '👁️';
    }
  },

  showAlert(message, type = 'error') {
    const box = document.getElementById('auth-alert');
    const textEl = document.getElementById('auth-alert-text');
    const iconEl = document.getElementById('auth-alert-icon');
    if (!box || !textEl) return;

    box.className = `auth-alert-box ${type}`;
    textEl.textContent = message;
    iconEl.textContent = type === 'success' ? '✅' : '⚠️';
    box.style.display = 'flex';
  },

  hideAlert() {
    const box = document.getElementById('auth-alert');
    if (box) box.style.display = 'none';
  },

  setFieldError(inputId, errorId, message) {
    const input = document.getElementById(inputId);
    const errorEl = document.getElementById(errorId);
    if (input) input.classList.add('has-error');
    if (errorEl) {
      errorEl.textContent = message;
      errorEl.classList.add('visible');
    }
  },

  clearFieldError(inputId) {
    const input = document.getElementById(inputId);
    if (input) input.classList.remove('has-error');
    
    // Clear matching error msg
    const errorMap = {
      'login-email': 'error-login-email',
      'login-password': 'error-login-password',
      'reg-name': 'error-reg-name',
      'reg-email': 'error-reg-email',
      'reg-password': 'error-reg-password'
    };
    const errEl = document.getElementById(errorMap[inputId]);
    if (errEl) {
      errEl.textContent = '';
      errEl.classList.remove('visible');
    }
  },

  clearAllFieldErrors() {
    ['login-email', 'login-password', 'reg-name', 'reg-email', 'reg-password'].forEach(id => {
      this.clearFieldError(id);
    });
  },

  isValidEmail(email) {
    return /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(email.trim());
  },

  onRegisterPasswordInput(input) {
    this.clearFieldError('reg-password');
    const val = input.value;
    const box = document.getElementById('pw-strength-box');
    const fill = document.getElementById('pw-strength-fill');
    const label = document.getElementById('pw-strength-label');

    if (!val) {
      box.classList.remove('active');
      return;
    }

    box.classList.add('active');
    let strength = 0;
    if (val.length >= 8) strength++;
    if (/[A-Z]/.test(val) || /[0-9]/.test(val)) strength++;
    if (/[^A-Za-z0-9]/.test(val) && val.length >= 10) strength++;

    fill.className = 'pw-strength-fill';
    if (strength <= 1) {
      fill.classList.add('weak');
      label.textContent = 'Contraseña débil (mínimo 8 caracteres)';
      label.style.color = '#f87171';
    } else if (strength === 2) {
      fill.classList.add('medium');
      label.textContent = 'Contraseña media (agrega números o símbolos)';
      label.style.color = '#fbbf24';
    } else {
      fill.classList.add('strong');
      label.textContent = 'Contraseña segura ✔';
      label.style.color = '#34d399';
    }
  },

  async submitLogin(e) {
    e.preventDefault();
    this.hideAlert();
    this.clearAllFieldErrors();

    const emailInput = document.getElementById('login-email');
    const passwordInput = document.getElementById('login-password');
    const email = emailInput.value.trim();
    const password = passwordInput.value;
    const btn = document.getElementById('btn-submit-login');

    // Client-side Validation
    let hasError = false;

    if (!email) {
      this.setFieldError('login-email', 'error-login-email', 'Por favor ingresa tu correo electrónico.');
      hasError = true;
    } else if (!this.isValidEmail(email)) {
      this.setFieldError('login-email', 'error-login-email', 'El formato del correo electrónico no es válido.');
      hasError = true;
    }

    if (!password) {
      this.setFieldError('login-password', 'error-login-password', 'Por favor ingresa tu contraseña.');
      hasError = true;
    }

    if (hasError) {
      return;
    }

    // Loader State & Anti-Double-Submit
    btn.disabled = true;
    btn.innerHTML = '<span class="btn-spinner"></span><span>Verificando credenciales...</span>';

    try {
      const response = await fetch('api/auth.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': this.getCsrfToken()
        },
        body: JSON.stringify({
          action: 'login',
          email: email,
          password: password
        })
      });

      const res = await response.json();

      if (res.success) {
        this.showAlert('¡Bienvenido de vuelta! Redirigiendo al panel...', 'success');
        setTimeout(() => {
          window.location.href = 'dashboard.php';
        }, 500);
      } else {
        // Map field-specific error if provided
        if (res.field === 'email') {
          this.setFieldError('login-email', 'error-login-email', res.error || 'Correo no registrado.');
        } else if (res.field === 'password') {
          this.setFieldError('login-password', 'error-login-password', res.error || 'Contraseña incorrecta.');
        } else {
          this.showAlert(res.error || 'Error al iniciar sesión.');
        }
        btn.disabled = false;
        btn.innerHTML = '<span>Ingresar al Panel</span><span>→</span>';
      }
    } catch (err) {
      console.error(err);
      this.showAlert('Error de conexión con el servidor. Inténtalo de nuevo.');
      btn.disabled = false;
      btn.innerHTML = '<span>Ingresar al Panel</span><span>→</span>';
    }
  },

  async submitRegister(e) {
    e.preventDefault();
    this.hideAlert();
    this.clearAllFieldErrors();

    const nameInput = document.getElementById('reg-name');
    const emailInput = document.getElementById('reg-email');
    const passwordInput = document.getElementById('reg-password');
    const name = nameInput.value.trim();
    const email = emailInput.value.trim();
    const password = passwordInput.value;
    const btn = document.getElementById('btn-submit-register');

    // Client-side Validation
    let hasError = false;

    if (!name || name.length < 2) {
      this.setFieldError('reg-name', 'error-reg-name', 'El nombre debe tener al menos 2 caracteres.');
      hasError = true;
    }

    if (!email) {
      this.setFieldError('reg-email', 'error-reg-email', 'Por favor ingresa tu correo electrónico.');
      hasError = true;
    } else if (!this.isValidEmail(email)) {
      this.setFieldError('reg-email', 'error-reg-email', 'Introduce un correo electrónico válido (ej: nombre@dominio.com).');
      hasError = true;
    }

    if (!password) {
      this.setFieldError('reg-password', 'error-reg-password', 'Por favor crea una contraseña.');
      hasError = true;
    } else if (password.length < 8) {
      this.setFieldError('reg-password', 'error-reg-password', 'La contraseña debe tener al menos 8 caracteres para proteger tu cuenta.');
      hasError = true;
    }

    if (hasError) {
      return;
    }

    // Loader State & Anti-Double-Submit
    btn.disabled = true;
    btn.innerHTML = '<span class="btn-spinner"></span><span>Creando cuenta e inicializando espacio...</span>';

    try {
      const response = await fetch('api/auth.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': this.getCsrfToken()
        },
        body: JSON.stringify({
          action: 'register',
          name: name,
          email: email,
          password: password
        })
      });

      const res = await response.json();

      if (res.success) {
        this.showAlert('¡Cuenta creada con éxito! Entrando a tu espacio privado...', 'success');
        setTimeout(() => {
          window.location.href = 'dashboard.php';
        }, 600);
      } else {
        if (res.field === 'email') {
          this.setFieldError('reg-email', 'error-reg-email', res.error || 'Correo no disponible.');
        } else if (res.field === 'password') {
          this.setFieldError('reg-password', 'error-reg-password', res.error || 'Contraseña no válida.');
        } else if (res.field === 'name') {
          this.setFieldError('reg-name', 'error-reg-name', res.error || 'Nombre no válido.');
        } else {
          this.showAlert(res.error || 'Error al crear la cuenta.');
        }
        btn.disabled = false;
        btn.innerHTML = '<span>Crear mi Cuenta Gratis 🚀</span>';
      }
    } catch (err) {
      console.error(err);
      this.showAlert('Error de conexión con el servidor. Por favor intenta de nuevo.');
      btn.disabled = false;
      btn.innerHTML = '<span>Crear mi Cuenta Gratis 🚀</span>';
    }
  }
};
</script>

</body>
</html>
