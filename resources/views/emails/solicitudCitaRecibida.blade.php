<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Nueva Solicitud de Cita</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin:0; padding:0;">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding: 20px;">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="background-color: #2262a3; color: #ffffff; padding: 20px; text-align: center;">
                            <h2 style="margin:0;">Nueva Solicitud de Cita de Mantenimiento</h2>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px; color:#333;">
                            <p>Se ha recibido una nueva solicitud de cita con los siguientes datos:</p>

                            <h3 style="color:#2262a3; border-bottom:2px solid #2262a3; padding-bottom:5px;">Datos del Cliente</h3>
                            <p><strong>Nombre contacto:</strong> {{ $solicitud->nombre_contacto }}</p>
                            <p><strong>NIT:</strong> {{ $solicitud->NIT ?: 'No registrado' }}</p>
                            <p><strong>Razón Social:</strong> {{ $solicitud->razon_social ?: 'No registrada' }}</p>
                            <p><strong>Correo:</strong> {{ $solicitud->correo ?: 'No registrado' }}</p>
                            <p><strong>Teléfono:</strong> {{ $solicitud->telefono ?: 'No registrado' }}</p>

                            <h3 style="color:#2262a3; border-bottom:2px solid #2262a3; padding-bottom:5px;">Datos del Equipo</h3>
                            <p><strong>Serial:</strong> {{ $solicitud->serial_equipo ?: 'No registrado' }}</p>
                            <p><strong>Marca:</strong> {{ $solicitud->marca ?: 'No registrada' }}</p>
                            <p><strong>Modelo:</strong> {{ $solicitud->modelo ?: 'No registrado' }}</p>
                            <p><strong>Tipo/Descripción:</strong> {{ $solicitud->tipo_equipo_descripcion ?: 'No registrada' }}</p>

                            <h3 style="color:#2262a3; border-bottom:2px solid #2262a3; padding-bottom:5px;">Detalles de la Solicitud</h3>
                            <p><strong>Tipo de Cita:</strong> {{ ucfirst($solicitud->tipo_cita) }}</p>
                            <p><strong>Motivo:</strong> {{ $solicitud->motivo ?: 'No especificado' }}</p>
                            <p><strong>Estado:</strong> {{ ucfirst($solicitud->estado) }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding: 20px;">
                            <a href="#" style="display:inline-block; background-color:#2262a3; color:#ffffff; text-decoration:none; padding:12px 24px; border-radius:4px; font-weight:bold;">
                                Ver solicitud en el sistema
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#f9f9f9; padding:15px; text-align:center; font-size:12px; color:#777;">
                            Este correo es generado automáticamente por el sistema de citas de J&C Medical.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
