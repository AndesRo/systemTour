<?php
// Asegurarse de que la función enviarEmail esté disponible
if (!function_exists('enviarEmail')) {
    // Si la función no existe, intentar incluir config.php
    $configPath = __DIR__ . '/config.php';
    if (file_exists($configPath)) {
        require_once $configPath;
    } else {
        // Si config.php no existe en la ruta esperada, intentar una ruta alternativa
        $altConfigPath = dirname(__DIR__) . '/includes/config.php';
        if (file_exists($altConfigPath)) {
            require_once $altConfigPath;
        } else {
            error_log("Error: No se pudo encontrar config.php");
            throw new Exception("Error de configuración: archivo config.php no encontrado");
        }
    }
}

class Notificaciones {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Envía notificación cuando cambia el estado de un reclamo
     */
    public function notificarCambioEstado($reclamo_id, $estado_anterior, $estado_nuevo) {
        try {
            // Obtener información del reclamo y cliente
            $stmt = $this->conn->prepare("
                SELECT r.*, c.nombre, c.email 
                FROM reclamos r 
                JOIN clientes c ON r.cliente_id = c.id 
                WHERE r.id = ?
            ");
            $stmt->execute([$reclamo_id]);
            $reclamo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$reclamo || empty($reclamo['email'])) {
                error_log("No se encontró el reclamo o el email del cliente para el ID: $reclamo_id");
                return false;
            }
            
            // Preparar asunto y mensaje según el estado
            $asunto = "";
            $mensaje = "";
            
            switch ($estado_nuevo) {
                case 'En proceso':
                    $asunto = "Tu reclamo #{$reclamo['ticket_id']} está en proceso";
                    $mensaje = $this->crearMensajeEnProceso($reclamo);
                    break;
                    
                case 'Resuelto':
                    $asunto = "¡Tu reclamo #{$reclamo['ticket_id']} ha sido resuelto!";
                    $mensaje = $this->crearMensajeResuelto($reclamo);
                    break;
                    
                default:
                    // No enviar notificación para otros estados
                    return true;
            }
            
            // Enviar email
            $exito = $this->enviarEmail(
                $reclamo['email'],
                $reclamo['nombre'],
                $asunto,
                $mensaje
            );
            
            // Registrar la notificación
            $this->registrarNotificacion(
                $reclamo_id,
                'estado',
                $estado_anterior,
                $estado_nuevo,
                $asunto,
                $mensaje,
                $exito,
                $exito ? NULL : 'Error al enviar email'
            );
            
            return $exito;
            
        } catch (PDOException $e) {
            error_log("Error en notificarCambioEstado: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Error general en notificarCambioEstado: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crea mensaje HTML para estado "En proceso"
     */
    private function crearMensajeEnProceso($reclamo) {
        return "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='utf-8'>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; }
                    .header { background: #4361ee; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { padding: 20px; background: #f9f9f9; border-radius: 0 0 10px 10px; border: 1px solid #ddd; }
                    .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #777; }
                </style>
            </head>
            <body>
                <div class='header'>
                    <h2>Tu reclamo está siendo procesado</h2>
                </div>
                <div class='content'>
                    <p>Hola <strong>{$reclamo['nombre']}</strong>,</p>
                    <p>Queremos informarte que tu reclamo con número de ticket <strong>#{$reclamo['ticket_id']}</strong> ha cambiado de estado y ahora está <strong>En proceso</strong>.</p>
                    <p><strong>Detalles del reclamo:</strong></p>
                    <ul>
                        <li><strong>Ticket ID:</strong> #{$reclamo['ticket_id']}</li>
                        <li><strong>Fecha del incidente:</strong> " . date('d/m/Y', strtotime($reclamo['fecha_incidente'])) . "</li>
                        <li><strong>Lugar:</strong> {$reclamo['lugar']}</li>
                        <li><strong>Nuevo estado:</strong> En proceso</li>
                    </ul>
                    <p>Nuestro equipo está trabajando para resolver tu caso lo antes posible. Te mantendremos informado sobre cualquier novedad.</p>
                    <p>Puedes ver el estado actualizado de tu reclamo en cualquier momento accediendo a tu cuenta en nuestro sistema.</p>
                </div>
                <div class='footer'>
                    <p>Este es un mensaje automático, por favor no respondas a este correo.</p>
                    <p>&copy; " . date('Y') . " Sistema de Reclamos. Todos los derechos reservados.</p>
                </div>
            </body>
            </html>
        ";
    }
    
    /**
     * Crea mensaje HTML para estado "Resuelto"
     */
    private function crearMensajeResuelto($reclamo) {
        $comentario_admin = !empty($reclamo['comentario_admin']) ? 
            "<p><strong>Comentario del administrador:</strong> {$reclamo['comentario_admin']}</p>" : "";
            
        return "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='utf-8'>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; }
                    .header { background: #28a745; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { padding: 20px; background: #f9f9f9; border-radius: 0 0 10px 10px; border: 1px solid #ddd; }
                    .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #777; }
                </style>
            </head>
            <body>
                <div class='header'>
                    <h2>¡Tu reclamo ha sido resuelto!</h2>
                </div>
                <div class='content'>
                    <p>Hola <strong>{$reclamo['nombre']}</strong>,</p>
                    <p>Nos complace informarte que tu reclamo con número de ticket <strong>#{$reclamo['ticket_id']}</strong> ha sido <strong>resuelto</strong>.</p>
                    <p><strong>Detalles del reclamo:</strong></p>
                    <ul>
                        <li><strong>Ticket ID:</strong> #{$reclamo['ticket_id']}</li>
                        <li><strong>Fecha del incidente:</strong> " . date('d/m/Y', strtotime($reclamo['fecha_incidente'])) . "</li>
                        <li><strong>Lugar:</strong> {$reclamo['lugar']}</li>
                        <li><strong>Estado:</strong> Resuelto</li>
                    </ul>
                    {$comentario_admin}
                    <p>Agradecemos tu paciencia y comprensión. Si tienes alguna pregunta adicional, no dudes en contactarnos.</p>
                    <p>Puedes acceder a tu cuenta para ver todos los detalles de la resolución.</p>
                </div>
                <div class='footer'>
                    <p>Este es un mensaje automático, por favor no respondas a este correo.</p>
                    <p>&copy; " . date('Y') . " Sistema de Reclamos. Todos los derechos reservados.</p>
                </div>
            </body>
            </html>
        ";
    }
    
    /**
     * Envía un email utilizando la función enviarEmail de config.php
     */
    private function enviarEmail($destinatario, $nombreDestinatario, $asunto, $mensajeHTML) {
        // Verificar si la función enviarEmail existe
        if (!function_exists('enviarEmail')) {
            error_log("Error: La función enviarEmail no está disponible");
            return false;
        }
        
        try {
            $mensajeTexto = strip_tags($mensajeHTML);
            return enviarEmail($destinatario, $nombreDestinatario, $asunto, $mensajeHTML, $mensajeTexto);
        } catch (Exception $e) {
            error_log("Error al enviar email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra la notificación en la base de datos
     */
    private function registrarNotificacion($reclamo_id, $tipo, $estado_anterior, $estado_nuevo, $asunto, $mensaje, $enviado, $error = null) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO notificaciones 
                (reclamo_id, tipo, estado_anterior, estado_nuevo, asunto, mensaje, enviado, error_envio) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $reclamo_id,
                $tipo,
                $estado_anterior,
                $estado_nuevo,
                $asunto,
                $mensaje,
                $enviado ? 1 : 0,
                $error
            ]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Error al registrar notificación: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Procesa notificaciones pendientes (para usar en un cron job)
     */
    public function procesarNotificacionesPendientes() {
        try {
            $stmt = $this->conn->prepare("
                SELECT n.*, r.ticket_id, c.email, c.nombre 
                FROM notificaciones n
                JOIN reclamos r ON n.reclamo_id = r.id
                JOIN clientes c ON r.cliente_id = c.id
                WHERE n.enviado = 0
                LIMIT 10
            ");
            
            $stmt->execute();
            $notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $procesadas = 0;
            foreach ($notificaciones as $notificacion) {
                $exito = $this->enviarEmail(
                    $notificacion['email'],
                    $notificacion['nombre'],
                    $notificacion['asunto'],
                    $notificacion['mensaje']
                );
                
                // Actualizar estado de la notificación
                $updateStmt = $this->conn->prepare("
                    UPDATE notificaciones 
                    SET enviado = ?, error_envio = ?, fecha_envio = NOW() 
                    WHERE id = ?
                ");
                
                $updateStmt->execute([
                    $exito ? 1 : 0,
                    $exito ? NULL : 'Error al reenviar',
                    $notificacion['id']
                ]);
                
                if ($exito) {
                    $procesadas++;
                }
            }
            
            return $procesadas;
        } catch (PDOException $e) {
            error_log("Error en procesarNotificacionesPendientes: " . $e->getMessage());
            return false;
        }
    }
}
?>