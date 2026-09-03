<?php
/**
 * Standalone Password Reset View
 * Validates cryptographically hashed one-time tokens and updates user password
 */
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/auth.php';

Security::applySecurityHeaders(false);
$csrfToken = Security::getCsrfToken();

$rawToken = trim($_GET['token'] ?? '');
$tokenValidation = !empty($rawToken) ? Auth::validateResetToken($rawToken) : ['valid' => false, 'error' => 'No se proporcionó ningún token de recuperación.'];
$isValidToken = $tokenValidation['valid'];
$tokenError = $tokenValidation['error'] ?? '';
$userEmail = $tokenValidation['email'] ?? '';
$userName = $tokenValidation['name'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
  <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
  <title>Restablecer Contraseña — XINDRO Copilot</title>
  
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

    .reset-page-wrapper {
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

    .reset-card-box {
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

    .reset-header {
      text-align: center;
      margin-bottom: 24px;
    }

    .reset-brand-badge {
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

    .reset-brand-badge span.pulse-dot {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: #c084fc;
      box-shadow: 0 0 8px #c084fc;
    }

    .reset-logo-title {
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

    .reset-logo-title .brand-gradient {
      background: linear-gradient(135deg, #a78bfa 0%, #c084fc 50%, #f472b6 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .reset-subtitle {
      font-size: 0.86rem;
      color: var(--text-muted);
      line-height: 1.4;
    }

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

    .token-error-box {
      background: rgba(244, 63, 94, 0.1);
      border: 1px solid rgba(244, 63, 94, 0.3);
      border-radius: 12px;
      padding: 20px;
      text-align: center;
      margin-bottom: 20px;
    }

    .token-error-icon {
      font-size: 2.2rem;
      margin-bottom: 10px;
    }

    .token-error-title {
      font-size: 1.1rem;
      font-weight: 800;
      color: #fecdd3;
      margin-bottom: 8px;
    }

    .token-error-desc {
      font-size: 0.85rem;
      color: #94a3b8;
      line-height: 1.5;
      margin-bottom: 18px;
    }

    @media (max-width: 480px) {
      .reset-card-box {
        padding: 28px 20px;
      }
      .reset-logo-title {
        font-size: 1.6rem;
      }
    }
  </style>
</head>
<body>

<div class="ambient-glow-1"></div>
<div class="ambient-glow-2"></div>

<div class="reset-page-wrapper">
  
  <a href="login.php" class="back-home-link">
    <span>←</span>
    <span>Volver a Iniciar Sesión</span>
  </a>

  <div class="reset-card-box">
    
    <div class="reset-header">
      <div class="reset-brand-badge">
        <span class="pulse-dot"></span>
        <span>Recuperación Segura</span>
      </div>
      <h1 class="reset-logo-title">
        <span>⚡</span>
        <span class="brand-gradient">XINDRO</span>
      </h1>
      <p class="reset-subtitle">Crea una nueva contraseña segura para tu cuenta.</p>
    </div>

    <!-- Alert Box -->
    <div class="auth-alert-box" id="reset-alert" role="alert">
      <span id="reset-alert-icon">⚠️</span>
      <span id="reset-alert-text"></span>
    </div>

    <?php if (!$isValidToken): ?>
      <!-- Invalid or Expired Token State -->
      <div class="token-error-box">
        <div class="token-error-icon">⚠️</div>
        <h2 class="token-error-title">Enlace no válido o expirado</h2>
        <p class="token-error-desc">
          <?= htmlspecialchars($tokenError, ENT_QUOTES, 'UTF-8') ?>
        </p>
        <a href="login.php?tab=forgot" class="btn-auth-submit" style="text-decoration: none;">
          <span>Solicitar un Nuevo Enlace 📨</span>
        </a>
      </div>
    <?php else: ?>
      <!-- Valid Token: Reset Form -->
      <div style="background: rgba(124, 58, 237, 0.08); border: 1px solid rgba(124, 58, 237, 0.22); border-radius: 12px; padding: 12px 14px; margin-bottom: 20px; font-size: 0.8rem; color: #cbd5e1; line-height: 1.45;">
        <span>👤 Restableciendo contraseña para: <strong style="color: #c084fc;"><?= htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8') ?></strong></span>
      </div>

      <form id="form-reset-password" onsubmit="ResetUI.submitNewPassword(event)" novalidate>
        <input type="hidden" id="reset-token" value="<?= htmlspecialchars($rawToken, ENT_QUOTES, 'UTF-8') ?>">

        <div class="auth-form-group">
          <label for="new-password">Nueva Contraseña (Mínimo 8 caracteres):</label>
          <div class="auth-input-wrapper">
            <span class="auth-input-icon">🔑</span>
            <input 
              type="password" 
              id="new-password" 
              class="auth-input" 
              placeholder="Escribe tu nueva contraseña" 
              autocomplete="new-password" 
              maxlength="256"
              oninput="ResetUI.onPasswordInput(this)"
            />
            <button type="button" class="auth-input-toggle-pw" onclick="ResetUI.togglePasswordVisibility('new-password', this)" title="Mostrar/Ocultar contraseña" aria-label="Mostrar/Ocultar contraseña">
              👁️
            </button>
          </div>
          <div class="pw-strength-container" id="pw-strength-box">
            <div class="pw-strength-bar">
              <div class="pw-strength-fill" id="pw-strength-fill"></div>
            </div>
            <span class="pw-strength-text" id="pw-strength-label">Fuerza de la contraseña</span>
          </div>
          <p class="field-error-msg" id="error-new-password"></p>
        </div>

        <div class="auth-form-group">
          <label for="confirm-password">Confirmar Nueva Contraseña:</label>
          <div class="auth-input-wrapper">
            <span class="auth-input-icon">🔒</span>
            <input 
              type="password" 
              id="confirm-password" 
              class="auth-input" 
              placeholder="Repite tu nueva contraseña" 
              autocomplete="new-password" 
              maxlength="256"
              oninput="ResetUI.clearFieldError('confirm-password')"
            />
            <button type="button" class="auth-input-toggle-pw" onclick="ResetUI.togglePasswordVisibility('confirm-password', this)" title="Mostrar/Ocultar contraseña" aria-label="Mostrar/Ocultar contraseña">
              👁️
            </button>
          </div>
          <p class="field-error-msg" id="error-confirm-password"></p>
        </div>

        <button type="submit" class="btn-auth-submit" id="btn-submit-reset">
          <span>Actualizar Contraseña Segura ⚡</span>
        </button>
      </form>
    <?php endif; ?>

    <div style="margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--border-subtle); text-align: center; font-size: 0.75rem; color: var(--text-dim);">
      <span>Cifrado con Argon2ID / Bcrypt de 12 rondas. Tus credenciales nunca se exponen.</span>
    </div>

  </div>
</div>

<script>
const ResetUI = {
  getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
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
    const box = document.getElementById('reset-alert');
    const textEl = document.getElementById('reset-alert-text');
    const iconEl = document.getElementById('reset-alert-icon');
    if (!box || !textEl) return;

    box.className = `auth-alert-box ${type}`;
    textEl.textContent = message;
    iconEl.textContent = type === 'success' ? '✅' : '⚠️';
    box.style.display = 'flex';
  },

  hideAlert() {
    const box = document.getElementById('reset-alert');
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
    
    const errorMap = {
      'new-password': 'error-new-password',
      'confirm-password': 'error-confirm-password'
    };
    const errEl = document.getElementById(errorMap[inputId]);
    if (errEl) {
      errEl.textContent = '';
      errEl.classList.remove('visible');
    }
  },

  onPasswordInput(input) {
    this.clearFieldError('new-password');
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

  async submitNewPassword(e) {
    e.preventDefault();
    this.hideAlert();
    this.clearFieldError('new-password');
    this.clearFieldError('confirm-password');

    const token = document.getElementById('reset-token').value;
    const passwordInput = document.getElementById('new-password');
    const confirmInput = document.getElementById('confirm-password');
    const password = passwordInput.value;
    const confirm = confirmInput.value;
    const btn = document.getElementById('btn-submit-reset');

    let hasError = false;

    if (!password) {
      this.setFieldError('new-password', 'error-new-password', 'Por favor ingresa tu nueva contraseña.');
      hasError = true;
    } else if (password.length < 8) {
      this.setFieldError('new-password', 'error-new-password', 'La contraseña debe tener al menos 8 caracteres.');
      hasError = true;
    }

    if (!confirm) {
      this.setFieldError('confirm-password', 'error-confirm-password', 'Por favor repite tu nueva contraseña.');
      hasError = true;
    } else if (password !== confirm) {
      this.setFieldError('confirm-password', 'error-confirm-password', 'Las contraseñas ingresadas no coinciden.');
      hasError = true;
    }

    if (hasError) {
      return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="btn-spinner"></span><span>Actualizando contraseña...</span>';

    try {
      const response = await fetch('api/auth.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': this.getCsrfToken()
        },
        body: JSON.stringify({
          action: 'reset_password',
          token: token,
          password: password
        })
      });

      const res = await response.json();

      if (res.success) {
        this.showAlert(res.message + ' Redirigiendo al login...', 'success');
        setTimeout(() => {
          window.location.href = 'login.php';
        }, 1500);
      } else {
        if (res.field === 'password') {
          this.setFieldError('new-password', 'error-new-password', res.error);
        } else {
          this.showAlert(res.error || 'Error al restablecer la contraseña.');
        }
        btn.disabled = false;
        btn.innerHTML = '<span>Actualizar Contraseña Segura ⚡</span>';
      }
    } catch (err) {
      console.error(err);
      this.showAlert('Error de conexión con el servidor. Inténtalo nuevamente.');
      btn.disabled = false;
      btn.innerHTML = '<span>Actualizar Contraseña Segura ⚡</span>';
    }
  }
};
</script>

</body>
</html>
