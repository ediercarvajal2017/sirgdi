<?php
    $old = $form_old ?? [];
    $campoErr = $form_campo_error ?? '';
    $errCls = function($k) use ($campoErr) { return $campoErr === $k ? ' input-error' : ''; };
?>
<div class="form-container-modern">
    <div class="page-banner">
        <div class="page-banner__icon"><i class="fas fa-plus-circle"></i></div>
        <div class="page-banner__text">
            <h2>Crear Nueva Institución</h2>
            <p>Registra una nueva institución educativa y su administrador.</p>
        </div>
    </div>

    <form method="POST" action="<?php echo config('app.url_base'); ?>/?controlador=superadmin&accion=crear_institucion" class="form-modern" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

        <div class="form-group">
            <label for="nombre"><i class="fas fa-school"></i> Nombre de la Institución <span class="required">*</span></label>
            <input
                type="text"
                id="nombre"
                name="nombre"
                class="input-modern<?php echo $errCls('nombre'); ?>"
                placeholder="Ej: Colegio Técnico Municipal"
                required
                minlength="3"
                maxlength="90"
                value="<?php echo htmlspecialchars($old['nombre'] ?? '', ENT_QUOTES); ?>">
            <small class="form-help">Nombre oficial de la institución (máximo 90 caracteres)</small>
        </div>

        <div class="form-group">
            <label for="codigo_dane"><i class="fas fa-barcode"></i> Código DANE <span class="required">*</span></label>
            <input
                type="text"
                id="codigo_dane"
                name="codigo_dane"
                class="input-modern<?php echo $errCls('codigo_dane'); ?>"
                placeholder="Ej: 1234567890"
                required
                pattern="[0-9]{5,13}"
                minlength="5"
                maxlength="13"
                inputmode="numeric"
                title="El código DANE debe contener entre 5 y 13 dígitos numéricos"
                value="<?php echo htmlspecialchars($old['codigo_dane'] ?? '', ENT_QUOTES); ?>">
            <small class="form-help">Entre 5 y 13 dígitos numéricos (Ej: 1234567890)</small>
        </div>

        <div class="form-group">
            <label for="logo"><i class="fas fa-image"></i> Logo de la Institución</label>
            <div class="file-upload-modern">
                <input
                    type="file"
                    id="logo"
                    name="logo"
                    class="file-input"
                    accept="image/png,image/jpeg,image/jpg,image/webp"
                    onchange="previewLogo(this)">
                <div class="file-label">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <span class="file-text">Arrastra tu archivo aquí o haz clic</span>
                    <small>PNG, JPG o WebP (máx. 5MB)</small>
                </div>
                <div id="logo-preview" class="logo-preview" style="display: none;">
                    <img id="preview-img" src="" alt="Preview">
                    <button type="button" class="btn-remove-file" onclick="removeLogo()" title="Eliminar archivo">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <small class="form-help">Opcional. Imagen de logo en formato PNG, JPG o WebP</small>
        </div>

        <!-- PRIMER ADMINISTRADOR -->
        <div class="admin-divider">
            <i class="fas fa-user-shield"></i> Administrador de la Institución
        </div>
        <p class="admin-intro">Este será el primer usuario (rol Administrador) que gestionará la institución y creará al resto del equipo.</p>

        <div class="form-group">
            <label for="admin_nombre"><i class="fas fa-user"></i> Nombre del Administrador <span class="required">*</span></label>
            <input type="text" id="admin_nombre" name="admin_nombre" class="input-modern<?php echo $errCls('admin_nombre'); ?>" placeholder="Ej: María González" required minlength="3" maxlength="150"
                value="<?php echo htmlspecialchars($old['admin_nombre'] ?? '', ENT_QUOTES); ?>">
        </div>

        <div class="form-group">
            <label for="admin_documento"><i class="fas fa-id-card"></i> Número de Documento <span class="required">*</span></label>
            <input type="text" id="admin_documento" name="admin_documento" class="input-modern<?php echo $errCls('admin_documento'); ?>" placeholder="Ej: 1098765432" required pattern="[0-9]{5,20}" minlength="5" maxlength="20" inputmode="numeric" title="Solo números (5 a 20 dígitos)"
                value="<?php echo htmlspecialchars($old['admin_documento'] ?? '', ENT_QUOTES); ?>">
        </div>

        <div class="form-group">
            <label for="admin_email"><i class="fas fa-envelope"></i> Email (usuario de acceso) <span class="required">*</span></label>
            <input type="email" id="admin_email" name="admin_email" class="input-modern<?php echo $errCls('admin_email'); ?>" placeholder="admin@institucion.edu.co" required
                value="<?php echo htmlspecialchars($old['admin_email'] ?? '', ENT_QUOTES); ?>">
        </div>

        <div class="form-group">
            <label for="admin_password"><i class="fas fa-key"></i> Contraseña inicial</label>
            <div class="pass-wrap">
                <input type="text" id="admin_password" name="admin_password" class="input-modern<?php echo $errCls('admin_password'); ?>" minlength="6" maxlength="60" placeholder="Vacío = se genera automática">
                <button type="button" class="btn-gen" onclick="generarPassInst()" title="Generar contraseña"><i class="fas fa-dice"></i></button>
            </div>
            <small class="form-help">Si la dejas vacía, el sistema genera una y te la mostrará al crear.</small>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-modern btn-primary-modern">
                <i class="fas fa-check-circle"></i> Crear Institución
            </button>
            <a href="<?php echo config('app.url_base'); ?>/?controlador=superadmin&accion=inicio" class="btn-modern btn-secondary-modern">
                <i class="fas fa-times-circle"></i> Cancelar
            </a>
        </div>
    </form>
</div>

<style>
    :root {
        --primary-blue: #3498DB;
        --dark-blue: #2980B9;
        --gray-text: #808B96;
        --dark-text: #2C3E50;
        --light-bg: #F8FBFC;
    }

    .form-container-modern {
        max-width: 500px;
        margin: 40px auto;
        padding: 0;
    }

    .form-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .form-header h2 {
        font-size: 28px;
        font-weight: 700;
        color: var(--dark-text);
        margin: 0 0 8px 0;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
    }

    .form-header h2 i {
        color: var(--primary-blue);
        font-size: 28px;
    }

    .form-subtitle {
        color: var(--gray-text);
        font-size: 14px;
        margin: 0;
        font-weight: 500;
    }

    .form-modern {
        background: white;
        border-radius: 12px;
        padding: 40px;
        box-shadow: 0 8px 32px rgba(52, 152, 219, 0.1);
        border-left: 4px solid var(--primary-blue);
    }

    .form-group {
        margin-bottom: 24px;
    }

    .form-group label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        color: var(--dark-text);
        margin-bottom: 10px;
        font-size: 14px;
    }

    .form-group label i {
        color: var(--primary-blue);
        font-size: 16px;
    }

    .form-group .required {
        color: #E74C3C;
        font-weight: 700;
    }

    .input-modern {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid var(--primary-blue);
        border-radius: 8px;
        font-family: inherit;
        font-size: 15px;
        background-color: var(--light-bg);
        color: var(--dark-text);
        transition: all 0.3s ease;
    }

    .input-modern::placeholder {
        color: var(--gray-text);
    }

    .input-modern:focus {
        outline: none;
        border-color: var(--dark-blue);
        background-color: white;
        box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1);
    }

    .input-modern.input-error {
        border-color: #E74C3C !important;
        background: rgba(231, 76, 60, 0.05);
        box-shadow: 0 0 0 4px rgba(231, 76, 60, 0.12);
        animation: shakeError 0.4s;
    }
    @keyframes shakeError {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-6px); }
        75% { transform: translateX(6px); }
    }

    .form-help {
        display: block;
        color: var(--gray-text);
        font-size: 12px;
        margin-top: 8px;
        font-weight: 500;
    }

    .admin-divider {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 30px 0 6px;
        padding-top: 22px;
        border-top: 2px dashed rgba(52,152,219,.3);
        font-weight: 700;
        color: var(--dark-text);
        font-size: 16px;
    }
    .admin-divider i { color: var(--primary-blue); }
    .admin-intro { color: var(--gray-text); font-size: 13px; margin: 0 0 20px; }

    .pass-wrap { display: flex; gap: 8px; }
    .pass-wrap .input-modern { flex: 1; }
    .btn-gen {
        width: 48px; border: 2px solid var(--primary-blue); background: var(--light-bg);
        border-radius: 8px; cursor: pointer; color: var(--primary-blue); font-size: 16px; flex-shrink: 0; transition: all .3s;
    }
    .btn-gen:hover { background: var(--primary-blue); color: #fff; }

    .form-actions {
        display: flex;
        gap: 12px;
        justify-content: center;
        margin-top: 35px;
        flex-wrap: wrap;
    }

    .btn-modern {
        padding: 12px 28px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 700;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .btn-primary-modern {
        background: linear-gradient(135deg, var(--primary-blue) 0%, var(--dark-blue) 100%);
        color: white;
        min-width: 200px;
        justify-content: center;
    }

    .btn-primary-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(52, 152, 219, 0.4);
    }

    .btn-secondary-modern {
        background: rgba(128, 139, 150, 0.15);
        color: var(--gray-text);
        min-width: 200px;
        justify-content: center;
    }

    .btn-secondary-modern:hover {
        background: var(--gray-text);
        color: white;
        transform: translateY(-2px);
    }

    /* File Upload Moderno */
    .file-upload-modern {
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .file-input {
        display: none;
    }

    .file-label {
        padding: 40px 20px;
        border: 2px dashed var(--primary-blue);
        border-radius: 8px;
        background: rgba(52, 152, 219, 0.05);
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
    }

    .file-label:hover {
        background: rgba(52, 152, 219, 0.1);
        border-color: var(--dark-blue);
    }

    .file-label i {
        font-size: 32px;
        color: var(--primary-blue);
    }

    .file-text {
        font-weight: 600;
        color: var(--dark-text);
        font-size: 14px;
    }

    .file-label small {
        color: var(--gray-text);
        font-size: 12px;
        font-weight: 500;
    }

    .logo-preview {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: var(--light-bg);
        border-radius: 8px;
        border: 2px solid rgba(52, 152, 219, 0.2);
        min-height: 150px;
    }

    .logo-preview img {
        max-width: 100%;
        max-height: 200px;
        border-radius: 6px;
        box-shadow: 0 4px 12px rgba(52, 152, 219, 0.15);
    }

    .btn-remove-file {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 32px;
        height: 32px;
        background: linear-gradient(135deg, #E74C3C 0%, #C0392B 100%);
        border: none;
        border-radius: 50%;
        color: white;
        font-size: 16px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .btn-remove-file:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
    }

    @media (max-width: 768px) {
        .form-modern {
            padding: 25px;
        }

        .form-header h2 {
            font-size: 22px;
        }

        .btn-modern {
            width: 100%;
            justify-content: center;
        }

        .file-label {
            padding: 30px 15px;
        }

        .file-label i {
            font-size: 28px;
        }
    }
</style>

<script>
    // Drag and drop para archivo
    const fileUploadArea = document.querySelector('.file-label');
    const fileInput = document.getElementById('logo');

    if (fileUploadArea) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, () => {
                fileUploadArea.style.borderColor = 'var(--primary-blue)';
                fileUploadArea.style.background = 'rgba(52, 152, 219, 0.15)';
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, () => {
                fileUploadArea.style.borderColor = 'var(--primary-blue)';
                fileUploadArea.style.background = 'rgba(52, 152, 219, 0.05)';
            });
        });

        fileUploadArea.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            fileInput.files = files;
            previewLogo(fileInput);
        });

        fileUploadArea.addEventListener('click', () => fileInput.click());
    }

    function previewLogo(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];

            // Validar tipo de archivo
            const validTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                alert('Por favor sube un archivo PNG, JPG o WebP');
                input.value = '';
                document.getElementById('logo-preview').style.display = 'none';
                return;
            }

            // Validar tamaño (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('El archivo no puede exceder 5MB');
                input.value = '';
                document.getElementById('logo-preview').style.display = 'none';
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                document.getElementById('preview-img').src = e.target.result;
                document.getElementById('logo-preview').style.display = 'flex';
            };
            reader.readAsDataURL(file);
        }
    }

    function removeLogo() {
        document.getElementById('logo').value = '';
        document.getElementById('logo-preview').style.display = 'none';
    }

    function generarPassInst() {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        let p = '';
        for (let i = 0; i < 10; i++) p += chars.charAt(Math.floor(Math.random() * chars.length));
        document.getElementById('admin_password').value = p;
    }

    // Enfocar y limpiar el campo con error de validación
    (function() {
        const campoError = <?php echo json_encode($form_campo_error ?? ''); ?>;
        if (campoError) {
            const el = document.getElementById(campoError);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(function() { el.focus(); }, 300);
                el.addEventListener('input', function() { el.classList.remove('input-error'); }, { once: true });
            }
        }
    })();
</script>

<?php require APP_PATH . '/vistas/comunes/toast_helper.php'; ?>
