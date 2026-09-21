<?php
// Bootstrap de pruebas: solo carga lo estrictamente necesario para pruebas
// unitarias puras (sin BD, sin sesión, sin config.php). El proyecto no usa
// autoload de Composer para sus propias clases (todo es require_once), así
// que aquí se cargan explícitamente los archivos bajo prueba.

require_once dirname(__DIR__) . '/lib/validacion.php';
