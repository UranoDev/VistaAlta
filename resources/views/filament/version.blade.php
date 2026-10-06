{{--
    La versión desplegada, en letra chica y color suave junto al nombre del
    panel. Se monta con un render hook (ver AdminPanelProvider). Si no hay
    versión que mostrar —un archivo VERSION ausente o con otra cosa— no pinta nada.
--}}
@if ($version = \App\Support\Version::actual())
    <span class="cifra ml-2 self-end pb-0.5 text-xs font-normal text-gray-500" title="Versión del sitio">
        v{{ $version }}
    </span>
@endif
