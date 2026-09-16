<?php
/**
 * Catálogo institucional de categorías y subcategorías de reportes.
 *
 * Es la fuente única del catálogo: lo usa el alta de instituciones nuevas
 * (controlador_superadmin) y el script cargar_catalogo_categorias.php para
 * instituciones ya existentes. Para cambiar el catálogo, editar solo este archivo.
 *
 * Formato de cada entrada:
 *   [nombre, descripción, es_critica_escalada, orden, [subcategorías...]]
 *
 * es_critica_escalada = 1 hace que todo reporte de esa categoría se escale
 * automáticamente a urgencia "Urgente" al crearse. Se reserva para categorías
 * con riesgo para las personas; el gestor puede ajustar la urgencia después.
 */
return [
    ['Electricidad', 'Iluminación, tomas, tableros, cableado y puntos eléctricos.', 1, 1, [
        'Lámpara apagada', 'Bombillo fundido', 'Tubo LED dañado', 'Reflector dañado',
        'Interruptor dañado', 'Tomacorriente dañado', 'Cortocircuito', 'Cableado expuesto',
        'Canaleta deteriorada', 'Tablero eléctrico defectuoso', 'Breaker averiado',
        'Falta de energía', 'Punto eléctrico nuevo', 'Reubicación de punto eléctrico',
        'Puesta a tierra', 'Sobrecarga eléctrica', 'Temporizador eléctrico',
        'Sensor de movimiento', 'Alumbrado exterior',
    ]],
    ['Hidráulica y Plomería', 'Fugas, tuberías, sanitarios, llaves y tanques.', 0, 2, [
        'Fuga de agua', 'Tubería rota', 'Tubería obstruida', 'Lavamanos dañado',
        'Sanitario dañado', 'Orinal dañado', 'Llave defectuosa', 'Llave de paso dañada',
        'Tanque averiado', 'Bajo caudal de agua', 'Inundación', 'Instalación hidráulica',
        'Cambio de accesorios sanitarios',
    ]],
    ['Albañilería y Obra Civil', 'Muros, pisos, enchapes, andenes, escaleras y estructura.', 0, 3, [
        'Grieta en muro', 'Hueco en pared', 'Reparación de piso', 'Reparación de enchape',
        'Reparación de andén', 'Reparación de escalera', 'Reparación de rampa',
        'Construcción de muro', 'Demolición menor', 'Nivelación de pisos',
        'Reparación de columnas', 'Reparación estructural',
    ]],
    ['Techos y Cubiertas', 'Goteras, tejas, impermeabilización, canales y cielo raso.', 0, 4, [
        'Goteras', 'Tejas rotas', 'Cambio de tejas', 'Impermeabilización',
        'Limpieza de canales', 'Reparación de cubierta', 'Reparación de cielo raso',
        'Humedad en techo', 'Filtración de agua',
    ]],
    ['Pintura', 'Pintura interior y exterior, resanes y demarcación.', 0, 5, [
        'Pintura interior', 'Pintura exterior', 'Resane de pared', 'Eliminación de humedad',
        'Restauración de pintura', 'Demarcación de espacios', 'Pintura de puertas',
        'Pintura de ventanas', 'Pintura de barandas',
    ]],
    ['Carpintería', 'Puertas, marcos, bisagras y muebles de madera.', 0, 6, [
        'Reparación de puerta', 'Cambio de puerta', 'Ajuste de puerta', 'Bisagras dañadas',
        'Marco deteriorado', 'Mueble dañado', 'Biblioteca dañada', 'Estantería dañada',
        'Gabinete dañado', 'Reparación de escritorio',
    ]],
    ['Cerrajería', 'Cerraduras, llaves, pasadores y candados.', 0, 7, [
        'Cerradura dañada', 'Cambio de cerradura', 'Pérdida de llave', 'Duplicado de llave',
        'Apertura de puerta', 'Pasador dañado', 'Candado dañado',
    ]],
    ['Ventanería y Vidrios', 'Vidrios, ventanas, marcos metálicos y películas de seguridad.', 0, 8, [
        'Vidrio roto', 'Ventana dañada', 'Marco metálico deteriorado', 'Ajuste de ventana',
        'Película de seguridad', 'Instalación de vidrio', 'Cambio de vidrio',
    ]],
    ['Soldadura y Metalmecánica', 'Portones, mallas, rejas, barandas y estructuras metálicas.', 0, 9, [
        'Reparación de portón', 'Soldadura de estructura', 'Reparación de malla',
        'Reparación de reja', 'Reparación de baranda', 'Fabricación de soporte',
        'Ajuste de estructura metálica',
    ]],
    ['Mobiliario Escolar', 'Pupitres, sillas, mesas, tableros, archivadores y casilleros.', 0, 10, [
        'Pupitre dañado', 'Silla dañada', 'Mesa dañada', 'Tablero dañado', 'Archivador dañado',
        'Estante dañado', 'Casillero dañado', 'Escritorio dañado', 'Biblioteca dañada',
    ]],
    ['Zonas Verdes y Jardinería', 'Poda, tala autorizada, jardines y maleza.', 0, 11, [
        'Poda de árboles', 'Poda de césped', 'Tala autorizada', 'Mantenimiento de jardines',
        'Retiro de maleza', 'Recolección de ramas', 'Siembra de plantas',
    ]],
    ['Aseo y Saneamiento', 'Control de plagas, fumigación, lavado de tanques y residuos.', 0, 12, [
        'Control de plagas', 'Desratización', 'Fumigación', 'Lavado de tanques',
        'Limpieza profunda', 'Recolección de residuos especiales', 'Control de malos olores',
    ]],
    ['Infraestructura Deportiva', 'Canchas, graderías, arcos, mallas y tableros deportivos.', 0, 13, [
        'Reparación de cancha', 'Reparación de graderías', 'Arcos deportivos',
        'Mallas deportivas', 'Tableros de baloncesto', 'Demarcación deportiva',
        'Gimnasio escolar',
    ]],
    ['Seguridad Física', 'Cerramientos, señalización, rutas de evacuación y protección.', 1, 14, [
        'Reparación de cerramiento', 'Reparación de portón', 'Instalación de señalización',
        'Barandas de seguridad', 'Rutas de evacuación', 'Señalización de emergencia',
        'Elementos de protección',
    ]],
    ['Sistema Contra Incendios', 'Extintores, gabinetes, detectores, alarmas y red contra incendios.', 1, 15, [
        'Extintor vencido', 'Recarga de extintor', 'Gabinete contra incendios',
        'Detector de humo', 'Alarma de incendio', 'Señalización contra incendios',
        'Red contra incendios',
    ]],
    ['Tecnología y Telecomunicaciones', 'Red, equipos de comunicaciones, cámaras, sonido y proyección.', 0, 16, [
        'Punto de red', 'Cable de red', 'Switch', 'Router', 'Access Point',
        'Rack de comunicaciones', 'Cámara de seguridad', 'Sistema de sonido',
        'Videobeam', 'Pantalla interactiva',
    ]],
    ['Aire Acondicionado y Ventilación', 'Aires acondicionados, ventiladores y ventilación.', 0, 17, [
        'Aire acondicionado dañado', 'Ventilador dañado', 'Mantenimiento preventivo',
        'Recarga de gas', 'Limpieza de filtros', 'Problema de ventilación',
    ]],
    ['Espacios Académicos', 'Aulas, laboratorios, biblioteca, salas y oficinas.', 0, 18, [
        'Aula de clase', 'Laboratorio', 'Biblioteca', 'Sala de informática',
        'Taller técnico', 'Restaurante escolar', 'Coordinación', 'Rectoría',
    ]],
    ['Baños y Servicios Sanitarios', 'Sanitarios, lavamanos, orinales, divisiones y accesorios.', 0, 19, [
        'Sanitarios', 'Lavamanos', 'Orinales', 'Divisiones sanitarias', 'Espejos',
        'Accesorios sanitarios', 'Dispensadores',
    ]],
    ['Otros', 'Solicitudes que no encajan en las demás categorías.', 0, 20, [
        'Requiere inspección', 'Solicitud no clasificada', 'Mejoramiento locativo',
        'Adecuación de espacio', 'Emergencia', 'Otro',
    ]],
];
