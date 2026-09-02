<?php
/**
 * Multi-Tenant Authentication View (Login & Registration)
 * Hardened with Anti-CSRF, Rate Limiting & Modern Dark Glassmorphism UI
 */
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/auth.php';

Security::applySecurityHeaders(false);
$csrfToken = Security::getCsrfToken();

// If already logged in, redirect to main app
if (Auth::check()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
  <title>Acceso al Sistema - Mente Estoica AI Multi-Tenant SaaS</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🏛️</text></svg>">
  <style>
    .auth-page-container {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px 16px;
      background: radial-gradient(circle at 50% 20%, rgba(99, 102, 241, 0.15) 0%, rgba(10, 14, 23, 1) 70%);
    }

    .auth-card-box {
      width: 100%;
      max-width: 440px;
      background: rgba(17, 24, 39, 0.85);
      border: 1px solid var(--border-subtle);
      border-radius: var(--radius-md);
      padding: 32px 28px;
      box-shadow: 0 20px 45px rgba(0, 0, 0, 0.6);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
    }

    .auth-header {
      text-align: center;
      margin-bottom: 24px;
    }

    .auth-logo {
      font-size: 2.8rem;
      margin-bottom: 8px;
      display: inline-block;
      filter: drop-shadow(0 0 16px var(--primary-glow));
    }

    .auth-title {
      font-size: 1.35rem;
      font-weight: 800;
      color: #fff;
      margin-bottom: 4px;
    }

    .auth-subtitle {
      font-size: 0.82rem;
      color: var(--text-muted);
    }

    .auth-tabs-nav {
      display: flex;
      background: rgba(0, 0, 0, 0.35);
      border: 1px solid var(--border-subtle);
      border-radius: var(--radius-sm);
      padding: 4px;
      margin-bottom: 22px;
      gap: 4px;
    }

    .auth-tab-btn {
      flex: 1;
      background: transparent;
      border: none;
      color: var(--text-muted);
      font-family: inherit;
      font-size: 0.84rem;
      font-weight: 700;
      padding: 8px 12px;
      border-radius: 6px;
      cursor: pointer;
      transition: var(--transition-fast);
      text-align: center;
    }

    .auth-tab-btn.active {
      background: var(--primary);
      color: #fff;
      box-shadow: 0 2px 10px var(--primary-glow);
    }

    .auth-form-group {
      margin-bottom: 16px;
    }

    .auth-form-group label {
      display: block;
      font-size: 0.78rem;
      font-weight: 700;
      color: var(--text-muted);
      margin-bottom: 6px;
    }

    .auth-input-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }

    .auth-input-icon {
      position: absolute;
      left: 12px;
      font-size: 1rem;
      color: var(--text-dim);
      pointer-events: none;
    }

    .auth-input {
      width: 100%;
      background: rgba(0, 0, 0, 0.4);
      border: 1px solid var(--border-subtle);
      border-radius: var(--radius-sm);
      padding: 10px 14px 10px 38px;
      color: #fff;
      font-family: inherit;
      font-size: 0.9rem;
      outline: none;
      transition: var(--transition-fast);
    }

    .auth-input:focus {
      border-color: var(--border-active);
      box-shadow: 0 0 12px var(--primary-glow);
      background: rgba(0, 0, 0, 0.6);
    }

    .btn-auth-submit {
      width: 100%;
      background: linear-gradient(135deg, var(--primary), #4338ca);
      border: none;
      color: #fff;
      font-family: inherit;
      font-size: 0.92rem;
      font-weight: 800;
      padding: 12px 16px;
      border-radius: var(--radius-sm);
      cursor: pointer;
      transition: var(--transition-fast);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      margin-top: 20px;
      box-shadow: 0 4px 16px var(--primary-glow);
    }

    .btn-auth-submit:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 20px var(--primary-glow);
    }

    .auth-alert-box {
      display: none;
      padding: 10px 14px;
      border-radius: var(--radius-sm);
      font-size: 0.8rem;
      margin-bottom: 16px;
      line-height: 1.4;
    }

    .auth-alert-box.error {
      display: block;
      background: rgba(244, 63, 94, 0.15);
      border: 1px solid rgba(244, 63, 94, 0.35);
      color: #fda4af;
    }

    .auth-alert-box.success {
      display: block;
      background: rgba(16, 185, 129, 0.15);
      border: 1px solid rgba(16, 185, 129, 0.35);
      color: #6ee7b7;
    }

    .demo-account-hint {
      margin-top: 22px;
      padding: 12px;
      background: rgba(255, 255, 255, 0.03);
      border: 1px dashed var(--border-subtle);
      border-radius: var(--radius-sm);
      font-size: 0.76rem;
      color: var(--text-dim);
      text-align: center;
    }

    .demo-account-hint strong {
      color: var(--accent-cyan);
    }

    .btn-quick-fill {
      background: transparent;
      border: none;
      color: var(--accent-cyan);
      font-size: 0.76rem;
      font-weight: 700;
      text-decoration: underline;
      cursor: pointer;
      margin-left: 4px;
    }
  </style>
</head>
<body>

<div class="auth-page-container">
  <div class="auth-card-box">
    
    <div class="auth-header">
      <div class="auth-logo">🏛️</div>
      <h1 class="auth-title">Mente Estoica AI</h1>
      <p class="auth-subtitle">Agente Inteligente de Engagement & Redes Sociales</p>
    </div>

    <div class="auth-tabs-nav">
      <button type="button" class="auth-tab-btn active" id="tab-btn-login" onclick="AuthUI.switchTab('login')">
        Iniciar Sesión
      </button>
      <button type="button" class="auth-tab-btn" id="tab-btn-register" onclick="AuthUI.switchTab('register')">
        Crear Nueva Cuenta
      </button>
    </div>

    <!-- Alert Box -->
    <div class="auth-alert-box" id="auth-alert"></div>

    <!-- Form 1: Login -->
    <form id="form-login" onsubmit="AuthUI.submitLogin(event)">
      <div class="auth-form-group">
        <label for="login-email">Correo Electrónico:</label>
        <div class="auth-input-wrapper">
          <span class="auth-input-icon">✉️</span>
          <input type="email" id="login-email" class="auth-input" placeholder="tu@correo.com" required autocomplete="email" />
        </div>
      </div>

      <div class="auth-form-group">
        <label for="login-password">Contraseña:</label>
        <div class="auth-input-wrapper">
          <span class="auth-input-icon">🔒</span>
          <input type="password" id="login-password" class="auth-input" placeholder="••••••••" required autocomplete="current-password" />
        </div>
      </div>

      <button type="submit" class="btn-auth-submit" id="btn-submit-login">
        <span>Ingresar al Panel</span>
        <span>→</span>
      </button>
    </form>

    <!-- Form 2: Register -->
    <form id="form-register" style="display: none;" onsubmit="AuthUI.submitRegister(event)">
      <div class="auth-form-group">
        <label for="reg-name">Tu Nombre Completo / Marca:</label>
        <div class="auth-input-wrapper">
          <span class="auth-input-icon">👤</span>
          <input type="text" id="reg-name" class="auth-input" placeholder="Ej: Carlos Silva" required autocomplete="name" />
        </div>
      </div>

      <div class="auth-form-group">
        <label for="reg-email">Correo Electrónico:</label>
        <div class="auth-input-wrapper">
          <span class="auth-input-icon">✉️</span>
          <input type="email" id="reg-email" class="auth-input" placeholder="tu@correo.com" required autocomplete="email" />
        </div>
      </div>

      <div class="auth-form-group">
        <label for="reg-password">Crea una Contraseña Segura:</label>
        <div class="auth-input-wrapper">
          <span class="auth-input-icon">🔑</span>
          <input type="password" id="reg-password" class="auth-input" placeholder="Mínimo 6 caracteres" minlength="6" required autocomplete="new-password" />
        </div>
      </div>

      <button type="submit" class="btn-auth-submit" id="btn-submit-register">
        <span>Crear mi Cuenta Gratis 🚀</span>
      </button>
    </form>

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

    const btnLogin = document.getElementById('tab-btn-login');
    const btnReg = document.getElementById('tab-btn-register');
    const formLogin = document.getElementById('form-login');
    const formReg = document.getElementById('form-register');

    if (tab === 'login') {
      btnLogin.classList.add('active');
      btnReg.classList.remove('active');
      formLogin.style.display = 'block';
      formReg.style.display = 'none';
    } else {
      btnReg.classList.add('active');
      btnLogin.classList.remove('active');
      formReg.style.display = 'block';
      formLogin.style.display = 'none';
    }
  },

  showAlert(message, type = 'error') {
    const box = document.getElementById('auth-alert');
    if (!box) return;
    box.className = `auth-alert-box ${type}`;
    box.textContent = message;
    box.style.display = 'block';
  },

  hideAlert() {
    const box = document.getElementById('auth-alert');
    if (box) box.style.display = 'none';
  },

  async submitLogin(e) {
    e.preventDefault();
    this.hideAlert();

    const email = document.getElementById('login-email').value.trim();
    const password = document.getElementById('login-password').value;
    const btn = document.getElementById('btn-submit-login');

    btn.disabled = true;
    btn.innerHTML = '<span>Verificando credenciales...</span>';

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
          window.location.href = 'index.php';
        }, 600);
      } else {
        this.showAlert(res.error || 'Error al iniciar sesión.');
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

    const name = document.getElementById('reg-name').value.trim();
    const email = document.getElementById('reg-email').value.trim();
    const password = document.getElementById('reg-password').value;
    const btn = document.getElementById('btn-submit-register');

    btn.disabled = true;
    btn.innerHTML = '<span>Creando cuenta e inicializando IA...</span>';

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
          window.location.href = 'index.php';
        }, 700);
      } else {
        this.showAlert(res.error || 'Error al crear la cuenta.');
        btn.disabled = false;
        btn.innerHTML = '<span>Crear mi Cuenta Gratis 🚀</span>';
      }
    } catch (err) {
      console.error(err);
      this.showAlert('Error de conexión con el servidor.');
      btn.disabled = false;
      btn.innerHTML = '<span>Crear mi Cuenta Gratis 🚀</span>';
    }
  }
};
</script>

</body>
</html>
