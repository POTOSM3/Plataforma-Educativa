<?php
session_start();
if (!isset($_SESSION['usuario'])) { header('Location: login.php'); exit; }
ob_start();
?>
<section class="banner">
  <div>
    <h1 class="title">✉️ Contáctanos</h1>
    <p class="desc">¿Dudas o sugerencias? Envíanos un mensaje y te responderemos muy pronto.</p>
  </div>
</section>

<form class="form" action="guardar_contacto.php" method="POST">
  <div class="row">
    <div class="field">
      <label for="nombre">Nombre completo</label>
      <input type="text" id="nombre" name="nombre" required>
    </div>
    <div class="field">
      <label for="correo">Correo electrónico</label>
      <input type="email" id="correo" name="correo" required>
    </div>
  </div>
  <div class="row">
    <div class="field" style="flex:1 1 100%">
      <label for="mensaje">Mensaje</label>
      <textarea id="mensaje" name="mensaje" required></textarea>
    </div>
  </div>
  <div class="actions">
    <button class="btn" type="submit">Enviar</button>
  </div>
</form>
<?php
$content = ob_get_clean();
$page_title = 'Contacto — EduLive';
require 'components/layout.php';
