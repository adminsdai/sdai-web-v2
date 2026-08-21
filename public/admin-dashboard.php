<?php
/**
 * SDAI CHILE - Panel de Control CRUD para Leads (Mensajes de Contacto)
 * Cumple con la Ley N° 21.719 - Permite la visualización, actualización de estados y eliminación.
 */

session_start();
require_once __DIR__ . '/db.php';

// Cargar contraseña del administrador desde .env
$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    $envFile = __DIR__ . '/.env';
}
$adminPassword = "SdaiSuperSecureAdminPass2026!"; // Valor por defecto si no hay .env

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        if (trim($name) === 'ADMIN_PASSWORD') {
            $adminPassword = trim($value, '"\' ');
        }
    }
}

// Cerrar sesión
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin-dashboard.php");
    exit();
}

// Validar login
$loginError = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    if ($password === $adminPassword) {
        $_SESSION['sdai_auth'] = true;
        header("Location: admin-dashboard.php");
        exit();
    } else {
        $loginError = "Contraseña incorrecta.";
    }
}

// Proteger ruta
if (!isset($_SESSION['sdai_auth']) || $_SESSION['sdai_auth'] !== true) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Acceso Administrador | SDAI CHILE</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&family=Sora:wght@700;800&display=swap" rel="stylesheet">
        <style>
            :root {
                --primary: #00A3FF;
                --bg: #020617;
                --card-bg: rgba(255, 255, 255, 0.03);
                --border: rgba(255, 255, 255, 0.08);
            }
            body {
                margin: 0;
                font-family: 'Inter', sans-serif;
                background-color: var(--bg);
                color: #fff;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                overflow: hidden;
            }
            /* Tech Grid Background */
            .grid-bg {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-image: 
                    linear-gradient(rgba(0, 163, 255, 0.02) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(0, 163, 255, 0.02) 1px, transparent 1px);
                background-size: 50px 50px;
                z-index: -1;
                pointer-events: none;
            }
            .login-card {
                background: var(--card-bg);
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
                border: 1px solid var(--border);
                padding: 40px;
                border-radius: 24px;
                width: 100%;
                max-width: 400px;
                box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.5);
                text-align: center;
            }
            .logo-title {
                font-family: 'Sora', sans-serif;
                font-size: 2rem;
                font-weight: 800;
                margin-bottom: 8px;
                letter-spacing: -1px;
            }
            .logo-title span {
                color: var(--primary);
            }
            .logo-slogan {
                font-size: 0.85rem;
                text-transform: uppercase;
                letter-spacing: 2px;
                color: rgba(255,255,255,0.5);
                margin-bottom: 30px;
                display: block;
            }
            .form-group {
                display: flex;
                flex-direction: column;
                gap: 8px;
                text-align: left;
                margin-bottom: 20px;
            }
            label {
                font-weight: 600;
                font-size: 0.9rem;
                color: rgba(255,255,255,0.8);
            }
            input {
                background: rgba(255, 255, 255, 0.05);
                border: 1px solid rgba(255, 255, 255, 0.1);
                border-radius: 12px;
                padding: 14px;
                color: white;
                font-size: 1rem;
                transition: all 0.3s ease;
            }
            input:focus {
                outline: none;
                border-color: var(--primary);
                background: rgba(255, 255, 255, 0.08);
                box-shadow: 0 0 15px rgba(0, 163, 255, 0.2);
            }
            .btn-login {
                width: 100%;
                background: linear-gradient(135deg, var(--primary) 0%, #007acc 100%);
                color: white;
                padding: 14px;
                border-radius: 12px;
                font-weight: 700;
                border: none;
                cursor: pointer;
                font-size: 1rem;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(0, 163, 255, 0.3);
            }
            .btn-login:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(0, 163, 255, 0.5);
            }
            .error-msg {
                color: #ff4a4a;
                font-size: 0.9rem;
                margin-top: 15px;
            }
        </style>
    </head>
    <body>
        <div class="grid-bg"></div>
        <div class="login-card">
            <div class="logo-title">SDAI<span>CHILE</span></div>
            <span class="logo-slogan">Admin Control Center</span>
            <form method="POST">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label for="password">Contraseña de Acceso</label>
                    <input type="password" id="password" name="password" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn-login">Verificar e Ingresar</button>
            </form>
            <?php if (!empty($loginError)): ?>
                <div class="error-msg">❌ <?php echo htmlspecialchars($loginError); ?></div>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Procesar acciones CRUD (Update Status, Delete)
$feedback = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'update_status') {
            $id = (int)$_POST['id'];
            $status = $_POST['status'];
            Database::updateMessageStatus($id, $status);
            $feedback = "Estado actualizado con éxito.";
        } elseif ($_POST['action'] === 'delete') {
            $id = (int)$_POST['id'];
            Database::deleteMessage($id);
            $feedback = "Mensaje eliminado permanentemente.";
        }
    } catch (Exception $e) {
        $feedback = "Error al procesar la acción: " . $e->getMessage();
    }
}

// Obtener mensajes de la base de datos
$messages = [];
$dbError = "";
try {
    $messages = Database::getAllMessages();
} catch (Exception $e) {
    $dbError = "Error al cargar la base de datos. Por favor, asegúrate de haber creado la tabla con `schema.sql` y configurado el archivo `.env` correctamente.";
}

// Conteo de estados
$countAll = count($messages);
$countPending = 0;
$countContacted = 0;
$countArchived = 0;
foreach ($messages as $msg) {
    if ($msg['status'] === 'PENDING') $countPending++;
    elseif ($msg['status'] === 'CONTACTED') $countContacted++;
    elseif ($msg['status'] === 'ARCHIVED') $countArchived++;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Leads | SDAI CHILE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Sora:wght@700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #00A3FF;
            --bg: #020617;
            --card-bg: rgba(255, 255, 255, 0.03);
            --border: rgba(255, 255, 255, 0.08);
            --text-dim: rgba(248, 250, 252, 0.7);
        }
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            color: #fff;
            min-height: 100vh;
        }
        .grid-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(0, 163, 255, 0.01) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 163, 255, 0.01) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: -1;
            pointer-events: none;
        }
        header {
            border-bottom: 1px solid var(--border);
            padding: 20px 40px;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            font-family: 'Sora', sans-serif;
            font-size: 1.6rem;
            font-weight: 800;
            color: #fff;
            text-decoration: none;
        }
        .logo span {
            color: var(--primary);
        }
        .btn-logout {
            color: var(--text-dim);
            text-decoration: none;
            font-weight: 600;
            padding: 8px 16px;
            border: 1px solid var(--border);
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .btn-logout:hover {
            color: #ff4a4a;
            border-color: #ff4a4a;
            background: rgba(255, 74, 74, 0.05);
        }
        main {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .intro {
            margin-bottom: 30px;
        }
        .intro h1 {
            font-family: 'Sora', sans-serif;
            font-size: 2rem;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #fff 0%, var(--primary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .feedback {
            background: rgba(0, 163, 255, 0.1);
            border: 1px solid var(--primary);
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 30px;
            font-size: 0.95rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            backdrop-filter: blur(8px);
        }
        .stat-card .num {
            font-family: 'Sora', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 5px;
        }
        .stat-card.active-stat .num {
            color: var(--primary);
        }
        .stat-card .label {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-dim);
        }
        .filters {
            display: flex;
            gap: 12px;
            margin-bottom: 25px;
        }
        .btn-filter {
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--border);
            color: #fff;
            padding: 10px 20px;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-filter:hover, .btn-filter.active {
            background: var(--primary);
            border-color: var(--primary);
            box-shadow: 0 4px 15px rgba(0, 163, 255, 0.3);
        }
        .table-container {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            overflow: hidden;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }
        th, td {
            padding: 20px;
            border-bottom: 1px solid var(--border);
        }
        th {
            background: rgba(255,255,255,0.01);
            font-family: 'Sora', sans-serif;
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        tr:last-child td {
            border-bottom: none;
        }
        .td-name {
            font-weight: 700;
            color: #fff;
        }
        .td-email {
            color: var(--primary);
        }
        .td-date {
            font-size: 0.85rem;
            color: var(--text-dim);
        }
        .badge-status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-PENDING {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }
        .status-CONTACTED {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        .status-ARCHIVED {
            background: rgba(107, 114, 128, 0.1);
            color: #9ca3af;
            border: 1px solid rgba(107, 114, 128, 0.2);
        }
        .actions-form {
            display: inline-flex;
            gap: 8px;
            align-items: center;
        }
        select {
            background: rgba(0,0,0,0.5);
            border: 1px solid var(--border);
            color: #fff;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            cursor: pointer;
            outline: none;
            transition: border-color 0.2s;
        }
        select:focus {
            border-color: var(--primary);
        }
        .btn-action-submit {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border);
            color: #fff;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-action-submit:hover {
            background: var(--primary);
            border-color: var(--primary);
        }
        .btn-delete {
            background: rgba(255, 74, 74, 0.08);
            border: 1px solid rgba(255, 74, 74, 0.2);
            color: #ff4a4a;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-delete:hover {
            background: #ff4a4a;
            color: #fff;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-dim);
        }
        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        @media (max-width: 900px) {
            table, thead, tbody, th, td, tr {
                display: block;
            }
            thead {
                display: none;
            }
            tr {
                border-bottom: 1px solid var(--border);
                padding: 20px 0;
            }
            td {
                border-bottom: none;
                padding: 8px 20px;
            }
            td::before {
                content: attr(data-label);
                float: left;
                font-weight: 700;
                text-transform: uppercase;
                font-size: 0.8rem;
                color: var(--text-dim);
            }
            .actions-form {
                display: flex;
                width: 100%;
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>
    <div class="grid-bg"></div>
    <header>
        <a href="/" class="logo">SDAI<span>CHILE</span></a>
        <a href="?logout=true" class="btn-logout">Cerrar Sesión</a>
    </header>

    <main>
        <div class="intro">
            <h1>Panel de Control de Leads</h1>
            <p style="color: var(--text-dim);">Gestiona de forma segura los mensajes de tus clientes y cumple con los estándares de la Ley N° 21.719.</p>
        </div>

        <?php if (!empty($feedback)): ?>
            <div class="feedback">
                <span>✨ <?php echo htmlspecialchars($feedback); ?></span>
                <button onclick="this.parentElement.style.display='none'" style="background:none;border:none;color:white;cursor:pointer;font-weight:bold;">✕</button>
            </div>
        <?php endif; ?>

        <?php if (!empty($dbError)): ?>
            <div class="feedback" style="background: rgba(239, 68, 68, 0.1); border-color: #ef4444; color: #ef4444;">
                <span>⚠️ <?php echo htmlspecialchars($dbError); ?></span>
            </div>
        <?php else: ?>

            <div class="stats-grid">
                <div class="stat-card active-stat">
                    <div class="num"><?php echo $countAll; ?></div>
                    <div class="label">Total Leads</div>
                </div>
                <div class="stat-card">
                    <div class="num" style="color: #f59e0b;"><?php echo $countPending; ?></div>
                    <div class="label">Pendientes</div>
                </div>
                <div class="stat-card">
                    <div class="num" style="color: #10b981;"><?php echo $countContacted; ?></div>
                    <div class="label">Contactados</div>
                </div>
                <div class="stat-card">
                    <div class="num" style="color: #9ca3af;"><?php echo $countArchived; ?></div>
                    <div class="label">Archivados</div>
                </div>
            </div>

            <div class="filters">
                <button class="btn-filter active" onclick="filterTable('ALL')">Todos</button>
                <button class="btn-filter" onclick="filterTable('PENDING')">Pendientes</button>
                <button class="btn-filter" onclick="filterTable('CONTACTED')">Contactados</button>
                <button class="btn-filter" onclick="filterTable('ARCHIVED')">Archivados</button>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Mensaje</th>
                            <th>Consentimiento (Ley 21.719)</th>
                            <th>Estado</th>
                            <th style="text-align: right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($messages)): ?>
                            <tr>
                                <td colspan="7" class="empty-state">
                                    <div class="empty-state-icon">📥</div>
                                    <p>No se encontraron mensajes de contacto en la base de datos.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($messages as $msg): ?>
                                <tr class="message-row" data-status="<?php echo htmlspecialchars($msg['status']); ?>">
                                    <td data-label="Fecha" class="td-date"><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($msg['createdAt']))); ?></td>
                                    <td data-label="Nombre" class="td-name"><?php echo htmlspecialchars($msg['name']); ?></td>
                                    <td data-label="Email" class="td-email"><a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>" style="color:inherit;text-decoration:none;"><?php echo htmlspecialchars($msg['email']); ?></a></td>
                                    <td data-label="Mensaje" style="max-width: 250px; word-wrap: break-word; font-size: 0.95rem; line-height: 1.5; color: rgba(255,255,255,0.9);"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></td>
                                    <td data-label="Consentimiento" style="font-size: 0.85rem; line-height: 1.4; color: var(--text-dim);">
                                        <?php if (isset($msg['consentGiven']) && $msg['consentGiven']): ?>
                                            <span style="color: #10b981; font-weight: bold;">✔️ Aceptado</span><br>
                                            <small style="opacity: 0.8;">IP: <?php echo htmlspecialchars($msg['ipAddress'] ?? 'N/A'); ?></small><br>
                                            <small style="opacity: 0.6; display: inline-block; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($msg['consentText'] ?? ''); ?>">Texto: <?php echo htmlspecialchars($msg['consentText'] ?? ''); ?></small>
                                        <?php else: ?>
                                            <span style="color: #ff4a4a;">❌ No Registrado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Estado">
                                        <span class="badge-status status-<?php echo htmlspecialchars($msg['status']); ?>">
                                            <?php echo htmlspecialchars($msg['status']); ?>
                                        </span>
                                    </td>
                                    <td data-label="Acciones" style="text-align: right;">
                                        <div style="display: inline-flex; gap: 8px;">
                                            <form method="POST" class="actions-form">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="id" value="<?php echo $msg['id']; ?>">
                                                <select name="status">
                                                    <option value="PENDING" <?php echo $msg['status'] === 'PENDING' ? 'selected' : ''; ?>>Pendiente</option>
                                                    <option value="CONTACTED" <?php echo $msg['status'] === 'CONTACTED' ? 'selected' : ''; ?>>Contactado</option>
                                                    <option value="ARCHIVED" <?php echo $msg['status'] === 'ARCHIVED' ? 'selected' : ''; ?>>Archivar</option>
                                                </select>
                                                <button type="submit" class="btn-action-submit">Guardar</button>
                                            </form>
                                            <form method="POST" class="actions-form" onsubmit="return confirm('¿Estás seguro de eliminar este lead permanentemente de acuerdo a los derechos ARCOP de la Ley 21.719?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $msg['id']; ?>">
                                                <button type="submit" class="btn-delete">Eliminar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </main>

    <script>
        function filterTable(status) {
            // Actualizar botones activos
            document.querySelectorAll('.btn-filter').forEach(btn => {
                btn.classList.remove('active');
                if (btn.innerText.toUpperCase() === status || (status === 'ALL' && btn.innerText === 'Todos') || (status === 'PENDING' && btn.innerText === 'Pendientes') || (status === 'CONTACTED' && btn.innerText === 'Contactados') || (status === 'ARCHIVED' && btn.innerText === 'Archivados')) {
                    btn.classList.add('active');
                }
            });

            // Filtrar filas
            document.querySelectorAll('.message-row').forEach(row => {
                if (status === 'ALL' || row.getAttribute('data-status') === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
