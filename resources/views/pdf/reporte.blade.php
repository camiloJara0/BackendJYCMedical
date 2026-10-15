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
        margin: 150px 40px 60px 40px;
    }

    header {
        position: fixed;
        top: -130px;
        left: 0;
        right: 0;
        height: 90px;
    }

    .pagenum:before {
        content: counter(page);
    }

    .pagecount:before {
        content: counter(pages);
    }

    .component-table,
    .component-table th,
    .component-table tr,
    .component-table td {
        border: none !important;
    }
    </style>
</head>

<body style="font-family: Arial, sans-serif; font-size: 12px; color: #1f2937;">

    <!-- HEADER PROFESIONAL -->
    <header style="padding-bottom: 10px; margin-bottom: 20px;">
        <table style="width:100%;">
            <tr>
                <td style="width:60%;">
                    <table class="component-table" style="width:100%; border-collapse:collapse;">
                        <tr>
                            <td style="width:70%; vertical-align:middle;">
                                <img src="{{ public_path('logo.png') }}" style="width:55px;" />
                                <h2 style="margin:0; color:#2262a3; text-transform:uppercase; font-size:16px;">
                                    Reporte de Mantenimiento
                                </h2>
                                <p style="margin:0; font-size:11px; color:#6b7280;">
                                    Tipo de Mantenimiento - {{ $reporte->tipo }}
                                </p>
                            </td>
                            <td style="width:30%; text-align:right; vertical-align:middle;">
                                <img src="{{ public_path('jyc_logo.png') }}" style="width:80px;" />
                            </td>
                        </tr>
                    </table>
                </td>

                <td style="width:40%; text-align:right; font-size:11px;">
                    <p><strong>Código:</strong> FOR-MAN-001</p>
                    <p><strong>Fecha:</strong> {{ $reporte->fecha }}</p>
                    <p><strong style="font-size: 12px;">Reporte No:</strong> <span
                            style="color: #bc2e15; font-size: 12px;">{{ $reporte->id }}</span>.
                            <strong>Página:</strong><span class="pagenum"></span> de <span>{{ $totalPages }}</span>
                    </p>
                </td>
            </tr>
        </table>
        <div style="height:75px;"></div>
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
            <tr>
                <td><strong>Nombre del cliente:</strong> {{ $reporte->cliente->nombre }}</td>
                <td><strong>Telefono del cliente:</strong> {{ $reporte->cliente->telefono }}</td>
                <td><strong>Correo del cliente:</strong> {{ $reporte->cliente->correo }}</td>
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
                    Set/Equipo Patron/Materiales Utilizados
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
                    Mediciones Realizadas
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
                <td style="padding:6px;">{{ $medicion->unidad }}</td>
                <td style="padding:6px;">{{ $medicion->variable }}</td>
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
                    Repuestos/Accesorios Requeridos
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

    <!-- EVIDENCIA FOTOGRÁFICA -->
    @php
        $imagenesEvidencia = collect($reporte->images)->sortBy('position')->values();

        // Agrupar en filas según el tamaño configurado por imagen:
        // 100% -> fila sola; 50% -> de a 2; 30% -> de a 3.
        $filasEvidencia = [];
        $filaActual = [];
        $capacidadFila = null;

        foreach ($imagenesEvidencia as $imagenEvidencia) {
            $tamanoImagen = in_array((string) ($imagenEvidencia->tamaño ?? ''), ['100', '50', '30'], true)
                ? (string) $imagenEvidencia->tamaño
                : '50';
            $capacidad = $tamanoImagen === '100' ? 1 : ($tamanoImagen === '50' ? 2 : 3);

            if ($capacidadFila !== null && ($capacidad !== $capacidadFila || count($filaActual) >= $capacidadFila)) {
                $filasEvidencia[] = $filaActual;
                $filaActual = [];
            }

            $filaActual[] = ['imagen' => $imagenEvidencia, 'capacidad' => $capacidad];
            $capacidadFila = $capacidad;

            if (count($filaActual) >= $capacidadFila) {
                $filasEvidencia[] = $filaActual;
                $filaActual = [];
                $capacidadFila = null;
            }
        }

        if (!empty($filaActual)) {
            $filasEvidencia[] = $filaActual;
        }

        // Ruta local del archivo para dompdf (soporta URLs absolutas y relativas).
        $rutaImagenPdf = function ($url) {
            $path = parse_url($url, PHP_URL_PATH);
            if (!$path) {
                return '';
            }
            $path = ltrim($path, '/');
            $pos = strpos($path, 'storage/');
            $relativa = $pos === false ? $path : substr($path, $pos);

            return public_path($relativa);
        };
    @endphp

    @if($imagenesEvidencia->count() > 0)
        <section style="margin-top:20px;">
            <h3 style="color:#2262a3; border-bottom:1px solid #e5e7eb; padding-bottom:5px;">
                Evidencia Fotográfica
            </h3>

            @php
                $numeroFigura = 0;
                // dompdf no resuelve bien anchos porcentuales en tablas/imágenes:
                // se usan anchos absolutos en px para que 100% / 50% / 30% se respeten.
                $anchoContenidoPdf = 700;   // px útiles dentro de @page (A4 - márgenes 40+40)
                $altoMaximoPdf = 860;       // px de alto disponibles por página (deja espacio al pie)
                $paddingCeldaPdf = 8;       // 4px a cada lado de la celda
            @endphp

            @foreach($filasEvidencia as $filaEvidencia)
                <table style="width:{{ $anchoContenidoPdf }}px; table-layout:fixed; border-collapse:collapse; margin-bottom:12px;">
                    <tr style="page-break-inside:avoid;">
                        @foreach($filaEvidencia as $celdaEvidencia)
                            @php
                                $numeroFigura++;
                                $imagenPdf = $celdaEvidencia['imagen'];
                                $rutaPdf = $rutaImagenPdf($imagenPdf->url);

                                // Ancho de celda según el tamaño configurado (1 o 2 o 3 por fila)
                                $anchoCeldaPdf = (int) floor($anchoContenidoPdf / $celdaEvidencia['capacidad']);
                                $anchoImagenPdf = max(80, $anchoCeldaPdf - $paddingCeldaPdf);

                                // Si la foto no cabe en el alto de la página, se reduce el ancho
                                // proporcionalmente para evitar páginas en blanco.
                                if ($rutaPdf && file_exists($rutaPdf)) {
                                    $dimensionesPdf = @getimagesize($rutaPdf);
                                    if ($dimensionesPdf && $dimensionesPdf[1] > 0) {
                                        $altoImagenPdf = $anchoImagenPdf * $dimensionesPdf[1] / $dimensionesPdf[0];
                                        if ($altoImagenPdf > $altoMaximoPdf) {
                                            $anchoImagenPdf = max(80, (int) floor($altoMaximoPdf * $dimensionesPdf[0] / $dimensionesPdf[1]));
                                        }
                                    }
                                }
                            @endphp
                            <td style="width:{{ $anchoCeldaPdf }}px; vertical-align:top; padding:4px; text-align:center; border:none;">
                                @if($rutaPdf && file_exists($rutaPdf))
                                    <img src="{{ $rutaPdf }}"
                                        style="width:{{ $anchoImagenPdf }}px; max-width:100%; height:auto; border:1px solid #ddd; border-radius:4px;" />
                                @endif
                                <p style="margin:4px 0 0 0; font-size:9px; color:#374151; text-align:center;">
                                    <strong>Figura {{ $numeroFigura }}</strong>@if(!empty($imagenPdf->descripcion)) &mdash; {{ $imagenPdf->descripcion }}@endif
                                </p>
                            </td>
                        @endforeach
                    </tr>
                </table>
            @endforeach
        </section>
    @endif

    <!-- RECOMENDACIONES -->
    @if($reporte->recomendaciones_cliente || $reporte->riesgos_identificados || $reporte->proximas_acciones)
        <section style="margin-top:20px;">
            <h3 style="color:#2262a3; border-bottom:1px solid #e5e7eb; padding-bottom:5px;">
                Recomendaciones
            </h3>

            @if($reporte->recomendaciones_cliente)
                <div style="border:1px solid #e5e7eb; border-left:4px solid #2262a3; background:#f9fafb; padding:8px; margin-bottom:8px;">
                    <p style="margin:0 0 4px 0; font-weight:bold; font-size:11px; color:#2262a3;">
                        Recomendaciones al cliente
                    </p>
                    <p style="margin:0; font-size:10px; color:#374151; white-space:pre-wrap;">{{ $reporte->recomendaciones_cliente }}</p>
                </div>
            @endif

            @if($reporte->riesgos_identificados)
                <div style="border:1px solid #e5e7eb; border-left:4px solid #2262a3; background:#f9fafb; padding:8px; margin-bottom:8px;">
                    <p style="margin:0 0 4px 0; font-weight:bold; font-size:11px; color:#2262a3;">
                        Riesgos identificados
                    </p>
                    <p style="margin:0; font-size:10px; color:#374151; white-space:pre-wrap;">{{ $reporte->riesgos_identificados }}</p>
                </div>
            @endif

            @if($reporte->proximas_acciones)
                <div style="border:1px solid #e5e7eb; border-left:4px solid #2262a3; background:#f9fafb; padding:8px; margin-bottom:8px;">
                    <p style="margin:0 0 4px 0; font-weight:bold; font-size:11px; color:#2262a3;">
                        Próximas acciones sugeridas
                    </p>
                    <p style="margin:0; font-size:10px; color:#374151; white-space:pre-wrap;">{{ $reporte->proximas_acciones }}</p>
                </div>
            @endif
        </section>
    @endif

    <!-- FIRMAS -->
    <section style="margin-top:40px;">
        <table style="width:100%;">
            <tr>
                <td style="text-align:center; width:50%;">
                    <div style="border-top:1px solid #000; padding-top:5px;">
                        Nombre:<strong style="border-bottom: 1px solid #a3a3a3; ">
                            {{ $reporte->tecnico->nombre }}</strong><br>
                        @if($reporte->tecnico->sello)
                        <img src="{{ public_path('storage/'.$reporte->tecnico->sello) }}"
                            style="width:60px; height:60px; object-fit:contain;" /><br>
                        @else
                        Firma:<strong> ________________________________________</strong><br>
                        @endif
                        Cargo:<span style="border-bottom: 1px solid #a3a3a3; ">
                            {{ $reporte->tecnico->rol ?? 'N/A' }}</span><br>
                        <small>Realizado por</small>
                    </div>
                </td>
                <td style="text-align:center; width:50%;">
                    <div style="border-top:1px solid #000; padding-top:5px;">
                        Nombre: <strong
                            style="border-bottom: 1px solid #a3a3a3; ">{{ $reporte->firmaRecibido->nombre ?? 'N/A' }}</strong><br>
                        @if($reporte->firmaRecibido && $reporte->firmaRecibido->firma)
                        <img src="{{ public_path('storage/'.$reporte->firmaRecibido->firma) }}"
                            style="width:60px; height:60px; object-fit:contain;" /><br>
                        @else
                        Firma: ________________________________________ <br>
                        @endif
                        Cargo:<strong
                            style="border-bottom: 1px solid #a3a3a3; ">{{ $reporte->firmaRecibido->cargo ?? 'N/A' }}</strong><br>
                        <small>Recibido por</small>
                    </div>
                </td>
            </tr>
        </table>
    </section>

</body>


</html>