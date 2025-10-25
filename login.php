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
  <title>Iniciar Sesión — EduLive</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest"></script>
  <style>
    :root {
      --primary1: #6C63FF;
      --primary2: #00D4FF;
      --accent: #FFD166;
      --text: #fff;
    }

    * { box-sizing: border-box; }

    body {
      margin: 0;
      height: 100vh;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(120deg, var(--primary1), var(--primary2));
      color: var(--text);
    }

    /* === FONDO ANIMADO === */
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
        transform: translateY(50vh) translateX(20px) rotate(180deg);
      }
      100% {
        transform: translateY(110vh) translateX(-20px) rotate(360deg);
        opacity: 0;
      }
    }

    /* === TARJETA LOGIN === */
    .login-card {
      position: relative;
      z-index: 1;
      width: 400px;
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(15px);
      border-radius: 20px;
      padding: 50px 40px;
      text-align: center;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
      color: #fff;
    }

    .login-card h1 {
      font-size: 2rem;
      margin-bottom: 10px;
    }

    .login-card p {
      font-size: .95rem;
      opacity: 0.9;
      margin-bottom: 30px;
    }

    .login-card label {
      display: block;
      text-align: left;
      margin-bottom: 6px;
      font-weight: 600;
    }

    .login-card input {
      width: 100%;
      padding: 12px;
      border-radius: 10px;
      border: none;
      margin-bottom: 20px;
      outline: none;
      font-size: 1rem;
    }

    .login-card button {
      width: 100%;
      background: linear-gradient(135deg, var(--accent), #f4a261);
      border: none;
      color: #1d3557;
      font-weight: 700;
      padding: 12px;
      border-radius: 10px;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.3s;
    }

    .login-card button:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    }

    .extra {
      margin-top: 20px;
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

  <div class="login-card">
    <i data-lucide="graduation-cap" style="width:50px; height:50px; color:var(--accent);"></i>
    <h1>Bienvenido a EduLive</h1>
    <p>Accede a tus cursos, aprende y mide tu progreso 🚀</p>

    <form action="validar_login.php" method="POST">
      <label for="correo">Correo electrónico</label>
      <input type="email" name="correo" id="correo" placeholder="ejemplo@correo.com" required>

      <label for="contraseña">Contraseña</label>
      <input type="password" name="contraseña" id="contraseña" placeholder="********" required>

      <button type="submit">Iniciar Sesión</button>
    </form>

    <div class="extra">
      ¿No tienes cuenta? <a href="registro.php">Regístrate</a>
    </div>
  </div>

  <script>
  lucide.createIcons();

  const icons = ['book-open', 'flask-conical', 'globe', 'calculator', 'pen-tool', 'graduation-cap'];
  const colors = ['#FFD166', '#06D6A0', '#118AB2', '#EF476F', '#A78BFA', '#F4A261'];
  const container = document.getElementById('icon-container');

  // Crear íconos animados
  for (let i = 0; i < 25; i++) {
    const icon = document.createElement('i');
    const color = colors[Math.floor(Math.random() * colors.length)];
    icon.setAttribute('data-lucide', icons[Math.floor(Math.random() * icons.length)]);
    icon.style.position = 'absolute';
    icon.style.left = Math.random() * 100 + '%';
    icon.style.top = Math.random() * -100 + 'px'; // Empiezan fuera de pantalla
    icon.style.fontSize = 20 + Math.random() * 25 + 'px';
    icon.style.color = color;
    icon.style.opacity = 0.7;
    icon.style.filter = `drop-shadow(0 0 8px ${color})`;
    icon.style.animation = `fall-${i} linear infinite`;
    icon.style.animationDuration = 6 + Math.random() * 8 + 's';
    icon.style.animationDelay = Math.random() * 3 + 's';
    container.appendChild(icon);

    // Movimiento lateral y rotación personalizada
    const drift = Math.random() * 40 - 20;
    const style = document.createElement('style');
    style.innerHTML = `
      @keyframes fall-${i} {
        0% {
          transform: translateY(-10vh) translateX(0px) rotate(0deg);
          opacity: 0;
          filter: drop-shadow(0 0 5px ${color});
        }
        20% { opacity: 1; }
        50% {
          transform: translateY(50vh) translateX(${drift}px) rotate(180deg);
          filter: drop-shadow(0 0 10px ${color});
        }
        100% {
          transform: translateY(110vh) translateX(${-drift}px) rotate(360deg);
          opacity: 0;
          filter: drop-shadow(0 0 5px ${color});
        }
      }
    `;
    document.head.appendChild(style);
  }

  lucide.createIcons();
</script>

</body>
</html>
