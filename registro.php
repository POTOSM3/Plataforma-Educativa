<?php
session_start();
if (isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registro — EduLive</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <style>
    :root {
      --primary1: #00D4FF;
      --primary2: #6C63FF;
      --accent: #FFD166;
      --text: #fff;
    }

    * { box-sizing: border-box; }

    body {
      background: linear-gradient(120deg, var(--primary1), var(--primary2));
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
      overflow: hidden;
      font-family: 'Poppins', sans-serif;
      color: var(--text);
    }

    .floating-icons {
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 100%;
      overflow: hidden;
      z-index: 0;
      pointer-events: none;
    }

    .floating-icons i {
      position: absolute;
      color: rgba(255,255,255,0.25);
      text-shadow: 0 0 10px rgba(255,255,255,0.3);
      animation: fall linear infinite;
    }

    @keyframes fall {
      0% {
        transform: translateY(-10vh) translateX(0) rotate(0deg);
        opacity: 0;
      }
      10% { opacity: 1; }
      50% {
        transform: translateY(50vh) translateX(30px) rotate(180deg);
      }
      100% {
        transform: translateY(110vh) translateX(-30px) rotate(360deg);
        opacity: 0;
      }
    }

    .register-card {
      position: relative;
      z-index: 1;
      width: 450px;
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(15px);
      border-radius: 20px;
      padding: 40px;
      text-align: center;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    }

    .register-card h1 { font-size: 1.8rem; margin-bottom: 10px; }
    .register-card p { font-size: .95rem; opacity: .9; margin-bottom: 25px; }
    .register-card label { display: block; text-align: left; margin-bottom: 6px; font-weight: 600; }
    .register-card input, .register-card select {
      width: 100%; padding: 12px; border-radius: 10px; border: none; margin-bottom: 18px;
      outline: none; font-size: 1rem;
    }

    .register-card button {
      width: 100%;
      background: linear-gradient(135deg, #FFD166, #F4A261);
      border: none;
      color: #1d3557;
      font-weight: 700;
      padding: 12px;
      border-radius: 10px;
      font-size: 1rem;
      cursor: pointer;
      transition: 0.3s;
    }

    .register-card button:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    }

    .extra {
      margin-top: 15px;
      font-size: .9rem;
    }

    .extra a {
      color: var(--accent);
      font-weight: 600;
      text-decoration: none;
    }
  </style>
</head>
<body>
  <div class="floating-icons" id="icon-container"></div>

  <div class="register-card">
    <i data-lucide="user-plus" style="width:50px; height:50px; color:var(--accent);"></i>
    <h1>Crea tu cuenta en EduLive</h1>
    <p>Accede a cursos interactivos y mide tu progreso en cada materia 📚</p>

    <form action="guardar_estudiante.php" method="POST">
      <label for="nombre">Nombre completo</label>
      <input type="text" name="nombre" id="nombre" placeholder="Ej: Edward Potosme" required>

      <label for="correo">Correo electrónico</label>
      <input type="email" name="correo" id="correo" placeholder="ejemplo@correo.com" required>

      <label for="contraseña">Contraseña</label>
      <input type="password" name="contraseña" id="contraseña" placeholder="********" minlength="6" required>

      <label for="materia">Materia de interés</label>
      <select name="materia" id="materia" required>
        <option value="">Selecciona una materia</option>
        <option value="Lenguaje">Lenguaje</option>
        <option value="Matemática">Matemática</option>
        <option value="Ciencias">Ciencias</option>
        <option value="Sociales">Sociales</option>
        <option value="Inglés">Inglés</option>
      </select>

      <button type="submit">Registrarme</button>
    </form>

    <div class="extra">
      ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>
    </div>
  </div>

  <script>
    lucide.createIcons();
    const icons = ['book-open', 'flask-conical', 'globe', 'calculator', 'pen-tool', 'graduation-cap'];
    const container = document.getElementById('icon-container');

    for (let i = 0; i < 25; i++) {
      const icon = document.createElement('i');
      icon.setAttribute('data-lucide', icons[Math.floor(Math.random() * icons.length)]);
      icon.style.left = Math.random() * 100 + '%';
      icon.style.fontSize = 20 + Math.random() * 30 + 'px';
      icon.style.animationDuration = 8 + Math.random() * 10 + 's';
      icon.style.animationDelay = Math.random() * 5 + 's';
      container.appendChild(icon);
    }

    lucide.createIcons();
  </script>
</body>
</html>
