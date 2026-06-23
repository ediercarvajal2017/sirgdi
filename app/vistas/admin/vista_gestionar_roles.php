<div class="container roles-container">
    <h2>Gestionar Roles y Permisos</h2>
    <p class="subtitle">Configura los permisos de cada rol del sistema</p>

    <div class="roles-grid">
        <?php if (empty($roles)): ?>
            <div class="empty-state">
                <p>No hay roles disponibles.</p>
            </div>
        <?php else: ?>
            <?php foreach ($roles as $rol): ?>
                <div class="role-card">
                    <h3><?php echo htmlspecialchars($rol['nombre_rol']); ?></h3>
                    <p class="role-id">ID: <?php echo intval($rol['id_rol']); ?></p>

                    <div class="permisos-list">
                        <?php if (empty($permisos)): ?>
                            <p><em>No hay permisos configurados</em></p>
                        <?php else: ?>
                            <ul>
                                <?php foreach ($permisos as $permiso): ?>
                                    <li>
                                        <label>
                                            <input type="checkbox"
                                                   name="permiso_<?php echo intval($rol['id_rol']); ?>_<?php echo intval($permiso['id_permiso']); ?>"
                                                   class="permiso-checkbox"
                                                   data-rol="<?php echo intval($rol['id_rol']); ?>"
                                                   data-permiso="<?php echo intval($permiso['id_permiso']); ?>">
                                            <strong><?php echo htmlspecialchars($permiso['codigo']); ?></strong>
                                            <br>
                                            <small><?php echo htmlspecialchars($permiso['descripcion'] ?? ''); ?></small>
                                        </label>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.roles-container {
    max-width: 1200px;
    margin: 30px auto;
    padding: 20px;
}

.subtitle {
    color: #666;
    font-size: 0.95em;
    margin-bottom: 30px;
}

.roles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.role-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.role-card h3 {
    margin-bottom: 5px;
    color: #333;
}

.role-id {
    color: #999;
    font-size: 0.9em;
    margin-bottom: 15px;
}

.permisos-list ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.permisos-list li {
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}

.permisos-list li:last-child {
    border-bottom: none;
}

.permisos-list label {
    display: flex;
    align-items: flex-start;
    cursor: pointer;
}

.permisos-list input[type="checkbox"] {
    margin-right: 10px;
    margin-top: 2px;
    cursor: pointer;
}

.permisos-list strong {
    color: #333;
    font-size: 0.9em;
}

.permisos-list small {
    color: #666;
    display: block;
    margin-top: 3px;
}

.empty-state {
    text-align: center;
    padding: 40px;
    background: white;
    border-radius: 4px;
    color: #999;
}
</style>
