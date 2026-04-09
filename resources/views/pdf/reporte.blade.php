<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Reparación</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        h1,
        h2,
        h3 {
            margin: 5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
        }

        .system-title {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        body {
            font-family: Arial, sans-serif;
            color: #333;
        }

        td,
        th {
            border: 1px solid #ddd;
            padding: 5px;
            font-size: 10px;
        }

        h3 {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 10px;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
        }

        @page {
            margin: 140px 40px 60px 40px;
        }

        header {
            position: fixed;
            top: -120px;
            left: 0;
            right: 0;
            height: 80px;
        }

        .pagenum:before {
            content: counter(page);
        }

        .component-table,
        .component-table th,
        .component-table td {
            border: none !important;
        }

    </style>
</head>

<body style="font-family: Arial, sans-serif; font-size: 12px; color: #1f2937;">

    <!-- HEADER PROFESIONAL -->
    <header style="border-bottom: 3px solid #2262a3; padding-bottom: 10px; margin-bottom: 20px;">
        <table style="width:100%;">
            <tr>
                <td style="width:60%;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <img src="{{ public_path('logo.png') }}" style="width:55px;" />
                        <div>
                            <h2 style="margin:0; color:#2262a3; text-transform:uppercase; font-size:16px;">
                                Reporte de Mantenimiento
                            </h2>
                            <p style="margin:0; font-size:11px; color:#6b7280;">
                                Equipos Biomédicos
                            </p>
                        </div>
                    </div>
                </td>

                <td style="width:40%; text-align:right; font-size:11px;">
                    <p><strong>Código:</strong> FOR-MAN-001</p>
                    <p><strong>Fecha:</strong> {{ $reporte->fecha }}</p>
                    <p><strong style="font-size: 12px;">Reporte No:</strong> <span
                            style="color: #bc2e15; font-size: 12px;">{{ $reporte->id }}</span>. <strong>Página:</strong>
                        <span class="pagenum"></span>
                    </p>
                </td>
            </tr>
        </table>
        <div style="height:65px;"></div>
    </header>

    <!-- INFORMACIÓN DEL EQUIPO -->
    <section style="margin-bottom:20px;">
        <h3 style="color:#2262a3; border-bottom:1px solid #e5e7eb; padding-bottom:5px;">
            Información del Equipo
        </h3>

        <table style="width:100%; margin-top:10px;">
            <tr>
                <td><strong>Equipo:</strong> {{ $reporte->equipo->nombre }}</td>
                <td><strong>Marca:</strong> {{ $reporte->equipo->marca }}</td>
                <td><strong>Modelo:</strong> {{ $reporte->equipo->modelo }}</td>
            </tr>
            <tr>
                <td><strong>Serie:</strong> {{ $reporte->equipo->serie }}</td>
                <td><strong>Ubicación:</strong> {{ $reporte->equipo->ubicacion }}</td>
                <td><strong>Placa:</strong> {{ $reporte->equipo->placa }}</td>
            </tr>
        </table>
    </section>

    <!-- COMPONENTES POR SISTEMA -->
    <section>
        <h3 style="color:#2262a3; border-bottom:1px solid #e5e7eb; padding-bottom:5px;">
            Estado de Componentes
        </h3>

        @php
        $componentesPorSistema = collect($reporte->estado_componente)->groupBy('componente.sistema.nombre');
        $sistemasAgrupados = $componentesPorSistema->chunk(2); // Agrupar de a 2
        @endphp

        @foreach($sistemasAgrupados as $grupo)
        <table class="component-table" style="width:100%; border-collapse: collapse; margin-bottom:20px; padding: 0px;">
            <tr>
                @foreach($grupo as $sistemaNombre => $componentes)
                <td style="width:50%; vertical-align:top; padding:5px;">
                    <table style="width:100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background:#dbeafe;">
                                <th colspan="3" style="padding:6px; text-align:left; color:#2262a3;">
                                    {{ $sistemaNombre }}
                                </th>
                            </tr>
                            <tr style="background:#e3e8e9;">
                                <th style="padding:6px; text-align:left;">Componente</th>
                                <th style="padding:6px;">Estado</th>
                                <th style="padding:6px;">Observación</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($componentes as $comp)
                            <tr>
                                <td style="padding:6px;">{{ $comp->componente->nombre }}</td>
                                <td style="padding:6px; text-align:center;">
                                    <span style="
                                    padding:3px 8px;
                                    border-radius:8px;
                                    background:{{ $comp->estado == 'bueno' ? '#94c53d' : ($comp->estado == 'regular' ? '#dd9d5c' : '#bc2e15') }};
                                    color:white;">
                                        {{ $comp->estado }}
                                    </span>
                                </td>
                                <td style="padding:6px;">{{ $comp->observacion ?? 'N/A' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </td>
                @endforeach
            </tr>
        </table>
        @endforeach
    </section>


    <!-- ACCESORIOS -->
    <table style="width:100%; border-collapse: collapse; margin-bottom:20px;">
        <thead>
            <tr>
                <th colspan="2" style="padding:6px; text-align:left; color:#2262a3; font-size: 13px;">
                    Accesorios con los que cuenta
                </th>
            </tr>
            <tr style="background:#f3f4f6;">
                <th style="padding:6px;">Nombre</th>
                <th style="padding:6px;">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reporte->accesorios as $accesorio)
            <tr>
                <td style="padding:6px;">{{ $accesorio->nombre }}</td>
                <td style="padding:6px; text-align:center;">
                    <span style="
                    padding:3px 8px;
                    border-radius:8px;
                    background:{{ $accesorio->estado == 'Bueno' ? '#94c53d' : ($accesorio->estado == 'regular' ? '#dd9d5c' : '#bc2e15') }};
                    color:white;">
                        {{ $accesorio->estado }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- MATERIALES -->
    <table>
        <thead>
            <tr>
                <th colspan="2" style="padding:6px; text-align:left; color:#2262a3; font-size: 13px;">
                    Materiales Utilizados
                </th>
            </tr>
            <tr style="background:#f3f4f6;">
                <th style="padding:6px;">Cantidad</th>
                <th style="padding:6px;">Descripción</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reporte->materiales as $material)
            <tr>
                <td style="padding:6px; text-align:center;">{{ $material->cantidad }}</td>
                <td style="padding:6px;">{{ $material->descripcion }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- MEDICIONES -->
    <table>
        <thead>
            <tr>
                <th colspan="2" style="padding:6px; text-align:left; color:#2262a3; font-size: 13px;">
                    Mediciones
                </th>
            </tr>
            <tr style="background:#f3f4f6;">
                <th style="padding:6px;">Unidad</th>
                <th style="padding:6px;">Variable</th>
                <th style="padding:6px;">Valor Medido</th>
                <th style="padding:6px;">Valor Esperado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reporte->mediciones as $medicion)
            <tr>
                <td style="padding:6px;">{{ $medicion->variable }}</td>
                <td style="padding:6px;">{{ $medicion->unidad }}</td>
                <td style="padding:6px;">{{ $medicion->valor_medido }}</td>
                <td style="padding:6px;">{{ $medicion->valor_esperado }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- ACCESORIOS REQUERIDOS -->
    <table>
        <thead>
            <tr>
                <th colspan="2" style="padding:6px; text-align:left; color:#2262a3; font-size: 13px;">
                    Accesorios Requeridos
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach($reporte->repuestos as $repuesto)
            <tr>
                <td style="padding:6px;">{{ $repuesto->nombre }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- ACTIVIDADES -->
    <table>
        <thead>
            <tr>
                <th colspan="2" style="padding:6px; text-align:center; color:#2262a3;">
                    Actividades y Observaciones
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach($reporte->actividades as $actividad)
            <tr style="background:#f9fafb; padding:10px; border-radius:6px;">
                <td style="margin:5px 0;">{{ $actividad->descripcion }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- FIRMAS -->
    <section style="margin-top:40px;">
        <table style="width:100%;">
            <tr>
                <td style="text-align:center; width:50%;">
                    <div style="border-top:1px solid #000; padding-top:5px;">
                        Nombre:<strong style="border-bottom: 1px solid #a3a3a3; "> {{ $reporte->tecnico->nombre }}</strong><br>
                        @if($reporte->tecnico->sello)
                        <img src="{{ public_path('storage/'.$reporte->tecnico->sello) }}"
                            style="width:60px; height:60px; object-fit:contain;" /><br>
                        @else
                        Firma:<strong> ________________________________________</strong><br>
                        @endif
                        Cargo:<span style="border-bottom: 1px solid #a3a3a3; "> {{ $reporte->tecnico->rol ?? 'N/A' }}</span><br>
                        <small>Realizado por</small>
                    </div>
                </td>
                <td style="text-align:center; width:50%;">
                    <div style="border-top:1px solid #000; padding-top:5px;">
                        Nombre: <strong style="border-bottom: 1px solid #a3a3a3; ">{{ $reporte->firmaRecibido->nombre ?? 'N/A' }}</strong><br>
                        @if($reporte->firmaRecibido && $reporte->firmaRecibido->firma)
                            <img src="{{ public_path('storage/'.$reporte->firmaRecibido->firma) }}" 
                                style="width:60px; height:60px; object-fit:contain;" /><br>
                        @else
                            Firma: ________________________________________ <br>
                        @endif
                        Cargo:<strong style="border-bottom: 1px solid #a3a3a3; ">{{ $reporte->firmaRecibido->cargo ?? 'N/A' }}</strong><br>
                        <small>Recibido por</small>
                    </div>
                </td>
            </tr>
        </table>
    </section>

</body>


</html>