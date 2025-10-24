<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// Datos del usuario actual
$usuario = $_SESSION['usuario'];

// Cargar resultados
$archivo_resultados = 'data/resultados.json';
$resultados = file_exists($archivo_resultados)
    ? json_decode(file_get_contents($archivo_resultados), true)
    : [];

// Cargar temas
$archivo_temas = 'data/temas.json';
$temas = file_exists($archivo_temas)
    ? json_decode(file_get_contents($archivo_temas), true)
    : [];

// Filtrar resultados solo del usuario actual (por correo)
$resultados_usuario = array_filter($resultados, function ($r) use ($usuario) {
    return isset($r['correo']) && $r['correo'] === $usuario['correo'];
});
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel del Estudiante - Plataforma Educativa</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <header class="encabezado">
        <div class="logo">
            <i data-lucide="graduation-cap"></i>
            <h1>Plataforma Educativa</h1>
        </div>
        <nav>
            <a href="index.php">Inicio</a>
            <a href="panel.php" class="activo">Mi Panel</a>
            <a href="temas.php">Cursos</a>
            <a href="contacto.php">Contacto</a>
            <a href="logout.php">Cerrar sesión</a>
        </nav>
    </header>

    <main class="principal">
        <section class="bienvenida">
            <h2>🎓 Panel del Estudiante</h2>
            <p>Bienvenido, <strong><?= htmlspecialchars($usuario['nombre']) ?></strong></p>
            <p>Materia de interés: <strong><?= htmlspecialchars($usuario['materia']) ?></strong></p>
        </section>

        <section class="contenedor panel">
            <h3>📊 Tus Resultados</h3>
            <?php if (empty($resultados_usuario)): ?>
                <p>No tienes resultados todavía. ¡Empieza un cuestionario!</p>
            <?php else: ?>
                <table class="tabla-resultados">
                    <thead>
                        <tr>
                            <th>Curso</th>
                            <th>Aciertos</th>
                            <th>Total</th>
                            <th>Porcentaje</th>
                            <th>Nivel</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultados_usuario as $r): ?>
                            <tr>
                                <?php
                                // Buscar el nombre del tema
                                $nombre_tema = 'Desconocido';
                                foreach ($temas as $t) {
                                    if ($t['id'] == $r['tema']) {
                                        $nombre_tema = $t['titulo'];
                                        break;
                                    }
                                }
                                ?>
                                <td><?= htmlspecialchars($nombre_tema) ?></td>
                                <td><?= $r['aciertos'] ?></td>
                                <td><?= $r['total'] ?></td>
                                <td><?= $r['porcentaje'] ?>%</td>
                                <td><?= htmlspecialchars($r['nivel']) ?></td>
                                <td><?= htmlspecialchars($r['fecha']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <p>© <?= date('Y') ?> Plataforma Educativa | Equipo de Desarrollo Web</p>
    </footer>

    <script>lucide.createIcons();</script>
</body>
</html>
