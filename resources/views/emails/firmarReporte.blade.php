<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Solicitud de Firma de Reporte</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin:0; padding:0;">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding: 20px;">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                    <!-- Encabezado -->
                    <tr>
                        <td style="background-color: #007bff; color: #ffffff; padding: 20px; text-align: center;">
                            <h2 style="margin:0;">Solicitud de Firma de Reporte</h2>
                        </td>
                    </tr>
                    <!-- Contenido principal -->
                    <tr>
                        <td style="padding: 20px; color:#333;">
                            <p>Estimado(a),</p>
                            <p>Se ha generado un nuevo <strong>Reporte de Mantenimiento</strong> que requiere su firma de recibido.</p>
                            <p><strong>Reporte No:</strong> {{ $reporte->id }}</p>
                            <p><strong>Fecha:</strong> {{ $reporte->fecha }}</p>
                            <p><strong>Equipo:</strong> {{ $reporte->equipo->nombre }} ({{ $reporte->equipo->marca }} - {{ $reporte->equipo->modelo }})</p>
                            <p>Por favor ingrese al siguiente enlace para revisar y firmar el reporte:</p>
                        </td>
                    </tr>
                    <!-- Botón de acción -->
                    <tr>
                        <td align="center" style="padding: 20px;">
                            <a href="{{ config('app.frontend_url') }}{{ $url }}" 
                               style="display:inline-block; background-color:#007bff; color:#ffffff; text-decoration:none; padding:12px 24px; border-radius:4px; font-weight:bold;">
                                Firmar Reporte
                            </a>
                        </td>
                    </tr>
                    <!-- Pie de página -->
                    <tr>
                        <td style="background-color:#f9f9f9; padding:15px; text-align:center; font-size:12px; color:#777;">
                            Este enlace es único y válido por un solo uso. Si ya firmó el reporte, este enlace no será válido nuevamente.<br>
                            Sistema de Mantenimiento de Equipos Biomédicos.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
