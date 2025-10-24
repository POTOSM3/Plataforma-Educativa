<?php
$tema = isset($_GET['tema']) ? intval($_GET['tema']) : 1;
$archivo = "data/preguntas_{$tema}.json";
$preguntas = file_exists($archivo) ? json_decode(file_get_contents($archivo), true) : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cuestionario - Plataforma Educativa</title>
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
            <a href="temas.php">Cursos</a>
            <a href="contacto.php">Contacto</a>
            <a href="#">Cerrar sesión</a>
        </nav>
    </header>

    <main class="principal">
        <section class="bienvenida">
            <h2>🧩 Cuestionario</h2>
            <p>Responde las siguientes preguntas:</p>
        </section>

        <section class="quiz">
            <form action="guardar_resultado.php" method="POST" class="form">
                <input type="hidden" name="tema" value="<?= $tema ?>">
                <?php foreach ($preguntas as $i => $p): ?>
                    <div class="pregunta">
                        <h3><?= ($i + 1) . '. ' . htmlspecialchars($p['pregunta']) ?></h3>
                        <?php foreach ($p['opciones'] as $j => $opcion): ?>
                            <label class="opcion">
                                <input type="radio" name="respuestas[<?= $i ?>]" value="<?= $j ?>" required>
                                <?= htmlspecialchars($opcion) ?>
                            </label><br>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                <button type="submit" class="btn">Enviar Respuestas</button>
            </form>
        </section>
    </main>

    <footer>
        <p>© <?= date('Y') ?> Plataforma Educativa | Equipo de Desarrollo Web</p>
    </footer>

    <script>lucide.createIcons();</script>
</body>
</html>
