<style>
    .admin-login-wrapper {
        flex-grow: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1rem;
    }
    .admin-login-card {
        width: 100%;
        max-width: 400px;
        background: white;
        padding: 2rem;
        border-radius: 12px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }
    .admin-login-card h1 {
        font-size: 1.5rem;
        font-weight: bold;
        text-align: center;
        color: #4A148C;
        margin-bottom: 0.5rem;
        margin-top: 0;
    }
    .admin-login-card .subtitle {
        text-align: center;
        color: #6B7280;
        margin-bottom: 1.5rem;
        font-size: 0.9rem;
    }
    .admin-login-error {
        margin-bottom: 1rem;
        padding: 0.75rem;
        background-color: #FEE2E2;
        border: 1px solid #FECACA;
        color: #991B1B;
        border-radius: 6px;
        font-size: 0.9rem;
    }
    .admin-login-info {
        margin-bottom: 1.5rem;
        padding: 0.75rem;
        border-radius: 6px;
        border: 1px solid rgba(103, 58, 183, 0.2);
        background-color: rgba(103, 58, 183, 0.05);
        font-size: 0.85rem;
        color: #374151;
    }
    .admin-login-form-group {
        margin-bottom: 1rem;
    }
    .admin-login-form-group label {
        display: block;
        color: #374151;
        font-size: 0.85rem;
        font-weight: 600;
        margin-bottom: 0.3rem;
    }
    .admin-login-form-group input[type="email"],
    .admin-login-form-group input[type="password"] {
        width: 100%;
        padding: 0.6rem 0.8rem;
        border: 1px solid #D1D5DB;
        border-radius: 6px;
        font-size: 0.9rem;
        box-sizing: border-box;
        transition: all 0.2s;
    }
    .admin-login-form-group input:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(103, 58, 183, 0.3);
        border-color: #673AB7;
    }
    .admin-login-btn {
        width: 100%;
        background-color: #E91E63;
        color: white;
        font-weight: bold;
        padding: 0.7rem 1rem;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.9rem;
        transition: background-color 0.2s;
        margin-top: 0.5rem;
    }
    .admin-login-btn:hover {
        background-color: #673AB7;
    }
    .admin-login-link {
        margin-top: 0.75rem;
        text-align: center;
    }
    .admin-login-link a {
        font-size: 0.85rem;
        color: #673AB7;
        text-decoration: none;
        transition: color 0.2s;
    }
    .admin-login-link a:hover {
        color: #E91E63;
    }
</style>

<main class="admin-login-wrapper">
    <div class="admin-login-card">
        <h1>Acceso Administrativo</h1>
        <p class="subtitle">Ingresa con tu correo institucional y tu contraseña personal.</p>

        <?php if (isset($_GET['error'])): ?>
            <div class="admin-login-error">
                <?php
                if ($_GET['error'] == 'invalid_credentials') {
                    echo "Correo o contraseña incorrectos";
                } elseif ($_GET['error'] == 'campos_vacios') {
                    echo "Debe ingresar correo y contraseña";
                } elseif ($_GET['error'] == 'invalid_request') {
                    echo "La sesión del formulario expiró. Intente nuevamente";
                } else {
                    echo "Error al iniciar sesión";
                }
                ?>
            </div>
        <?php endif; ?>

        <div class="admin-login-info">
            Cada administrador debe tener su propia cuenta. Si es tu primer ingreso, deberás cambiar la contraseña temporal asignada.
        </div>

        <form action="<?php echo $basePath; ?>/admin/login/check" method="POST">
            <div class="admin-login-form-group">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" required autocomplete="username"
                    placeholder="usuario@superarse.edu.ec">
            </div>

            <div class="admin-login-form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required autocomplete="current-password"
                    placeholder="Ingrese su contraseña">
            </div>

            <button type="submit" class="admin-login-btn">
                Ingresar al Panel
            </button>
        </form>

        <div class="admin-login-link">
            <a href="<?php echo $basePath; ?>/admin/forgot-password">
                ¿Olvidaste tu contraseña?
            </a>
        </div>

        <div class="admin-login-link">
            <a href="<?php echo $basePath; ?>/login">
                ← Volver al login de estudiantes
            </a>
        </div>
    </div>
</main>
