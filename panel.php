<?php
session_start();
if (!isset($_SESSION['usuario'])) {
  header("Location: login.php");
  exit;
}

$usuario = $_SESSION['usuario'];
require 'conexion.php'; // Incluye la conexión a la BD

// 1. Cargar resultados del quiz del usuario desde la BD
$resultados = [];
try {
    // Consulta para obtener los resultados del usuario actual (por correo)
    // Asumimos que tienes una tabla llamada 'resultados_quiz'
    $sql_resultados = "
        SELECT 
            r.curso_id, r.aciertos, r.total_preguntas, 
            r.porcentaje, r.nivel_logro, r.fecha_resultado,
            c.titulo AS titulo_curso
        FROM resultados_quiz r
        JOIN cursos c ON r.curso_id = c.id
        WHERE r.usuario_correo = ?
        ORDER BY r.fecha_resultado DESC
    ";
    $stmt = $pdo->prepare($sql_resultados);
    $stmt->execute([$usuario['correo']]);
    $resultados = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error al cargar resultados: " . $e->getMessage());
    // Puedes dejar $resultados como un array vacío si hay un error
}

// Los temas ya no se necesitan para enlazar, ya están en $resultados
// Si necesitas los temas completos:
// $stmt_temas = $pdo->query("SELECT id, titulo FROM cursos");
// $temas_map = $stmt_temas->fetchAll(PDO::FETCH_KEY_PAIR); // ['id' => 'titulo']

// El resto del HTML queda igual, solo que ahora usa $resultados
include 'components/layout.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Panel - EduLive</title>
    <link rel="stylesheet" href="css/style_moderno.css">
    <script>
        (function() {
            const savedMode = localStorage.getItem('theme');
            if (savedMode === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>
</head>
<body style="background-color: #141a29;">

<?php renderSidebar($usuario, 'panel'); ?>

<main class="content" id="content">
    <section class="banner">
        <h1 class="title">📊 Panel de Progreso</h1>
        <p class="desc">Aquí puedes revisar tus resultados, logros y la información de tu cuenta.</p>
    </section>

    <section class="progress-info">
        <div class="stat-card">
            <i data-lucide="book-open"></i>
            <h3><?= count($resultados) ?> Quizzes Completados</h3>
            <p>Revisa tus notas y fortalece tus conocimientos.</p>
        </div>
        
        <div class="stat-card" style="background: #22C55E; color: white;">
            <i data-lucide="trophy"></i>
            <h3>Nivel: ⭐ Avanzado</h3> 
            <p>¡Sigue así para obtener tu certificado!</p>
        </div>

        <div class="stat-card" style="background: #EF4444; color: white;">
            <i data-lucide="bell"></i>
            <h3>Notificaciones</h3>
            <p>Tienes 2 nuevas clases en Lenguaje.</p>
        </div>
    </section>

    <h2 style="margin-top:2rem; margin-bottom:1rem; font-size:1.5rem; border-bottom: 2px solid var(--accent); padding-bottom: 5px;">Últimos Resultados de Quizzes</h2>

    <section class="results-table">
        <?php if (empty($resultados)): ?>
            <p style="text-align:center;">Aún no tienes resultados registrados. ¡Inscríbete en un curso y haz un quiz!</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Curso</th>
                        <th>Aciertos</th>
                        <th>Total</th>
                        <th>%</th>
                        <th>Nivel</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultados as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['titulo_curso'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($r['aciertos'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($r['total_preguntas'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($r['porcentaje'] ?? '-') ?>%</td>
                            <td><?= htmlspecialchars($r['nivel_logro'] ?? '-') ?></td>
                            <td><?= date('d/m/Y', strtotime($r['fecha_resultado'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <footer class="footer">
        © <?= date('Y') ?> EduLive — Plataforma Educativa • Hecho con ❤️
    </footer>
</main>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    lucide.createIcons();
    
    // --- LÓGICA DEL BOTÓN DE MODO OSCURO/CLARO ---
    const body = document.body;
    const modeBtn = document.getElementById("modeBtn");

    const savedMode = localStorage.getItem("theme");
    if (savedMode === "dark") {
      body.classList.add("dark");
      modeBtn.innerHTML = '<i data-lucide="sun"></i> Claro';
    } else {
      body.classList.remove("dark");
      modeBtn.innerHTML = '<i data-lucide="moon"></i> Oscuro';
    }

    modeBtn.addEventListener("click", () => {
      const isDark = body.classList.toggle("dark");
      if (isDark) {
        localStorage.setItem("theme", "dark");
        modeBtn.innerHTML = '<i data-lucide="sun"></i> Claro';
      } else {
        localStorage.setItem("theme", "light");
        modeBtn.innerHTML = '<i data-lucide="moon"></i> Oscuro';
      }
      lucide.createIcons(); 
    });
</script>

</body>
</html>